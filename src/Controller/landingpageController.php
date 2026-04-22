<?php

namespace App\Controller;

use App\Utils\WeatherImpactService;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\MenuRepository;
use App\Repository\RestaurantTableRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class landingpageController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
        private MenuRepository $menuRepository,
        private RestaurantTableRepository $tableRepository,
        private Connection $connection,
        private HttpClientInterface $httpClient,
        private PaginatorInterface $paginator,
        private WeatherImpactService $weatherImpactService,
    ) {}

    #[Route('/', name: 'app_home')]
    public function home(Request $request): Response
    {
        $session  = $this->requestStack->getSession();
        $userRole = $session->get('user_role');

        if ($userRole === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->renderLandingPage($request);
    }

    #[Route('/landingpage', name: 'app_landingpage')]
    public function index(Request $request): Response
    {
        return $this->renderLandingPage($request);
    }

    private function renderLandingPage(Request $request): Response
    {
        $menuSections = $this->buildMenuSections();

        $selectedMenuId = $request->query->getInt('menu');
        if ($selectedMenuId <= 0 && $menuSections !== []) {
            $selectedMenuId = (int) ($menuSections[0]['menu']['id'] ?? 0);
        }

        $selectedSection = null;
        foreach ($menuSections as $section) {
            if ((int) ($section['menu']['id'] ?? 0) === $selectedMenuId) {
                $selectedSection = $section;
                break;
            }
        }

        if ($selectedSection === null && $menuSections !== []) {
            $selectedSection = $menuSections[0];
            $selectedMenuId = (int) ($selectedSection['menu']['id'] ?? 0);
        }

        $dishes = is_array($selectedSection['dishes'] ?? null) ? $selectedSection['dishes'] : [];
        $paginatedDishes = $this->paginator->paginate(
            $dishes,
            max(1, $request->query->getInt('page', 1)),
            3,
            ['pageParameterName' => 'page']
        );

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections'    => $menuSections,
            'selectedSection' => $selectedSection,
            'selectedMenuId'  => $selectedMenuId,
            'paginatedDishes' => $paginatedDishes,
            'availableTables' => $this->tableRepository->findBy(['status' => 'AVAILABLE']),
        ]);
    }

    #[Route('/landingpage/mood-recommendations', name: 'app_landingpage_mood_recommendations', methods: ['POST'])]
    public function moodRecommendations(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid request payload.',
            ], 400);
        }

        $moodInput = trim((string) ($payload['mood'] ?? ''));
        $userMessage = trim((string) ($payload['message'] ?? ''));
        $combinedInput = trim($moodInput.' '.$userMessage);

        if ($combinedInput === '') {
            return $this->json([
                'success' => false,
                'message' => 'Please share your mood so I can suggest dishes.',
            ], 400);
        }

        $detectedMood = $this->detectMood($combinedInput);

        $modelRecommendations = $this->requestAiFoodRecommendations($payload, $combinedInput, $detectedMood);
        if ($modelRecommendations !== null) {
            return $this->json([
                'success' => true,
                'detectedMood' => $detectedMood,
                'assistantMessage' => 'I used our AI recommender to find dishes that match your mood and preferences.',
                'recommendations' => $modelRecommendations,
                'source' => 'ai-model',
            ]);
        }

        $profile = $this->getMoodProfile($detectedMood);
        $dishes = $this->getAvailableDishesWithIngredients();

        if ($dishes === []) {
            return $this->json([
                'success' => false,
                'message' => 'No available dishes found right now.',
            ]);
        }

        $searchTerms = $this->extractSearchTerms($combinedInput);
        $scored = [];

        foreach ($dishes as $dish) {
            $searchable = mb_strtolower(implode(' ', [
                $dish['name'] ?? '',
                $dish['description'] ?? '',
                implode(' ', $dish['ingredients'] ?? []),
            ]));

            $score = 0;
            $why = [];

            foreach ($profile['prefer'] as $keyword) {
                if (str_contains($searchable, $keyword)) {
                    $score += 3;
                    $why[] = $keyword;
                }
            }

            foreach ($profile['avoid'] as $keyword) {
                if (str_contains($searchable, $keyword)) {
                    $score -= 2;
                }
            }

            foreach ($searchTerms as $term) {
                if (str_contains($searchable, $term)) {
                    $score += 1;
                }
            }

            if (($dish['description'] ?? '') !== '') {
                $score += 1;
            }
            if (!empty($dish['ingredients'])) {
                $score += 1;
            }

            $dish['score'] = $score;
            $dish['reasonKeywords'] = array_values(array_unique(array_slice($why, 0, 3)));
            $scored[] = $dish;
        }

        usort($scored, static function (array $left, array $right): int {
            return ($right['score'] <=> $left['score']) ?: (($left['id'] ?? 0) <=> ($right['id'] ?? 0));
        });

        $recommendations = array_slice($scored, 0, 3);
        $formatted = array_map(function (array $dish) use ($detectedMood): array {
            $ingredients = $dish['ingredients'] ?? [];
            $reasonBits = [];

            if (!empty($dish['reasonKeywords'])) {
                $reasonBits[] = 'matches your '.$detectedMood.' mood with '.implode(', ', $dish['reasonKeywords']);
            }

            if ($ingredients !== []) {
                $reasonBits[] = 'contains '.implode(', ', array_slice($ingredients, 0, 5));
            }

            if ($reasonBits === []) {
                $reasonBits[] = 'fits your current mood and menu preferences';
            }

            return [
                'id' => $dish['id'],
                'name' => $dish['name'],
                'description' => $dish['description'] ?: 'Chef recommendation from our menu.',
                'ingredients' => $ingredients,
                'price' => isset($dish['basePrice']) ? number_format((float) $dish['basePrice'], 2, '.', '').' TND' : null,
                'reason' => ucfirst(implode('; ', $reasonBits)).'.',
            ];
        }, $recommendations);

        return $this->json([
            'success' => true,
            'detectedMood' => $detectedMood,
            'assistantMessage' => $profile['message'],
            'recommendations' => $formatted,
            'source' => 'local-fallback',
        ]);
    }

    #[Route('/api/dish/fallback-image', name: 'app_api_dish_fallback_image', methods: ['GET'])]
    public function dishFallbackImage(Request $request): JsonResponse
    {
        $dishName = trim((string) $request->query->get('name', ''));

        return $this->json([
            'success' => true,
            'dishName' => $dishName,
            'imageUrl' => $this->getDishFallbackImageUrl($dishName),
        ]);
    }

    #[Route('/api/dishes/{id}', name: 'app_api_dish_details', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function dishDetails(int $id): JsonResponse
    {
        $dish = $this->connection->fetchAssociative(
            'SELECT d.id, d.name, d.description, d.base_price, d.stock_quantity, d.image_url, d.available, d.created_at, d.updated_at,
                    m.id AS menu_id, m.title AS menu_title
             FROM dish d
             LEFT JOIN menu m ON m.id = d.menu_id
             WHERE d.id = :id
             LIMIT 1',
            ['id' => $id]
        );

        if (!$dish) {
            return $this->json([
                'success' => false,
                'message' => 'Dish not found.',
            ], 404);
        }

        $ingredientRows = $this->connection->fetchAllAssociative(
            'SELECT i.id, i.name, i.unit, di.quantity_required
             FROM dish_ingredient di
             INNER JOIN ingredient i ON i.id = di.ingredient_id
             WHERE di.dish_id = :dishId
             ORDER BY i.name ASC',
            ['dishId' => $id]
        );

        $ingredients = array_map(static function (array $row): array {
            return [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'name' => (string) ($row['name'] ?? ''),
                'quantityRequired' => isset($row['quantity_required']) ? (float) $row['quantity_required'] : null,
                'unit' => $row['unit'] ?? null,
            ];
        }, $ingredientRows);

        $dishName = (string) ($dish['name'] ?? 'Dish');
        $imageUrl = trim((string) ($dish['image_url'] ?? ''));
        $fallbackUsed = false;
        if ($imageUrl === '') {
            $imageUrl = $this->getDishFallbackImageUrl($dishName);
            $fallbackUsed = true;
        }

        $dishColumns = [];
        try {
            $dishColumns = array_map(
                static fn ($column): string => $column->getName(),
                $this->connection->createSchemaManager()->listTableColumns('dish')
            );
        } catch (\Throwable) {
            $dishColumns = [];
        }

        $nutrition = [
            'calories' => in_array('calories', $dishColumns, true) && isset($dish['calories']) ? (int) $dish['calories'] : null,
            'proteinLevel' => in_array('protein_level', $dishColumns, true) && isset($dish['protein_level']) ? (int) $dish['protein_level'] : null,
            'carbLevel' => in_array('carb_level', $dishColumns, true) && isset($dish['carb_level']) ? (int) $dish['carb_level'] : null,
            'fatLevel' => in_array('fat_level', $dishColumns, true) && isset($dish['fat_level']) ? (int) $dish['fat_level'] : null,
        ];

        $prepTimeMinutes = in_array('prep_time', $dishColumns, true) && isset($dish['prep_time'])
            ? (int) $dish['prep_time']
            : null;

        return $this->json([
            'success' => true,
            'dish' => [
                'id' => (int) $dish['id'],
                'name' => $dishName,
                'description' => (string) ($dish['description'] ?? ''),
                'menu' => [
                    'id' => isset($dish['menu_id']) ? (int) $dish['menu_id'] : null,
                    'title' => $dish['menu_title'] ?? null,
                ],
                'basePrice' => isset($dish['base_price']) ? (float) $dish['base_price'] : null,
                'stockQuantity' => isset($dish['stock_quantity']) ? (int) $dish['stock_quantity'] : null,
                'availability' => [
                    'isAvailable' => array_key_exists('available', $dish) ? (bool) $dish['available'] : null,
                    'reason' => null,
                    'nextAvailableAt' => null,
                ],
                'image' => [
                    'url' => $imageUrl,
                    'isFallback' => $fallbackUsed,
                ],
                'ingredients' => $ingredients,
                'allergens' => $this->inferAllergensFromIngredients($ingredients),
                'nutrition' => $nutrition,
                'prepTimeMinutes' => $prepTimeMinutes,
                'ratingSummary' => [
                    'average' => null,
                    'count' => 0,
                ],
                'createdAt' => isset($dish['created_at']) ? (string) $dish['created_at'] : null,
                'updatedAt' => isset($dish['updated_at']) ? (string) $dish['updated_at'] : null,
            ],
        ]);
    }

    #[Route('/api/weather/order-suggestions', name: 'app_api_weather_order_suggestions', methods: ['GET'])]
    public function weatherOrderSuggestions(): JsonResponse
    {
        $weather = $this->weatherImpactService->getWeatherImpact();
        $temperature = isset($weather['temperature']) && is_numeric($weather['temperature'])
            ? (float) $weather['temperature']
            : null;

        $target = $this->resolveWeatherTarget($temperature, (string) ($weather['statusClass'] ?? 'wx-unknown'));
        $dishes = $this->getAvailableDishesWithIngredients();

        if ($dishes === []) {
            return $this->json([
                'success' => false,
                'message' => 'No available dishes found for weather suggestions.',
                'weather' => $weather,
                'target' => $target,
                'suggestions' => [],
            ], 404);
        }

        $scored = [];
        foreach ($dishes as $dish) {
            $searchable = mb_strtolower(trim(implode(' ', [
                (string) ($dish['name'] ?? ''),
                (string) ($dish['description'] ?? ''),
                implode(' ', $dish['ingredients'] ?? []),
            ])));

            $score = 1;

            foreach ($target['prefer'] as $keyword) {
                if (str_contains($searchable, $keyword)) {
                    $score += 3;
                }
            }

            foreach ($target['avoid'] as $keyword) {
                if (str_contains($searchable, $keyword)) {
                    $score -= 2;
                }
            }

            if (($dish['description'] ?? '') !== '') {
                $score += 1;
            }

            if (($dish['ingredients'] ?? []) !== []) {
                $score += 1;
            }

            $dish['score'] = $score;
            $scored[] = $dish;
        }

        usort($scored, static function (array $left, array $right): int {
            return ($right['score'] <=> $left['score']) ?: (($left['id'] ?? 0) <=> ($right['id'] ?? 0));
        });

        $picks = array_slice($scored, 0, 5);
        $suggestions = array_map(function (array $dish) use ($target): array {
            $imageUrl = trim((string) ($dish['imageUrl'] ?? ''));
            if ($imageUrl === '') {
                $imageUrl = $this->getDishFallbackImageUrl((string) ($dish['name'] ?? 'Dish'));
            }

            return [
                'id' => (int) ($dish['id'] ?? 0),
                'name' => (string) ($dish['name'] ?? 'Dish'),
                'description' => (string) ($dish['description'] ?? ''),
                'price' => isset($dish['basePrice']) ? number_format((float) $dish['basePrice'], 2, '.', '').' TND' : null,
                'imageUrl' => $imageUrl,
                'reason' => $target['message'],
            ];
        }, $picks);

        return $this->json([
            'success' => true,
            'weather' => [
                'temperature' => $temperature,
                'statusLabel' => $weather['statusLabel'] ?? null,
                'statusClass' => $weather['statusClass'] ?? null,
                'source' => $weather['source'] ?? null,
                'isFallback' => $weather['isFallback'] ?? null,
            ],
            'target' => [
                'type' => $target['type'],
                'message' => $target['message'],
            ],
            'suggestions' => $suggestions,
        ]);
    }

    private function requestAiFoodRecommendations(array $payload, string $combinedInput, string $detectedMood): ?array
    {
        $apiUrl = trim((string) ($_ENV['AI_FOOD_API_URL'] ?? $_SERVER['AI_FOOD_API_URL'] ?? 'http://127.0.0.1:8001/recommend'));
        if ($apiUrl === '') {
            return null;
        }

        $timeout = (float) ($_ENV['AI_FOOD_API_TIMEOUT'] ?? $_SERVER['AI_FOOD_API_TIMEOUT'] ?? 4);
        if ($timeout <= 0) {
            $timeout = 4;
        }

        $requestBody = $this->buildAiModelPayload($payload, $combinedInput, $detectedMood);

        try {
            $response = $this->httpClient->request('POST', $apiUrl, [
                'json' => $requestBody,
                'timeout' => $timeout,
            ]);

            if ($response->getStatusCode() >= 400) {
                return null;
            }

            $data = $response->toArray(false);
        } catch (\Throwable) {
            return null;
        }

        $results = $data['results'] ?? null;
        if (!is_array($results) || $results === []) {
            return [];
        }

        $recommendations = [];
        foreach ($results as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? 'Recommended dish'));
            $description = trim((string) ($item['description'] ?? ''));
            $priceValue = $item['base_price'] ?? null;
            $price = is_numeric($priceValue) ? number_format((float) $priceValue, 2, '.', '').' TND' : null;
            $score = isset($item['score']) && is_numeric($item['score']) ? (float) $item['score'] : null;
            $category = trim((string) ($item['category'] ?? ''));

            $reasonParts = [];
            if ($score !== null) {
                $reasonParts[] = 'model confidence '.number_format($score * 100, 1, '.', '').'%';
            }
            if ($category !== '') {
                $reasonParts[] = 'matches your preferred category ('.$category.')';
            }
            if ($reasonParts === []) {
                $reasonParts[] = 'fits your current preference profile';
            }

            $recommendations[] = [
                'id' => (int) ($item['dish_id'] ?? 0),
                'name' => $name !== '' ? $name : 'Recommended dish',
                'description' => $description !== '' ? $description : 'AI recommendation based on your preferences.',
                'ingredients' => [],
                'price' => $price,
                'reason' => ucfirst(implode('; ', $reasonParts)).'.',
            ];
        }

        return $recommendations;
    }

    private function buildAiModelPayload(array $payload, string $combinedInput, string $detectedMood): array
    {
        $category = mb_strtolower(trim((string) ($payload['category'] ?? '')));
        $budget = mb_strtolower(trim((string) ($payload['budget_level'] ?? '')));

        $moodTags = $this->buildMoodTags($combinedInput, $detectedMood);
        $isVegetarian = filter_var($payload['is_vegetarian'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $isVegan = filter_var($payload['is_vegan'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $isGlutenFree = filter_var($payload['is_gluten_free'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        $defaultsByMood = [
            'happy' => ['healthy' => 3, 'comfort' => 4, 'energy' => 4, 'spicy' => 2, 'heaviness' => 3],
            'sad' => ['healthy' => 2, 'comfort' => 5, 'energy' => 2, 'spicy' => 1, 'heaviness' => 4],
            'stressed' => ['healthy' => 5, 'comfort' => 3, 'energy' => 2, 'spicy' => 1, 'heaviness' => 2],
            'tired' => ['healthy' => 3, 'comfort' => 3, 'energy' => 5, 'spicy' => 2, 'heaviness' => 3],
            'romantic' => ['healthy' => 3, 'comfort' => 4, 'energy' => 3, 'spicy' => 2, 'heaviness' => 3],
            'angry' => ['healthy' => 4, 'comfort' => 3, 'energy' => 2, 'spicy' => 1, 'heaviness' => 2],
            'neutral' => ['healthy' => 3, 'comfort' => 3, 'energy' => 3, 'spicy' => 2, 'heaviness' => 3],
        ];

        $picked = $defaultsByMood[$detectedMood] ?? $defaultsByMood['neutral'];

        return [
            'base_price' => $budget === 'high' ? 26 : ($budget === 'low' ? 12 : 19),
            'stock_quantity' => 10,
            'calories' => $picked['heaviness'] >= 4 ? 780 : 520,
            'prep_time' => 18,
            'spicy' => $picked['spicy'],
            'sweet' => str_contains(mb_strtolower($combinedInput), 'sweet') ? 4 : 2,
            'salty' => 3,
            'healthy' => $picked['healthy'],
            'comfort' => $picked['comfort'],
            'heaviness' => $picked['heaviness'],
            'protein_level' => 3,
            'carb_level' => 3,
            'fat_level' => 3,
            'energy' => $picked['energy'],
            'is_vegetarian' => $isVegetarian,
            'is_vegan' => $isVegan,
            'is_gluten_free' => $isGlutenFree,
            'category' => $category !== '' ? $category : 'pasta',
            'budget_level' => in_array($budget, ['low', 'medium', 'high'], true) ? $budget : 'medium',
            'mood_tags' => $moodTags,
        ];
    }

    private function buildMoodTags(string $combinedInput, string $detectedMood): array
    {
        $tagsByMood = [
            'happy' => ['energetic', 'comfort'],
            'sad' => ['comfort', 'calm'],
            'stressed' => ['calm', 'light'],
            'tired' => ['energetic', 'focused'],
            'romantic' => ['comfort'],
            'angry' => ['calm', 'fresh'],
            'neutral' => ['comfort'],
        ];

        $tags = $tagsByMood[$detectedMood] ?? ['comfort'];
        $normalized = mb_strtolower($combinedInput);

        $keywordToTag = [
            'healthy' => 'healthy',
            'fresh' => 'fresh',
            'light' => 'light',
            'calm' => 'calm',
            'hungry' => 'hungry',
            'focus' => 'focused',
            'energy' => 'energetic',
            'adventur' => 'adventurous',
        ];

        foreach ($keywordToTag as $keyword => $tag) {
            if (str_contains($normalized, $keyword)) {
                $tags[] = $tag;
            }
        }

        return array_values(array_unique($tags));
    }

    private function detectMood(string $input): string
    {
        $normalized = mb_strtolower($input);

        $dictionary = [
            'happy' => ['happy', 'excited', 'great', 'good', 'celebrate', 'amazing', 'joyful'],
            'sad' => ['sad', 'down', 'blue', 'upset', 'lonely', 'depressed'],
            'stressed' => ['stressed', 'anxious', 'overwhelmed', 'nervous', 'pressure'],
            'tired' => ['tired', 'sleepy', 'exhausted', 'drained', 'low energy', 'fatigued'],
            'romantic' => ['romantic', 'date', 'love', 'cozy', 'special night'],
            'angry' => ['angry', 'mad', 'frustrated', 'irritated'],
        ];

        foreach ($dictionary as $mood => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return $mood;
                }
            }
        }

        return 'neutral';
    }

    private function getMoodProfile(string $mood): array
    {
        $profiles = [
            'happy' => [
                'prefer' => ['chocolate', 'berry', 'vanilla', 'caramel', 'cheese', 'grilled', 'fresh'],
                'avoid' => ['bitter'],
                'message' => 'Love that energy. Here are upbeat picks to match your mood.',
            ],
            'sad' => [
                'prefer' => ['creamy', 'soup', 'chocolate', 'warm', 'pasta', 'cheese', 'comfort'],
                'avoid' => ['cold'],
                'message' => 'I picked comforting dishes to help you feel better.',
            ],
            'stressed' => [
                'prefer' => ['tea', 'herbal', 'salad', 'avocado', 'salmon', 'light', 'fresh'],
                'avoid' => ['spicy', 'fried'],
                'message' => 'These lighter options can feel calmer and easier when stress is high.',
            ],
            'tired' => [
                'prefer' => ['coffee', 'banana', 'protein', 'egg', 'nut', 'chicken', 'energ'],
                'avoid' => ['heavy cream'],
                'message' => 'I selected energizing dishes with ingredients that can help you recharge.',
            ],
            'romantic' => [
                'prefer' => ['truffle', 'chocolate', 'strawberry', 'cream', 'seafood', 'fine'],
                'avoid' => [],
                'message' => 'Great mood for a special meal. Here are elegant choices.',
            ],
            'angry' => [
                'prefer' => ['crunch', 'protein', 'fresh', 'citrus', 'tea'],
                'avoid' => ['extra spicy'],
                'message' => 'I chose balanced dishes that are satisfying without being too intense.',
            ],
            'neutral' => [
                'prefer' => ['fresh', 'chef', 'signature', 'classic', 'grilled'],
                'avoid' => [],
                'message' => 'Here are some of our best all-around dishes for your current vibe.',
            ],
        ];

        return $profiles[$mood] ?? $profiles['neutral'];
    }

    private function extractSearchTerms(string $input): array
    {
        $normalized = mb_strtolower($input);
        $parts = preg_split('/[^a-z0-9]+/', $normalized) ?: [];
        $parts = array_filter($parts, static fn (string $word): bool => mb_strlen($word) >= 4);

        return array_values(array_unique($parts));
    }

    private function getAvailableDishesWithIngredients(): array
    {
        try {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT d.id, d.name, d.description, d.base_price, d.image_url, i.name AS ingredient_name
                 FROM dish d
                 LEFT JOIN dish_ingredient di ON di.dish_id = d.id
                 LEFT JOIN ingredient i ON i.id = di.ingredient_id
                 WHERE d.available = 1 OR d.available IS NULL
                 ORDER BY d.created_at ASC, d.id ASC'
            );
        } catch (\Throwable) {
            return [];
        }

        $dishesById = [];

        foreach ($rows as $row) {
            $dishId = (int) ($row['id'] ?? 0);
            if ($dishId <= 0) {
                continue;
            }

            if (!isset($dishesById[$dishId])) {
                $dishesById[$dishId] = [
                    'id' => $dishId,
                    'name' => (string) ($row['name'] ?? 'Unnamed dish'),
                    'description' => (string) ($row['description'] ?? ''),
                    'basePrice' => isset($row['base_price']) ? (float) $row['base_price'] : null,
                    'imageUrl' => (string) ($row['image_url'] ?? ''),
                    'ingredients' => [],
                ];
            }

            $ingredient = trim((string) ($row['ingredient_name'] ?? ''));
            if ($ingredient !== '') {
                $dishesById[$dishId]['ingredients'][] = $ingredient;
            }
        }

        foreach ($dishesById as &$dish) {
            $dish['ingredients'] = array_values(array_unique($dish['ingredients']));
        }

        unset($dish);

        return array_values($dishesById);
    }

    /**
     * @return array{type: string, message: string, prefer: string[], avoid: string[]}
     */
    private function resolveWeatherTarget(?float $temperature, string $statusClass): array
    {
        if ($temperature !== null) {
            if ($temperature >= 28) {
                return [
                    'type' => 'cooling',
                    'message' => 'It is hot outside, so lighter and cooler dishes are recommended.',
                    'prefer' => ['salad', 'cold', 'iced', 'fresh', 'yogurt', 'sorbet', 'smoothie', 'juice'],
                    'avoid' => ['soup', 'stew', 'hot', 'roast'],
                ];
            }

            if ($temperature <= 14) {
                return [
                    'type' => 'warming',
                    'message' => 'It is cold outside, so warm and comforting dishes are recommended.',
                    'prefer' => ['soup', 'stew', 'hot', 'warm', 'grilled', 'roast', 'pasta', 'tea', 'coffee'],
                    'avoid' => ['iced', 'cold', 'sorbet'],
                ];
            }
        }

        if (in_array($statusClass, ['wx-hot', 'wx-warm'], true)) {
            return [
                'type' => 'cooling',
                'message' => 'Warm weather detected, recommending cooler dishes.',
                'prefer' => ['salad', 'cold', 'iced', 'fresh', 'smoothie', 'juice'],
                'avoid' => ['soup', 'stew', 'roast'],
            ];
        }

        if (in_array($statusClass, ['wx-cold', 'wx-cool'], true)) {
            return [
                'type' => 'warming',
                'message' => 'Cool weather detected, recommending warmer dishes.',
                'prefer' => ['soup', 'stew', 'hot', 'warm', 'pasta', 'grilled', 'tea', 'coffee'],
                'avoid' => ['iced', 'cold'],
            ];
        }

        return [
            'type' => 'balanced',
            'message' => 'Weather is moderate, recommending balanced menu choices.',
            'prefer' => ['signature', 'fresh', 'classic', 'grilled'],
            'avoid' => [],
        ];
    }

    private function getDishFallbackImageUrl(string $dishName): string
    {
        $normalized = mb_strtolower(trim($dishName));

        $imageBuckets = [
            'pizza' => [
                'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1541745537411-b8046dc6d66c?auto=format&fit=crop&w=1200&q=80',
            ],
            'burger' => [
                'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1553979459-d2229ba7433b?auto=format&fit=crop&w=1200&q=80',
            ],
            'pasta' => [
                'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1555949258-eb67b1ef0ceb?auto=format&fit=crop&w=1200&q=80',
            ],
            'salad' => [
                'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&q=80',
            ],
            'soup' => [
                'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?auto=format&fit=crop&w=1200&q=80',
            ],
            'dessert' => [
                'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=1200&q=80',
            ],
            'seafood' => [
                'https://images.unsplash.com/photo-1559847844-d721426d6edc?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1615141982883-c7ad0e69fd62?auto=format&fit=crop&w=1200&q=80',
            ],
            'breakfast' => [
                'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1506084868230-bb9d95c24759?auto=format&fit=crop&w=1200&q=80',
            ],
            'generic' => [
                'https://images.unsplash.com/photo-1499028344343-cd173ffc68a9?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=1200&q=80',
            ],
        ];

        $keywordMap = [
            'pizza' => 'pizza',
            'burger' => 'burger',
            'pasta' => 'pasta',
            'spaghetti' => 'pasta',
            'lasagna' => 'pasta',
            'salad' => 'salad',
            'soup' => 'soup',
            'dessert' => 'dessert',
            'cake' => 'dessert',
            'taco' => 'seafood',
            'shrimp' => 'seafood',
            'fish' => 'seafood',
            'salmon' => 'seafood',
            'omelette' => 'breakfast',
            'egg' => 'breakfast',
            'brunch' => 'breakfast',
        ];

        $bucket = 'generic';
        foreach ($keywordMap as $keyword => $mappedBucket) {
            if ($normalized !== '' && str_contains($normalized, $keyword)) {
                $bucket = $mappedBucket;
                break;
            }
        }

        $urls = $imageBuckets[$bucket] ?? $imageBuckets['generic'];
        $seed = $normalized !== '' ? $normalized : 'dish';
        $index = (int) (abs(crc32($seed)) % max(1, count($urls)));

        return $urls[$index] ?? $imageBuckets['generic'][0];
    }

    /**
     * @param array<int, array{id: ?int, name: string, quantityRequired: ?float, unit: ?string}> $ingredients
     * @return string[]
     */
    private function inferAllergensFromIngredients(array $ingredients): array
    {
        $allergenKeywords = [
            'gluten' => ['wheat', 'flour', 'bread', 'pasta', 'barley', 'rye'],
            'milk' => ['milk', 'cheese', 'butter', 'cream', 'yogurt'],
            'eggs' => ['egg', 'omelette', 'mayo'],
            'nuts' => ['nut', 'almond', 'hazelnut', 'walnut', 'pistachio', 'peanut'],
            'soy' => ['soy', 'tofu'],
            'fish' => ['fish', 'salmon', 'tuna', 'anchovy'],
            'shellfish' => ['shrimp', 'prawn', 'crab', 'lobster', 'oyster', 'mussel'],
            'sesame' => ['sesame'],
        ];

        $found = [];

        foreach ($ingredients as $ingredient) {
            $name = mb_strtolower((string) ($ingredient['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            foreach ($allergenKeywords as $allergen => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($name, $keyword)) {
                        $found[$allergen] = true;
                        break;
                    }
                }
            }
        }

        $labels = array_keys($found);
        sort($labels);

        return $labels;
    }

    private function buildMenuSections(): array
    {
        // Try the ORM query first; if schema/mapping is out-of-sync this can throw.
        try {
            $menus = $this->menuRepository->createQueryBuilder('m')
                ->andWhere('m.isActive = :active OR m.isActive IS NULL')
                ->setParameter('active', true)
                ->orderBy('m.created_at', 'ASC')
                ->getQuery()
                ->getResult();

            if (!$menus) {
                $menus = $this->menuRepository->createQueryBuilder('m')
                    ->orderBy('m.created_at', 'ASC')
                    ->getQuery()
                    ->getResult();
            }

            $menuSections = [];
            foreach ($menus as $menu) {
                $dishes = [];
                foreach ($menu->getDishs() as $dish) {
                    $available = $dish->isAvailable();
                    // Treat NULL availability as available to avoid hiding dishes when schema/data is inconsistent
                    if ($available === null || $available) {
                        $dishes[] = [
                            'id'          => $dish->getId(),
                            'name'        => $dish->getName(),
                            'description' => $dish->getDescription(),
                            'basePrice'   => $dish->getBase_price(),
                            'imageUrl'    => $dish->getImageUrl() ?? null,
                        ];
                    }
                }
                // Always include the menu section even if there are no available dishes.
                $menuSections[] = [
                    'menu'   => [
                        'id'          => $menu->getId(),
                        'title'       => $menu->getTitle(),
                        'description' => $menu->getDescription(),
                    ],
                    'dishes' => $dishes,
                ];
            }
            return $menuSections;
        } catch (\Throwable $e) {
            // Fallback: use DBAL raw queries to be resilient to schema/mapping drift.
        }

        // DBAL fallback: inspect columns and query raw rows.
        try {
            $sm = $this->connection->createSchemaManager();
            $columns = $sm->listTableColumns('menu');
            $colNames = array_map(fn($c) => $c->getName(), $columns);
            $activeCol = in_array('is_active', $colNames, true) ? 'is_active' : (in_array('isActive', $colNames, true) ? 'isActive' : null);
        } catch (\Throwable $e) {
            return [];
        }

        if (null === $activeCol) {
            $menuRows = $this->connection->fetchAllAssociative('SELECT id, title, description FROM menu ORDER BY created_at ASC');
        } else {
            $menuRows = $this->connection->fetchAllAssociative("SELECT id, title, description FROM menu WHERE $activeCol = 1 OR $activeCol IS NULL ORDER BY created_at ASC");
            if (!$menuRows) {
                $menuRows = $this->connection->fetchAllAssociative('SELECT id, title, description FROM menu ORDER BY created_at ASC');
            }
        }
        $menuSections = [];
        foreach ($menuRows as $mRow) {
            $dishRows = $this->connection->fetchAllAssociative('SELECT id, name, description, base_price, image_url, available FROM dish WHERE menu_id = ? ORDER BY created_at ASC', [$mRow['id']]);
            $dishes = [];
            foreach ($dishRows as $dRow) {
                if (isset($dRow['available']) && !$dRow['available']) {
                    continue;
                }
                $dishes[] = [
                    'id' => $dRow['id'],
                    'name' => $dRow['name'],
                    'description' => $dRow['description'],
                    'basePrice' => isset($dRow['base_price']) ? (float) $dRow['base_price'] : null,
                    'imageUrl' => $dRow['image_url'] ?? null,
                ];
            }
            // Always include the menu section even if there are no available dishes.
            $menuSections[] = [
                'menu' => [
                    'id' => $mRow['id'],
                    'title' => $mRow['title'],
                    'description' => $mRow['description'],
                ],
                'dishes' => $dishes,
            ];
        }
        return $menuSections;
    }
}