<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Form\IngredientType;
use App\Repository\IngredientRepository;
use App\Service\ExpiredIngredientWasteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/inventory/ingredient')]
final class AdminIngredientController extends AbstractController
{
    #[Route('/', name: 'admin_ingredient_index', methods: ['GET'])]
    public function index(Request $request, IngredientRepository $ingredientRepository, ExpiredIngredientWasteService $expiredWasteService): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $moved = $expiredWasteService->moveExpiredStockToWaste();
        if ($moved > 0) {
            $this->addFlash('success', sprintf('Automation moved %d expired ingredient stocks to waste records.', $moved));
        }

        $search = trim((string) $request->query->get('q', ''));
        $ingredients = $ingredientRepository->findForAdminList($search);

        $today = new \DateTimeImmutable('today');

        return $this->render('admin/inventory/ingredient/index.html.twig', [
            'ingredients' => $ingredients,
            'search' => $search,
            'stats' => [
                'total' => count($ingredients),
                'lowStock' => $ingredientRepository->countLowStock(),
                'expired' => $ingredientRepository->countExpired($today),
                'inventoryValue' => $ingredientRepository->sumInventoryValue(),
            ],
            'today' => $today,
        ]);
    }

    #[Route('/new', name: 'admin_ingredient_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $ingredient = new Ingredient();
        $ingredient->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(IngredientType::class, $ingredient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please complete all required fields and fix invalid values.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $violations = $validator->validate([
                'quantityInStock' => (float) ($ingredient->getQuantityInStock() ?? 0),
                'minStockLevel' => (float) ($ingredient->getMinStockLevel() ?? 0),
                'unitCost' => (float) ($ingredient->getUnitCost() ?? 0),
            ], new Assert\Collection([
                'allowExtraFields' => true,
                'fields' => [
                    'quantityInStock' => [new Assert\PositiveOrZero(message: 'Quantity in stock must be 0 or greater.')],
                    'minStockLevel' => [new Assert\PositiveOrZero(message: 'Minimum stock level must be 0 or greater.')],
                    'unitCost' => [new Assert\PositiveOrZero(message: 'Unit cost must be 0 or greater.')],
                ],
            ]));

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }

                return $this->render('admin/inventory/ingredient/new.html.twig', [
                    'form' => $form,
                ]);
            }

            if ((float) ($ingredient->getMinStockLevel() ?? 0) > (float) ($ingredient->getQuantityInStock() ?? 0)) {
                $this->addFlash('error', 'Minimum stock level cannot be greater than quantity in stock.');

                return $this->render('admin/inventory/ingredient/new.html.twig', [
                    'form' => $form,
                ]);
            }

            $entityManager->persist($ingredient);
            $entityManager->flush();

            $this->addFlash('success', 'Ingredient created successfully.');

            return $this->redirectToRoute('admin_ingredient_index');
        }

        return $this->render('admin/inventory/ingredient/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_ingredient_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ingredient $ingredient, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $form = $this->createForm(IngredientType::class, $ingredient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please complete all required fields and fix invalid values.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $violations = $validator->validate([
                'quantityInStock' => (float) ($ingredient->getQuantityInStock() ?? 0),
                'minStockLevel' => (float) ($ingredient->getMinStockLevel() ?? 0),
                'unitCost' => (float) ($ingredient->getUnitCost() ?? 0),
            ], new Assert\Collection([
                'allowExtraFields' => true,
                'fields' => [
                    'quantityInStock' => [new Assert\PositiveOrZero(message: 'Quantity in stock must be 0 or greater.')],
                    'minStockLevel' => [new Assert\PositiveOrZero(message: 'Minimum stock level must be 0 or greater.')],
                    'unitCost' => [new Assert\PositiveOrZero(message: 'Unit cost must be 0 or greater.')],
                ],
            ]));

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }

                return $this->render('admin/inventory/ingredient/edit.html.twig', [
                    'ingredient' => $ingredient,
                    'form' => $form,
                ]);
            }

            if ((float) ($ingredient->getMinStockLevel() ?? 0) > (float) ($ingredient->getQuantityInStock() ?? 0)) {
                $this->addFlash('error', 'Minimum stock level cannot be greater than quantity in stock.');

                return $this->render('admin/inventory/ingredient/edit.html.twig', [
                    'ingredient' => $ingredient,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Ingredient updated successfully.');

            return $this->redirectToRoute('admin_ingredient_index');
        }

        return $this->render('admin/inventory/ingredient/edit.html.twig', [
            'ingredient' => $ingredient,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_ingredient_delete', methods: ['POST'])]
    public function delete(Request $request, Ingredient $ingredient, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        if (!$this->isCsrfTokenValid('delete_ingredient'.$ingredient->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_ingredient_index');
        }

        if ($ingredient->getDishIngredients()->count() > 0) {
            $this->addFlash('error', 'Cannot delete ingredient used in dish recipes. Remove recipe links first.');
            return $this->redirectToRoute('admin_ingredient_index');
        }

        if ($ingredient->getWasteRecords()->count() > 0) {
            $this->addFlash('error', 'Cannot delete ingredient referenced by waste records.');
            return $this->redirectToRoute('admin_ingredient_index');
        }

        $entityManager->remove($ingredient);
        $entityManager->flush();

        $this->addFlash('success', 'Ingredient deleted successfully.');

        return $this->redirectToRoute('admin_ingredient_index');
    }

    private function denyUnlessAdmin(Request $request): ?RedirectResponse
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return null;
    }
}
