<?php

namespace App\Imports;

use App\Models\Area;
use App\Models\Asset;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class AssetsImport implements ToCollection
{
    protected ?int $targetAreaId;
    protected ?int $userId;
    protected bool $overwrite;
    protected int $createdCount = 0;
    protected int $updatedCount = 0;

    public function __construct(?int $targetAreaId = null, ?int $userId = null, bool $overwrite = false) {
        $this->targetAreaId = $targetAreaId;
        $this->userId = $userId;
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows) {
        if ($rows->isEmpty()) {
            throw new Exception('El archivo Excel está vacío.');
        }

        // Si no se proporcionó un área explícita, intentar detectarla en las primeras filas
        $resolvedAreaId = $this->targetAreaId;
        if (! $resolvedAreaId) {
            $resolvedAreaId = $this->detectAreaFromRows($rows);
        }

        if (! $resolvedAreaId) {
            $firstArea = Area::query()->first();
            $resolvedAreaId = $firstArea?->id;
        }

        if (! $resolvedAreaId) {
            throw new Exception('No se pudo determinar el departamento o área institucional para los bienes.');
        }

        $area = Area::query()->findOrFail($resolvedAreaId);

        DB::transaction(function () use ($rows, $area) {
            // Detectar la fila de inicio de datos
            $headerRowIndex = $this->findHeaderRowIndex($rows);
            $startIndex = $headerRowIndex !== null ? $headerRowIndex + 1 : 0;

            // Si la siguiente fila es un subencabezado (como 'B', 'R', 'M', 'BAJA', 'C', 'D'), saltarla
            if (isset($rows[$startIndex])) {
                $checkRow = $this->rowToArray($rows[$startIndex]);
                $joined = mb_strtoupper(implode(' ', array_filter(array_map('strval', $checkRow))));
                if (Str::contains($joined, ['BAJA', 'TIPO ADQ', 'CONDICION', 'CONDICIÓN']) || 
                    (in_array('B', $checkRow, true) && in_array('R', $checkRow, true))) {
                    $startIndex++;
                }
            }

            for ($i = $startIndex; $i < count($rows); $i++) {
                $row = $this->rowToArray($rows[$i]);

                // Verificar si es una fila de datos válida
                if (! $this->isValidDataRow($row)) {
                    continue;
                }

                $this->processRow($row, $area);
            }
        });
    }

    protected function processRow(array $row, Area $area): void {
        // Columna A (0): N° ORD
        $rawOrden = trim((string) ($row[0] ?? ''));
        $orden = is_numeric($rawOrden) ? (int) $rawOrden : null;

        // Columna B (1): CÓDIGO PRODUCTO (SBN)
        $rawCodProducto = trim((string) ($row[1] ?? ''));
        $codigoProducto = $rawCodProducto !== '' ? $rawCodProducto : null;

        // Columna C (2): CÓDIGO (Patrimonial Interno)
        $rawCodigo = trim((string) ($row[2] ?? ''));
        $codigo = $rawCodigo !== '' ? $rawCodigo : null;

        // Columna D (3): DESCRIPCIÓN
        $descripcion = mb_strtoupper(trim((string) ($row[3] ?? '')));
        if ($descripcion === '') {
            return;
        }

        // Columna E (4): MARCA
        $rawMarca = trim((string) ($row[4] ?? ''));
        $marca = ($rawMarca !== '' && mb_strtoupper($rawMarca) !== 'SIN MARCA') ? mb_strtoupper($rawMarca) : 'SIN MARCA';

        // Columna F (5): MODELO
        $rawModelo = trim((string) ($row[5] ?? ''));
        $modelo = ($rawModelo !== '' && mb_strtoupper($rawModelo) !== 'SIN MODELO') ? mb_strtoupper($rawModelo) : 'SIN MODELO';

        // Columna G (6): SERIE
        $rawSerie = trim((string) ($row[6] ?? ''));
        $serie = ($rawSerie !== '' && mb_strtoupper($rawSerie) !== 'SIN SERIE') ? mb_strtoupper($rawSerie) : 'SIN SERIE';

        // Columna H (7): COSTO
        $rawCosto = trim((string) ($row[7] ?? '0'));
        $costo = is_numeric($rawCosto) ? (float) $rawCosto : 0.00;

        // Columnas I, J, K, L (8, 9, 10, 11): CONDICIÓN (B, R, M, BAJA)
        $condicion = $this->resolveCondition($row);

        // Columnas M, N (12, 13): TIPO ADQUISICIÓN (C, D)
        $tipoAdquisicion = $this->resolveAdquisitionType($row);

        // Columna O (14): AÑO ADQUISICIÓN
        $rawAnio = trim((string) ($row[14] ?? ''));
        $anio = null;
        $fechaAdq = null;
        if (is_numeric($rawAnio) && (int) $rawAnio >= 1950 && (int) $rawAnio <= 2100) {
            $anio = (int) $rawAnio;
            $fechaAdq = "{$anio}-01-01";
        } elseif ($rawAnio !== '') {
            try {
                $parsedDate = Carbon::parse($rawAnio);
                $anio = (int) $parsedDate->format('Y');
                $fechaAdq = $parsedDate->format('Y-m-d');
            } catch (\Throwable) {
                // Dejar nulo si no es fecha válida
            }
        }

        // Columna P (15): UBICACIÓN
        $rawUbicacion = trim((string) ($row[15] ?? ''));
        $ubicacion = $rawUbicacion !== '' ? mb_strtoupper($rawUbicacion) : mb_strtoupper($area->name);

        // Columna Q (16): OBSERVACIÓN
        $rawObs = trim((string) ($row[16] ?? ''));
        $observaciones = $rawObs !== '' ? $rawObs : null;

        // Buscar si ya existe un bien para actualizar o insertar
        $existingAsset = null;
        if ($codigo !== null) {
            $existingAsset = Asset::query()
                ->where('area_id', $area->id)
                ->where('codigo', $codigo)
                ->first();
        } elseif ($serie !== 'SIN SERIE') {
            $existingAsset = Asset::query()
                ->where('area_id', $area->id)
                ->where('serie', $serie)
                ->first();
        }

        if ($existingAsset && $this->overwrite) {
            $existingAsset->update([
                'codigo_producto'   => $codigoProducto ?? $existingAsset->codigo_producto,
                'descripcion'       => $descripcion,
                'marca'             => $marca,
                'modelo'            => $modelo,
                'serie'             => $serie,
                'costo'             => $costo,
                'condicion'         => $condicion,
                'tipo_adquisicion'  => $tipoAdquisicion,
                'anio_adquisicion'  => $anio ?? $existingAsset->anio_adquisicion,
                'fecha_adquisicion' => $fechaAdq ?? $existingAsset->fecha_adquisicion,
                'ubicacion'         => $ubicacion,
                'observaciones'     => $observaciones ?? $existingAsset->observaciones,
            ]);
            $this->updatedCount++;
        } elseif (! $existingAsset) {
            $assetData = [
                'area_id'           => $area->id,
                'user_id'           => $this->userId,
                'codigo_producto'   => $codigoProducto,
                'codigo'            => $codigo,
                'descripcion'       => $descripcion,
                'marca'             => $marca,
                'modelo'            => $modelo,
                'serie'             => $serie,
                'costo'             => $costo,
                'condicion'         => $condicion,
                'tipo_adquisicion'  => $tipoAdquisicion,
                'anio_adquisicion'  => $anio,
                'fecha_adquisicion' => $fechaAdq,
                'ubicacion'         => $ubicacion,
                'observaciones'     => $observaciones,
            ];

            if ($orden !== null && $orden > 0) {
                $assetData['orden'] = $orden;
            }

            Asset::create($assetData);
            $this->createdCount++;
        }
    }

    protected function resolveCondition(array $row): string {
        // En formato institucional (subcolumnas 8=B, 9=R, 10=M, 11=BAJA):
        if (! empty($row[8]) && in_array(mb_strtolower(trim((string) $row[8])), ['x', 'si', '1', 'b'], true)) {
            return 'B';
        }
        if (! empty($row[9]) && in_array(mb_strtolower(trim((string) $row[9])), ['x', 'si', '1', 'r'], true)) {
            return 'R';
        }
        if (! empty($row[10]) && in_array(mb_strtolower(trim((string) $row[10])), ['x', 'si', '1', 'm'], true)) {
            return 'M';
        }
        if (! empty($row[11]) && in_array(mb_strtolower(trim((string) $row[11])), ['x', 'si', '1', 'baja', 'w'], true)) {
            return 'BAJA';
        }

        // Si se encuentra en una celda única
        $possibleVal = trim((string) ($row[8] ?? ''));
        return Asset::normalizeCondition($possibleVal);
    }

    protected function resolveAdquisitionType(array $row): string {
        // En formato institucional (subcolumnas 12=C, 13=D):
        if (! empty($row[12]) && in_array(mb_strtolower(trim((string) $row[12])), ['x', 'si', '1', 'c'], true)) {
            return 'C';
        }
        if (! empty($row[13]) && in_array(mb_strtolower(trim((string) $row[13])), ['x', 'si', '1', 'd'], true)) {
            return 'D';
        }

        $possibleVal = trim((string) ($row[12] ?? ''));
        return Asset::normalizeAdquisitionType($possibleVal);
    }

    protected function isValidDataRow(array $row): bool {
        $col0 = trim((string) ($row[0] ?? ''));
        $col3 = trim((string) ($row[3] ?? ''));

        if ($col3 === '') {
            return false;
        }

        $upperCol3 = mb_strtoupper($col3);
        if (Str::contains($upperCol3, ['DESCRIPCIÓN', 'DESCRIPCION', 'INSTITUTO', 'MINISTERIO', 'RESPONSABLE', 'TOTAL', 'PAGE'])) {
            return false;
        }

        // Si tiene N° de orden numérico o una descripción coherente
        return is_numeric($col0) || mb_strlen($col3) >= 3;
    }

    protected function findHeaderRowIndex(Collection $rows): ?int {
        foreach ($rows as $index => $row) {
            $line = mb_strtoupper(implode(' ', array_filter(array_map('strval', $this->rowToArray($row)))));
            if (Str::contains($line, ['Nº ORD', 'N° ORD', 'ORD']) && Str::contains($line, ['DESCRIPCIÓN', 'DESCRIPCION', 'CODIGO'])) {
                return (int) $index;
            }
        }
        return null;
    }

    protected function detectAreaFromRows(Collection $rows): ?int {
        for ($i = 0; $i < min(10, count($rows)); $i++) {
            $line = mb_strtoupper(implode(' ', array_filter(array_map('strval', $this->rowToArray($rows[$i])))));
            if (Str::contains($line, ['ÁREA', 'AREA', 'INVENTARIO GENERAL'])) {
                $areas = Area::all();
                foreach ($areas as $a) {
                    if (Str::contains($line, mb_strtoupper($a->name)) || Str::contains($line, mb_strtoupper($a->code))) {
                        return (int) $a->id;
                    }
                }
            }
        }
        return null;
    }

    protected function rowToArray($row): array {
        if ($row instanceof Collection) {
            return $row->toArray();
        }
        return (array) $row;
    }

    public function getSummary(): array {
        return [
            'created' => $this->createdCount,
            'updated' => $this->updatedCount,
            'total'   => $this->createdCount + $this->updatedCount,
        ];
    }
}
