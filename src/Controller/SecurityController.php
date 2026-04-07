<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, SessionInterface $session): Response
    {
        $error = null;
        $credentials = [
            'admin@big4.test' => [
                'password' => 'admin123',
                'role' => 'ROLE_ADMIN',
                'name' => 'Admin User',
            ],
            'driver@big4.test' => [
                'password' => 'driver123',
                'role' => 'ROLE_DELIVERY_MAN',
                'name' => 'Delivery Man',
                'delivery_man_id' => 1,
            ],
            'client@big4.test' => [
                'password' => 'client123',
                'role' => 'ROLE_CLIENT',
                'name' => 'Client User',
                'phone' => '+21600000000',
            ],
        ];

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));
            $password = trim((string) $request->request->get('password', ''));

            if (isset($credentials[$email]) && $credentials[$email]['password'] === $password) {
                $user = $credentials[$email];
                $session->set('user_email', $email);
                $session->set('user_name', $user['name']);
                $session->set('user_role', $user['role']);

                if (isset($user['delivery_man_id'])) {
                    $session->set('delivery_man_id', $user['delivery_man_id']);
                }
                if (isset($user['phone'])) {
                    $session->set('client_phone', $user['phone']);
                }

                if ($user['role'] === 'ROLE_ADMIN') {
                    return $this->redirectToRoute('app_admin_dashboard');
                }
                if ($user['role'] === 'ROLE_DELIVERY_MAN') {
                    return $this->redirectToRoute('app_driver_deliveries');
                }

                return $this->redirectToRoute('app_home');
            }

            $error = 'Invalid email or password.';
        }

        return $this->render('security/login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(SessionInterface $session): Response
    {
        $session->clear();

        return $this->redirectToRoute('app_home');
    }
}
