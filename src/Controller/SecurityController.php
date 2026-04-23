<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Form\LoginType;
use App\Form\RegistrationType;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use App\Service\AuthSessionService;

final class SecurityController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage,
        AuthSessionService $authSessionService,
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Normalize the email and hash the password
            $normalizedEmail = strtolower(trim($user->getEmail() ?? ''));
            $existingUser = $this->findUserByEmail($userRepository, $normalizedEmail);
            if ($existingUser instanceof User) {
                $form->get('email')->addError(new FormError('An account with this email already exists. Please sign in or reset your password.'));

                return $this->render('security/register.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $user->setEmail($normalizedEmail);

            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            // Set role based on name
            $fullName = strtolower(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? ''));
            if (strpos($fullName, 'delivery') !== false) {
                $user->setRole('ROLE_DELIVERY_MAN');
            } else {
                $user->setRole('ROLE_CLIENT');
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $tokenStorage->setToken(new PostAuthenticationToken($user, 'main', $user->getRoles()));
            $authSessionService->populateSession($request->getSession(), $user);

            $this->addFlash('success', 'Account created successfully. You are now signed in.');
            $this->addFlash('info', 'Finish setup by enabling Face ID on this device from your profile.');

            return $this->redirectToRoute('app_profile_edit', ['faceid' => 'setup']);
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(SessionInterface $session): Response
    {
        $form = $this->createForm(LoginType::class);

        $error = null;
        $sessionError = $session->get('auth_login_error');
        if (is_string($sessionError) && trim($sessionError) !== '') {
            $error = $sessionError;
            $session->remove('auth_login_error');
        }

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET'])]
    public function forgotPasswordPage(): Response
    {
        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/api/auth/forgot-password', name: 'app_api_forgot_password', methods: ['POST'])]
    public function apiForgotPassword(
        Request $request,
        UserRepository $userRepository,
        PasswordResetTokenRepository $passwordResetTokenRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email !== '') {
            $user = $this->findUserByEmail($userRepository, $email);

            if ($user instanceof User) {
                $this->issuePasswordResetToken($user, $passwordResetTokenRepository, $entityManager, $mailer);
            }
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'An email has been sent with instructions to reset your password.',
        ]);
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET'])]
    public function resetPasswordPage(Request $request, PasswordResetTokenRepository $passwordResetTokenRepository): Response
    {
        $token = trim((string) $request->query->get('token', ''));
        if ($token === '') {
            $this->addFlash('error', 'Invalid reset link.');

            return $this->redirectToRoute('app_forgot_password');
        }

        $tokenRecord = $passwordResetTokenRepository->findActiveByTokenHash(hash('sha256', $token));
        if (!$tokenRecord instanceof PasswordResetToken) {
            $this->addFlash('error', 'This reset link is invalid or expired.');

            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
        ]);
    }

    #[Route('/api/auth/reset-password', name: 'app_api_reset_password', methods: ['POST'])]
    public function apiResetPassword(
        Request $request,
        PasswordResetTokenRepository $passwordResetTokenRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $token = trim((string) ($payload['token'] ?? ''));
        $newPassword = (string) ($payload['newPassword'] ?? '');
        $confirmPassword = (string) ($payload['confirmPassword'] ?? '');

        if ($token === '' || $newPassword === '' || $confirmPassword === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Please fill in all required fields.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($newPassword !== $confirmPassword) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Passwords do not match.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($newPassword) < 8) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Password must be at least 8 characters long.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $tokenRecord = $passwordResetTokenRepository->findActiveByTokenHash(hash('sha256', $token));
        if (!$tokenRecord instanceof PasswordResetToken) {
            return new JsonResponse([
                'success' => false,
                'message' => 'This reset link is invalid or expired.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $tokenRecord->getUser();
        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'This reset link is invalid or expired.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $tokenRecord->setUsedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Password updated successfully. You can now sign in.',
            'redirect' => $this->generateUrl('app_login'),
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET', 'POST'])]
    public function logout(SessionInterface $session): Response
    {
        $session->clear();

        return $this->redirectToRoute('app_home');
    }

    private function isLegacyPasswordValid(User $user, string $plainPassword): bool
    {
        $stored = (string) ($user->getPassword() ?? '');
        if ($stored === '') {
            return false;
        }

        $legacyHash = base64_encode(hash('sha256', $plainPassword, true));

        return hash_equals($stored, $legacyHash);
    }

    private function normalizeRole(?string $role): string
    {
        $upper = strtoupper(trim((string) $role));

        return match ($upper) {
            'ROLE_ADMIN', 'ADMIN' => 'ROLE_ADMIN',
            'ROLE_CLIENT', 'CLIENT' => 'ROLE_CLIENT',
            'ROLE_DELIVERY_MAN', 'DELIVERY_MAN', 'DELIVERY' => 'ROLE_DELIVERY_MAN',
            default => 'ROLE_CLIENT',
        };
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $normalized = preg_replace('/[^0-9+]/', '', $phone);
        if ($normalized === false) {
            return null;
        }

        return $normalized;
    }

    private function issuePasswordResetToken(
        User $user,
        PasswordResetTokenRepository $passwordResetTokenRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): void {
        $passwordResetTokenRepository->invalidateActiveTokensForUser($user);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $now = new \DateTimeImmutable();

        $resetToken = (new PasswordResetToken())
            ->setUser($user)
            ->setTokenHash($tokenHash)
            ->setCreatedAt($now)
            ->setExpiresAt($now->modify('+1 hour'));

        $entityManager->persist($resetToken);
        $entityManager->flush();

        $resetLink = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
        $mail = (new Email())
            ->to((string) $user->getEmail())
            ->subject('Reset your password')
            ->text(
                "Hello,\n\n" .
                "You requested to reset your password.\n" .
                "Click the link below to set a new password:\n" .
                $resetLink . "\n\n" .
                "This link is valid for 1 hour.\n\n" .
                "If you did not request this, please ignore this email."
            );

        $mailer->send($mail);
    }

    private function findUserByEmail(UserRepository $userRepository, string $email): ?User
    {
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail === '') {
            return null;
        }

        return $userRepository->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) = :email')
            ->setParameter('email', $normalizedEmail)
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
