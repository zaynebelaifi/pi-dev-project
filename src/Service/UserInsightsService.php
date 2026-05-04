<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;

class UserInsightsService
{
    public function __construct(
        private UserRepository $userRepository,
        private OrderRepository $orderRepository,
    ) {
    }

    public function getUserStats(int $userId): ?array
    {
        $user = $this->userRepository->find($userId);
        if (!$user instanceof User) {
            return null;
        }

        $result = $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.order_id) AS totalOrders')
            ->addSelect('COALESCE(SUM(o.total_amount), 0) AS totalSpent')
            ->addSelect('MAX(o.order_date) AS lastOrderDate')
            ->where('o.client_id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleResult();

        $lastOrderDate = $result['lastOrderDate'] ?? null;
        if ($lastOrderDate instanceof \DateTimeInterface) {
            $lastOrderDate = $lastOrderDate->format(DATE_ATOM);
        } elseif (is_string($lastOrderDate) && $lastOrderDate !== '') {
            $lastOrderDate = (new \DateTimeImmutable($lastOrderDate))->format(DATE_ATOM);
        } else {
            $lastOrderDate = null;
        }

        return [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'totalOrders' => (int) ($result['totalOrders'] ?? 0),
            'totalSpent' => round((float) ($result['totalSpent'] ?? 0), 2),
            'lastOrderDate' => $lastOrderDate,
        ];
    }

