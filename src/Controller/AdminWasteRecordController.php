<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\Wasterecord;
use App\Form\WasterecordType;
use App\Repository\WasterecordRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
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

        $filterData = $this->extractFilters($request, $wasterecordRepository);
        $records = $wasterecordRepository->findForAdminList(
            $filterData['search'],
            $filterData['sort'],
            $filterData['dir'],
            '' === $filterData['wasteType'] ? null : $filterData['wasteType'],
            $filterData['dateFrom'],
            $filterData['dateTo']
        );

        return $this->render('admin/inventory/waste/index.html.twig', [
            'records' => $records,
            'search' => $filterData['search'],
            'sort' => $filterData['sort'],
            'dir' => 'ASC' === $filterData['dir'] ? 'ASC' : 'DESC',
            'wasteType' => $filterData['wasteType'],
            'wasteTypes' => $wasterecordRepository->findDistinctWasteTypes(),
            'dateFrom' => $filterData['dateFromRaw'],
            'dateTo' => $filterData['dateToRaw'],
            'totalWasted' => $wasterecordRepository->totalWastedQuantity(),
        ]);
    }

    #[Route('/export/pdf', name: 'admin_waste_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, WasterecordRepository $wasterecordRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        if (!class_exists(Dompdf::class)) {
            $this->addFlash('error', 'PDF export dependency missing. Run composer require dompdf/dompdf.');

            return $this->redirectToRoute('admin_waste_index', $request->query->all());
        }

        $filterData = $this->extractFilters($request, $wasterecordRepository);
        $records = $wasterecordRepository->findForAdminList(
            $filterData['search'],
            $filterData['sort'],
            $filterData['dir'],
            '' === $filterData['wasteType'] ? null : $filterData['wasteType'],
            $filterData['dateFrom'],
            $filterData['dateTo']
        );

        if ([] === $records) {
            $this->addFlash('error', 'No rows match the current filter set.');

            return $this->redirectToRoute('admin_waste_index', $request->query->all());
        }

        $html = $this->renderView('admin/inventory/waste/export_pdf.html.twig', [
            'records' => $records,
            'generatedAt' => new \DateTimeImmutable(),
            'filters' => [
                'search' => $filterData['search'],
                'wasteType' => $filterData['wasteType'] ?: 'All',
                'dateFrom' => $filterData['dateFromRaw'] ?: 'Any',
                'dateTo' => $filterData['dateToRaw'] ?: 'Any',
                'sort' => $filterData['sort'],
                'dir' => $filterData['dir'],
            ],
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('isFontSubsettingEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        try {
            $dompdf = new Dompdf($options);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            $pdfOutput = $dompdf->output();
        } catch (\Throwable) {
            $this->addFlash('error', 'Unable to generate PDF with the current content. Please simplify filters and retry.');

            return $this->redirectToRoute('admin_waste_index', $request->query->all());
        }

        $filename = sprintf('waste-records-%s.pdf', (new \DateTimeImmutable())->format('Ymd-His'));
        $response = new Response($pdfOutput);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
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

    /**
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request, WasterecordRepository $wasterecordRepository): array
    {
        $search = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'date');
        $dir = strtoupper((string) $request->query->get('dir', 'DESC'));
        $wasteType = trim((string) $request->query->get('waste_type', ''));
        $dateFromRaw = trim((string) $request->query->get('date_from', ''));
        $dateToRaw = trim((string) $request->query->get('date_to', ''));

        $allowedWasteTypes = $wasterecordRepository->findDistinctWasteTypes();
        if ('' !== $wasteType && !in_array($wasteType, $allowedWasteTypes, true)) {
            $wasteType = '';
        }

        return [
            'search' => $search,
            'sort' => $sort,
            'dir' => 'ASC' === $dir ? 'ASC' : 'DESC',
            'wasteType' => $wasteType,
            'dateFromRaw' => $dateFromRaw,
            'dateToRaw' => $dateToRaw,
            'dateFrom' => $this->parseDate($dateFromRaw),
            'dateTo' => $this->parseDate($dateToRaw),
        ];
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        if ('' === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTimeImmutable ? $date : null;
    }

    private function denyUnlessAdmin(Request $request): ?RedirectResponse
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return null;
    }
}
