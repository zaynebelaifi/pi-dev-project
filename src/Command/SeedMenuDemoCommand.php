<?php

namespace App\Command;

use App\Entity\Dish;
use App\Entity\DishIngredient;
use App\Entity\Ingredient;
use App\Entity\Menu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-menu-demo', description: 'Create demo menus, dishes, and recipe ingredients for pagination and homepage validation.')]
final class SeedMenuDemoCommand extends Command
{
    private const MENU_BLUEPRINTS = [
        1 => [
            'title' => 'Signature Breakfast',
            'description' => 'Morning favourites with rich coffee-house flavours and polished presentation.',
            'dishes' => ['Pistachio French Toast', 'Truffle Omelette Croissant', 'Sunrise Avocado Tartine', 'Big 4 Breakfast Board'],
        ],
        2 => [
            'title' => 'Artisan Coffee',
            'description' => 'Barista-made classics and indulgent coffee creations for all-day sipping.',
            'dishes' => ['Velvet Cappuccino', 'Salted Caramel Latte', 'Spanish Iced Latte', 'Mocha Royale'],
        ],
        3 => [
            'title' => 'Fresh Salads',
            'description' => 'Light bowls and vibrant plates built around fresh produce and balanced textures.',
            'dishes' => ['Grilled Chicken Caesar', 'Burrata Garden Salad', 'Quinoa Citrus Bowl', 'Smoked Salmon Greens'],
        ],
        4 => [
            'title' => 'Gourmet Burgers',
            'description' => 'Comfort-forward burgers with premium fillings and lounge-style sides.',
            'dishes' => ['Angus Lounge Burger', 'Crispy Chicken Burger', 'Truffle Mushroom Burger', 'Halloumi Veggie Burger'],
        ],
        5 => [
            'title' => 'Pasta & Risotto',
            'description' => 'Creamy, savoury mains designed for relaxed lunches and elegant dinners.',
            'dishes' => ['Truffle Mushroom Pasta', 'Creamy Chicken Alfredo', 'Seafood Arrabbiata', 'Parmesan Risotto'],
        ],
        6 => [
            'title' => 'Main Courses',
            'description' => 'Signature plates for guests looking for a refined full-course experience.',
            'dishes' => ['Herb-Grilled Salmon', 'Pepper Steak Frites', 'Chicken Supreme', 'Rosemary Lamb Chops'],
        ],
        7 => [
            'title' => 'Sharing Plates',
            'description' => 'Warm starters and social plates made for groups, dates, and late evenings.',
            'dishes' => ['Mediterranean Mezze Board', 'Crispy Calamari', 'Loaded Truffle Fries', 'Golden Chicken Tenders'],
        ],
        8 => [
            'title' => 'Desserts',
            'description' => 'Sweet finishes and pastry-shop favourites with a polished lounge touch.',
            'dishes' => ['Chocolate Lava Cake', 'Tiramisu Glass', 'Pistachio Cheesecake', 'Vanilla Creme Brulee'],
        ],
        9 => [
            'title' => 'Mocktails & Coolers',
            'description' => 'Refreshing zero-proof drinks with bright fruit notes and evening energy.',
            'dishes' => ['Passion Mojito', 'Berry Sparkler', 'Citrus Cooler', 'Peach Iced Tea'],
        ],
        10 => [
            'title' => 'Signature Drinks',
            'description' => 'House beverages with playful flavours, layered textures, and café flair.',
            'dishes' => ['Rose Latte', 'Salted Caramel Frappe', 'Matcha Cloud', 'Classic Iced Americano'],
        ],
        11 => [
            'title' => 'Late Night Bites',
            'description' => 'Satisfying sandwiches and quick plates for relaxed evening dining.',
            'dishes' => ['Turkey Club Sandwich', 'Grilled Chicken Wrap', 'Tuna Melt Panini', 'Margherita Flatbread'],
        ],
        12 => [
            'title' => 'Bakery Selection',
            'description' => 'Freshly baked favourites that pair naturally with coffee and dessert service.',
            'dishes' => ['Butter Croissant', 'Almond Danish', 'Pain au Chocolat', 'Cinnamon Roll'],
        ],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('menus', null, InputOption::VALUE_REQUIRED, 'How many demo menus to create.', '12')
            ->addOption('dishes-per-menu', null, InputOption::VALUE_REQUIRED, 'How many demo dishes to create per menu.', '4');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $menuCount = max(1, (int) $input->getOption('menus'));
        $dishesPerMenu = max(1, (int) $input->getOption('dishes-per-menu'));

        $ingredientCatalog = $this->seedIngredients();
        $createdMenus = 0;
        $createdDishes = 0;

        for ($menuNumber = 1; $menuNumber <= $menuCount; ++$menuNumber) {
            $menuBlueprint = $this->getMenuBlueprint($menuNumber);
            $legacyMenuTitle = sprintf('Demo Menu %02d', $menuNumber);
            $menuTitle = $menuBlueprint['title'];

            $menu = $this->entityManager->getRepository(Menu::class)->findOneBy(['title' => $menuTitle]);
            if (!$menu instanceof Menu) {
                $menu = $this->entityManager->getRepository(Menu::class)->findOneBy(['title' => $legacyMenuTitle]);
            }

            if (!$menu instanceof Menu) {
                $menu = (new Menu())
                    ->setCreated_at(new \DateTimeImmutable());
            }

            $menu
                ->setTitle($menuTitle)
                ->setDescription($menuBlueprint['description'])
                ->setIsActive(true)
                ->setUpdated_at(new \DateTimeImmutable());

            $this->entityManager->persist($menu);
            if ($menu->getId() === null) {
                ++$createdMenus;
            }

            for ($dishNumber = 1; $dishNumber <= $dishesPerMenu; ++$dishNumber) {
                $dishName = $this->getDishName($menuNumber, $dishNumber);
                $legacyDishName = sprintf('Demo Dish %02d-%02d', $menuNumber, $dishNumber);
                $dish = $this->entityManager->getRepository(Dish::class)->findOneBy(['name' => $dishName]);

                if (!$dish instanceof Dish) {
                    $dish = $this->entityManager->getRepository(Dish::class)->findOneBy(['name' => $legacyDishName]);
                }

                if (!$dish instanceof Dish) {
                    $dish = (new Dish())
                        ->setCreated_at(new \DateTimeImmutable());
                    ++$createdDishes;
                }

                $dish
                    ->setMenu($menu)
                    ->setName($dishName)
                    ->setDescription($this->buildDishDescription($menuNumber, $dishNumber))
                    ->setBasePrice(12.5 + $menuNumber + ($dishNumber * 1.75))
                    ->setAvailable(true)
                    ->setStockQuantity(20 + ($dishNumber * 5))
                    ->setImageUrl($this->pickImageUrl($dishNumber))
                    ->setUpdated_at(new \DateTimeImmutable());

                $this->entityManager->persist($dish);

                foreach ($dish->getRecipeLines() as $existingRecipeLine) {
                    $this->entityManager->remove($existingRecipeLine);
                }

                foreach ($this->pickRecipeIngredients($ingredientCatalog, $menuNumber, $dishNumber) as $recipeRow) {
                    $line = (new DishIngredient())
                        ->setDish($dish)
                        ->setIngredient($recipeRow['ingredient'])
                        ->setQuantityRequired($recipeRow['qty']);

                    $this->entityManager->persist($line);
                }
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Demo seed complete. Created %d menu(s) and %d dish(es). Admin pagination and homepage AI widget now have visible data.',
            $createdMenus,
            $createdDishes
        ));
        $io->text('Open /admin/menu, /admin/dish, and the homepage to validate the demo.');

        return Command::SUCCESS;
    }

