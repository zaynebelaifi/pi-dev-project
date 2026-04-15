<?php

namespace App\Service;

use App\Entity\Ingredient;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class IngredientCsvImportService
{
    private const HEADER_ALIASES = [
        'name' => ['name', 'ingredient', 'ingredientname', 'ingredient_name'],
        'quantityInStock' => ['quantity', 'qty', 'stock', 'quantityinstock', 'quantity_in_stock'],
        'unit' => ['unit', 'uom', 'measure_unit'],
        'minStockLevel' => ['minstock', 'minimumstock', 'minstocklevel', 'minimum_stock_level', 'min_stock_level'],
        'unitCost' => ['unitcost', 'cost', 'price', 'costperunit', 'unit_cost'],
        'expiryDate' => ['expiry', 'expirydate', 'expiration', 'expirationdate', 'expiry_date'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IngredientRepository $ingredientRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function import(UploadedFile $csvFile): array
    {
        $path = $csvFile->getRealPath();
        if (false === $path) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 1,
                'processed' => 0,
                'errors' => ['Cannot read uploaded file path.'],
                'headerDetected' => false,
                'delimiter' => ',',
            ];
        }

        $handle = fopen($path, 'rb');
        if (false === $handle) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 1,
                'processed' => 0,
                'errors' => ['Cannot open uploaded CSV file.'],
                'headerDetected' => false,
                'delimiter' => ',',
            ];
        }

        $firstRawLine = '';
        while (($line = fgets($handle)) !== false) {
            if ('' !== trim($line)) {
                $firstRawLine = $this->removeUtf8Bom($line);
                break;
            }
        }

        if ('' === $firstRawLine) {
            fclose($handle);

            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'processed' => 0,
                'errors' => ['CSV file is empty.'],
                'headerDetected' => false,
                'delimiter' => ',',
            ];
        }

        $delimiter = $this->detectDelimiter($firstRawLine);
        rewind($handle);

        $lineNumber = 0;
        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $mapping = [
            'name' => 0,
            'quantityInStock' => 1,
            'unit' => 2,
            'minStockLevel' => 3,
            'unitCost' => 4,
            'expiryDate' => 5,
        ];
        $headerDetected = false;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            ++$lineNumber;

            if (null === $row || [] === $row) {
                continue;
            }

            $row = array_map(static fn ($value): string => trim((string) $value), $row);
            if ($lineNumber === 1) {
                $row[0] = $this->removeUtf8Bom($row[0] ?? '');
                $detectedMapping = $this->detectHeaderMapping($row);
                if (null !== $detectedMapping) {
                    $mapping = $detectedMapping;
                    $headerDetected = true;
                    continue;
                }
            }

            if ($this->isRowEmpty($row)) {
                continue;
            }

            ++$processed;

            $rowData = $this->extractRowData($row, $mapping);
            $validationError = $this->validateRowData($rowData);
            if (null !== $validationError) {
                ++$skipped;
                $errors[] = sprintf('Line %d skipped: %s', $lineNumber, $validationError);
                continue;
            }

            $ingredient = $this->ingredientRepository->findOneByNormalizedNameAndUnit($rowData['name'], $rowData['unit']);
            if (!$ingredient instanceof Ingredient) {
                $ingredient = (new Ingredient())->setCreatedAt(new \DateTimeImmutable());
                ++$created;
                $this->entityManager->persist($ingredient);
            } else {
                ++$updated;
            }

            $ingredient
                ->setName($rowData['name'])
                ->setUnit($rowData['unit'])
                ->setQuantityInStock($rowData['quantityInStock'])
                ->setMinStockLevel($rowData['minStockLevel'])
                ->setUnitCost($rowData['unitCost'])
                ->setExpiryDate($rowData['expiryDate']);
        }

        fclose($handle);
        $this->entityManager->flush();

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'processed' => $processed,
            'errors' => $errors,
            'headerDetected' => $headerDetected,
            'delimiter' => $delimiter,
        ];
    }

    private function detectDelimiter(string $rawLine): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestScore = 0;

        foreach ($delimiters as $delimiter) {
            $score = count(str_getcsv($rawLine, $delimiter));
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }

    /**
     * @param string[] $headerRow
     *
     * @return array<string, int>|null
     */
    private function detectHeaderMapping(array $headerRow): ?array
    {
        $normalized = array_map([$this, 'normalizeHeaderCell'], $headerRow);
        $mapping = [];

        foreach (self::HEADER_ALIASES as $targetField => $aliases) {
            foreach ($normalized as $index => $column) {
                if (in_array($column, $aliases, true)) {
                    $mapping[$targetField] = $index;
                    break;
                }
            }
        }

        if (count($mapping) < 4 || !isset($mapping['name'], $mapping['quantityInStock'], $mapping['unit'])) {
            return null;
        }

        $mapping += [
            'minStockLevel' => 3,
            'unitCost' => 4,
            'expiryDate' => 5,
        ];

        return $mapping;
    }

    /**
     * @param string[] $row
     * @param array<string, int> $mapping
     *
     * @return array<string, mixed>
     */
    private function extractRowData(array $row, array $mapping): array
    {
        $name = trim((string) ($row[$mapping['name']] ?? ''));
        $unit = trim((string) ($row[$mapping['unit']] ?? ''));
        $quantityRaw = trim((string) ($row[$mapping['quantityInStock']] ?? ''));
        $minRaw = trim((string) ($row[$mapping['minStockLevel']] ?? ''));
        $costRaw = trim((string) ($row[$mapping['unitCost']] ?? ''));
        $expiryRaw = trim((string) ($row[$mapping['expiryDate']] ?? ''));

        return [
            'name' => $name,
            'unit' => $unit,
            'quantityInStock' => $this->parseNumber($quantityRaw),
            'minStockLevel' => $this->parseNumber($minRaw),
            'unitCost' => $this->parseNumber($costRaw),
            'expiryDate' => $this->parseDate($expiryRaw),
            'raw' => [
                'quantity' => $quantityRaw,
                'min' => $minRaw,
                'cost' => $costRaw,
                'expiry' => $expiryRaw,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $rowData
     */
    private function validateRowData(array $rowData): ?string
    {
        if ('' === $rowData['name']) {
            return 'Ingredient name is required.';
        }

        if ('' === $rowData['unit']) {
            return 'Unit is required.';
        }

        if (!is_float($rowData['quantityInStock']) || $rowData['quantityInStock'] < 0) {
            return sprintf('Invalid quantity "%s".', (string) $rowData['raw']['quantity']);
        }

        if (!is_float($rowData['minStockLevel']) || $rowData['minStockLevel'] < 0) {
            return sprintf('Invalid min stock "%s".', (string) $rowData['raw']['min']);
        }

        if (!is_float($rowData['unitCost']) || $rowData['unitCost'] < 0) {
            return sprintf('Invalid unit cost "%s".', (string) $rowData['raw']['cost']);
        }

        if (!$rowData['expiryDate'] instanceof \DateTimeImmutable) {
            return sprintf('Invalid expiry date "%s".', (string) $rowData['raw']['expiry']);
        }

        if ($rowData['minStockLevel'] > $rowData['quantityInStock']) {
            return 'Minimum stock cannot exceed quantity in stock.';
        }

        return null;
    }

    private function parseNumber(string $value): ?float
    {
        if ('' === $value) {
            return null;
        }

        $normalized = str_replace([' ', ','], ['', '.'], $value);
        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        if ('' === $value) {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
        foreach ($formats as $format) {
            $parsed = \DateTimeImmutable::createFromFormat($format, $value);
            if ($parsed instanceof \DateTimeImmutable) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * @param string[] $row
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ('' !== trim($cell)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeaderCell(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace([' ', '-', '.'], '_', $value);

        return preg_replace('/[^a-z0-9_]/', '', $value) ?? '';
    }

    private function removeUtf8Bom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }
}
