<?php

namespace App\Services\Services;

use App\Models\ServiceAttendee;
use App\Models\ServiceEngagement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ParticipantImportService
{
    /**
     * Import participants from Excel or CSV file.
     */
    public function import(ServiceEngagement $engagement, UploadedFile $file, ?int $sessionId = null): array {
        $extension  = strtolower($file->getClientOriginalExtension());
        $rows       = [];

        if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
            try {
                $rawCollection = Excel::toCollection(null, $file);
                if ($rawCollection->isNotEmpty() && $rawCollection->first()->isNotEmpty()) {
                    $sheet  = $rawCollection->first();
                    $header = $sheet->first()->map(fn($col) => strtolower(trim((string)$col)))->toArray();

                    foreach ($sheet->slice(1) as $row) {
                        $rowData = [];
                        foreach ($row as $colIdx => $val) {
                            $colName = $header[$colIdx] ?? (string)$colIdx;
                            $rowData[$colName] = is_string($val) ? trim($val) : $val;
                        }
                        $rows[] = $rowData;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to manual CSV reading if Excel parsing encounters any format quirk
                if ($extension === 'csv') {
                    $rows = $this->readCsvFallback($file->getRealPath());
                } else {
                    throw $e;
                }
            }
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $dni    = $this->extractField($row, ['dni', 'documento', 'nro_documento', 'dni_or_document', 'id']);
                $name   = $this->extractField($row, ['nombres', 'nombre', 'nombre_completo', 'full_name', 'participante']);

                if (empty($dni) || empty($name)) {
                    $skipped++;
                    continue;
                }

                $email = $this->extractField($row, ['email', 'correo', 'e-mail']);
                $phone = $this->extractField($row, ['telefono', 'celular', 'phone']);
                $org   = $this->extractField($row, ['organizacion', 'organization', 'empresa', 'institucion', 'cooperativa']);

                // Target session ID: if none passed, check if engagement has sessions and associate to session 1 or null
                $targetSessionId = $sessionId;
                if (!$targetSessionId) {
                    $firstSession = $engagement->sessions()->first();
                    $targetSessionId = $firstSession?->id;
                }

                if ($targetSessionId) {
                    ServiceAttendee::updateOrCreate(
                        [
                            'service_session_id' => $targetSessionId,
                            'dni_or_document'    => (string) $dni,
                        ],
                        [
                            'service_engagement_id' => $engagement->id,
                            'full_name'             => (string) $name,
                            'email'                 => $email ? (string)$email : null,
                            'phone'                 => $phone ? (string)$phone : null,
                            'organization'          => $org ? (string)$org : null,
                            'attended'              => true,
                        ]
                    );
                    $imported++;
                } else {
                    // Create engagement attendee record
                    ServiceAttendee::updateOrCreate(
                        [
                            'service_engagement_id' => $engagement->id,
                            'dni_or_document'       => (string) $dni,
                            'service_session_id'    => null,
                        ],
                        [
                            'full_name'             => (string) $name,
                            'email'                 => $email ? (string)$email : null,
                            'phone'                 => $phone ? (string)$phone : null,
                            'organization'          => $org ? (string)$org : null,
                            'attended'              => true,
                        ]
                    );
                    $imported++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }

    protected function extractField(array $row, array $candidates): ?string {
        foreach ($candidates as $key) {
            if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
                return trim((string)$row[$key]);
            }
        }
        return null;
    }

    protected function readCsvFallback(string $path): array {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle, 1000, ',');
            if ($header) {
                $header = array_map(fn($h) => strtolower(trim((string)$h)), $header);
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $rowData = [];
                    foreach ($data as $idx => $val) {
                        $k = $header[$idx] ?? (string)$idx;
                        $rowData[$k] = trim((string)$val);
                    }
                    $rows[] = $rowData;
                }
            }
            fclose($handle);
        }
        return $rows;
    }
}
