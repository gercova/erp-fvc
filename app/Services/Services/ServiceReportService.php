<?php

namespace App\Services\Services;

use App\Models\Business;
use App\Models\ServiceEngagement;
use Barryvdh\DomPDF\Facade\Pdf;

class ServiceReportService
{
    /**
     * Render the Final Service Closure Report PDF via DomPDF.
     */
    public function generateFinalReportPdf(ServiceEngagement $engagement)
    {
        $engagement->load([
            'client.tipoDocumento',
            'technologicalService.area',
            'agreement',
            'productiveActivity',
            'responsibleUser',
            'closedBy',
            'sessions.instructor',
            'hourLogs.specialist',
            'deliverables.approver',
            'deliverables.clientSignoffUser',
            'attendees',
        ]);

        $business = Business::first();

        // Calculate unique participants summary
        $uniqueAttendees = $engagement->attendees()
            ->select('dni_or_document', 'full_name', 'organization', 'certificate_code')
            ->distinct()
            ->get()
            ->map(function ($att) use ($engagement) {
                $att->attendance_percent = $engagement->calculateAttendancePercent($att->dni_or_document);
                return $att;
            });

        $data = [
            'engagement'      => $engagement,
            'business'        => $business,
            'uniqueAttendees' => $uniqueAttendees,
            'generatedAt'     => now(),
        ];

        return Pdf::loadView('admin.services.reports.final_report_pdf', $data)
            ->setPaper('a4', 'portrait');
    }
}
