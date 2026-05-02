<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\DeliveryManRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthSessionService
{
    public function __construct(
        private DeliveryManRepository $deliveryManRepository,
    ) {
    }

    public function populateSession(SessionInterface $session, User $user): void
    {
        $normalizedRole = $this->normalizeRole($user->getRole());
        $email = strtolower(trim((string) ($user->getEmail() ?? '')));
        $displayName = trim((string) (($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')));

        $session->set('user_id', $user->getId());
        $session->set('user_email', $email);
        $session->set('user_name', $displayName);
        $session->set('user_role', $normalizedRole);
        $session->set('user_phone', $this->normalizePhone($user->getPhoneNumber() ?: $user->getPhone()));
        $session->set('user_address', $user->getAddress());

        if ($normalizedRole === 'ROLE_DELIVERY_MAN') {
            $deliveryMan = $this->deliveryManRepository->createQueryBuilder('dm')
                ->andWhere('LOWER(dm.email) = :email')
                ->setParameter('email', $email)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($deliveryMan) {
                $session->set('delivery_man_id', $deliveryMan->getDelivery_man_id());
            } elseif ($user->getReference_id()) {
                $session->set('delivery_man_id', $user->getReference_id());
            } else {
                $session->set('delivery_man_id', null);
            }
        }

        if ($normalizedRole === 'ROLE_CLIENT') {
            $session->set('client_phone', $this->normalizePhone($user->getPhone()));
            $session->set('client_name', $displayName);
        }
    }

    public function normalizeRole(?string $role): string
    {
        $upper = strtoupper(trim((string) $role));

        return match ($upper) {
            'ROLE_ADMIN', 'ADMIN' => 'ROLE_ADMIN',
            'ROLE_CLIENT', 'CLIENT' => 'ROLE_CLIENT',
            'ROLE_DELIVERY_MAN', 'DELIVERY_MAN', 'DELIVERY' => 'ROLE_DELIVERY_MAN',
            default => 'ROLE_CLIENT',
        };
    }

    public function normalizePhone(?string $phone): ?string
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
