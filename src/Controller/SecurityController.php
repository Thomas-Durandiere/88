<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    // #[Route(path: '/verify/email', name: 'app_verify_email')]
    // public function verifyUserEmail(
    //     EmailVerifier $emailVerifier,
    //     UserRepository $userRepository
    // ): Response {
    //     $email = $this->getParameter('email');
    //     $user = $userRepository->findOneBy(['email' => $email]);

    //     if (!$user) {
    //         $this->addFlash('error', 'Utilisateur non trouvé.');

    //         return $this->redirectToRoute('app_register');
    //     }

    //     try {
    //         $emailVerifier->handleEmailConfirmation($this->container->get('request_stack')->getMainRequest(), $user);
    //     } catch (VerifyEmailExceptionInterface) {
    //         $this->addFlash('error', 'Lien invalide ou expiré.');

    //         return $this->redirectToRoute('app_register');
    //     }

    //     $user->setIsVerified(true);
    //     $this->entityManager->flush();  // ✅ OK

    //     return $this->redirectToRoute('app_login');
    // }
  

#[Route(path: '/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        EmailVerifier $emailVerifier,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non connecté.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Vérifie la signature et la validité du lien
            $emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_register');
        }

        // ✅ Marque l'utilisateur comme vérifié si ce n'est pas déjà fait
        if (!$user->isVerified()) {
            $user->setIsVerified(true);
            $entityManager->persist($user); // facultatif si l'objet est déjà géré
            $entityManager->flush();
        }

        $this->addFlash('success', 'Email vérifié avec succès.');
        return $this->redirectToRoute('app_login');
    }

}
