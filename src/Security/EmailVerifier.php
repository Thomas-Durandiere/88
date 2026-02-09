<?php


namespace App\Security;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer
    ) {
    }

    public function sendEmailConfirmation(
        string $verifyEmailRoute,
        object $user,
        TemplatedEmail $email
    ): void {
        $signature = $this->verifyEmailHelper
            ->generateSignature($verifyEmailRoute, (string)$user->getId(), $user->getEmail());

        $context = $email->getContext();
        $context['signedUrl'] = $signature->getSignedUrl();
        $context['expiresMessageKey'] = $signature->getExpirationMessageKey();
        $context['expiresMessageData'] = '1 heure';

        $email->context($context);

        $this->mailer->send($email);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, object $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request,
            (string)$user->getId(),
            $user->getEmail()
        );
    }
}
