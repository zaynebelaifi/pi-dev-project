<?php

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Handles the complete Forgot Password / Reset Password flow.
 *
 * Flow:
 *  1. GET  /reset-password          → show "Forgot Password" form
 *  2. POST /reset-password          → validate email, create token, send email
 *  3. GET  /reset-password/check    → confirmation page ("check your inbox")
 *  4. GET  /reset-password/{token}  → show "Reset Password" form (validates token)
 *  5. POST /reset-password/{token}  → validate new password, update user, invalidate token
 *
 * Security notes:
 *  - The real token is NEVER stored in the database; only its SHA-256 hash is.
 *  - If the email is not found we still show the same success message (prevents user enumeration).
 *  - Tokens expire after ResetPasswordRequest::TOKEN_TTL seconds (1 hour).
 *  - Old tokens for the same user are deleted before creating a new one.
 *  - The token is consumed (deleted) immediately after a successful password reset.
 */
#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface          $entityManager,
        private readonly UserRepository                  $userRepository,
        private readonly ResetPasswordRequestRepository  $resetPasswordRequestRepository,
        private readonly UserPasswordHasherInterface     $passwordHasher,
        private readonly MailerInterface                 $mailer,
    ) {
    }

    // ----------------------------------------------------------------
    // Step 1 & 2 — Forgot Password form
    // ----------------------------------------------------------------

    /**
     * Displays the "Forgot Password" form and processes the email submission.
     *
     * Route: GET|POST /reset-password
     * Name:  app_forgot_password
     */
    #[Route('', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function request(Request $request): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = strtolower(trim((string) $form->get('email')->getData()));

            // Always redirect to the "check your inbox" page regardless of whether
            // the email exists — this prevents user enumeration attacks.
            $this->processForgotPassword($email);

            return $this->redirectToRoute('app_check_email');
        }

        return $this->render('reset_password/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ----------------------------------------------------------------
    // Step 3 — "Check your email" confirmation page
    // ----------------------------------------------------------------

    /**
     * Displays a generic "check your inbox" confirmation page.
     * No sensitive information is shown here.
     *
     * Route: GET /reset-password/check
     * Name:  app_check_email
     */
    #[Route('/check', name: 'app_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('reset_password/check_email.html.twig');
    }

    // ----------------------------------------------------------------
    // Steps 4 & 5 — Reset Password form
    // ----------------------------------------------------------------

    /**
     * Validates the reset token and allows the user to set a new password.
     *
     * Route: GET|POST /reset-password/{token}
     * Name:  app_reset_password
     *
     * @param string $token The plain (unhashed) token from the email link.
     */
    #[Route('/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(Request $request, string $token): Response
    {
        // Hash the token from the URL to look it up in the database.
        $hashedToken = $this->hashToken($token);

        $resetRequest = $this->resetPasswordRequestRepository->findValidRequest($hashedToken);

        // Token not found or already expired.
        if (!$resetRequest) {
            $this->addFlash('error', 'This password reset link is invalid or has expired. Please request a new one.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Retrieve the plain password from the unmapped field.
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user = $resetRequest->getUser();

            // Hash and persist the new password.
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

            // Invalidate the token immediately — prevents reuse.
            $this->entityManager->remove($resetRequest);

            $this->entityManager->flush();

            $this->addFlash('success', 'Your password has been reset successfully. You can now sign in with your new password.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'form'        => $form->createView(),
            'token'       => $token,
            'expiresAt'   => $resetRequest->getExpiresAt(),
        ]);
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    /**
     * Looks up the user by email, creates a reset token, and sends the email.
     * If the email is not found, this method silently returns (no exception).
     */
    private function processForgotPassword(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        // Do nothing if the user does not exist — the caller always redirects
        // to the same "check your inbox" page to prevent user enumeration.
        if (!$user) {
            return;
        }

        // Remove any existing reset requests for this user before creating a new one.
        // This prevents token accumulation and ensures only one active token exists.
        $this->resetPasswordRequestRepository->removeAllForUser($user);

        // Generate a cryptographically secure random token (32 bytes → 64 hex chars).
        $plainToken  = bin2hex(random_bytes(32));
        $hashedToken = $this->hashToken($plainToken);

        // Persist the hashed token.
        $resetRequest = new ResetPasswordRequest($user, $hashedToken);
        $this->entityManager->persist($resetRequest);
        $this->entityManager->flush();

        // Build the reset URL that will be embedded in the email.
        $resetUrl = $this->generateUrl(
            'app_reset_password',
            ['token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Send the email using a Twig template.
        $email = (new TemplatedEmail())
            ->from('emnadridi979@gmail.com')
            ->to((string) $user->getEmail())
            ->subject('BIG 4 — Reset your password')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'resetUrl'    => $resetUrl,
                'firstName'   => $user->getFirstName() ?? 'there',
                'expiresAt'   => $resetRequest->getExpiresAt(),
                'tokenTtlMin' => (int) (ResetPasswordRequest::TOKEN_TTL / 60),
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Throwable) {
            // Silently swallow mailer errors so the user still sees the
            // "check your inbox" page. Log this in production.
        }
    }

    /**
     * Returns the SHA-256 hex digest of the given token string.
     * This is what gets stored in the database.
     */
    private function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
