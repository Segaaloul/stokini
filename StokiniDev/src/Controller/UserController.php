<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use App\Entity\User;

#[Route('/users')] // 👈 prefix ici
class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }


    #[Route('/userlist', name: 'app_user_list')]
    public function listusers(Request $request, EntityManagerInterface $em, Security $security): Response
    {


        $user = $security->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir cette page.');
        }
        if ($user->getRoles()[0] != 'ROLE_ADMIN') {
            // Vérifie si l'utilisateur n'a PAS le rôle ADMIN
            // Redirige vers une page d'accès refusé
            return $this->redirectToRoute('acces_refuse');
        }



        $users = $em->getRepository(User::class)->findAll();




        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
            'users' => $users
        ]);
    }

   

    #[Route('/user/{id}', name: 'user_state_toggle')]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        if ($user->getIsActive() == 1) {
            $user->setIsActive(0);
            $em->flush();
        } else {
            $user->setIsActive(1);
            $em->flush();
        }
        return $this->redirectToRoute('app_user_list');
    }
}
