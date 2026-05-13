<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace demo public menu data with real BIG 4 food and drink items';
    }

    public function up(Schema $schema): void
    {
        $menus = [
            1 => ['Coffee & Espresso', 'Barista-made classics, house signatures, and premium roasted coffee.'],
            2 => ['Breakfast & Brunch', 'Morning plates with eggs, breads, pancakes, and fresh lounge favorites.'],
            3 => ['Signature Plates', 'Chef-inspired mains for lunch, dinner, and polished lounge dining.'],
            4 => ['Fresh Salads', 'Light, colorful bowls with crisp vegetables, grains, and bright dressings.'],
            5 => ['Desserts & Pastries', 'Cakes, pastries, chocolate, and sweet treats for coffee pairings.'],
            6 => ['Cold Drinks & Smoothies', 'Iced coffees, juices, smoothies, and refreshing house drinks.'],
            7 => ['Gourmet Burgers', 'Stacked burgers with premium sauces, toasted buns, and rich sides.'],
            8 => ['Pasta & Risotto', 'Creamy pasta, seafood risotto, and warm comfort plates.'],
            9 => ['Tunisian Specials', 'Local flavors with harissa, grilled meats, seafood, and fresh herbs.'],
            10 => ['Sharing Bites', 'Small plates, fries, sliders, and snacks made for the table.'],
            11 => ['Healthy Bowls', 'Protein bowls, grains, fruit, and lighter balanced meals.'],
            12 => ['Chef Specials', 'Premium seasonal picks and house favorites from the BIG 4 kitchen.'],
        ];

        foreach ($menus as $id => [$title, $description]) {
            $this->addSql(
                'INSERT INTO menu (id, title, description, isActive, created_at, updated_at)
                 VALUES (:id, :title, :description, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), isActive = 1, updated_at = NOW()',
                ['id' => $id, 'title' => $title, 'description' => $description]
            );
        }

        $dishes = [
            1 => [1, 'Single Origin Espresso', 'Rich specialty espresso with dark chocolate notes and a clean crema finish.', 5.5, 'https://images.unsplash.com/photo-1510707577719-ae7c14805e3a?auto=format&fit=crop&w=1000&q=80'],
            2 => [1, 'Caramel Macchiato', 'Velvety steamed milk, espresso, vanilla, and a warm caramel drizzle.', 8.5, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=1000&q=80'],
            3 => [1, 'Pistachio Latte', 'Creamy latte blended with pistachio syrup and a toasted nut finish.', 9.5, 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1000&q=80'],
            4 => [1, 'Mocha Noisette', 'Espresso, cocoa, hazelnut, and silky milk for a sweet coffee treat.', 9.0, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=1000&q=80'],
            5 => [2, 'Avocado Toast & Poached Eggs', 'Sourdough toast with avocado, poached eggs, herbs, and lemon olive oil.', 18.0, 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=1000&q=80'],
            6 => [2, 'Smoked Salmon Benedict', 'English muffin with smoked salmon, poached eggs, and hollandaise sauce.', 26.0, 'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?auto=format&fit=crop&w=1000&q=80'],
            7 => [2, 'French Brioche Pancakes', 'Fluffy brioche pancakes with berries, maple syrup, and vanilla cream.', 17.0, 'https://images.unsplash.com/photo-1528207776546-365bb710ee93?auto=format&fit=crop&w=1000&q=80'],
            8 => [2, 'Mediterranean Omelette', 'Three-egg omelette with feta, tomato, olives, herbs, and toasted bread.', 15.0, 'https://images.unsplash.com/photo-1510693206972-df098062cb71?auto=format&fit=crop&w=1000&q=80'],
            9 => [3, 'Grilled Chicken Supreme', 'Juicy grilled chicken breast with pepper sauce, vegetables, and potato puree.', 32.0, 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?auto=format&fit=crop&w=1000&q=80'],
            10 => [3, 'Beef Tenderloin Pepper Steak', 'Tender beef fillet with creamy pepper sauce and golden fries.', 45.0, 'https://images.unsplash.com/photo-1558030006-450675393462?auto=format&fit=crop&w=1000&q=80'],
            11 => [3, 'Honey Mustard Chicken', 'Pan-seared chicken with honey mustard glaze and seasonal vegetables.', 29.0, 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?auto=format&fit=crop&w=1000&q=80'],
            12 => [3, 'Sea Bass Lemon Butter', 'Seared sea bass with lemon butter, herbs, and roasted potatoes.', 42.0, 'https://images.unsplash.com/photo-1559847844-5315695dadae?auto=format&fit=crop&w=1000&q=80'],
            13 => [4, 'Chicken Caesar Salad', 'Romaine lettuce, grilled chicken, parmesan, croutons, and Caesar dressing.', 23.0, 'https://images.unsplash.com/photo-1550304943-4f24f54ddde9?auto=format&fit=crop&w=1000&q=80'],
            14 => [4, 'Quinoa Avocado Bowl', 'Quinoa, avocado, cucumber, tomato, chickpeas, and lemon tahini dressing.', 21.0, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1000&q=80'],
            15 => [4, 'Greek Feta Salad', 'Tomato, cucumber, olives, feta, oregano, and extra virgin olive oil.', 17.0, 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=1000&q=80'],
            16 => [4, 'Spicy Tuna Salad', 'Tuna, greens, corn, boiled egg, chili dressing, and crispy onions.', 24.0, 'https://images.unsplash.com/photo-1547496502-affa22d38842?auto=format&fit=crop&w=1000&q=80'],
            17 => [5, 'Chocolate Fondant', 'Warm chocolate cake with a molten center and vanilla ice cream.', 16.0, 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=1000&q=80'],
            18 => [5, 'Pistachio Tiramisu', 'Coffee-soaked biscuits, mascarpone cream, pistachio, and cocoa dust.', 18.0, 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?auto=format&fit=crop&w=1000&q=80'],
            19 => [5, 'Lotus Cheesecake', 'Creamy cheesecake with Lotus biscuit crust and caramel topping.', 17.0, 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?auto=format&fit=crop&w=1000&q=80'],
            20 => [5, 'French Macaron Selection', 'Assorted macarons with pistachio, raspberry, vanilla, and chocolate.', 14.0, 'https://images.unsplash.com/photo-1558326567-98ae2405596b?auto=format&fit=crop&w=1000&q=80'],
            21 => [6, 'Iced Spanish Latte', 'Cold espresso, milk, and condensed milk over ice for a sweet finish.', 10.0, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=1000&q=80'],
            22 => [6, 'Mango Passion Smoothie', 'Mango, passion fruit, yogurt, and ice blended until bright and creamy.', 13.0, 'https://images.unsplash.com/photo-1505252585461-04db1eb84625?auto=format&fit=crop&w=1000&q=80'],
            23 => [6, 'Fresh Orange Juice', 'Freshly squeezed orange juice served chilled with no added sugar.', 8.0, 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=1000&q=80'],
            24 => [6, 'Strawberry Mojito Mocktail', 'Strawberry, mint, lime, soda, and crushed ice for a fresh sparkle.', 12.0, 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=1000&q=80'],
            25 => [7, 'BIG 4 Classic Beef Burger', 'Beef patty, cheddar, lettuce, tomato, pickles, and house burger sauce.', 27.0, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=1000&q=80'],
            26 => [7, 'Crispy Chicken Burger', 'Crispy chicken breast with slaw, spicy mayo, lettuce, and fries.', 24.0, 'https://images.unsplash.com/photo-1606755962773-d324e0a13086?auto=format&fit=crop&w=1000&q=80'],
            27 => [7, 'Truffle Mushroom Burger', 'Beef patty, mushrooms, Swiss cheese, truffle mayo, and caramelized onion.', 31.0, 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=1000&q=80'],
            28 => [7, 'Vegetarian Halloumi Burger', 'Grilled halloumi, roasted peppers, tomato, lettuce, and basil sauce.', 23.0, 'https://images.unsplash.com/photo-1520072959219-c595dc870360?auto=format&fit=crop&w=1000&q=80'],
            29 => [8, 'Creamy Chicken Alfredo', 'Fettuccine pasta with chicken, parmesan, mushrooms, and cream sauce.', 28.0, 'https://images.unsplash.com/photo-1645112411341-6c4fd023714a?auto=format&fit=crop&w=1000&q=80'],
            30 => [8, 'Seafood Risotto', 'Creamy arborio rice with shrimp, calamari, herbs, and parmesan.', 36.0, 'https://images.unsplash.com/photo-1633964913295-ceb43826e7c8?auto=format&fit=crop&w=1000&q=80'],
            31 => [8, 'Spicy Arrabbiata Pasta', 'Penne pasta with tomato, chili, garlic, basil, and parmesan.', 22.0, 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=1000&q=80'],
            32 => [8, 'Pesto Burrata Linguine', 'Linguine with basil pesto, burrata, cherry tomato, and toasted pine nuts.', 29.0, 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=1000&q=80'],
            33 => [9, 'Tunisian Ojja Merguez', 'Spicy tomato and pepper stew with eggs, merguez, garlic, and harissa.', 24.0, 'https://images.unsplash.com/photo-1604909052743-94e838986d24?auto=format&fit=crop&w=1000&q=80'],
            34 => [9, 'Grilled Harissa Chicken', 'Chicken marinated with harissa, herbs, lemon, and served with couscous.', 30.0, 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?auto=format&fit=crop&w=1000&q=80'],
            35 => [9, 'Seafood Couscous', 'Steamed couscous with fish, vegetables, chickpeas, and Tunisian spices.', 34.0, 'https://images.unsplash.com/photo-1511690656952-34342bb7c2f2?auto=format&fit=crop&w=1000&q=80'],
            36 => [9, 'Brik au Thon', 'Crispy pastry filled with tuna, egg, capers, parsley, and lemon.', 13.0, 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=1000&q=80'],
            37 => [10, 'Loaded Cheese Fries', 'Golden fries topped with cheddar sauce, crispy onions, and spicy mayo.', 16.0, 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=1000&q=80'],
            38 => [10, 'Chicken Bao Buns', 'Soft bao buns with crispy chicken, pickled cucumber, and sweet chili sauce.', 22.0, 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=1000&q=80'],
            39 => [10, 'Mini Beef Sliders', 'Three mini beef burgers with cheddar, pickles, and house sauce.', 25.0, 'https://images.unsplash.com/photo-1550317138-10000687a72b?auto=format&fit=crop&w=1000&q=80'],
            40 => [10, 'Crispy Mozzarella Sticks', 'Mozzarella sticks served with marinara sauce and basil oil.', 15.0, 'https://images.unsplash.com/photo-1548340748-6d2b7d7da280?auto=format&fit=crop&w=1000&q=80'],
            41 => [11, 'Salmon Protein Bowl', 'Grilled salmon, quinoa, avocado, greens, sesame, and citrus dressing.', 38.0, 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1000&q=80'],
            42 => [11, 'Chicken Fitness Bowl', 'Grilled chicken, brown rice, broccoli, carrots, and yogurt herb sauce.', 27.0, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=1000&q=80'],
            43 => [11, 'Vegan Buddha Bowl', 'Chickpeas, sweet potato, avocado, quinoa, greens, and tahini sauce.', 24.0, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1000&q=80'],
            44 => [11, 'Acai Fruit Bowl', 'Acai blend with banana, berries, granola, coconut, and honey.', 19.0, 'https://images.unsplash.com/photo-1494597564530-871f2b93ac55?auto=format&fit=crop&w=1000&q=80'],
            45 => [12, 'Chef Ribeye Steak', 'Premium ribeye steak with herb butter, fries, and grilled vegetables.', 58.0, 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?auto=format&fit=crop&w=1000&q=80'],
            46 => [12, 'Lobster Linguine', 'Linguine pasta with lobster, tomato, garlic, chili, and fresh basil.', 52.0, 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=1000&q=80'],
            47 => [12, 'Black Truffle Pizza', 'Thin crust pizza with mozzarella, mushrooms, truffle cream, and arugula.', 39.0, 'https://images.unsplash.com/photo-1594007654729-407eedc4be65?auto=format&fit=crop&w=1000&q=80'],
            48 => [12, 'BIG 4 Signature Platter', 'A sharing platter with grilled chicken, beef skewers, fries, dips, and salad.', 49.0, 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1000&q=80'],
        ];

        foreach ($dishes as $id => [$menuId, $name, $description, $price, $imageUrl]) {
            $this->addSql(
                'INSERT INTO dish (id, menu_id, name, description, base_price, available, stock_quantity, image_url, created_at, updated_at)
                 VALUES (:id, :menu_id, :name, :description, :base_price, 1, 50, :image_url, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE menu_id = VALUES(menu_id), name = VALUES(name), description = VALUES(description),
                    base_price = VALUES(base_price), available = 1, stock_quantity = VALUES(stock_quantity),
                    image_url = VALUES(image_url), updated_at = NOW()',
                [
                    'id' => $id,
                    'menu_id' => $menuId,
                    'name' => $name,
                    'description' => $description,
                    'base_price' => $price,
                    'image_url' => $imageUrl,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        for ($menuId = 1; $menuId <= 12; ++$menuId) {
            $this->addSql(
                'UPDATE menu SET title = :title, description = :description, isActive = 1, updated_at = NOW() WHERE id = :id',
                [
                    'id' => $menuId,
                    'title' => sprintf('Demo Menu %02d', $menuId),
                    'description' => sprintf('Validation demo category %d with premium coffee lounge items.', $menuId),
                ]
            );
        }

        for ($dishId = 1; $dishId <= 48; ++$dishId) {
            $menuId = (int) ceil($dishId / 4);
            $dishNumber = (($dishId - 1) % 4) + 1;
            $this->addSql(
                'UPDATE dish SET menu_id = :menu_id, name = :name, description = :description, base_price = :base_price,
                    available = 1, stock_quantity = 50, image_url = NULL, updated_at = NOW() WHERE id = :id',
                [
                    'id' => $dishId,
                    'menu_id' => $menuId,
                    'name' => sprintf('Demo Dish %02d-%02d', $menuId, $dishNumber),
                    'description' => 'A polished signature plate crafted for validation demos and premium presentation.',
                    'base_price' => 14 + $menuId + ($dishNumber * 2),
                ]
            );
        }
    }
}
