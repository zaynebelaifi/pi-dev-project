<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\AIPriorityService;

final class AIPriorityServiceTest extends TestCase
{
    public function testComputeScorePrefersCloserAndHigherRating(): void
    {
        $refLat = 35.032;
        $refLon = 9.470;

        // Driver A: high rating, close to ref
        $ratingA = 4.8;
        $latA = 35.033; // ~0.11 km away
        $lonA = 9.471;

        // Driver B: lower rating, farther away
        $ratingB = 3.5;
        $latB = 35.100; // several km away
        $lonB = 9.500;

        $scoreA = AIPriorityService::computeScoreFromParams($ratingA, $latA, $lonA, $refLat, $refLon);
        $scoreB = AIPriorityService::computeScoreFromParams($ratingB, $latB, $lonB, $refLat, $refLon);

        $this->assertIsFloat($scoreA);
        $this->assertIsFloat($scoreB);
        $this->assertGreaterThan($scoreB, $scoreA, 'Expected closer, higher-rated driver to score higher');
    }

    public function testMissingDriverLocationIsPenalized(): void
    {
        $refLat = 35.032;
        $refLon = 9.470;

        $rating = 5.0;
        $scoreWithLoc = AIPriorityService::computeScoreFromParams($rating, 35.032, 9.470, $refLat, $refLon);
        $scoreWithoutLoc = AIPriorityService::computeScoreFromParams($rating, null, null, $refLat, $refLon);

        $this->assertGreaterThan($scoreWithoutLoc, $scoreWithLoc);
    }
}
