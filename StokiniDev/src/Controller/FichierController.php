<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\Fichier;
use App\Form\FichierType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Dossier;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Repository\DossierRepository;
use App\Repository\FichierRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Message\OptimizeImageMessage;
use Symfony\Component\Messenger\MessageBusInterface;

#[Route('/fichier')] // 👈 prefix ici
class FichierController extends AbstractController
{
    #[Route('/', name: 'app_fichier')]
    public function index(): Response
    {
        return $this->render('fichier/index.html.twig', [
            'controller_name' => 'FichierController',
        ]);
    }




    #[Route('/upload', name: 'app_upload')]
    public function upload(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(FichierType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('fichier')->getData(); // ✅ bien récupérer le tableau
            $dossier = $form->get('dossier')->getData();


            if ($files) {

                foreach ($files as $file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                    $file->move(
                        $this->getParameter('uploads_directory') . '/' . $dossier->getCreateBy() . '/' . $dossier->getNom(),
                        $newFilename
                    );

                    $fichier = new Fichier();
                    $fichier->setNom($originalFilename);
                    $fichier->setDossier($dossier); // lie le fichier au dossier

                    $fichier->setChemin($newFilename);
                    $fichier->setUploadedAt(new \DateTimeImmutable());



                    $em->persist($fichier);
                }



                $em->flush(); // ✅ flush une seule fois à la fin
            }

            return $this->redirectToRoute('app_upload');
        }

        return $this->render('fichier/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }





    #[Route('/fichiers', name: 'app_fichiers')]
    public function liste(EntityManagerInterface $em, Security $security): Response
    {
        $users = $security->getUser();

        // Récupérer uniquement les dossiers liés à l'utilisateur connecté
        $dossiers = $em->getRepository(Dossier::class)->findByUser($users);

        // Récupérer fichiers sans dossier (tu peux filtrer aussi par user si besoin)
        $fichiersSansDossier = $em->getRepository(Fichier::class)->findBy(['dossier' => null]);
        $allUsers = $em->getRepository(User::class)->findAll();

        return $this->render('fichier/liste.html.twig', [
            'dossiers' => $dossiers,
            'fichiersSansDossier' => $fichiersSansDossier,
            'all_users' => $allUsers



        ]);
    }





    #[Route('/fichier/supprimer/{id}', name: 'app_fichier_supprimer')]
    public function supprimer(int $id, EntityManagerInterface $em): RedirectResponse
    {
        $fichier = $em->getRepository(Fichier::class)->find($id);

        if (!$fichier) {
            throw $this->createNotFoundException('Fichier non trouvé');
        }

        $dossier = $fichier->getDossier();
        $filesystem = new Filesystem();
        $cheminFichier = $this->getParameter('uploads_directory') . '/' . $fichier->getChemin();

        if ($filesystem->exists($cheminFichier)) {
            $filesystem->remove($cheminFichier);
        }

        $em->remove($fichier);
        $em->flush();

        return $this->redirectToRoute('app_dossier_afficher', ['id' => $dossier->getId()]); // ou ta route de liste
    }


    #[Route('/fichier/dupliquer/{{path}/{id}', name: 'app_fichier_dupliquer', requirements: ['path' => '.+'])]
    public function dupliquerFichier(string $path, EntityManagerInterface $em, int $id, MessageBusInterface $bus): Response
    {
        $basePath = $this->getParameter('uploads_directory') . '/';
        $originalPath = $basePath . '/' . $path;

        if (!file_exists($originalPath)) {
            throw $this->createNotFoundException('Fichier original introuvable');
        }

        // Créer un nouveau nom de fichier
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $newName = uniqid() . '.' . $extension;
        $dir = dirname($originalPath);
        $newPath = $dir . '/' . $newName;

        // Copier le fichier
        if (!copy($originalPath, $newPath)) {
            throw new \Exception('Erreur lors de la duplication du fichier.');
        }
        

        $dossier = $em->getRepository(Dossier::class)->find($id);
        // Dispatch message d'optimisation
        $bus->dispatch(new OptimizeImageMessage($dossier->getId(), $newName));
        // Enregistrement BDD
        $nouveauFichier = new Fichier();
        $nouveauFichier->setNom('Copie de ' . basename($path));
        $nouveauFichier->setChemin(str_replace($basePath . '/', '', $newPath)); // chemin relatif
        $nouveauFichier->setUploadedAt(new \DateTimeImmutable());
        $nouveauFichier->setDossier($dossier); // ou le dossier auquel il appartient
        // Si tu veux attribuer à l'utilisateur connecté :
        // $nouveauFichier->setUser($this->getUser());

        $em->persist($nouveauFichier);
        $em->flush();

        $this->addFlash('success', 'Fichier dupliqué avec succès !');

        return $this->redirectToRoute('app_dossier_afficher', ['id' => $dossier->getId()]); // ou ta route de liste
    }



    #[Route('/fichiers/delete-selection', name: 'app_fichiers_supprimer_selection', methods: ['POST'])]
    public function deleteSelection(
        Request $request,
        FichierRepository $fichierRepo,
        DossierRepository $dossierRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $ids = $data['ids'] ?? [];
        $dossierId = $data['dossierId'] ?? null;

        // ⚠️ Tu peux réactiver ceci une fois que tout fonctionne
        if (!$this->isCsrfTokenValid('supprimer_fichiers', $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        if (!$dossierId || !$dossierRepo->find($dossierId)) {
            return new JsonResponse(['error' => 'Dossier introuvable'], 400);
        }

        foreach ($ids as $id) {
            $fichier = $fichierRepo->find($id);
            if ($fichier && $fichier->getDossier()->getId() === $dossierId) {
                $cheminFichier = $this->getParameter('uploads_directory') . '/' . $fichier->getChemin();

                if ((new Filesystem())->exists($cheminFichier)) {
                    (new Filesystem())->remove($cheminFichier);
                }

                $em->remove($fichier);
            }
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }


    #[Route('/editeur-fichier/{id}', name: 'app_editeur_fichier')]
    public function editeurFichier(int $id, FichierRepository $fichierRepository): Response
    {
        $fichier = $fichierRepository->find($id);

        if (!$fichier) {
            throw $this->createNotFoundException("Fichier non trouvé.");
        }

        $cheminFichier =  $this->getParameter('uploads_directory') . '/' . $fichier->getChemin();

        $ext = strtolower(pathinfo($cheminFichier, PATHINFO_EXTENSION));

        $extensionsAutorisees = ['txt', 'md', 'json', 'html'];
        if (!in_array($ext, $extensionsAutorisees)) {
            throw new \Exception("Ce format de fichier ne peut pas être édité.");
        }


        if (!file_exists($cheminFichier) || !is_readable($cheminFichier)) {
            throw $this->createNotFoundException("Le fichier physique est introuvable.");
        }

        $contenu = file_get_contents($cheminFichier);

        return $this->render('fichier/editeur.html.twig', [
            'contenu' => $contenu,
            'fichier' => $fichier,
        ]);
    }


    #[Route('/enregistrer-fichier/{id}', name: 'app_enregistrer_fichier', methods: ['POST'])]
    public function enregistrerFichier(int $id, Request $request, FichierRepository $fichierRepository): Response
    {
        $fichier = $fichierRepository->find($id);

        if (!$fichier) {
            throw $this->createNotFoundException("Fichier non trouvé.");
        }

        $submittedToken = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('editer_fichier_' . $id, $submittedToken)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $cheminFichier =  $this->getParameter('uploads_directory') . '/' . $fichier->getChemin();

        if (!is_writable($cheminFichier)) {
            throw $this->createAccessDeniedException("Le fichier n'est pas modifiable.");
        }

        $contenu = $request->request->get('contenu');
        file_put_contents($cheminFichier, $contenu);

        $this->addFlash('success', 'Fichier modifié avec succès.');

        return $this->redirectToRoute('app_editeur_fichier', ['id' => $id]);
    }
}
