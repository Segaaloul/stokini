<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Dossier;
use App\Form\DossierType;
use App\Form\FichierType;
use App\Entity\Fichier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

use Symfony\Component\Security\Core\Security;


class DossierController extends AbstractController
{
    #[Route('/dossier', name: 'app_dossier')]
    public function index(): Response
    {
        return $this->render('dossier/index.html.twig', [
            'controller_name' => 'DossierController',
        ]);
    }


#[Route('/dossier/create', name: 'app_dossier_create', methods: ['POST'])]
public function create(Request $request, EntityManagerInterface $em, Security $security): Response
{
    $dossier = new Dossier();

    // Récupérer l'utilisateur connecté
    $user = $security->getUser();
    $dossier->setUser($user);

    // Récupérer le nom depuis le formulaire (ex: input name="nom")
    $nom = $request->request->get('nom');
    $dossier->setNom($nom);
       $dossier->setCreateAt(new \DateTimeImmutable());

    $em->persist($dossier);
    $em->flush();

    // Rediriger vers la liste des fichiers/dossiers
    return $this->redirectToRoute('app_fichiers');
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

                $file->move($this->getParameter('uploads_directory'), $newFilename);

                $fichier = new Fichier();
                $fichier->setChemin($newFilename);
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






}