    public function getInactiveUsers(int $days = 30): array
    {
        $cutoff = (new \DateTimeImmutable('now'))->modify(sprintf('-%d days', $days));

        $rows = $this->userRepository->createQueryBuilder('u')
            ->select('u.id AS id')
            ->addSelect('u.email AS email')
            ->addSelect('u.role AS role')
            ->addSelect('u.first_name AS firstName')
            ->addSelect('u.last_name AS lastName')
            ->addSelect('u.phone AS phone')
            ->addSelect('u.phone_number AS phoneNumber')
            ->addSelect('u.address AS address')
            ->addSelect('MAX(o.order_date) AS lastOrderDate')
            ->leftJoin(Order::class, 'o', 'WITH', 'o.client_id = u.id')
            ->andWhere('UPPER(COALESCE(u.role, :defaultRole)) = :clientRole')
            ->groupBy('u.id')
            ->having('MAX(o.order_date) IS NULL OR MAX(o.order_date) < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->setParameter('defaultRole', 'ROLE_CLIENT')
            ->setParameter('clientRole', 'ROLE_CLIENT')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(function (array $row): array {
            $firstName = trim((string) ($row['firstName'] ?? ''));
            $lastName = trim((string) ($row['lastName'] ?? ''));
            $fullName = trim($firstName . ' ' . $lastName);

            $lastOrderDate = $row['lastOrderDate'] ?? null;
            if ($lastOrderDate instanceof \DateTimeInterface) {
                $lastOrderDate = $lastOrderDate->format(DATE_ATOM);
            } elseif (is_string($lastOrderDate) && $lastOrderDate !== '') {
                $lastOrderDate = (new \DateTimeImmutable($lastOrderDate))->format(DATE_ATOM);
            } else {
                $lastOrderDate = null;
            }

            return [
                'id' => (int) $row['id'],
                'email' => $row['email'],
                'role' => $this->formatRole((string) ($row['role'] ?? 'ROLE_CLIENT')),
                'firstName' => $firstName,
                'lastName' => $lastName,
                'fullName' => $fullName !== '' ? $fullName : null,
                'phone' => $row['phoneNumber'] ?: $row['phone'],
                'address' => $row['address'],
                'lastOrderDate' => $lastOrderDate,
            ];
        }, $rows);
    }

    public function getLoyaltyScore(int $userId): ?array
    {
        $stats = $this->getUserStats($userId);
        if ($stats === null) {
            return null;
        }

        $score = ($stats['totalOrders'] * 2) + ($stats['totalSpent'] / 5);

        return [
            'userId' => $stats['userId'],
            'email' => $stats['email'],
            'formula' => '(number_of_orders * 2) + (total_spent / 5)',
            'totalOrders' => $stats['totalOrders'],
            'totalSpent' => $stats['totalSpent'],
            'loyaltyScore' => round($score, 2),
        ];
    }

    public function getRecommendation(int $userId): ?array
    {
        $user = $this->userRepository->find($userId);
        if (!$user instanceof User) {
            return null;
        }

        $orders = $this->orderRepository->createQueryBuilder('o')
            ->select('o.cart_items AS cartItems')
            ->addSelect('o.order_type AS orderType')
            ->addSelect('o.total_amount AS totalAmount')
            ->where('o.client_id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('o.order_date', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getArrayResult();

        $itemFrequency = [];
        $deliveryOrders = 0;
        $dineInOrders = 0;

        foreach ($orders as $order) {
            $orderType = strtoupper((string) ($order['orderType'] ?? ''));
            if ($orderType === 'DELIVERY') {
                $deliveryOrders++;
            } elseif ($orderType === 'DINE_IN') {
                $dineInOrders++;
            }

            foreach ($this->extractItemNames((string) ($order['cartItems'] ?? '')) as $itemName) {
                $key = mb_strtolower($itemName);
                $itemFrequency[$key] = ($itemFrequency[$key] ?? 0) + 1;
            }
        }

        arsort($itemFrequency);

        $message = 'Try our signature cappuccino with a butter croissant for your next order.';
        $reason = 'No strong order history yet, so this is a high-rated starter recommendation.';
        $confidence = 0.62;

        if (!empty($itemFrequency)) {
            $topItemKey = (string) array_key_first($itemFrequency);
            $topItemCount = (int) ($itemFrequency[$topItemKey] ?? 0);
            $topItemLabel = ucwords($topItemKey);

            if ($deliveryOrders > $dineInOrders) {
                $message = sprintf('You often order %s. Add an iced latte for a better delivery combo.', $topItemLabel);
            } else {
                $message = sprintf('You often enjoy %s. Pair it with our chef dessert this time.', $topItemLabel);
            }

            $reason = sprintf('Recommendation is based on your last %d orders and recurring cart items.', count($orders));
            $confidence = min(0.95, 0.65 + ($topItemCount * 0.04));
        }

        return [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'message' => $message,
            'reason' => $reason,
            'engine' => 'simulated-ai',
            'confidence' => round($confidence, 2),
        ];
    }

    public function getDefaultInsightsUserId(): ?int
    {
        $target = $this->getDefaultInsightsTarget();

        return is_array($target) ? (int) ($target['id'] ?? 0) : null;
    }

    public function getDefaultInsightsTarget(): ?array
    {
        $row = $this->userRepository->createQueryBuilder('u')
            ->select('u.id AS id')
            ->addSelect('u.email AS email')
            ->addSelect('u.first_name AS firstName')
            ->addSelect('u.last_name AS lastName')
            ->addSelect('COUNT(o.order_id) AS totalOrders')
            ->leftJoin(Order::class, 'o', 'WITH', 'o.client_id = u.id')
            ->andWhere('UPPER(COALESCE(u.role, :defaultRole)) = :clientRole')
            ->setParameter('defaultRole', 'ROLE_CLIENT')
            ->setParameter('clientRole', 'ROLE_CLIENT')
            ->groupBy('u.id')
            ->orderBy('totalOrders', 'DESC')
            ->addOrderBy('u.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $fullName = trim((string) (($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? '')));

        return [
            'id' => (int) $row['id'],
            'email' => (string) ($row['email'] ?? ''),
            'fullName' => $fullName !== '' ? $fullName : null,
            'totalOrders' => (int) ($row['totalOrders'] ?? 0),
        ];
    }

    /**
     * @return string[]
     */
    private function extractItemNames(string $cartItems): array
    {
        if (trim($cartItems) === '') {
            return [];
        }

        $decoded = json_decode($cartItems, true);
        if (!is_array($decoded)) {
            return [];
        }

        $names = [];
        foreach ($decoded as $item) {
            if (is_array($item) && isset($item['name']) && is_string($item['name'])) {
                $name = trim($item['name']);
                if ($name !== '') {
                    $names[] = $name;
                }
                continue;
            }

            if (is_string($item) && trim($item) !== '') {
                $names[] = trim($item);
            }
        }

        return $names;
    }

    private function formatRole(string $role): string
    {
        $normalized = strtoupper(trim($role));

        return match ($normalized) {
            'ROLE_ADMIN', 'ADMIN' => 'Admin',
            'ROLE_DELIVERY_MAN', 'DELIVERY_MAN', 'DELIVERY' => 'Delivery Man',
            default => 'Client',
        };
    }
}
