<?php

namespace App\Service;

use App\Entity\RestaurantTable;
use App\Service\OpenWeatherMapService;
use App\Repository\ReservationRepository;
use App\Repository\RestaurantTableRepository;

final class SmartTableMatcher
{
    private const ACCESSIBLE_TABLE_IDS = [1, 2, 3, 4];
    private const QUIET_TABLE_IDS = [7, 8, 9, 10, 11, 12];
    private const GROUP_FRIENDLY_TABLE_IDS = [5, 6, 11, 12, 13, 14, 15];

    public function __construct(
        private RestaurantTableRepository $tableRepository,
        private ReservationRepository $reservationRepository,
        private OpenWeatherMapService $openWeatherMapService,
    ) {
    }

    public function recommend(
        \DateTimeInterface $date,
        \DateTimeInterface $time,
        int $guests,
        string $occasion = '',
        bool $mobilityNeeds = false,
        ?int $clientId = null,
    ): ?array {
        $ranked = $this->recommendRanked($date, $time, $guests, $occasion, $mobilityNeeds, $clientId);

        return $ranked[0] ?? null;
    }

    public function recommendRanked(
        \DateTimeInterface $date,
        \DateTimeInterface $time,
        int $guests,
        string $occasion = '',
        bool $mobilityNeeds = false,
        ?int $clientId = null,
        int $limit = 3,
    ): array {
        if ($guests < 1) {
            return [];
        }

        $bookedTableIds = $this->reservationRepository->findBookedTableIdsAt($date, $time);
        $clientPreferences = ($clientId !== null && $clientId > 0)
            ? $this->reservationRepository->getClientTablePreferenceCounts($clientId)
            : [];

        $occasion = strtolower(trim($occasion));
        $tables = $this->tableRepository->findBy(['status' => 'AVAILABLE']);
        $weather = $this->openWeatherMapService->getCurrentByCity('Tunis');

        $ranked = [];

        foreach ($tables as $table) {
            if (!$table instanceof RestaurantTable) {
                continue;
            }

            $tableId = (int) $table->getTableId();
            $capacity = (int) $table->getCapacity();

            if ($tableId === 0 || $capacity < $guests) {
                continue;
            }

            if (in_array($tableId, $bookedTableIds, true)) {
                continue;
            }

            $scored = $this->scoreTable(
                table: $table,
                guests: $guests,
                occasion: $occasion,
                mobilityNeeds: $mobilityNeeds,
                clientPreferences: $clientPreferences,
                weather: $weather,
            );

            $scored['confidence'] = $this->confidenceLabel((int) $scored['score']);
            $ranked[] = $scored;
        }

        usort($ranked, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        if ($limit > 0 && count($ranked) > $limit) {
            return array_slice($ranked, 0, $limit);
        }

        return $ranked;
    }

    private function confidenceLabel(int $score): string
    {
        if ($score >= 120) {
            return 'High';
        }

        if ($score >= 90) {
            return 'Medium';
        }

        return 'Low';
    }

    private function scoreTable(
        RestaurantTable $table,
        int $guests,
        string $occasion,
        bool $mobilityNeeds,
        array $clientPreferences,
        ?array $weather,
    ): array {
        $tableId = (int) $table->getTableId();
        $capacity = (int) $table->getCapacity();

        $reasons = [];
        $score = 100;

        // Keep large tables free by preferring the tightest valid fit.
        $waste = max(0, $capacity - $guests);
        $fitPenalty = $waste * 8;
        $score -= $fitPenalty;
        $reasons[] = sprintf('Capacity fit: %d-seat table for %d guest(s).', $capacity, $guests);

        if ($mobilityNeeds) {
            if (in_array($tableId, self::ACCESSIBLE_TABLE_IDS, true)) {
                $score += 35;
                $reasons[] = 'Mobility-friendly position matched.';
            } else {
                $score -= 50;
                $reasons[] = 'Reduced score: table is less mobility-friendly.';
            }
        }

        if (isset($clientPreferences[$tableId])) {
            $uses = $clientPreferences[$tableId];
            $bonus = min(30, $uses * 6);
            $score += $bonus;
            $reasons[] = sprintf('Past preference boost: used %d time(s) before.', $uses);
        }

        if ($occasion !== '') {
            $occasionBonus = $this->occasionBonus($occasion, $tableId, $capacity, $guests);
            $score += $occasionBonus['score'];
            if ($occasionBonus['reason'] !== '') {
                $reasons[] = $occasionBonus['reason'];
            }
        }

        if ($weather !== null) {
            if (($weather['is_rainy'] ?? false) === true) {
                if (in_array($tableId, self::ACCESSIBLE_TABLE_IDS, true)) {
                    $score += 14;
                    $reasons[] = 'Weather-aware boost: rainy conditions favor easier-access tables.';
                } else {
                    $score -= 8;
                    $reasons[] = 'Weather-aware penalty: non-priority table during rainy conditions.';
                }
            }

            if (($weather['is_hot'] ?? false) === true && in_array($tableId, self::QUIET_TABLE_IDS, true)) {
                $score += 6;
                $reasons[] = 'Weather-aware boost: calmer seating zone preferred during hot weather.';
            }
        }

        return [
            'table' => $table,
            'score' => $score,
            'reasons' => $reasons,
            'weather' => $weather,
        ];
    }

    private function occasionBonus(string $occasion, int $tableId, int $capacity, int $guests): array
    {
        if (in_array($occasion, ['date', 'romantic', 'anniversary'], true)) {
            if ($capacity <= 4 && in_array($tableId, self::QUIET_TABLE_IDS, true)) {
                return ['score' => 22, 'reason' => 'Occasion match: quieter cozy table for a romantic setting.'];
            }

            if ($capacity <= 4) {
                return ['score' => 12, 'reason' => 'Occasion match: cozy table sizing for a romantic setting.'];
            }
        }

        if (in_array($occasion, ['birthday', 'celebration', 'party'], true)) {
            if (in_array($tableId, self::GROUP_FRIENDLY_TABLE_IDS, true)) {
                return ['score' => 20, 'reason' => 'Occasion match: celebration-friendly table zone.'];
            }

            if ($capacity >= max(6, $guests)) {
                return ['score' => 10, 'reason' => 'Occasion match: extra space for celebration comfort.'];
            }
        }

        if (in_array($occasion, ['business', 'meeting'], true)) {
            if (in_array($tableId, self::QUIET_TABLE_IDS, true)) {
                return ['score' => 16, 'reason' => 'Occasion match: quieter table for conversation-focused meeting.'];
            }

            return ['score' => 8, 'reason' => 'Occasion match: balanced table profile for business meeting.'];
        }

        return ['score' => 0, 'reason' => ''];
    }
}
