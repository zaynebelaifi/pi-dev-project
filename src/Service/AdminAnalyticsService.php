<?php

namespace App\Service;

use App\Repository\IngredientRepository;
use App\Utils\WeatherImpactService;
use Doctrine\DBAL\Connection;

class AdminAnalyticsService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly IngredientRepository $ingredientRepository,
        private readonly WeatherImpactService $weatherImpactService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildAnalyticsViewData(
        string $wastePeriod = 'Month',
        string $revenuePeriod = 'Month',
        ?string $revenueFrom = null,
        ?string $revenueTo = null,
        string $revenueSort = 'revenue_desc'
    ): array {
        $today = new \DateTimeImmutable('today');
        $wastePeriod = $this->normalizePeriod($wastePeriod);
        $revenuePeriod = $this->normalizePeriod($revenuePeriod);
        $revenueSort = $this->normalizeRevenueSort($revenueSort);

        [$wasteWhere, $wasteParams] = $this->buildPeriodWhereClause('w.date', $wastePeriod);
        [$revenueWhere, $revenueParams, $rangeLabel, $rangeFrom, $rangeTo] = $this->buildRevenueWhereClause(
            $revenuePeriod,
            $revenueFrom,
            $revenueTo
        );

        $wasteSummary = $this->connection->fetchAssociative(
            'SELECT
                COUNT(*) AS waste_records,
                COALESCE(SUM(w.quantityWasted), 0) AS waste_quantity,
                COALESCE(SUM(w.quantityWasted * COALESCE(i.unitCost, 0)), 0) AS waste_cost
             FROM wasterecord w
             LEFT JOIN ingredient i ON i.id = w.ingredientId
             WHERE '.$wasteWhere,
            $wasteParams
        ) ?: [];

        $wasteByTypeRows = $this->connection->fetchAllAssociative(
            'SELECT COALESCE(w.wasteType, "Unknown") AS waste_type,
                    COALESCE(SUM(w.quantityWasted), 0) AS total_quantity
             FROM wasterecord w
             WHERE '.$wasteWhere.'
             GROUP BY COALESCE(w.wasteType, "Unknown")
             ORDER BY total_quantity DESC',
            $wasteParams
        );

        $topWastedRows = $this->connection->fetchAllAssociative(
            'SELECT COALESCE(i.name, "Unknown") AS ingredient,
                    COALESCE(SUM(w.quantityWasted), 0) AS total_quantity,
                    COALESCE(SUM(w.quantityWasted * COALESCE(i.unitCost, 0)), 0) AS total_cost
             FROM wasterecord w
             LEFT JOIN ingredient i ON i.id = w.ingredientId
             WHERE '.$wasteWhere.'
             GROUP BY COALESCE(i.name, "Unknown")
             ORDER BY total_quantity DESC
             LIMIT 8',
            $wasteParams
        );

        $totalIngredients = $this->ingredientRepository->count([]);
        $lowStock = $this->ingredientRepository->countLowStock();
        $expired = $this->ingredientRepository->countExpired($today);
        $inventoryValue = $this->ingredientRepository->sumInventoryValue();

        $nearExpiry = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM ingredient i
             WHERE i.quantityInStock > 0
               AND i.expiryDate >= CURDATE()
               AND i.expiryDate <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)'
        );

        $outOfStock = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM ingredient i WHERE i.quantityInStock <= 0'
        );

        $avgUnitCost = (float) $this->connection->fetchOne(
            'SELECT COALESCE(AVG(i.unitCost), 0) FROM ingredient i'
        );

        $lowStockRate = $totalIngredients > 0 ? ($lowStock / $totalIngredients) * 100 : 0;

        $stockHealthRows = $this->connection->fetchAllAssociative(
            'SELECT health_bucket, COUNT(*) AS total
             FROM (
                SELECT
                  CASE
                    WHEN i.quantityInStock <= 0 THEN "Out of Stock"
                    WHEN i.expiryDate < CURDATE() THEN "Expired"
                    WHEN i.expiryDate <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN "Near Expiry"
                    WHEN i.quantityInStock <= i.minStockLevel THEN "Low Stock"
                    ELSE "Healthy"
                  END AS health_bucket
                FROM ingredient i
             ) stock_health
             GROUP BY health_bucket'
        );

        $stockHealth = [
            'Healthy' => 0,
            'Low Stock' => 0,
            'Near Expiry' => 0,
            'Expired' => 0,
            'Out of Stock' => 0,
        ];
        foreach ($stockHealthRows as $row) {
            $key = (string) ($row['health_bucket'] ?? '');
            if (isset($stockHealth[$key])) {
                $stockHealth[$key] = (int) ($row['total'] ?? 0);
            }
        }

        $weatherImpact = $this->weatherImpactService->getWeatherImpact();

        $riskComputation = $this->computeIngredientRisk(
            $today,
            (float) ($weatherImpact['demandMultiplier'] ?? 1.0),
            (float) ($weatherImpact['expiryAcceleration'] ?? 1.0),
            $wasteWhere,
            $wasteParams
        );

        $revenueSummary = $this->connection->fetchAssociative(
            'SELECT
                COUNT(*) AS total_deliveries,
                COALESCE(SUM(d.order_total), 0) AS total_revenue,
                COALESCE(AVG(d.order_total), 0) AS avg_order,
                COALESCE(SUM(CASE WHEN DATE(d.created_at) = CURDATE() THEN d.order_total ELSE 0 END), 0) AS today_revenue
             FROM delivery d
             WHERE '.$revenueWhere,
            $revenueParams
        ) ?: [];

        $revenueTrendRows = $this->connection->fetchAllAssociative(
            'SELECT
                DATE(d.created_at) AS day_key,
                COUNT(*) AS deliveries,
                COALESCE(SUM(d.order_total), 0) AS revenue
             FROM delivery d
             WHERE '.$revenueWhere.'
             GROUP BY DATE(d.created_at)
             ORDER BY revenue '.('revenue_asc' === $revenueSort ? 'ASC' : 'DESC').', day_key ASC
             LIMIT 14',
            $revenueParams
        );

        $revenueLabels = [];
        $revenueValues = [];
        $revenueDeliveryCounts = [];
        foreach ($revenueTrendRows as $row) {
            $dateLabel = (string) ($row['day_key'] ?? '');
            $revenueLabels[] = '' !== $dateLabel ? (new \DateTimeImmutable($dateLabel))->format('d M') : 'Unknown';
            $revenueValues[] = (float) ($row['revenue'] ?? 0);
            $revenueDeliveryCounts[] = (int) ($row['deliveries'] ?? 0);
        }

        $wasteTypeLabels = [];
        $wasteTypeData = [];
        foreach ($wasteByTypeRows as $row) {
            $wasteTypeLabels[] = (string) $row['waste_type'];
            $wasteTypeData[] = (float) $row['total_quantity'];
        }

        $topWastedLabels = [];
        $topWastedData = [];
        foreach ($topWastedRows as $row) {
            $topWastedLabels[] = (string) $row['ingredient'];
            $topWastedData[] = (float) $row['total_quantity'];
        }

        $totalRevenuePeriodLabel = sprintf('Total Revenue (%s): %.2f TND', $rangeLabel, (float) ($revenueSummary['total_revenue'] ?? 0));

        $inventoryItemsRows = $this->connection->fetchAllAssociative(
            'SELECT i.id, i.name, i.quantityInStock, i.unitCost, i.unit, i.minStockLevel, i.expiryDate
             FROM ingredient i
             ORDER BY i.name ASC'
        );

        $shortageByIngredient = [];
        foreach ($riskComputation['shortageAlerts'] as $alert) {
            $shortageByIngredient[(string) ($alert['ingredient'] ?? '')] = (float) ($alert['shortageGap'] ?? 0);
        }

        $inventoryItems = [];
        foreach ($inventoryItemsRows as $row) {
            $name = (string) ($row['name'] ?? 'Unknown');
            $qty = (float) ($row['quantityInStock'] ?? 0);
            $unitCost = (float) ($row['unitCost'] ?? 0);
            $minStock = (float) ($row['minStockLevel'] ?? 0);
            $status = 'healthy';

            $expiryDate = null;
            if (!empty($row['expiryDate'])) {
                try {
                    $expiryDate = new \DateTimeImmutable((string) $row['expiryDate']);
                } catch (\Throwable) {
                    $expiryDate = null;
                }
            }

            if ($qty <= 0) {
                $status = 'out_of_stock';
            } elseif ($expiryDate instanceof \DateTimeImmutable && $expiryDate < $today) {
                $status = 'expired';
            } elseif ($expiryDate instanceof \DateTimeImmutable && $expiryDate <= $today->modify('+3 day')) {
                $status = 'near_expiry';
            } elseif ($qty <= $minStock) {
                $status = 'low_stock';
            }

            $inventoryItems[] = [
                'id' => (int) ($row['id'] ?? 0),
                'ingredient' => $name,
                'quantity' => $qty,
                'unit' => (string) ($row['unit'] ?? 'units'),
                'unitCost' => $unitCost,
                'stockValue' => round($qty * $unitCost, 2),
                'minStockLevel' => $minStock,
                'status' => $status,
                'shortageGap' => (float) ($shortageByIngredient[$name] ?? 0),
            ];
        }

        return [
            'filters' => [
                'wastePeriod' => $wastePeriod,
                'revenuePeriod' => $revenuePeriod,
                'revenueFrom' => $rangeFrom?->format('Y-m-d') ?? '',
                'revenueTo' => $rangeTo?->format('Y-m-d') ?? '',
                'revenueSort' => $revenueSort,
            ],
            'kpis' => [
                'totalIngredients' => $totalIngredients,
                'lowStock' => $lowStock,
                'expiredItems' => $expired,
                'wasteRecords' => (int) ($wasteSummary['waste_records'] ?? 0),
                'inventoryValue' => (float) $inventoryValue,
                'wasteQuantity' => (float) ($wasteSummary['waste_quantity'] ?? 0),
                'wasteCost' => (float) ($wasteSummary['waste_cost'] ?? 0),
                'nearExpiry' => $nearExpiry,
                'outOfStock' => $outOfStock,
                'avgUnitCost' => $avgUnitCost,
                'lowStockRate' => $lowStockRate,
                'totalRevenue' => (float) ($revenueSummary['total_revenue'] ?? 0),
                'totalDeliveries' => (int) ($revenueSummary['total_deliveries'] ?? 0),
                'avgOrder' => (float) ($revenueSummary['avg_order'] ?? 0),
                'todayRevenue' => (float) ($revenueSummary['today_revenue'] ?? 0),
            ],
            'wasteByTypeChart' => [
                'labels' => $wasteTypeLabels,
                'data' => $wasteTypeData,
            ],
            'stockHealthChart' => [
                'labels' => array_keys($stockHealth),
                'data' => array_values($stockHealth),
            ],
            'topWastedChart' => [
                'labels' => $topWastedLabels,
                'data' => $topWastedData,
            ],
            'revenueTrendChart' => [
                'labels' => $revenueLabels,
                'data' => $revenueValues,
                'deliveries' => $revenueDeliveryCounts,
            ],
            'totalRevenuePeriodLabel' => $totalRevenuePeriodLabel,
            'weatherImpact' => $weatherImpact,
            'riskRows' => $riskComputation['riskRows'],
            'shortageAlerts' => $riskComputation['shortageAlerts'],
            'expiryAlerts' => $riskComputation['expiryAlerts'],
            'predictedWaste' => $riskComputation['predictedWaste'],
            'chatContext' => [
                'liveStockInsights' => [
                    'totalIngredients' => $totalIngredients,
                    'lowStock' => $lowStock,
                    'nearExpiry' => $nearExpiry,
                    'outOfStock' => $outOfStock,
                    'expiredItems' => $expired,
                    'inventoryValue' => (float) $inventoryValue,
                    'lowStockRate' => $lowStockRate,
                ],
                'predictedWaste' => $riskComputation['predictedWaste'],
                'potentialStockouts' => array_slice($riskComputation['riskRows'], 0, 8),
                'shortageAlerts' => $riskComputation['shortageAlerts'],
                'expiryAlerts' => $riskComputation['expiryAlerts'],
                'weather' => $weatherImpact,
                'topWasteIngredients' => array_map(static fn (array $row): array => [
                    'ingredient' => (string) $row['ingredient'],
                    'quantity' => (float) $row['total_quantity'],
                    'cost' => (float) $row['total_cost'],
                ], $topWastedRows),
                'inventoryItems' => $inventoryItems,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildChatContext(): array
    {
        $data = $this->buildAnalyticsViewData('Month', 'Month', null, null, 'revenue_desc');

        return $data['chatContext'] ?? [];
    }

    /**
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildPeriodWhereClause(string $field, string $period): array
    {
        return match ($period) {
            'Week' => [
                sprintf('YEAR(%s) = YEAR(CURDATE()) AND WEEK(%s, 3) = WEEK(CURDATE(), 3)', $field, $field),
                [],
            ],
            'Year' => [sprintf('YEAR(%s) = YEAR(CURDATE())', $field), []],
            default => [
                sprintf('YEAR(%s) = YEAR(CURDATE()) AND MONTH(%s) = MONTH(CURDATE())', $field, $field),
                [],
            ],
        };
    }

    /**
     * @return array{0:string,1:array<string,mixed>,2:string,3:?\DateTimeImmutable,4:?\DateTimeImmutable}
     */
    private function buildRevenueWhereClause(string $period, ?string $fromRaw, ?string $toRaw): array
    {
        [$periodSql, $params] = $this->buildPeriodWhereClause('d.created_at', $period);

        $fromDate = $this->parseDate($fromRaw);
        $toDate = $this->parseDate($toRaw);

        $where = $periodSql;

        if ($fromDate instanceof \DateTimeImmutable) {
            $where .= ' AND DATE(d.created_at) >= :revenueFromDate';
            $params['revenueFromDate'] = $fromDate->format('Y-m-d');
        }

        if ($toDate instanceof \DateTimeImmutable) {
            $where .= ' AND DATE(d.created_at) <= :revenueToDate';
            $params['revenueToDate'] = $toDate->format('Y-m-d');
        }

        $periodLabel = match ($period) {
            'Week' => 'ISO Week',
            'Year' => 'Current Year',
            default => 'Current Month',
        };

        if ($fromDate instanceof \DateTimeImmutable || $toDate instanceof \DateTimeImmutable) {
            $periodLabel .= sprintf(
                ' | %s -> %s',
                $fromDate?->format('Y-m-d') ?? '...',
                $toDate?->format('Y-m-d') ?? '...'
            );
        }

        return [$where, $params, $periodLabel, $fromDate, $toDate];
    }

    /**
     * @param array<string, mixed> $wasteParams
     *
     * @return array<string, mixed>
     */
    private function computeIngredientRisk(
        \DateTimeImmutable $today,
        float $demandMultiplier,
        float $expiryAcceleration,
        string $wasteWhere,
        array $wasteParams
    ): array {
        $wasteByIngredientRows = $this->connection->fetchAllAssociative(
            'SELECT w.ingredientId AS ingredient_id, COALESCE(SUM(w.quantityWasted), 0) AS total_waste
             FROM wasterecord w
             WHERE '.$wasteWhere.'
             GROUP BY w.ingredientId',
            $wasteParams
        );

        $wasteByIngredient = [];
        foreach ($wasteByIngredientRows as $row) {
            $wasteByIngredient[(int) $row['ingredient_id']] = (float) $row['total_waste'];
        }

        $ingredients = $this->connection->fetchAllAssociative(
            'SELECT i.id, i.name, i.quantityInStock, i.minStockLevel, i.unitCost, i.unit, i.expiryDate
             FROM ingredient i
             ORDER BY i.name ASC'
        );

        $riskRows = [];
        $predictedWasteQty = 0.0;
        $predictedWasteCost = 0.0;

        foreach ($ingredients as $row) {
            $ingredientId = (int) $row['id'];
            $name = (string) ($row['name'] ?? 'Unknown');
            $stock = (float) ($row['quantityInStock'] ?? 0);
            $min = (float) ($row['minStockLevel'] ?? 0);
            $unitCost = (float) ($row['unitCost'] ?? 0);
            $unit = (string) ($row['unit'] ?? 'units');
            $wasteHistory = (float) ($wasteByIngredient[$ingredientId] ?? 0);

            $baseNeed = max($min * 1.15, $wasteHistory * 1.20, 1.0);
            $predictedNeed = round($baseNeed * $demandMultiplier, 2);
            $shortageGap = round(max(0, $predictedNeed - $stock), 2);

            $expiryDate = null;
            if (!empty($row['expiryDate'])) {
                try {
                    $expiryDate = new \DateTimeImmutable((string) $row['expiryDate']);
                } catch (\Throwable) {
                    $expiryDate = null;
                }
            }

            $weatherAdjustedExpiryDate = null;
            $adjustedDays = null;
            if ($expiryDate instanceof \DateTimeImmutable) {
                $daysToExpiry = (int) $today->diff($expiryDate)->format('%r%a');
                $adjustedDays = (int) floor($daysToExpiry / max($expiryAcceleration, 0.1));
                $weatherAdjustedExpiryDate = $today->modify(sprintf('%+d day', $adjustedDays))->format('Y-m-d');
            }

            $riskScore = 0;
            if ($stock <= 0) {
                $riskScore += 45;
            } elseif ($stock <= $min) {
                $riskScore += 24;
            }

            if ($shortageGap > 0) {
                $riskScore += min(28, (int) round($shortageGap * 3));
            }

            if (null !== $adjustedDays) {
                if ($adjustedDays <= 0) {
                    $riskScore += 35;
                } elseif ($adjustedDays <= 2) {
                    $riskScore += 26;
                } elseif ($adjustedDays <= 5) {
                    $riskScore += 14;
                }
            }

            if ($expiryAcceleration > 1.1) {
                $riskScore += 8;
            }

            $riskLevel = 'Low';
            if ($riskScore >= 75) {
                $riskLevel = 'High';
            } elseif ($riskScore >= 45) {
                $riskLevel = 'Medium';
            }

            $predictedWasteForIngredient = 0.0;
            if ($stock > 0 && null !== $adjustedDays) {
                if ($adjustedDays <= 0) {
                    $predictedWasteForIngredient = $stock;
                } elseif ($adjustedDays <= 2) {
                    $predictedWasteForIngredient = min($stock, round($stock * 0.55 * $expiryAcceleration, 2));
                } elseif ($adjustedDays <= 5) {
                    $predictedWasteForIngredient = min($stock, round($stock * 0.25 * $expiryAcceleration, 2));
                }
            }

            $predictedWasteQty += $predictedWasteForIngredient;
            $predictedWasteCost += $predictedWasteForIngredient * $unitCost;

            $riskRows[] = [
                'ingredient' => $name,
                'unit' => $unit,
                'stock' => $stock,
                'minStock' => $min,
                'predictedNeed' => $predictedNeed,
                'shortageGap' => $shortageGap,
                'riskScore' => $riskScore,
                'riskLevel' => $riskLevel,
                'weatherAdjustedExpiryDate' => $weatherAdjustedExpiryDate ?? 'N/A',
                'predictedWasteQuantity' => round($predictedWasteForIngredient, 2),
            ];
        }

        usort($riskRows, static function (array $a, array $b): int {
            if ($a['riskScore'] === $b['riskScore']) {
                return $b['shortageGap'] <=> $a['shortageGap'];
            }

            return $b['riskScore'] <=> $a['riskScore'];
        });

        $shortageAlerts = array_values(array_map(static fn (array $row): array => [
            'ingredient' => $row['ingredient'],
            'shortageGap' => $row['shortageGap'],
            'predictedNeed' => $row['predictedNeed'],
        ], array_filter($riskRows, static fn (array $row): bool => $row['shortageGap'] > 0)));

        $expiryAlerts = array_values(array_map(static fn (array $row): array => [
            'ingredient' => $row['ingredient'],
            'weatherAdjustedExpiryDate' => $row['weatherAdjustedExpiryDate'],
            'predictedWasteQuantity' => $row['predictedWasteQuantity'],
        ], array_filter(
            $riskRows,
            static fn (array $row): bool => 'N/A' !== $row['weatherAdjustedExpiryDate'] && $row['predictedWasteQuantity'] > 0
        )));

        return [
            'riskRows' => array_slice($riskRows, 0, 12),
            'shortageAlerts' => array_slice($shortageAlerts, 0, 8),
            'expiryAlerts' => array_slice($expiryAlerts, 0, 8),
            'predictedWaste' => [
                'quantity' => round($predictedWasteQty, 2),
                'cost' => round($predictedWasteCost, 2),
            ],
        ];
    }

    private function normalizePeriod(string $period): string
    {
        $normalized = strtolower(trim($period));

        return match ($normalized) {
            'week' => 'Week',
            'year' => 'Year',
            default => 'Month',
        };
    }

    private function normalizeRevenueSort(string $sort): string
    {
        return 'revenue_asc' === strtolower(trim($sort)) ? 'revenue_asc' : 'revenue_desc';
    }

    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        if (null === $value || '' === trim($value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', trim($value));

        return $date instanceof \DateTimeImmutable ? $date : null;
    }
}
