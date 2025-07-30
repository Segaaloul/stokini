<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Dossier;
use App\Form\DossierType;
use App\Form\FichierType;
use App\Entity\Fichier;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\UserRepository;
use App\Repository\DossierRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
#[Route('/dossier')] // 👈 prefix ici
class DossierController extends AbstractController
{
    #[Route('/', name: 'app_dossier')]
    public function index(): Response
    {
        return $this->render('dossier/index.html.twig', [
            'controller_name' => 'DossierController',

        ]);
    }

    #[Route('/fichier/{path}', name: 'app_serve_fichier', requirements: ['path' => '.+'])]
public function serveFichier(string $path,Security $security): BinaryFileResponse
{
    $baseDir = $this->getParameter('uploads_directory'); // ex: /home/user/mon-projet/uploads

    $fullPath = $baseDir . '/' . $path;

    if (!file_exists($fullPath)) {
        throw new NotFoundHttpException('Fichier non trouvé.');
    }


      $user = $security->getUser();
    if (!$user) {
        throw new AccessDeniedHttpException('Vous devez être connecté.');
    }

    $username = $user->getNom(); // ou ->getUserIdentifier() selon ton User

    if (!str_starts_with($path, $username . '/')) {
        throw new AccessDeniedHttpException('Accès interdit à ce fichier.');
    }

    $response = new BinaryFileResponse($fullPath);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($fullPath));

    return $response;
}



    #[Route('/test-upload-create')]
public function testUploadCreate(): Response
{
    $baseDir = $this->getParameter('uploads_directory'); // ex: /var/www/stokini/StokiniDev/uploads
    $testDir = $baseDir . '/test123';

    try {
        if (!is_dir($testDir)) {
            if (!mkdir($testDir, 0775, true)) {
                return new Response("❌ Échec de création du dossier : $testDir", 500);
            }
        }

        return new Response("✅ Dossier créé avec succès : $testDir");
    } catch (\Throwable $e) {
        return new Response("❌ Erreur : " . $e->getMessage(), 500);
    }
}


    #[Route('/dossier/create', name: 'app_dossier_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, Security $security): Response
    {
        $dossier = new Dossier();

        // Récupérer l'utilisateur connecté
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour créer un dossier.');
        }

        $dossier->addUser($user);

        // Récupérer le nom depuis le formulaire (ex: input name="nom")
        $nom = $request->request->get('nom');
        $dossier->setNom($nom);
        $dossier->setCreateAt(new \DateTimeImmutable());
        $dossier->setCreatedBy($user->getNom());
        $em->persist($dossier);
        $em->flush();
       // $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/'  . $user->getNom() . '/' . $dossier->getNom();
       $uploadDir = $this->getParameter('uploads_directory') . '/'. $user->getNom() . '/' . $dossier->getNom();

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0775, true); // crée le dossier récursivement
        }

        return $this->redirectToRoute('app_fichiers');
    }

    #[Route('/dossier/partager', name: 'app_dossier_partager', methods: ['POST'])]
    public function partagerdossier(Request $request, EntityManagerInterface $em, Security $security, UserRepository $userRepository, DossierRepository $dossierRepository): Response
    {
        $userId = $request->request->get('user_id');
        $dossierId = $request->request->get('dossier_id');

        /** @var User|null $user */
        $user = $userRepository->find($userId);
        $dossier = $dossierRepository->find($dossierId);

        if (!$user || !$dossier) {
            $this->addFlash('error', 'Utilisateur ou dossier introuvable.');
            return $this->redirectToRoute('app_fichiers');
        }

        // Ajouter l'utilisateur à la liste des utilisateurs partagés
        $dossier->addUser($user);

        $em->flush();

        $this->addFlash('success', 'Dossier partagé avec succès !');
        return $this->redirectToRoute('app_fichiers');
    }

    public function liste(EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer les dossiers liés à l'utilisateur connecté
        $dossiers = $em->getRepository(Dossier::class)->createQueryBuilder('d')
            ->join('d.users', 'u')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        // Récupérer fichiers sans dossier
        $fichiersSansDossier = $em->getRepository(Fichier::class)->findBy(['dossier' => null]);

        // Récupérer tous les utilisateurs sauf l'utilisateur connecté
        $allUsers = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u != :currentUser')
            ->setParameter('currentUser', $user)
            ->getQuery()
            ->getResult();

        return $this->render('fichier/liste.html.twig', [
            'dossiers' => $dossiers,
            'fichiersSansDossier' => $fichiersSansDossier,
            'all_users' => $allUsers,
        ]);
    }




    // public function afficher(Dossier $dossier): Response
    // {
    // $dossier contient déjà ses fichiers si ta relation est bien configurée (fetch="EAGER" ou que tu appelles getFichiers())
    //     return $this->render('dossier/afficher.html.twig', [
    //         'dossier' => $dossier,
    //         'fichiers' => $dossier->getFichiers(),
    //     ]);
    // }
    // }
    #[Route('/dossier/{id}', name: 'app_dossier_afficher')]
    public function afficher(int $id, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $dossier = $em->getRepository(Dossier::class)->find($id);
        if (!$dossier) {
            throw $this->createNotFoundException('Dossier non trouvé.');
        }

        

        // Crée formulaire upload
        $form = $this->createForm(FichierType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('fichier')->getData();

            if ($files) {
                foreach ($files as $file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                    $file->move($this->getParameter('uploads_directory') . '/' . $dossier->getCreatedBy() . '/' . $dossier->getNom(),  $newFilename);

                    $fichier = new Fichier();
                    $fichier->setChemin($dossier->getCreatedBy() . '/' . $dossier->getNom() . '/' . $newFilename);
                    $fichier->setNom($originalFilename);
                    $fichier->setUploadedAt(new \DateTimeImmutable());
                    $fichier->setDossier($dossier);

                    $em->persist($fichier);
                }
                $em->flush();
            }

            return $this->redirectToRoute('app_dossier_afficher', ['id' => $id]);
        }

        return $this->render('dossier/afficher.html.twig', [
            'dossier' => $dossier,
            'fichiers' => $dossier->getFichiers(),
            'form' => $form->createView(),
        ]);
    }






    #[Route('/dossier/supprimer/{id}', name: 'app_dossier_supprimer')]
    public function supprimer(int $id, EntityManagerInterface $em): Response
    {
        $dossier = $em->getRepository(Dossier::class)->find($id);

        if (!$dossier) {
            throw $this->createNotFoundException('Dossier non rencontré');
        }

        foreach ($dossier->getFichiers() as $fichier) {


            if (!$fichier) {
                throw $this->createNotFoundException('Fichier non trouvé');
            }

            $filesystem = new Filesystem();
            $cheminFichier = $this->getParameter('uploads_directory') . '/' . $fichier->getChemin();

            if ($filesystem->exists($cheminFichier)) {
                $filesystem->remove($cheminFichier);
            }

            $em->remove($fichier);
            $em->flush();
        }

        $em->remove($dossier);
        $em->flush();

        return $this->redirectToRoute('app_fichiers');
    }
}
