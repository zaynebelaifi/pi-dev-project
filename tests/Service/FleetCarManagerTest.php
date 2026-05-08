<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\FleetCarManager;
use PHPUnit\Framework\TestCase;

/**
 * FleetCarManagerTest — validates all business rules for the FleetCar module.
 *
 * Business rules tested:
 * 1. make, model, license_plate, vehicle_type must not be empty.
 * 2. license_plate must be at least 4 characters.
 * 3. vehicle_type must be one of: MOTORCYCLE, CAR, VAN, TRUCK.
 * 4. fuel_level must be between 0 and 100.
 * 5. status must be one of: AVAILABLE, IN_USE, MAINTENANCE.
 * 6. year must be between 1990 and current year.
 */
class FleetCarManagerTest extends TestCase
{
    private FleetCarManager $manager;

    protected function setUp(): void
    {
        $this->manager = new FleetCarManager();
    }

    // ─────────────────────────────────────────────────────────
    // HAPPY PATH
    // ─────────────────────────────────────────────────────────

    /** Test 1 — valid complete fleet car data */
    public function testValidFleetCar(): void
    {
        $result = $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => 'TRUCK',
            'fuel_level'    => 75,
            'status'        => 'AVAILABLE',
            'year'          => 2020,
        ]);

        $this->assertTrue($result);
    }

    /** Test 2 — valid car with minimal fields */
    public function testValidFleetCarMinimal(): void
    {
        $result = $this->manager->validate([
            'make'          => 'Renault',
            'model'         => 'Clio',
            'license_plate' => 'RS-5678',
            'vehicle_type'  => 'CAR',
        ]);

        $this->assertTrue($result);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 1 — REQUIRED FIELDS
    // ─────────────────────────────────────────────────────────

    /** Test 3 — empty make throws exception */
    public function testFleetCarWithoutMake(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Car make must not be empty.');

        $this->manager->validate([
            'make'          => '',
            'model'         => 'Hilux',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => 'TRUCK',
        ]);
    }

    /** Test 4 — empty model throws exception */
    public function testFleetCarWithoutModel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Car model must not be empty.');

        $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => '',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => 'TRUCK',
        ]);
    }

    /** Test 5 — empty license_plate throws exception */
    public function testFleetCarWithoutLicensePlate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('License plate must not be empty.');

        $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => '',
            'vehicle_type'  => 'TRUCK',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 2 — LICENSE PLATE MIN LENGTH
    // ─────────────────────────────────────────────────────────

    /** Test 6 — license plate too short throws exception */
    public function testFleetCarWithShortLicensePlate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('License plate must be at least');

        $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => 'AB',
            'vehicle_type'  => 'TRUCK',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 3 — VEHICLE TYPE
    // ─────────────────────────────────────────────────────────

    /** Test 7 — invalid vehicle type throws exception */
    public function testFleetCarWithInvalidVehicleType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vehicle type');

        $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => 'SPACESHIP',
        ]);
    }

    /** Test 8 — empty vehicle type throws exception */
    public function testFleetCarWithEmptyVehicleType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vehicle type must not be empty.');

        $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => '',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 4 — FUEL LEVEL
    // ─────────────────────────────────────────────────────────

    /** Test 9 — negative fuel level throws exception */
    public function testFleetCarWithNegativeFuelLevel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fuel level must be between 0 and 100');

        $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => 'CAR',
            'fuel_level'    => -5,
        ]);
    }

    /** Test 10 — fuel level above 100 throws exception */
    public function testFleetCarWithFuelLevelAbove100(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fuel level must be between 0 and 100');

        $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => 'CAR',
            'fuel_level'    => 150,
        ]);
    }

    /** Test 11 — null fuel level is allowed */
    public function testFleetCarWithNullFuelLevelIsValid(): void
    {
        $result = $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => 'CAR',
            'fuel_level'    => null,
        ]);

        $this->assertTrue($result);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 5 — STATUS
    // ─────────────────────────────────────────────────────────

    /** Test 12 — invalid status throws exception */
    public function testFleetCarWithInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status');

        $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => 'CAR',
            'status'        => 'BROKEN',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 6 — YEAR
    // ─────────────────────────────────────────────────────────

    /** Test 13 — year before 1990 throws exception */
    public function testFleetCarWithYearBefore1990(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Year must be between');

        $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => 'CAR',
            'year'          => 1985,
        ]);
    }

    /** Test 14 — future year throws exception */
    public function testFleetCarWithFutureYear(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Year must be between');

        $this->manager->validate([
            'make'          => 'Toyota',
            'model'         => 'Hilux',
            'license_plate' => 'TN-1234',
            'vehicle_type'  => 'CAR',
            'year'          => (int) date('Y') + 5,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // HELPER METHODS
    // ─────────────────────────────────────────────────────────

    /** Test 15 — isAvailable returns true for AVAILABLE status */
    public function testIsAvailableReturnsTrue(): void
    {
        $this->assertTrue($this->manager->isAvailable('AVAILABLE'));
        $this->assertFalse($this->manager->isAvailable('IN_USE'));
        $this->assertFalse($this->manager->isAvailable('MAINTENANCE'));
    }

    /** Test 16 — isFuelCritical returns true below 15% */
    public function testIsFuelCritical(): void
    {
        $this->assertTrue($this->manager->isFuelCritical(10));
        $this->assertTrue($this->manager->isFuelCritical(0));
        $this->assertFalse($this->manager->isFuelCritical(15));
        $this->assertFalse($this->manager->isFuelCritical(80));
        $this->assertFalse($this->manager->isFuelCritical(null));
    }
}