    /**
     * @return array<string, Ingredient>
     */
    private function seedIngredients(): array
    {
        $rows = [
            ['Arabica Beans', 120, 'g', 20, 0.12],
            ['Milk', 80, 'cl', 15, 0.05],
            ['Chocolate', 70, 'g', 10, 0.09],
            ['Caramel Syrup', 60, 'cl', 10, 0.07],
            ['Croissant Dough', 90, 'unit', 12, 0.18],
            ['Chicken Fillet', 100, 'g', 20, 0.22],
            ['Salad Mix', 75, 'g', 12, 0.06],
            ['Pasta', 150, 'g', 25, 0.08],
            ['Parmesan', 65, 'g', 10, 0.14],
            ['Vanilla Cream', 55, 'g', 8, 0.11],
        ];

        $catalog = [];
        $expiry = new \DateTimeImmutable('+60 days');

        foreach ($rows as [$name, $stock, $unit, $min, $cost]) {
            $ingredient = $this->entityManager->getRepository(Ingredient::class)->findOneBy(['name' => $name]);

            if (!$ingredient instanceof Ingredient) {
                $ingredient = (new Ingredient())
                    ->setName($name)
                    ->setQuantityInStock((float) $stock)
                    ->setUnit($unit)
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setMinStockLevel((float) $min)
                    ->setUnitCost((float) $cost)
                    ->setExpiryDate($expiry);

                $this->entityManager->persist($ingredient);
            }

            $catalog[$name] = $ingredient;
        }

        return $catalog;
    }

