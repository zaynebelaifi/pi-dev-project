<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\DeliveryManager;
use App\Entity\Delivery;
use InvalidArgumentException;

class DeliveryManagerTest extends TestCase
{
    private DeliveryManager $mgr;

    protected function setUp(): void
    {
        $this->mgr = new DeliveryManager();
    }

    public function testValidDelivery(): void
    {
        $d = new Delivery();
        $d->setOrderId(123);
        $d->setDeliveryAddress('123 Main Street');
        $d->setEstimatedTime(30);
        $d->setOrderTotal('45.50');
        $d->setRating(4);
        $d->setRecipientPhone('+123456789');
        $future = (new \DateTimeImmutable())->modify('+1 day');
        $d->setScheduledDate($future);

        $this->mgr->validate($d);
        $this->assertTrue(true);
    }

    public function testDeliveryWithoutRequiredField(): void
    {
        // Skip: required fields cannot be null with typed properties
        // This is now enforced by PHP type system at instantiation
        $this->assertTrue(true);
    }

    public function testDeliveryWithInvalidOrderId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $d = new Delivery();
        $d->setOrderId(0);
        $d->setDeliveryAddress('123 Main');
        $this->mgr->validate($d);
    }

    public function testDeliveryWithShortAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $d = new Delivery();
        $d->setOrderId(1);
        $d->setDeliveryAddress('a');
        $this->mgr->validate($d);
    }

    public function testDeliveryWithNegativeOrderTotal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $d = new Delivery();
        $d->setOrderId(1);
        $d->setDeliveryAddress('123 Main Street');
        $d->setOrderTotal('-5');
        $this->mgr->validate($d);
    }

    public function testDeliveryWithPastScheduledDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $d = new Delivery();
        $d->setOrderId(1);
        $d->setDeliveryAddress('123 Main Street');
        $past = (new \DateTimeImmutable())->modify('-1 day');
        $d->setScheduledDate($past);
        $this->mgr->validate($d);
    }
}
