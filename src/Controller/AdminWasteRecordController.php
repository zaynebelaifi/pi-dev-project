<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\Wasterecord;
use App\Form\WasterecordType;
use App\Repository\WasterecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/inventory/waste')]
final class AdminWasteRecordController extends AbstractController
{
    #[Route('/', name: 'admin_waste_index', methods: ['GET'])]
    public function index(Request $request, WasterecordRepository $wasterecordRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $search = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'date');
        $dir = strtoupper((string) $request->query->get('dir', 'DESC'));
        $wasteType = trim((string) $request->query->get('waste_type', ''));
        $dateFromRaw = trim((string) $request->query->get('date_from', ''));
        $dateToRaw = trim((string) $request->query->get('date_to', ''));

        $dateFrom = null;
        if ('' !== $dateFromRaw) {
            $parsedFrom = \DateTimeImmutable::createFromFormat('Y-m-d', $dateFromRaw);
            if ($parsedFrom instanceof \DateTimeImmutable) {
                $dateFrom = $parsedFrom;
            }
        }

        $dateTo = null;
        if ('' !== $dateToRaw) {
            $parsedTo = \DateTimeImmutable::createFromFormat('Y-m-d', $dateToRaw);
            if ($parsedTo instanceof \DateTimeImmutable) {
                $dateTo = $parsedTo;
            }
        }

        $records = $wasterecordRepository->findForAdminList(
            $search,
            $sort,
            $dir,
            '' === $wasteType ? null : $wasteType,
            $dateFrom,
            $dateTo
        );

        return $this->render('admin/inventory/waste/index.html.twig', [
            'records' => $records,
            'search' => $search,
            'sort' => $sort,
            'dir' => 'ASC' === $dir ? 'ASC' : 'DESC',
            'wasteType' => $wasteType,
            'wasteTypes' => $wasterecordRepository->findDistinctWasteTypes(),
            'dateFrom' => $dateFromRaw,
            'dateTo' => $dateToRaw,
            'totalWasted' => $wasterecordRepository->totalWastedQuantity(),
        ]);
    }

    #[Route('/new', name: 'admin_waste_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $record = new Wasterecord();
        $record->setDate(new \DateTimeImmutable('today'));

        $form = $this->createForm(WasterecordType::class, $record);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please complete all required fields and fix invalid values.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $ingredient = $record->getIngredient();
            $quantity = (float) $record->getQuantityWasted();
            $wasteDate = $record->getDate();

            $violations = $validator->validate([
                'ingredient' => $ingredient?->getId(),
                'quantity' => $quantity,
                'date' => $wasteDate,
            ], new Assert\Collection([
                'allowExtraFields' => true,
                'fields' => [
                    'ingredient' => [new Assert\NotBlank(message: 'Ingredient is required.')],
                    'quantity' => [new Assert\Positive(message: 'Wasted quantity must be greater than 0.')],
                    'date' => [new Assert\NotNull(message: 'Waste date is required.')],
                ],
            ]));

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }

                return $this->render('admin/inventory/waste/new.html.twig', [
                    'form' => $form,
                ]);
            }

            if (!$ingredient instanceof Ingredient) {
                $this->addFlash('error', 'Ingredient is required.');
            } else {
                $stock = (float) ($ingredient->getQuantityInStock() ?? 0);
                if ($stock < $quantity) {
                    $this->addFlash('error', sprintf('Not enough stock for %s. Available: %.2f.', $ingredient->getName(), $stock));
                } else {
                    $ingredient->setQuantityInStock($stock - $quantity);
                    $entityManager->persist($record);
                    $entityManager->flush();

                    $this->addFlash('success', 'Waste record created and stock updated.');
                    $this->addLowStockWarning($ingredient);

                    return $this->redirectToRoute('admin_waste_index');
                }
            }
        }

        return $this->render('admin/inventory/waste/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_waste_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Wasterecord $record, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $originalIngredient = $record->getIngredient();
        $originalQuantity = (float) ($record->getQuantityWasted() ?? 0);
        $originalIngredientStock = $originalIngredient instanceof Ingredient ? (float) ($originalIngredient->getQuantityInStock() ?? 0) : 0;

        $form = $this->createForm(WasterecordType::class, $record);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please complete all required fields and fix invalid values.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $newIngredient = $record->getIngredient();
            $newQuantity = (float) ($record->getQuantityWasted() ?? 0);
            $wasteDate = $record->getDate();

            $violations = $validator->validate([
                'ingredient' => $newIngredient?->getId(),
                'quantity' => $newQuantity,
                'date' => $wasteDate,
            ], new Assert\Collection([
                'allowExtraFields' => true,
                'fields' => [
                    'ingredient' => [new Assert\NotBlank(message: 'Ingredient is required.')],
                    'quantity' => [new Assert\Positive(message: 'Wasted quantity must be greater than 0.')],
                    'date' => [new Assert\NotNull(message: 'Waste date is required.')],
                ],
            ]));

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }

                return $this->render('admin/inventory/waste/edit.html.twig', [
                    'record' => $record,
                    'form' => $form,
                ]);
            }

            if (!$newIngredient instanceof Ingredient || !$originalIngredient instanceof Ingredient) {
                $this->addFlash('error', 'Ingredient is required.');
            } else {
                $newIngredientStockBefore = (float) ($newIngredient->getQuantityInStock() ?? 0);

                $originalIngredient->setQuantityInStock($originalIngredientStock + $originalQuantity);
                $available = (float) ($newIngredient->getQuantityInStock() ?? 0);

                if ($available < $newQuantity) {
                    $originalIngredient->setQuantityInStock($originalIngredientStock);
                    if ($newIngredient !== $originalIngredient) {
                        $newIngredient->setQuantityInStock($newIngredientStockBefore);
                    }

                    $this->addFlash('error', sprintf('Not enough stock for %s. Available: %.2f.', $newIngredient->getName(), $available));
                } else {
                    $newIngredient->setQuantityInStock($available - $newQuantity);
                    $entityManager->flush();

                    $this->addFlash('success', 'Waste record updated and stock adjusted.');
                    $this->addLowStockWarning($newIngredient);

                    return $this->redirectToRoute('admin_waste_index');
                }
            }
        }

        return $this->render('admin/inventory/waste/edit.html.twig', [
            'record' => $record,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_waste_delete', methods: ['POST'])]
    public function delete(Request $request, Wasterecord $record, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        if (!$this->isCsrfTokenValid('delete_waste'.$record->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_waste_index');
        }

        $ingredient = $record->getIngredient();
        if ($ingredient instanceof Ingredient) {
            $ingredient->setQuantityInStock((float) ($ingredient->getQuantityInStock() ?? 0) + (float) ($record->getQuantityWasted() ?? 0));
        }

        $entityManager->remove($record);
        $entityManager->flush();

        $this->addFlash('success', 'Waste record deleted and stock restored.');

        return $this->redirectToRoute('admin_waste_index');
    }

    private function addLowStockWarning(Ingredient $ingredient): void
    {
        if ((float) ($ingredient->getQuantityInStock() ?? 0) <= (float) ($ingredient->getMinStockLevel() ?? 0)) {
            $this->addFlash('error', sprintf('%s is now below minimum stock level.', $ingredient->getName()));
        }
    }

    private function denyUnlessAdmin(Request $request): ?RedirectResponse
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return null;
    }
}