    private function buildDishDescription(int $menuNumber, int $dishNumber): string
    {
        $descriptions = [
            'A house favourite prepared with polished presentation and rich lounge-style flavour.',
            'Balanced textures and premium ingredients make this an easy pick for all-day dining.',
            'Fresh, comforting, and carefully plated for a refined café experience.',
            'A signature recipe built to feel generous, memorable, and ready for service.',
        ];

        return $descriptions[($menuNumber + $dishNumber) % count($descriptions)];
    }

    /**
     * @return array{title: string, description: string, dishes: string[]}
     */
    private function getMenuBlueprint(int $menuNumber): array
    {
        return self::MENU_BLUEPRINTS[(($menuNumber - 1) % count(self::MENU_BLUEPRINTS)) + 1];
    }

    private function getDishName(int $menuNumber, int $dishNumber): string
    {
        $menuBlueprint = $this->getMenuBlueprint($menuNumber);
        $dishes = $menuBlueprint['dishes'];

        return $dishes[(($dishNumber - 1) % count($dishes))];
    }

    private function pickImageUrl(int $dishNumber): string
    {
        $images = [
            'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1521017432531-fbd92d768814?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=80',
        ];

        return $images[($dishNumber - 1) % count($images)];
    }

    /**
     * @param array<string, Ingredient> $catalog
     * @return array<int, array{ingredient: Ingredient, qty: float}>
     */
    private function pickRecipeIngredients(array $catalog, int $menuNumber, int $dishNumber): array
    {
        $sets = [
            [
                ['ingredient' => $catalog['Arabica Beans'], 'qty' => 8.0],
                ['ingredient' => $catalog['Milk'], 'qty' => 12.0],
                ['ingredient' => $catalog['Caramel Syrup'], 'qty' => 3.0],
            ],
            [
                ['ingredient' => $catalog['Croissant Dough'], 'qty' => 1.0],
                ['ingredient' => $catalog['Chocolate'], 'qty' => 6.0],
                ['ingredient' => $catalog['Vanilla Cream'], 'qty' => 4.0],
            ],
            [
                ['ingredient' => $catalog['Chicken Fillet'], 'qty' => 14.0],
                ['ingredient' => $catalog['Salad Mix'], 'qty' => 8.0],
                ['ingredient' => $catalog['Parmesan'], 'qty' => 2.0],
            ],
            [
                ['ingredient' => $catalog['Pasta'], 'qty' => 10.0],
                ['ingredient' => $catalog['Parmesan'], 'qty' => 3.0],
                ['ingredient' => $catalog['Milk'], 'qty' => 5.0],
            ],
        ];

        return $sets[($menuNumber + $dishNumber) % count($sets)];
    }
}
