<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

class UserStatsService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get basic stats for a user (total orders and total spent).
     */
    public function getUserStats(User $user): array
    {
        $orders = $this->entityManager->getRepository(Order::class)->findBy(['client' => $user]);
        
        $totalOrders = count($orders);
        $totalSpent = 0;

        foreach ($orders as $order) {
            $totalSpent += (float) $order->getTotalAmount();
        }

        return [
            'total_orders' => $totalOrders,
            'total_spent' => number_format($totalSpent, 2, '.', ''),
            'currency' => 'TND',
            'last_order_date' => $totalOrders > 0 ? end($orders)->getOrderDate()->format('Y-m-d H:i') : 'Never'
        ];
    }

    /**
     * Calculate loyalty score based on a formula: (orders * 2) + (total_spent / 5).
     */
    public function calculateLoyaltyScore(User $user): array
    {
        $stats = $this->getUserStats($user);
        $score = ($stats['total_orders'] * 2) + ($stats['total_spent'] / 5);

        return [
            'score' => round($score, 1),
            'tier' => $this->getLoyaltyTier($score),
            'next_tier_progress' => $this->calculateNextTierProgress($score)
        ];
    }

    /**
     * Find users who haven't ordered in the last 30 days.
     */
    public function getInactiveUsers(): array
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        // This is a simplified version. In a real scenario, we'd use a more complex DQL query.
        $allUsers = $this->entityManager->getRepository(User::class)->findAll();
        $inactiveUsers = [];

        foreach ($allUsers as $user) {
            $lastOrder = $this->entityManager->getRepository(Order::class)->findOneBy(
                ['client' => $user],
                ['order_date' => 'DESC']
            );

            if (!$lastOrder || $lastOrder->getOrderDate() < $thirtyDaysAgo) {
                if (in_array('ROLE_CLIENT', $user->getRoles())) {
                    $inactiveUsers[] = [
                        'id' => $user->getId(),
                        'name' => $user->getFirstName() . ' ' . $user->getLastName(),
                        'email' => $user->getEmail(),
                        'last_order' => $lastOrder ? $lastOrder->getOrderDate()->format('Y-m-d') : 'Never'
                    ];
                }
            }
        }

        return $inactiveUsers;
    }

    /**
     * Generate AI-driven recommendation based on user history.
     */
    public function getAIRecommendation(User $user): array
    {
        $stats = $this->getUserStats($user);
        
        // Mocking AI logic based on user behavior
        $recommendations = [
            "Based on your high spending, we recommend our Platinum Tasting Menu.",
            "You haven't ordered in a while. Use code 'MISSYOU10' for 10% off your next meal!",
            "Since you like dining in, why not try our private chef experience?",
            "Your loyalty score is high! You qualify for a complimentary dessert on your next visit."
        ];

        if ($stats['total_orders'] == 0) {
            $rec = "Welcome to BIG 4! Start with our signature Espresso Martini.";
        } elseif ($stats['total_orders'] > 10) {
            $rec = $recommendations[3];
        } else {
            $rec = $recommendations[array_rand($recommendations)];
        }

        return [
            'recommendation' => $rec,
            'confidence' => '94%',
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function getLoyaltyTier(float $score): string
    {
        if ($score >= 500) return 'PLATINUM';
        if ($score >= 200) return 'GOLD';
        if ($score >= 50) return 'SILVER';
        return 'BRONZE';
    }

    private function calculateNextTierProgress(float $score): int
    {
        if ($score >= 500) return 100;
        if ($score >= 200) return (int)(($score - 200) / 300 * 100);
        if ($score >= 50) return (int)(($score - 50) / 150 * 100);
        return (int)($score / 50 * 100);
    }
}
