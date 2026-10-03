<?php

namespace App\Services\Services;

use App\Models\Business;
use App\Models\ServiceAttendee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateService
{
    /**
     * Issue certificate for a participant if eligible by attendance percentage.
     */
    public function issueCertificate(ServiceAttendee $attendee, ?float $minAttendancePercent = null): ServiceAttendee {
        $engagement = $attendee->engagement;
        if (!$engagement) {
            throw new \InvalidArgumentException('El participante no tiene una orden de servicio asociada.');
        }

        $minThreshold       = $minAttendancePercent ?? (float) ($engagement->min_attendance_percent ?? 80.00);
        $attendancePercent  = $engagement->calculateAttendancePercent($attendee->dni_or_document);

        if ($attendancePercent < $minThreshold) {
            throw new \DomainException(
                "El participante cuenta con {$attendancePercent}% de asistencia, por debajo del umbral mínimo requerido ({$minThreshold}%)."
            );
        }

        if (empty($attendee->certificate_code)) {
            $year = date('Y');
            $code = $this->generateUniqueCertificateCode($year);
            $hash = hash('sha256', "{$code}|{$attendee->dni_or_document}|{$attendee->full_name}|{$engagement->code}");

            $attendee->update([
                'certificate_code'      => $code,
                'certificate_issued_at' => now(),
                'certificate_hash'      => $hash,
            ]);
        }

        return $attendee;
    }

    /**
     * Generate unique sequential certificate code.
     */
    public function generateUniqueCertificateCode(int $year): string {
        return DB::transaction(function () use ($year) {
            $prefix = "CERT-{$year}-";
            $latest = ServiceAttendee::where('certificate_code', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('certificate_code');

            $seq = 1;
            if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
                $seq = (int) $matches[1] + 1;
            }

            return $prefix . str_pad((string)$seq, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Generate QR code as Base64 SVG for DomPDF.
     */
    public function generateQrBase64(string $url, int $size = 130): string {
        $prev = error_reporting(error_reporting() & ~E_DEPRECATED);
        try {
            $svg = (string) QrCode::format('svg')->size($size)->margin(1)->generate($url);
            return base64_encode($svg);
        } finally {
            error_reporting($prev);
        }
    }

    /**
     * Render Certificate PDF via DomPDF.
     */
    public function generatePdf(ServiceAttendee $attendee) {
        $engagement = $attendee->engagement()->with(['technologicalService', 'responsibleUser', 'client'])->first();
        $business   = Business::first();
        $url        = route('services.certificates.verify_public', ['code' => $attendee->certificate_code ?? $attendee->uuid]);
        $qrBase64   = $this->generateQrBase64($url, 130);

        $attendancePercent  = $engagement ? $engagement->calculateAttendancePercent($attendee->dni_or_document) : 100.00;
        $totalHours         = (float) ($engagement->contracted_hours > 0
            ? $engagement->contracted_hours
            : $engagement->sessions()->sum('duration_hours'));

        $data = [
            'attendee'          => $attendee,
            'engagement'        => $engagement,
            'business'          => $business,
            'verificationUrl'   => $url,
            'qrBase64'          => $qrBase64,
            'attendancePercent' => $attendancePercent,
            'totalHours'        => $totalHours > 0 ? $totalHours : 40,
            'issuedDate'        => $attendee->certificate_issued_at ?? now(),
        ];

        return Pdf::loadView('admin.services.certificates.pdf', $data)
            ->setPaper('a4', 'landscape');
    }

    /**
     * Find attendee by certificate code or UUID for public verification.
     */
    public function verifyCertificate(string $code): ?array {
        $attendee = ServiceAttendee::where('certificate_code', $code)
            ->orWhere('uuid', $code)
            ->with(['engagement.technologicalService', 'engagement.client', 'engagement.responsibleUser'])
            ->first();

        if (!$attendee) {
            return null;
        }

        $engagement = $attendee->engagement;
        return [
            'valid'                 => true,
            'certificate_code'      => $attendee->certificate_code,
            'issued_at'             => $attendee->certificate_issued_at ? Carbon::parse($attendee->certificate_issued_at)->format('d/m/Y') : null,
            'participant_name'      => $attendee->full_name,
            'participant_document'  => $attendee->dni_or_document,
            'service_name'          => $engagement?->technologicalService?->name ?? 'Servicio Tecnológico',
            'organization'          => $attendee->organization ?? ($engagement?->client?->nombres ?? 'IESTP FVC'),
            'total_hours'           => (float) ($engagement?->contracted_hours ?? 0),
            'attendance_percent'    => $engagement ? $engagement->calculateAttendancePercent($attendee->dni_or_document) : 100.00,
            'responsible_name'      => $engagement?->responsibleUser?->nombres ?? 'Dirección General',
            'hash'                  => $attendee->certificate_hash,
        ];
    }
}
