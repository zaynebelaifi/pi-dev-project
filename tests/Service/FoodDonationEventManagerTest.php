<?php

namespace App\Tests\Service;

use App\Service\FoodDonationEventManager;
use App\Entity\FoodDonationEvent;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use DateTime;

class FoodDonationEventManagerTest extends TestCase
{
    private FoodDonationEventManager $manager;

    protected function setUp(): void
    {
        $this->manager = new FoodDonationEventManager();
    }

    /**
     * Test 1: Valid FoodDonationEvent passes validation
     */
    public function testValidEventPassesValidation(): void
    {
        $event = new FoodDonationEvent();
        $event->setCharityName('Red Crescent');
        $event->setTotalQuantity(50);
        $event->setEventDate(new DateTime('+1 day'));
        $event->setStatus('SCHEDULED');

        // Should not throw exception
        $this->manager->validate($event);
        $this->assertTrue(true);
    }

    /**
     * Test 2: Event without charity name throws exception
     */
    public function testEventWithoutCharityNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Charity name must be at least 3 characters');
        
        $event = new FoodDonationEvent();
        $event->setCharityName('');
        $event->setTotalQuantity(50);
        $event->setEventDate(new DateTime('+1 day'));
        $event->setStatus('SCHEDULED');

        $this->manager->validate($event);
    }

    /**
     * Test 3: Event with short charity name throws exception
     */
    public function testEventWithShortCharityNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Charity name must be at least 3 characters');
        
        $event = new FoodDonationEvent();
        $event->setCharityName('RC');
        $event->setTotalQuantity(50);
        $event->setEventDate(new DateTime('+1 day'));
        $event->setStatus('SCHEDULED');

        $this->manager->validate($event);
    }

    /**
     * Test 4: Event with zero quantity throws exception
     */
    public function testEventWithZeroQuantityThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total quantity must be greater than 0');
        
        $event = new FoodDonationEvent();
        $event->setCharityName('Red Crescent');
        $event->setTotalQuantity(0);
        $event->setEventDate(new DateTime('+1 day'));
        $event->setStatus('SCHEDULED');

        $this->manager->validate($event);
    }

    /**
     * Test 5: Event with negative quantity throws exception
     */
    public function testEventWithNegativeQuantityThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total quantity must be greater than 0');
        
        $event = new FoodDonationEvent();
        $event->setCharityName('Red Crescent');
        $event->setTotalQuantity(-10);
        $event->setEventDate(new DateTime('+1 day'));
        $event->setStatus('SCHEDULED');

        $this->manager->validate($event);
    }

    /**
     * Test 6: Event with past date throws exception
     */
    public function testEventWithPastDateThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Event date must be in the future');
        
        $event = new FoodDonationEvent();
        $event->setCharityName('Red Crescent');
        $event->setTotalQuantity(50);
        $event->setEventDate(new DateTime('-1 day'));
        $event->setStatus('SCHEDULED');

        $this->manager->validate($event);
    }

    /**
     * Test 7: Event with invalid status throws exception
     */
    public function testEventWithInvalidStatusThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status');
        
        $event = new FoodDonationEvent();
        $event->setCharityName('Red Crescent');
        $event->setTotalQuantity(50);
        $event->setEventDate(new DateTime('+1 day'));
        $event->setStatus('INVALID_STATUS');

        $this->manager->validate($event);
    }
}