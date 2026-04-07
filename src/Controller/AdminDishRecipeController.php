<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Entity\DishIngredient;
use App\Entity\Ingredient;
use App\Form\DishIngredientType;
use App\Repository\DishIngredientRepository;
use App\Repository\IngredientRepository;
use App\Service\DishAvailabilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/dish/{id}/recipe')]
final class AdminDishRecipeController extends AbstractController
{
    #[Route('', name: 'admin_dish_recipe_index', methods: ['GET'])]
    public function index(Request $request, Dish $dish, DishIngredientRepository $dishIngredientRepository, DishAvailabilityService $availabilityService): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        return $this->render('admin/dish/recipe.html.twig', [
            'dish' => $dish,
            'recipeLines' => $dishIngredientRepository->findByDishWithIngredient($dish),
            'availability' => $availabilityService->evaluateDish($dish),
        ]);
    }

    #[Route('/new', name: 'admin_dish_recipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Dish $dish, DishIngredientRepository $dishIngredientRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $recipeLine = new DishIngredient();
        $recipeLine->setDish($dish);

        $form = $this->createForm(DishIngredientType::class, $recipeLine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please complete all required fields and fix invalid values.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $ingredient = $recipeLine->getIngredient();
            $quantity = (float) ($recipeLine->getQuantityRequired() ?? 0);

            $violations = $validator->validate([
                'ingredient' => $ingredient?->getId(),
                'quantity' => $quantity,
            ], new Assert\Collection([
                'allowExtraFields' => true,
                'fields' => [
                    'ingredient' => [
                        new Assert\NotBlank(message: 'Ingredient is required.'),
                    ],
                    'quantity' => [
                        new Assert\Positive(message: 'Quantity required must be greater than 0.'),
                    ],
                ],
            ]));

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }

                return $this->render('admin/dish/recipe_edit.html.twig', [
                    'dish' => $dish,
                    'recipeLine' => $recipeLine,
                    'form' => $form,
                    'isEdit' => false,
                ]);
            }

            if ($ingredient instanceof Ingredient && $dishIngredientRepository->findOneByDishAndIngredient($dish, $ingredient)) {
                $this->addFlash('error', 'This ingredient already exists in the dish recipe. Edit the existing line instead.');
            } else {
                $entityManager->persist($recipeLine);
                $entityManager->flush();

                $this->addFlash('success', 'Recipe line added.');

                return $this->redirectToRoute('admin_dish_recipe_index', ['id' => $dish->getId()]);
            }
        }

        return $this->render('admin/dish/recipe_edit.html.twig', [
            'dish' => $dish,
            'recipeLine' => $recipeLine,
            'form' => $form,
            'isEdit' => false,
        ]);
    }

    #[Route('/{ingredientId}/edit', name: 'admin_dish_recipe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dish $dish, int $ingredientId, IngredientRepository $ingredientRepository, DishIngredientRepository $dishIngredientRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $ingredient = $ingredientRepository->find($ingredientId);
        if (!$ingredient instanceof Ingredient) {
            throw $this->createNotFoundException('Ingredient not found.');
        }

        $recipeLine = $dishIngredientRepository->findOneByDishAndIngredient($dish, $ingredient);
        if (!$recipeLine instanceof DishIngredient) {
            throw $this->createNotFoundException('Recipe line not found.');
        }

        $form = $this->createForm(DishIngredientType::class, $recipeLine, [
            'lock_ingredient' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please complete all required fields and fix invalid values.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = (float) ($recipeLine->getQuantityRequired() ?? 0);

            $violations = $validator->validate($quantity, [
                new Assert\Positive(message: 'Quantity required must be greater than 0.'),
            ]);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }

                return $this->render('admin/dish/recipe_edit.html.twig', [
                    'dish' => $dish,
                    'recipeLine' => $recipeLine,
                    'form' => $form,
                    'isEdit' => true,
                ]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Recipe line updated.');

            return $this->redirectToRoute('admin_dish_recipe_index', ['id' => $dish->getId()]);
        }

        return $this->render('admin/dish/recipe_edit.html.twig', [
            'dish' => $dish,
            'recipeLine' => $recipeLine,
            'form' => $form,
            'isEdit' => true,
        ]);
    }

    #[Route('/{ingredientId}', name: 'admin_dish_recipe_delete', methods: ['POST'])]
    public function delete(Request $request, Dish $dish, int $ingredientId, IngredientRepository $ingredientRepository, DishIngredientRepository $dishIngredientRepository, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        if (!$this->isCsrfTokenValid('delete_recipe'.$dish->getId().'_'.$ingredientId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_dish_recipe_index', ['id' => $dish->getId()]);
        }

        $ingredient = $ingredientRepository->find($ingredientId);
        if (!$ingredient instanceof Ingredient) {
            $this->addFlash('error', 'Ingredient not found.');
            return $this->redirectToRoute('admin_dish_recipe_index', ['id' => $dish->getId()]);
        }

        $recipeLine = $dishIngredientRepository->findOneByDishAndIngredient($dish, $ingredient);
        if (!$recipeLine instanceof DishIngredient) {
            $this->addFlash('error', 'Recipe line not found.');
            return $this->redirectToRoute('admin_dish_recipe_index', ['id' => $dish->getId()]);
        }

        $entityManager->remove($recipeLine);
        $entityManager->flush();

        $this->addFlash('success', 'Recipe line removed.');

        return $this->redirectToRoute('admin_dish_recipe_index', ['id' => $dish->getId()]);
    }

    private function denyUnlessAdmin(Request $request): ?RedirectResponse
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return null;
    }
}
