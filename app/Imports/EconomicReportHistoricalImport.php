<?php

namespace App\Imports;

use App\Models\ActivityTransaction;
use App\Models\ActivityTransactionCategory;
use App\Models\FundSource;
use App\Models\ProductiveActivity;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EconomicReportHistoricalImport
{
    protected int $defaultYear;
    protected array $importSummary = [
        'total_sheets_processed'    => 0,
        'total_rows_imported'       => 0,
        'total_transactions_created'=> 0,
        'total_amount_imported'     => 0.0,
        'pending_manual_review'     => [],
        'assumptions_applied'       => [],
        'activities_detected'       => [],
    ];

    public function __construct(int $defaultYear = 2025)
    {
        $this->defaultYear = $defaultYear;
        $this->recordAssumption('Año fiscal asignado: ' . $defaultYear . ' salvo que la celda especifique explícitamente otro ejercicio.');
        $this->recordAssumption('Día de transacción: Se asigna el día 15 de cada mes para consolidación mensual cuando no exista fecha puntual.');
        $this->recordAssumption('Fuente de Financiamiento: Si la fila no explicita "Banco de la Nación", "Coop. Tocache" o "Caja", se asigna la fuente por defecto de la actividad.');
    }

    /**
     * Importa el archivo Excel procesando cada hoja por actividad
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheetNames = $spreadsheet->getSheetNames();

        $defaultFundSource = FundSource::where('code', 'BN')
            ->orWhere('code', 'BN-09-RDR')
            ->orWhere('name', 'like', '%Nación%')
            ->first() ?? FundSource::where('is_active', true)->first();

        foreach ($sheetNames as $sheetName) {
            $normalizedName = mb_strtoupper(trim($sheetName));

            // Omitir hojas de resumen consolidado, parámetros o portadas
            if (
                str_contains($normalizedName, 'RESUMEN') ||
                str_contains($normalizedName, 'SUMMARY') ||
                str_contains($normalizedName, 'CONSOLIDADO') ||
                str_contains($normalizedName, 'GRAFICO') ||
                str_contains($normalizedName, 'PARAMETRO') ||
                str_contains($normalizedName, 'PORTADA') ||
                str_contains($normalizedName, 'INDICE')
            ) {
                continue;
            }

            $worksheet = $spreadsheet->getSheetByName($sheetName);
            $this->processActivitySheet($worksheet, $sheetName, $defaultFundSource);
        }

        return $this->importSummary;
    }

    /**
     * Procesa una hoja correspondiente a una actividad específica
     */
    protected function processActivitySheet(Worksheet $worksheet, string $sheetTitle, ?FundSource $defaultFundSource): void
    {
        $this->importSummary['total_sheets_processed']++;

        // 1. Detectar o crear la Actividad Productiva
        $activity = $this->resolveOrCreateActivity($sheetTitle);
        $this->importSummary['activities_detected'][] = $activity->name;

        $rows = $worksheet->toArray(null, true, true, true);
        if (empty($rows)) {
            return;
        }

        $currentSection = null; // 'INCOME' o 'EXPENSE'
        $monthColumnMap = []; // ['A' => 1, 'B' => 2, ...]
        $conceptCol = null;
        $fundSourceCol = null;

        foreach ($rows as $rowIndex => $row) {
            $rowValues = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);
            $rowText = mb_strtoupper(implode(' ', array_filter($rowValues, 'is_string')));

            // Detectar inicio de bloque INGRESOS
            if (str_contains($rowText, 'INGRESOS') || str_contains($rowText, 'VENTAS') || str_contains($rowText, 'RECAUDACIÓN')) {
                $currentSection = 'INCOME';
                $monthColumnMap = $this->detectMonthColumns($row);
                continue;
            }

            // Detectar inicio de bloque EGRESOS / GASTOS
            if (str_contains($rowText, 'EGRESOS') || str_contains($rowText, 'GASTOS') || str_contains($rowText, 'COSTOS')) {
                $currentSection = 'EXPENSE';
                $monthColumnMap = $this->detectMonthColumns($row);
                continue;
            }

            // Si es fila de encabezados de meses
            $detectedMonths = $this->detectMonthColumns($row);
            if (count($detectedMonths) >= 3) {
                $monthColumnMap = $detectedMonths;
                $conceptCol = $this->detectConceptColumn($row);
                $fundSourceCol = $this->detectFundSourceColumn($row);
                continue;
            }

            // Si no estamos en sección activa o no hay mapeo de meses, continuar
            if (!$currentSection || empty($monthColumnMap)) {
                continue;
            }

            // Omitir filas de TOTALES, RESÚMENES o SUBTOTALES
            if (str_contains($rowText, 'TOTAL') || str_contains($rowText, 'SUBTOTAL') || str_contains($rowText, 'SALDO')) {
                continue;
            }

            // Obtener el concepto / rubro de la fila
            $concept = $this->extractConcept($row, $conceptCol);
            if (empty($concept) || mb_strlen($concept) < 3) {
                continue;
            }

            // Detectar fuente de fondos específica de la fila
            $fundSource = $this->extractFundSource($row, $fundSourceCol, $activity, $defaultFundSource);

            // Obtener o auto-crear categoría en activity_transaction_categories
            $category = $this->resolveOrCreateCategory($concept, $currentSection, $activity);

            // Procesar montos de cada mes
            $hasAnyAmount = false;
            foreach ($monthColumnMap as $colLetter => $monthNumber) {
                $rawVal = $row[$colLetter] ?? null;
                $amount = $this->cleanNumericAmount($rawVal);

                if ($amount !== null && $amount > 0) {
                    $hasAnyAmount = true;

                    ActivityTransaction::create([
                        'transaction_code'       => 'IMP-' . $this->defaultYear . '-' . sprintf('%02d', $monthNumber) . '-' . strtoupper(Str::random(10)),
                        'productive_activity_id' => $activity->id,
                        'category_id'            => $category->id,
                        'fund_source_id'         => $fundSource->id,
                        'transaction_type'       => $currentSection,
                        'amount'                 => $amount,
                        'transaction_date'       => Carbon::create($this->defaultYear, $monthNumber, 15)->format('Y-m-d'),
                        'period_month'           => $monthNumber,
                        'period_year'            => $this->defaultYear,
                        'voucher_type'           => 'MIGRACIÓN_EXCEL',
                        'voucher_number'         => 'HOJA-' . substr($sheetTitle, 0, 20),
                        'concept'                => $concept . ' (Mes: ' . $monthNumber . ')',
                        'status'                 => 'VERIFIED',
                        'registered_by_user_id'  => 1,
                    ]);

                    $this->importSummary['total_transactions_created']++;
                    $this->importSummary['total_amount_imported'] += $amount;
                } elseif ($rawVal !== null && !empty($rawVal) && $amount === null) {
                    // Valor ambiguo o no numérico en celda de mes
                    $this->recordPendingReview($sheetTitle, $rowIndex, $colLetter, $concept, $rawVal, 'Valor no numérico o fórmula inválida en mes ' . $monthNumber);
                }
            }

            if ($hasAnyAmount) {
                $this->importSummary['total_rows_imported']++;
            }
        }
    }

    /**
     * Resuelve o auto-crea una Actividad Productiva a partir del nombre de la hoja
     */
    protected function resolveOrCreateActivity(string $sheetTitle): ProductiveActivity
    {
        $cleanTitle = trim($sheetTitle);
        $activity = ProductiveActivity::where('name', 'like', '%' . $cleanTitle . '%')->first();

        if ($activity) {
            return $activity;
        }

        // Determinar tipo tentativo según nombre
        $type = 'AGRICULTURAL';
        $upper = mb_strtoupper($cleanTitle);
        if (str_contains($upper, 'PALMA') || str_contains($upper, 'GAVILAN') || str_contains($upper, 'CACAO') || str_contains($upper, 'CHACRA')) {
            $type = 'AGRICULTURAL';
        } elseif (str_contains($upper, 'FORESTAL') || str_contains($upper, 'VIVERO') || str_contains($upper, 'MADERA')) {
            $type = 'FORESTRY';
        } elseif (str_contains($upper, 'PISCI') || str_contains($upper, 'PEZ') || str_contains($upper, 'ALEVIN')) {
            $type = 'AQUACULTURE';
        } elseif (str_contains($upper, 'PECUARI') || str_contains($upper, 'GANAD') || str_contains($upper, 'CERD') || str_contains($upper, 'CUY')) {
            $type = 'LIVESTOCK';
        } elseif (str_contains($upper, 'IDIOMA') || str_contains($upper, 'SERVICIO') || str_contains($upper, 'TALLER')) {
            $type = 'SERVICES';
        }

        $baseSlug = Str::slug(substr($cleanTitle, 0, 8), '');
        $code = 'ACT-' . strtoupper($baseSlug ?: Str::random(6));
        while (ProductiveActivity::where('code', $code)->exists()) {
            $code = 'ACT-' . strtoupper(substr($baseSlug, 0, 5)) . '-' . strtoupper(Str::random(4));
        }

        return ProductiveActivity::create([
            'code'                      => $code,
            'name'                      => $cleanTitle,
            'type'                      => $type,
            'cost_center_code'          => 'CC-' . strtoupper(substr($cleanTitle, 0, 6)),
            'responsible_area_id'       => 1,
            'status'                    => 'ACTIVA',
            'execution_progress_percent'=> 100.0,
            'monitoring_observations'   => 'Creada automáticamente por importador histórico de Excel.',
        ]);
    }

    /**
     * Resuelve o crea categorías jerárquicas dinámicas
     */
    protected function resolveOrCreateCategory(string $concept, string $type, ProductiveActivity $activity): ActivityTransactionCategory
    {
        $cleanConcept = trim($concept);
        $category = ActivityTransactionCategory::where('name', $cleanConcept)
            ->where('type', $type)
            ->first();

        if ($category) {
            return $category;
        }

        $code = 'CAT-' . strtoupper(Str::random(5));

        return ActivityTransactionCategory::create([
            'code'        => $code,
            'name'        => $cleanConcept,
            'type'        => $type,
            'is_active'   => true,
            'description' => 'Auto-creada por importación Excel para ' . $activity->name,
        ]);
    }

    /**
     * Detecta dinámicamente columnas de meses
     */
    protected function detectMonthColumns(array $row): array
    {
        $map = [];
        $monthRegexes = [
            1 => '/(ENE|ENERO|JAN)/i',
            2 => '/(FEB|FEBRERO)/i',
            3 => '/(MAR|MARZO)/i',
            4 => '/(ABR|ABRIL|APR)/i',
            5 => '/(MAY|MAYO)/i',
            6 => '/(JUN|JUNIO)/i',
            7 => '/(JUL|JULIO)/i',
            8 => '/(AGO|AGOSTO|AUG)/i',
            9 => '/(SET|SEP|SETIEMBRE|SEPTIEMBRE)/i',
            10 => '/(OCT|OCTUBRE)/i',
            11 => '/(NOV|NOVIEMBRE)/i',
            12 => '/(DIC|DICIEMBRE|DEC)/i',
        ];

        foreach ($row as $col => $val) {
            if (!is_string($val)) continue;
            $str = trim($val);
            foreach ($monthRegexes as $mNum => $regex) {
                if (preg_match($regex, $str)) {
                    $map[$col] = $mNum;
                    break;
                }
            }
        }

        return $map;
    }

    protected function detectConceptColumn(array $row): ?string
    {
        foreach ($row as $col => $val) {
            if (!is_string($val)) continue;
            $str = mb_strtoupper(trim($val));
            if (str_contains($str, 'CONCEPTO') || str_contains($str, 'DESCRIPCI') || str_contains($str, 'RUBRO') || str_contains($str, 'DETALLE')) {
                return $col;
            }
        }
        return 'B'; // Por defecto columna B
    }

    protected function detectFundSourceColumn(array $row): ?string
    {
        foreach ($row as $col => $val) {
            if (!is_string($val)) continue;
            $str = mb_strtoupper(trim($val));
            if (str_contains($str, 'FUENTE') || str_contains($str, 'BANCO') || str_contains($str, 'CUENTA') || str_contains($str, 'FONDOS')) {
                return $col;
            }
        }
        return null;
    }

    protected function extractConcept(array $row, ?string $conceptCol): ?string
    {
        if ($conceptCol && !empty($row[$conceptCol])) {
            return trim($row[$conceptCol]);
        }

        // Buscar la primera celda de texto que no sea un número ni un mes
        foreach (['B', 'A', 'C'] as $c) {
            if (!empty($row[$c]) && is_string($row[$c]) && !is_numeric($row[$c])) {
                return trim($row[$c]);
            }
        }

        return null;
    }

    protected function extractFundSource(array $row, ?string $fundSourceCol, ProductiveActivity $activity, ?FundSource $default): FundSource
    {
        if ($fundSourceCol && !empty($row[$fundSourceCol])) {
            $txt = mb_strtoupper(trim($row[$fundSourceCol]));
            if (str_contains($txt, 'TOCACHE') || str_contains($txt, 'COOP')) {
                return FundSource::where('code', 'COOP_TOCACHE')
                    ->orWhere('code', 'COOP-TOCACHE')
                    ->orWhere('name', 'like', '%Tocache%')
                    ->first() ?? $default;
            }
            if (str_contains($txt, 'CAJA') || str_contains($txt, 'EFECTIVO')) {
                return FundSource::where('code', 'CAJA_CHICA_INST')
                    ->orWhere('code', 'CAJA-APE')
                    ->orWhere('name', 'like', '%Caja%')
                    ->first() ?? $default;
            }
            if (str_contains($txt, 'BANCO') || str_contains($txt, 'NACION') || str_contains($txt, 'BN') || str_contains($txt, 'RDR')) {
                return FundSource::where('code', 'BN')
                    ->orWhere('code', 'BN-09-RDR')
                    ->orWhere('name', 'like', '%Nación%')
                    ->first() ?? $default;
            }
        }

        return $activity->defaultFundSource ?? $default ?? FundSource::first();
    }

    protected function cleanNumericAmount(mixed $val): ?float
    {
        if ($val === null || $val === '' || $val === '-') {
            return null;
        }

        $amount = null;
        if (is_numeric($val)) {
            $amount = (float) $val;
        } elseif (is_string($val)) {
            // Remover símbolos de moneda y espacios
            $clean = str_replace(['S/', 's/', '$', ' ', ',', 'S/.'], ['', '', '', '', '', ''], trim($val));
            if (is_numeric($clean)) {
                $amount = (float) $clean;
            }
        }

        if ($amount !== null) {
            // Protección contra desbordamiento numérico en decimal(14,2)
            if ($amount > 999999999.99) {
                $this->recordAssumption("Monto {$amount} excede el límite operacional; ajustado para evitar desbordamiento.");
                return 99999999.99;
            }
            return $amount;
        }

        return null;
    }

    protected function recordAssumption(string $text): void
    {
        $this->importSummary['assumptions_applied'][] = $text;
    }

    protected function recordPendingReview(string $sheet, int $row, string $col, string $concept, mixed $rawVal, string $reason): void
    {
        $this->importSummary['pending_manual_review'][] = [
            'sheet'   => $sheet,
            'row'     => $row,
            'col'     => $col,
            'concept' => $concept,
            'value'   => (string) $rawVal,
            'reason'  => $reason,
        ];
    }
}
