<?php

namespace App\Services\Treasury;

use App\Models\BankAccount;
use App\Models\BankMovement;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BankStatementImportService
{
    /**
     * Standard template headers for statement import.
     */
    public function getTemplateHeaders(): array
    {
        return [
            'fecha',             // YYYY-MM-DD o DD/MM/YYYY
            'numero_operacion',  // Referencia única / N° Operación bancaria
            'tipo',              // INFLOW (Abono) o OUTFLOW (Cargo)
            'monto',             // 1250.00
            'concepto',          // Descripción o glosa del extracto
        ];
    }

    /**
     * Generate sample CSV template content.
     */
    public function generateTemplateCsv(): string
    {
        $headers = implode(',', $this->getTemplateHeaders());
        $sample1 = '2026-05-15,OP-987452,INFLOW,5400.00,Abono por venta institucional';
        $sample2 = '2026-05-16,CHQ-001245,OUTFLOW,1200.00,Pago a proveedor comercial';
        $sample3 = '2026-05-31,ITF-000001,OUTFLOW,4.50,Comision bancaria e ITF';

        return "{$headers}\n{$sample1}\n{$sample2}\n{$sample3}\n";
    }

    /**
     * Import statement from an array of rows or CSV/Excel file.
     *
     * Deduplication rule: (bank_account_id, movement_date, operation_number, amount)
     *
     * @param BankAccount $bankAccount
     * @param array|UploadedFile|string $data Array of rows or file path / uploaded file
     * @return array Summary of import with counts and created movements
     */
    public function import(BankAccount $bankAccount, mixed $data): array
    {
        $rows = is_array($data) ? $data : $this->parseFileToRows($data);

        if (empty($rows)) {
            throw new DomainException("El archivo o conjunto de datos no contiene filas para importar.");
        }

        $batchId = 'BATCH-' . date('YmdHis') . '-' . strtoupper(Str::random(4));
        $imported = collect();
        $duplicatesCount = 0;
        $totalRows = count($rows);

        DB::transaction(function () use ($bankAccount, $rows, $batchId, &$imported, &$duplicatesCount) {
            foreach ($rows as $index => $row) {
                $parsed = $this->normalizeRow($row, $index + 1);
                if (!$parsed) {
                    continue;
                }

                // DEDUPLICATION CHECK: date + reference (operation_number) + amount for this bank account
                $exists = BankMovement::query()
                    ->where('bank_account_id', $bankAccount->id)
                    ->where('movement_date', $parsed['movement_date'])
                    ->where('operation_number', $parsed['operation_number'])
                    ->where('amount', $parsed['amount'])
                    ->exists();

                if ($exists) {
                    $duplicatesCount++;
                    continue;
                }

                $movement = BankMovement::create([
                    'uuid'                  => (string) Str::uuid(),
                    'bank_account_id'       => $bankAccount->id,
                    'movement_date'         => $parsed['movement_date'],
                    'operation_number'      => $parsed['operation_number'],
                    'movement_type'         => $parsed['movement_type'],
                    'amount'                => $parsed['amount'],
                    'concept'               => $parsed['concept'],
                    'reconciliation_status' => 'PENDING',
                    'import_batch_id'       => $batchId,
                ]);

                $imported->push($movement);
            }
        });

        return [
            'total_rows'         => $totalRows,
            'imported_count'     => $imported->count(),
            'duplicates_skipped' => $duplicatesCount,
            'batch_id'           => $batchId,
            'movements'          => $imported,
        ];
    }

    /**
     * Parse CSV file or uploaded file into array of associative rows.
     */
    protected function parseFileToRows(mixed $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : (string) $file;

        if (!file_exists($path) || !is_readable($path)) {
            throw new DomainException("No se puede leer el archivo de extracto bancario en la ruta indicada: {$path}");
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new DomainException("Error al abrir el archivo.");
        }

        $rows = [];
        $header = null;
        $delimiter = ',';

        // Auto-detect delimiter (, or ;)
        $firstLine = fgets($handle);
        rewind($handle);
        if ($firstLine && substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        }

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
            if (!$header) {
                // Sanitize header keys
                $header = array_map(function ($col) {
                    $cleaned = trim(strtolower((string) $col));
                    $cleaned = preg_replace('/[\x{FEFF}]/u', '', $cleaned); // remove BOM
                    return str_replace([' ', '-', '.'], '_', $cleaned);
                }, $data);
                continue;
            }

            if (empty(array_filter($data))) {
                continue;
            }

            $row = [];
            foreach ($header as $idx => $colName) {
                $row[$colName] = $data[$idx] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Normalize flexible row columns into standardized schema.
     */
    protected function normalizeRow(array $row, int $lineNum): ?array
    {
        // 1. Date resolution
        $rawDate = $row['fecha'] ?? $row['date'] ?? $row['movement_date'] ?? $row['fec_operacion'] ?? null;
        if (!$rawDate) {
            return null; // Empty line
        }

        try {
            $date = Carbon::parse(str_replace('/', '-', trim((string) $rawDate)))->format('Y-m-d');
        } catch (\Throwable $e) {
            throw new DomainException("Línea {$lineNum}: Formato de fecha inválido '{$rawDate}'. Use YYYY-MM-DD o DD/MM/YYYY.");
        }

        // 2. Reference / Operation number
        $ref = $row['numero_operacion'] ?? $row['operacion'] ?? $row['referencia'] ?? $row['voucher'] ?? $row['operation_number'] ?? null;
        if (!$ref) {
            $ref = 'MOV-' . strtoupper(Str::random(8));
        } else {
            $ref = trim((string) $ref);
        }

        // 3. Amount and Movement Type
        $rawAmount = $row['monto'] ?? $row['amount'] ?? $row['importe'] ?? 0;
        $rawType = strtoupper(trim((string) ($row['tipo'] ?? $row['movement_type'] ?? '')));

        // Separate cargo / abono column support
        if (isset($row['cargo']) && floatval(str_replace(',', '', (string) $row['cargo'])) > 0) {
            $rawAmount = floatval(str_replace(',', '', (string) $row['cargo']));
            $rawType = 'OUTFLOW';
        } elseif (isset($row['abono']) && floatval(str_replace(',', '', (string) $row['abono'])) > 0) {
            $rawAmount = floatval(str_replace(',', '', (string) $row['abono']));
            $rawType = 'INFLOW';
        }

        $amount = round(abs((float) str_replace(',', '', (string) $rawAmount)), 2);
        if ($amount <= 0) {
            return null; // Zero amount movement ignored
        }

        // Determine movement type if not explicitly set
        if (!in_array($rawType, ['INFLOW', 'OUTFLOW'])) {
            if (in_array($rawType, ['ABONO', 'INGRESO', 'DEPOSITO', 'CREDITO', 'C'])) {
                $rawType = 'INFLOW';
            } elseif (in_array($rawType, ['CARGO', 'EGRESO', 'RETIRO', 'DEBITO', 'D'])) {
                $rawType = 'OUTFLOW';
            } else {
                // If amount in row was negative, infer OUTFLOW, else INFLOW
                $numVal = floatval(str_replace(',', '', (string) $rawAmount));
                $rawType = $numVal < 0 ? 'OUTFLOW' : 'INFLOW';
            }
        }

        // 4. Concept / Description
        $concept = $row['concepto'] ?? $row['concept'] ?? $row['descripcion'] ?? $row['glosa'] ?? 'Movimiento bancario según extracto';
        $concept = Str::limit(trim((string) $concept), 250);

        return [
            'movement_date'    => $date,
            'operation_number' => $ref,
            'movement_type'    => $rawType,
            'amount'           => $amount,
            'concept'          => $concept,
        ];
    }
}
