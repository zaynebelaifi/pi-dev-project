<?php

namespace App\Tests\Entity;

use App\Entity\Dish;
use App\Entity\Menu;
use PHPUnit\Framework\TestCase;

final class MenuTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $menu = new Menu();
        $created = new \DateTimeImmutable('2026-05-02 10:00:00');
        $updated = new \DateTimeImmutable('2026-05-02 12:00:00');

        $menu
            ->setTitle('Brunch Menu')
            ->setDescription('Morning favorites')
            ->setIsActive(true)
            ->setCreated_at($created)
            ->setUpdated_at($updated);

        $this->assertSame('Brunch Menu', $menu->getTitle());
        $this->assertSame('Morning favorites', $menu->getDescription());
        $this->assertTrue($menu->isIsActive());
        $this->assertSame($created, $menu->getCreated_at());
        $this->assertSame($updated, $menu->getUpdated_at());
    }

    public function testDishCollectionAvoidsDuplicates(): void
    {
        $menu = new Menu();
        $dish = new Dish();

        $menu->addDish($dish);
        $menu->addDish($dish);

        $this->assertCount(1, $menu->getDishs());
    }

    public function testRemoveDish(): void
    {
        $menu = new Menu();
        $dish = new Dish();

        $menu->addDish($dish);
        $menu->removeDish($dish);

        $this->assertCount(0, $menu->getDishs());
    }
}
