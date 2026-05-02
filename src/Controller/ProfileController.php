<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(SessionInterface $session, UserRepository $userRepository): Response
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SessionInterface $session, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Your profile has been updated successfully.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/quick-save', name: 'app_profile_quick_save', methods: ['POST'])]
    public function quickSave(Request $request, SessionInterface $session, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $userId = (int) $session->get('user_id');
        if ($userId <= 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Please sign in first.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('profile_quick_save', $token)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid form token.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user = $userRepository->find($userId);
        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $fullName = trim((string) $request->request->get('full_name', ''));
        $email = strtolower(trim((string) $request->request->get('email', '')));
        $phone = trim((string) $request->request->get('phone', ''));
        $address = trim((string) $request->request->get('address', ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Please enter a valid email address.',
            ], Response::HTTP_BAD_REQUEST);
        }

        [$firstName, $lastName] = $this->splitFullName($fullName);

        if ($fullName !== '') {
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
        }

        $user->setEmail($email);
        $user->setPhone($phone !== '' ? $phone : null);
        $user->setPhoneNumber($phone !== '' ? $phone : null);
        $user->setAddress($address !== '' ? $address : null);

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'This email is already used by another account.',
            ], Response::HTTP_CONFLICT);
        }

        $normalizedRole = strtoupper((string) $session->get('user_role', ''));
        $normalizedPhone = $this->normalizePhone($user->getPhoneNumber() ?: $user->getPhone());
        $displayName = trim((string) (($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')));

        $session->set('user_email', $user->getEmail());
        $session->set('user_name', $displayName);
        $session->set('user_phone', $normalizedPhone);
        $session->set('user_address', $user->getAddress());

        if ($normalizedRole === 'ROLE_CLIENT') {
            $session->set('client_name', $displayName);
            $session->set('client_phone', $normalizedPhone);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Profile saved successfully.',
            'profile' => [
                'fullName' => $displayName,
                'email' => $user->getEmail(),
                'phone' => $normalizedPhone,
                'address' => $user->getAddress(),
            ],
        ]);
    }

    private function splitFullName(string $fullName): array
    {
        if ($fullName === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts) || $parts === []) {
            return ['', ''];
        }

        $firstName = array_shift($parts) ?? '';
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
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
}
