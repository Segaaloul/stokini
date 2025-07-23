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

class FichierController extends AbstractController
{
    #[Route('/fichier', name: 'app_fichier')]
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
                        $this->getParameter('uploads_directory'),
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
            'all_users' => $allUsers,


        ]);
    }




    #[Route('/fichier/supprimer/{id}', name: 'app_fichier_supprimer')]
    public function supprimer(int $id, EntityManagerInterface $em): RedirectResponse
    {
        $fichier = $em->getRepository(Fichier::class)->find($id);

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

        return $this->redirectToRoute('app_fichiers');
    }
}
