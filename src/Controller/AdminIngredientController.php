<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Form\IngredientType;
use App\Repository\IngredientRepository;
use App\Service\IngredientCsvImportService;
use App\Service\IngredientInventorySyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
    public function index(Request $request, IngredientRepository $ingredientRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $search = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'name');
        $dir = strtoupper((string) $request->query->get('dir', 'ASC'));
        $stockStatus = trim((string) $request->query->get('status', ''));
        $unit = trim((string) $request->query->get('unit', ''));

        $today = new \DateTimeImmutable('today');
        $ingredients = $ingredientRepository->findForAdminList(
            $search,
            $sort,
            $dir,
            '' === $stockStatus ? null : $stockStatus,
            '' === $unit ? null : $unit,
            $today
        );

        return $this->render('admin/inventory/ingredient/index.html.twig', [
            'ingredients' => $ingredients,
            'search' => $search,
            'sort' => $sort,
            'dir' => 'DESC' === $dir ? 'DESC' : 'ASC',
            'status' => $stockStatus,
            'unit' => $unit,
            'units' => $ingredientRepository->findDistinctUnits(),
            'stats' => [
                'total' => count($ingredients),
                'lowStock' => $ingredientRepository->countLowStock(),
                'expired' => $ingredientRepository->countExpired($today),
                'inventoryValue' => $ingredientRepository->sumInventoryValue(),
            ],
            'today' => $today,
        ]);
    }

    #[Route('/import-csv', name: 'admin_ingredient_import_csv', methods: ['POST'])]
    public function importCsv(Request $request, IngredientCsvImportService $ingredientCsvImportService): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        if (!$this->isCsrfTokenValid('import_ingredients_csv', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');

            return $this->redirectToRoute('admin_ingredient_index');
        }

        $csvFile = $request->files->get('csv_file');
        if (!$csvFile instanceof UploadedFile || !$csvFile->isValid()) {
            $this->addFlash('error', 'Please upload a valid CSV file.');

            return $this->redirectToRoute('admin_ingredient_index');
        }

        $extension = strtolower((string) $csvFile->getClientOriginalExtension());
        if ('csv' !== $extension && 'text/csv' !== $csvFile->getMimeType()) {
            $this->addFlash('error', 'Invalid file type. Upload a .csv file.');

            return $this->redirectToRoute('admin_ingredient_index');
        }

        $result = $ingredientCsvImportService->import($csvFile);

        $this->addFlash(
            'success',
            sprintf(
                'CSV import complete: %d created, %d updated, %d skipped, %d processed. Header detected: %s. Delimiter: %s.',
                (int) ($result['created'] ?? 0),
                (int) ($result['updated'] ?? 0),
                (int) ($result['skipped'] ?? 0),
                (int) ($result['processed'] ?? 0),
                !empty($result['headerDetected']) ? 'yes' : 'no',
                (string) ($result['delimiter'] ?? ',')
            )
        );

        $errors = array_slice($result['errors'] ?? [], 0, 8);
        foreach ($errors as $error) {
            $this->addFlash('error', (string) $error);
        }

        return $this->redirectToRoute('admin_ingredient_index');
    }

    #[Route('/new', name: 'admin_ingredient_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, IngredientRepository $ingredientRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $existingIngredients = $ingredientRepository->findForAdminList();
        $stockDraft = [
            'ingredient_id' => (string) $request->request->get('existing_ingredient_id', ''),
            'quantity' => (string) $request->request->get('add_quantity', ''),
            'expiry_date' => (string) $request->request->get('new_expiry_date', ''),
        ];

        if ($request->isMethod('POST') && 'existing' === (string) $request->request->get('stock_mode')) {
            if (!$this->isCsrfTokenValid('add_existing_ingredient_stock', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid token.');

                return $this->render('admin/inventory/ingredient/new.html.twig', [
                    'form' => $this->createForm(IngredientType::class, new Ingredient()),
                    'existingIngredients' => $existingIngredients,
                    'stockDraft' => $stockDraft,
                ]);
            }

            $ingredientId = (int) $request->request->get('existing_ingredient_id', 0);
            $addQuantityRaw = (string) $request->request->get('add_quantity', '');
            $expiryDateRaw = trim((string) $request->request->get('new_expiry_date', ''));

            $violations = $validator->validate([
                'ingredient_id' => $ingredientId,
                'add_quantity' => is_numeric($addQuantityRaw) ? (float) $addQuantityRaw : null,
            ], new Assert\Collection([
                'allowExtraFields' => true,
                'fields' => [
                    'ingredient_id' => [new Assert\Positive(message: 'Please select an existing ingredient.')],
                    'add_quantity' => [new Assert\NotNull(message: 'Added quantity is required.'), new Assert\Positive(message: 'Added quantity must be greater than 0.')],
                ],
            ]));

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }

                return $this->render('admin/inventory/ingredient/new.html.twig', [
                    'form' => $this->createForm(IngredientType::class, new Ingredient()),
                    'existingIngredients' => $existingIngredients,
                    'stockDraft' => $stockDraft,
                ]);
            }

            $existingIngredient = $ingredientRepository->find($ingredientId);
            if (!$existingIngredient instanceof Ingredient) {
                $this->addFlash('error', 'Selected ingredient was not found.');

                return $this->render('admin/inventory/ingredient/new.html.twig', [
                    'form' => $this->createForm(IngredientType::class, new Ingredient()),
                    'existingIngredients' => $existingIngredients,
                    'stockDraft' => $stockDraft,
                ]);
            }

            $existingIngredient->setQuantityInStock(
                (float) ($existingIngredient->getQuantityInStock() ?? 0) + (float) $addQuantityRaw
            );

            if ('' !== $expiryDateRaw) {
                $expiryDate = \DateTimeImmutable::createFromFormat('Y-m-d', $expiryDateRaw);
                if (!$expiryDate instanceof \DateTimeImmutable) {
                    $this->addFlash('error', 'Expiry date format is invalid. Use YYYY-MM-DD.');

                    return $this->render('admin/inventory/ingredient/new.html.twig', [
                        'form' => $this->createForm(IngredientType::class, new Ingredient()),
                        'existingIngredients' => $existingIngredients,
                        'stockDraft' => $stockDraft,
                    ]);
                }

                $existingIngredient->setExpiryDate($expiryDate);
            }

            $entityManager->flush();
            $this->addFlash('success', sprintf('Stock updated for %s.', (string) $existingIngredient->getName()));

            return $this->redirectToRoute('admin_ingredient_index');
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
                    'existingIngredients' => $existingIngredients,
                    'stockDraft' => $stockDraft,
                ]);
            }

            if ((float) ($ingredient->getMinStockLevel() ?? 0) > (float) ($ingredient->getQuantityInStock() ?? 0)) {
                $this->addFlash('error', 'Minimum stock level cannot be greater than quantity in stock.');

                return $this->render('admin/inventory/ingredient/new.html.twig', [
                    'form' => $form,
                    'existingIngredients' => $existingIngredients,
                    'stockDraft' => $stockDraft,
                ]);
            }

            $duplicate = $ingredientRepository
                ->findOneByNormalizedNameAndUnit((string) $ingredient->getName(), (string) $ingredient->getUnit());
            if ($duplicate instanceof Ingredient) {
                $this->addFlash('error', 'Ingredient already exists. Use the existing ingredient instead of creating a duplicate.');

                return $this->render('admin/inventory/ingredient/new.html.twig', [
                    'form' => $form,
                    'existingIngredients' => $existingIngredients,
                    'stockDraft' => $stockDraft,
                ]);
            }

            $entityManager->persist($ingredient);
            $entityManager->flush();

            $this->addFlash('success', 'Ingredient created successfully.');

            return $this->redirectToRoute('admin_ingredient_index');
        }

        return $this->render('admin/inventory/ingredient/new.html.twig', [
            'form' => $form,
            'existingIngredients' => $existingIngredients,
            'stockDraft' => $stockDraft,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_ingredient_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Ingredient $ingredient, EntityManagerInterface $entityManager, ValidatorInterface $validator, IngredientRepository $ingredientRepository): Response
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

            $duplicate = $ingredientRepository
                ->findOneByNormalizedNameAndUnit((string) $ingredient->getName(), (string) $ingredient->getUnit(), $ingredient->getId());
            if ($duplicate instanceof Ingredient) {
                $this->addFlash('error', 'Ingredient already exists. Keep one unique ingredient per name and unit.');

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

    #[Route('/{id}', name: 'admin_ingredient_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
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

    #[Route('/sync-all', name: 'admin_ingredient_sync_all', methods: ['POST'])]
    public function syncAll(Request $request, IngredientInventorySyncService $syncService): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        if (!$this->isCsrfTokenValid('sync_ingredients', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_ingredient_index');
        }

        $result = $syncService->syncAll();

        $this->addFlash(
            'success',
            sprintf(
                'Inventory sync complete: %d expired stocks moved to waste, %d duplicate ingredients removed, %d duplicate recipe lines merged.',
                $result['expiredMoved'],
                $result['duplicatesRemoved'],
                $result['recipeLinesMerged']
            )
        );

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
