<?php

namespace App\Controller;





use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'app_registration')]
    public function index(): Response
    {
        return $this->render('registration/index.html.twig', [
            'controller_name' => 'RegistrationController',
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash le mot de passe en clair avant de le stocker
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setRoles(['ROLE_USER']);
            $user->setIsActive(0);
            $entityManager->persist($user);
            $entityManager->flush();
            $uploadDir = $this->getParameter('uploads_directory') . '/' . $user->getNom();

            if (!file_exists($uploadDir)) {
                //mkdir($uploadDir, 0775, true); // crée le dossier récursivement
                if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                    throw new \RuntimeException(sprintf('Impossible de créer le dossier : %s', $uploadDir));
                }
            }

            // Connecte automatiquement l'utilisateur (optionnel)
            // $this->container->get('security.login_manager')->logInUser($user);

            // Redirige vers la page de login ou upload
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
