<?php

namespace App\Tests\Service;

use App\Service\FoodDonationItemManager;
use App\Entity\FoodDonationItem;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class FoodDonationItemManagerTest extends TestCase
{
    private FoodDonationItemManager $manager;

    protected function setUp(): void
    {
        $this->manager = new FoodDonationItemManager();
    }

    /**
     * Test 1: Valid FoodDonationItem with positive quantity passes validation
     */
    public function testValidItemPassesValidation(): void
    {
        $item = new FoodDonationItem();
        $item->setItemId(1);
        $item->setQuantity(10);
        $item->setDonationEventId(1);

        // Should not throw exception
        $this->manager->validate($item);
        $this->assertTrue(true);
    }

    /**
     * Test 2: Item with zero quantity throws exception
     */
    public function testItemWithZeroQuantityThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item quantity must be greater than 0');
        
        $item = new FoodDonationItem();
        $item->setItemId(1);
        $item->setQuantity(0);
        $item->setDonationEventId(1);

        $this->manager->validate($item);
    }

    /**
     * Test 3: Item with negative quantity throws exception
     */
    public function testItemWithNegativeQuantityThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item quantity must be greater than 0');
        
        $item = new FoodDonationItem();
        $item->setItemId(1);
        $item->setQuantity(-5);
        $item->setDonationEventId(1);

        $this->manager->validate($item);
    }

    /**
     * Test 4: Item without donation event ID throws exception
     */
    public function testItemWithoutDonationEventIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item must be assigned to a donation event');
        
        $item = new FoodDonationItem();
        $item->setItemId(1);
        $item->setQuantity(10);
        $item->setDonationEventId(null);

        $this->manager->validate($item);
    }

    /**
     * Test 5: Item without item ID throws exception
     */
    public function testItemWithoutItemIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item ID must be assigned');
        
        $item = new FoodDonationItem();
        $item->setItemId(null);
        $item->setQuantity(10);
        $item->setDonationEventId(1);

        $this->manager->validate($item);
    }

    /**
     * Test 6: Item with negative item ID throws exception
     */
    public function testItemWithNegativeItemIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item ID must be greater than 0');
        
        $item = new FoodDonationItem();
        $item->setItemId(-1);
        $item->setQuantity(10);
        $item->setDonationEventId(1);

        $this->manager->validate($item);
    }
}


















