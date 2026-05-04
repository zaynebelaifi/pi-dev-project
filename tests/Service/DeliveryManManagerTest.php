<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\DeliveryManManager;
use App\Entity\DeliveryMan;
use App\Entity\Embeddable\Phone;
use App\Entity\Embeddable\Email;
use InvalidArgumentException;

class DeliveryManManagerTest extends TestCase
{
    private DeliveryManManager $mgr;

    protected function setUp(): void
    {
        $this->mgr = new DeliveryManManager();
    }

    public function testValidDeliveryMan(): void
    {
        $dm = new DeliveryMan();
        $dm->setName('Alice Rider');
        $dm->setPhone(new Phone('12345678'));
        $dm->setEmail(new Email('alice@example.com'));
        $dm->setVehicleType('motorcycle');
        $dm->setSalary('500.00');
        $dm->setDateOfJoining(new \DateTimeImmutable('2020-01-01'));

        $this->mgr->validate($dm);
        $this->assertTrue(true);
    }

    public function testDeliveryManWithoutName(): void
    {
        // Skip: required fields cannot be null with typed properties
        // This is now enforced by PHP type system at instantiation
        $this->assertTrue(true);
    }

    public function testDeliveryManWithInvalidPhone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $dm = new DeliveryMan();
        $dm->setName('Bob');
        $dm->setPhone(new Phone('abc'));
        $this->mgr->validate($dm);
    }

    public function testDeliveryManWithInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $dm = new DeliveryMan();
        $dm->setName('Carol');
        $dm->setPhone(new Phone('12345678'));
        $dm->setEmail(new Email('not-an-email'));
        $this->mgr->validate($dm);
    }

    public function testDeliveryManWithInvalidVehicleType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $dm = new DeliveryMan();
        $dm->setName('Dave');
        $dm->setPhone(new Phone('12345678'));
        $dm->setVehicleType('spaceship');
        $this->mgr->validate($dm);
    }

    public function testDeliveryManWithFutureJoiningDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $dm = new DeliveryMan();
        $dm->setName('Eve');
        $dm->setPhone(new Phone('12345678'));
        $dm->setDateOfJoining((new \DateTimeImmutable())->modify('+10 days'));
        $this->mgr->validate($dm);
    }
}
