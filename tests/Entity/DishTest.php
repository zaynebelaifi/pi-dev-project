<?php

namespace App\Tests\Entity;

use App\Entity\Dish;
use App\Entity\Menu;
use PHPUnit\Framework\TestCase;

final class DishTest extends TestCase
{
    public function testMenuAssignmentAndBasePriceCastsToFloat(): void
    {
        $dish = new Dish();
        $menu = new Menu();

        $dish->setMenu($menu);
        $dish->setBasePrice(12.5);

        $this->assertSame($menu, $dish->getMenu());
        $this->assertSame(12.5, $dish->getBasePrice());
    }
}
