<?php

declare(strict_types=1);

namespace App\Service;

/**
 * FleetCarManager — enforces business rules for the FleetCar entity.
 *
 * Business rules:
 * 1. make, model, license_plate, vehicle_type must not be empty.
 * 2. license_plate must be at least 4 characters.
 * 3. vehicle_type must be one of: MOTORCYCLE, CAR, VAN, TRUCK.
 * 4. fuel_level (if provided) must be between 0 and 100.
 * 5. status must be one of: AVAILABLE, IN_USE, MAINTENANCE.
 * 6. year (if provided) must be between 1990 and current year.
 */
final class FleetCarManager
{
    /** @var string[] */
    public const ALLOWED_VEHICLE_TYPES = ['MOTORCYCLE', 'CAR', 'VAN', 'TRUCK'];

    /** @var string[] */
    public const ALLOWED_STATUSES = ['AVAILABLE', 'IN_USE', 'MAINTENANCE'];

    public const MIN_LICENSE_PLATE_LENGTH = 4;
    public const MIN_YEAR = 1990;

    /**
     * Validates all FleetCar business rules.
     *
     * @param array{
     *     make?: string,
     *     model?: string,
     *     license_plate?: string,
     *     vehicle_type?: string,
     *     fuel_level?: int|null,
     *     status?: string,
     *     year?: int|null
     * } $data
     *
     * @throws \InvalidArgumentException on any rule violation
     */
    public function validate(array $data): bool
    {
        // Rule 1a: make must not be empty
        if (empty(trim($data['make'] ?? ''))) {
            throw new \InvalidArgumentException('Car make must not be empty.');
        }

        // Rule 1b: model must not be empty
        if (empty(trim($data['model'] ?? ''))) {
            throw new \InvalidArgumentException('Car model must not be empty.');
        }

        // Rule 1c: license_plate must not be empty
        if (empty(trim($data['license_plate'] ?? ''))) {
            throw new \InvalidArgumentException('License plate must not be empty.');
        }

        // Rule 2: license_plate minimum length
        if (strlen(trim($data['license_plate'] ?? '')) < self::MIN_LICENSE_PLATE_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('License plate must be at least %d characters.', self::MIN_LICENSE_PLATE_LENGTH)
            );
        }

        // Rule 1d: vehicle_type must not be empty
        if (empty(trim($data['vehicle_type'] ?? ''))) {
            throw new \InvalidArgumentException('Vehicle type must not be empty.');
        }

        // Rule 3: vehicle_type must be valid
        $type = strtoupper(trim($data['vehicle_type'] ?? ''));
        if (!in_array($type, self::ALLOWED_VEHICLE_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Vehicle type "%s" is invalid. Allowed: %s', $type, implode(', ', self::ALLOWED_VEHICLE_TYPES))
            );
        }

        // Rule 4: fuel_level must be 0–100 if provided
        if (isset($data['fuel_level'])) {
            $fuel = (int) $data['fuel_level'];
            if ($fuel < 0 || $fuel > 100) {
                throw new \InvalidArgumentException(
                    sprintf('Fuel level must be between 0 and 100, got %d.', $fuel)
                );
            }
        }

        // Rule 5: status must be valid
        if (isset($data['status'])) {
            $status = strtoupper(trim($data['status']));
            if (!in_array($status, self::ALLOWED_STATUSES, true)) {
                throw new \InvalidArgumentException(
                    sprintf('Status "%s" is invalid. Allowed: %s', $status, implode(', ', self::ALLOWED_STATUSES))
                );
            }
        }

        // Rule 6: year must be between MIN_YEAR and current year
        if (isset($data['year'])) {
            $year = (int) $data['year'];
            $currentYear = (int) date('Y');
            if ($year < self::MIN_YEAR || $year > $currentYear) {
                throw new \InvalidArgumentException(
                    sprintf('Year must be between %d and %d, got %d.', self::MIN_YEAR, $currentYear, $year)
                );
            }
        }

        return true;
    }

    /**
     * Returns true if car is available for assignment.
     */
    public function isAvailable(string $status): bool
    {
        return strtoupper(trim($status)) === 'AVAILABLE';
    }

    /**
     * Returns true if fuel level is critically low (below 15%).
     */
    public function isFuelCritical(?int $fuelLevel): bool
    {
        if ($fuelLevel === null) {
            return false;
        }
        return $fuelLevel < 15;
    }
}
