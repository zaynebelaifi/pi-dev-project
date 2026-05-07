<?php

namespace App\Tests\Service;

use App\Service\DistanceCalculator;
use PHPUnit\Framework\TestCase;

final class DistanceCalculatorTest extends TestCase
{
    public function testHaversineDistanceReturnsNearZeroForSamePoint(): void
    {
        $service = new DistanceCalculator();

        $distance = $service->haversineDistance(36.8065, 10.1815, 36.8065, 10.1815);

        self::assertLessThan(0.001, $distance);
    }

    public function testHaversineDistanceIsPositiveForDifferentPoints(): void
    {
        $service = new DistanceCalculator();

        $distance = $service->haversineDistance(36.8065, 10.1815, 36.8189, 10.1658);

        self::assertGreaterThan(0.0, $distance);
    }
}
