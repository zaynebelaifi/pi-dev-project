<?php
 
namespace App\Controller;
 
use App\Entity\Dish;
use App\Entity\DishIngredient;
use App\Form\DishType;
use App\Repository\DishRepository;
use App\Repository\IngredientRepository;
use App\Repository\MenuRepository;
use App\Service\DishAvailabilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
 
#[Route('/admin/dish')]
final class AdminDishController extends AbstractController
{
    #[Route('/', name: 'admin_dish_index', methods: ['GET'])]
    public function index(Request $request, DishRepository $dishRepository, MenuRepository $menuRepository, DishAvailabilityService $availabilityService): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        $selectedMenu = null;
        $menuId = $request->query->getInt('menu', 0);
        if ($menuId > 0) {
            $selectedMenu = $menuRepository->find($menuId);
            if (null === $selectedMenu) {
                $this->addFlash('error', 'Selected menu was not found.');
                return $this->redirectToRoute('admin_menu_index');
            }
        }

        $search = \trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'created_at');
        $dir = (string) $request->query->get('dir', 'DESC');

        $dishes = $dishRepository->findForAdminList($search, $sort, $dir, $selectedMenu);

        return $this->render('admin/dish/index.html.twig', [
            'dishes' => $dishes,
            'selectedMenu' => $selectedMenu,
            'search' => $search,
            'sort' => $sort,
            'dir' => \strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC',
            'dishAvailability' => $availabilityService->evaluateForDishes($dishes),
        ]);
    }
 
    #[Route('/new', name: 'admin_dish_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MenuRepository $menuRepository, IngredientRepository $ingredientRepository, ValidatorInterface $validator): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        $dish = new Dish();
        $dish->setAvailable(true);
        $dish->setCreated_at(new \DateTimeImmutable());
        $dish->setUpdated_at(new \DateTimeImmutable());
 
        // Pre-select and lock menu when coming from a specific menu page
        $menuId = $request->query->getInt('menu', 0);
        $lockedMenu = null;
        if ($menuId > 0) {
            $lockedMenu = $menuRepository->find($menuId);
            if (null === $lockedMenu) {
                $this->addFlash('error', 'Selected menu was not found.');
                return $this->redirectToRoute('admin_menu_index');
            }
            $dish->setMenu($lockedMenu);
        }
 
        $form = $this->createForm(DishType::class, $dish, [
            'lock_menu' => null !== $lockedMenu,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please complete all required fields and fix invalid values.');
        }

        $ingredients = $ingredientRepository->findBy([], ['name' => 'ASC']);
        $ingredientsById = [];
        foreach ($ingredients as $ingredient) {
            $ingredientsById[$ingredient->getId()] = $ingredient;
        }
        $recipeIngredientDraft = (array) $request->request->all('recipe_ingredient');
        $recipeQuantityDraft = (array) $request->request->all('recipe_quantity');
 
        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $lockedMenu) {
                $dish->setMenu($lockedMenu);
            }

            $recipeErrors = [];
            $recipeLines = [];
            $seenIngredients = [];

            foreach ($recipeIngredientDraft as $idx => $ingredientIdRaw) {
                $ingredientId = (int) $ingredientIdRaw;
                $quantityRaw = $recipeQuantityDraft[$idx] ?? null;
                $quantity = is_numeric($quantityRaw) ? (float) $quantityRaw : 0.0;

                // Allow empty rows in the dynamic UI.
                if ($ingredientId <= 0 && ($quantityRaw === null || $quantityRaw === '' || $quantity <= 0)) {
                    continue;
                }

                $violations = $validator->validate([
                    'ingredient' => $ingredientIdRaw,
                    'quantity' => $quantityRaw,
                ], new Assert\Collection([
                    'allowExtraFields' => true,
                    'fields' => [
                        'ingredient' => [
                            new Assert\NotBlank(message: sprintf('Recipe row %d: ingredient is required.', $idx + 1)),
                            new Assert\Regex(pattern: '/^\d+$/', message: sprintf('Recipe row %d: ingredient is required.', $idx + 1)),
                        ],
                        'quantity' => [
                            new Assert\NotBlank(message: sprintf('Recipe row %d: quantity is required.', $idx + 1)),
                            new Assert\Positive(message: sprintf('Recipe row %d: quantity must be greater than 0.', $idx + 1)),
                        ],
                    ],
                ]));

                if (count($violations) > 0) {
                    foreach ($violations as $violation) {
                        $recipeErrors[] = $violation->getMessage();
                    }
                    continue;
                }

                if (isset($seenIngredients[$ingredientId])) {
                    $recipeErrors[] = sprintf('Recipe row %d: duplicate ingredient selected.', $idx + 1);
                    continue;
                }

                $ingredient = $ingredientsById[$ingredientId] ?? null;
                if (null === $ingredient) {
                    $recipeErrors[] = sprintf('Recipe row %d: selected ingredient was not found.', $idx + 1);
                    continue;
                }

                $seenIngredients[$ingredientId] = true;

                $line = new DishIngredient();
                $line->setDish($dish);
                $line->setIngredient($ingredient);
                $line->setQuantityRequired($quantity);
                $recipeLines[] = $line;
            }

            if ([] === $recipeLines) {
                $recipeErrors[] = 'Add at least one ingredient recipe line for this dish.';
            }

            if ([] !== $recipeErrors) {
                foreach ($recipeErrors as $message) {
                    $this->addFlash('error', $message);
                }

                return $this->render('admin/dish/new.html.twig', [
                    'dish' => $dish,
                    'form' => $form,
                    'selectedMenu' => $lockedMenu,
                    'ingredients' => $ingredients,
                    'recipeIngredientDraft' => $recipeIngredientDraft,
                    'recipeQuantityDraft' => $recipeQuantityDraft,
                ]);
            }

            $dish->setUpdated_at(new \DateTimeImmutable());
            $entityManager->persist($dish);

            foreach ($recipeLines as $recipeLine) {
                $entityManager->persist($recipeLine);
            }

            $entityManager->flush();
 
            $this->addFlash('success', 'Dish and recipe created successfully.');
 
            return $this->redirectToRoute('admin_dish_recipe_index', ['id' => $dish->getId()]);
        }
 
        return $this->render('admin/dish/new.html.twig', [
            'dish' => $dish,
            'form' => $form,
            'selectedMenu' => $lockedMenu,
            'ingredients' => $ingredients,
            'recipeIngredientDraft' => $recipeIngredientDraft,
            'recipeQuantityDraft' => $recipeQuantityDraft,
        ]);
    }
 
    #[Route('/{id}', name: 'admin_dish_show', methods: ['GET'])]
    public function show(Request $request, Dish $dish, DishAvailabilityService $availabilityService): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        return $this->render('admin/dish/show.html.twig', [
            'dish' => $dish,
            'availability' => $availabilityService->evaluateDish($dish),
        ]);
    }
 
    #[Route('/{id}/edit', name: 'admin_dish_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dish $dish, EntityManagerInterface $entityManager, MenuRepository $menuRepository): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $lockedMenu = null;
        $menuId = $request->query->getInt('menu', 0);
        if ($menuId > 0) {
            $lockedMenu = $menuRepository->find($menuId);
            if (null === $lockedMenu) {
                $this->addFlash('error', 'Selected menu was not found.');
                return $this->redirectToRoute('admin_menu_index');
            }
            if (null === $dish->getMenu() || $dish->getMenu()->getId() !== $lockedMenu->getId()) {
                $this->addFlash('error', 'This dish does not belong to the selected menu.');
                return $this->redirectToRoute('admin_dish_index', ['menu' => $lockedMenu->getId()]);
            }
        }

        $form = $this->createForm(DishType::class, $dish, [
            'lock_menu' => null !== $lockedMenu,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please complete all required fields and fix invalid values.');
        }
 
        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $lockedMenu) {
                $dish->setMenu($lockedMenu);
            }
            $dish->setUpdated_at(new \DateTimeImmutable());
            $entityManager->flush();
 
            $this->addFlash('success', 'Dish updated successfully.');

            if (null !== $lockedMenu) {
                return $this->redirectToRoute('admin_dish_index', ['menu' => $lockedMenu->getId()]);
            }

            return $this->redirectToRoute('admin_dish_index');
        }
 
        return $this->render('admin/dish/edit.html.twig', [
            'dish' => $dish,
            'form' => $form,
            'selectedMenu' => $lockedMenu,
        ]);
    }
 
    #[Route('/{id}', name: 'admin_dish_delete', methods: ['POST'])]
    public function delete(Request $request, Dish $dish, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        if ($this->isCsrfTokenValid('delete'.$dish->getId(), $request->request->get('_token'))) {
            $entityManager->remove($dish);
            $entityManager->flush();
            $this->addFlash('success', 'Dish deleted successfully.');
        }
 
        return $this->redirectToRoute('admin_dish_index');
    }
}
