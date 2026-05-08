<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\OrderRepository;

class OrderHistoryService
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    /**
     * @return array
     */
    public function getLastOrders(User $user, int $limit = 3): array
    {
        return $this->orderRepository->findBy(
            ['client' => $user],
            ['order_date' => 'DESC'],
            $limit
        );
    }
}
