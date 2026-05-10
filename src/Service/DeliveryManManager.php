<?php

namespace App\Service;

use App\Entity\DeliveryMan;
use App\Entity\Embeddable\Phone;
use App\Entity\Embeddable\Email;
use InvalidArgumentException;

class DeliveryManManager
{
    /**
     * Validate business rules for DeliveryMan entity.
     *
     * @throws InvalidArgumentException
     */
    public function validate(DeliveryMan $dm): void
    {
        // name is guaranteed to be string (non-nullable)
        $name = $dm->getName();
        if (mb_strlen(trim($name)) < 2) {
            throw new InvalidArgumentException('name must be at least 2 characters.');
        }

        // phone is a Phone Embeddable (non-nullable)
        $phone = $dm->getPhone();
        if (!preg_match('/^\d{8}$/', $phone->getNumber())) {
            throw new InvalidArgumentException('phone must be exactly 8 digits.');
        }

        // email is an Email Embeddable (non-nullable, may be empty)
        $email = $dm->getEmail();
        $emailAddress = $email->getAddress();
        if ($emailAddress !== null && $emailAddress !== '' && !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('email must be a valid email address when provided.');
        }

        // vehicle_type if provided must be one of allowed values
        $vt = $dm->getVehicle_type();
        if ($vt !== null && $vt !== '') {
            $allowed = ['motorcycle', 'car', 'bicycle', 'scooter', 'van', 'truck', 'other'];
            if (!in_array($vt, $allowed, true)) {
                throw new InvalidArgumentException('vehicle_type is invalid.');
            }
        }

        // salary if provided must be numeric and > 0
        $salary = $dm->getSalary();
        if ($salary !== null && $salary !== '' && !is_numeric($salary)) {
            throw new InvalidArgumentException('salary must be numeric when provided.');
        }
        if ($salary !== null && (float)$salary <= 0.0) {
            throw new InvalidArgumentException('salary must be greater than zero when provided.');
        }

        // date_of_joining cannot be in the future
        $doj = $dm->getDate_of_joining();
        if ($doj !== null) {
            $now = new \DateTimeImmutable();
            if ($doj > $now) {
                throw new InvalidArgumentException('date_of_joining cannot be in the future.');
            }
        }
    }
}
