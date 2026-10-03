<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Models\Agreement;
use App\Models\AccountingPeriodClosure;
use App\Models\ActivityPeriodClosure;
use App\Models\Area;
use App\Models\AssetInventory;
use App\Models\Budget;
use App\Models\BudgetApproval;
use App\Models\DocumentApproval;
use App\Models\ExitSlip;
use App\Models\ExpenseDeclaration;
use App\Models\FuelControlSlip;
use App\Models\ProductiveActivity;
use App\Models\Requisition;
use App\Models\User;
use App\Models\VacationExitSlip;
use App\Models\VehicleExitSlip;
use App\Notifications\DocumentPendingApprovalNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class DocumentApprovalService
{
    /**
     * Build the approval chain for any newly created document.
     */
    public function generateWorkflow(Model $document, User $creator): void
    {
        $area = $document->area_id ? Area::find($document->area_id) : $creator->primaryArea;
        $steps = $this->getStepDefinitions($document, $area, $creator);

        $firstPending = null;

        foreach ($steps as $index => $step) {
            $stepOrder = $index + 1;
            $isAutoApproved = $step['role_name'] === 'SOLICITANTE';

            $approval = DocumentApproval::create([
                'document_type' => get_class($document),
                'document_id' => $document->id,
                'step_order' => $stepOrder,
                'role_name' => $step['role_name'],
                'label' => $step['label'],
                'approver_id' => $isAutoApproved ? $creator->id : ($step['approver_id'] ?? null),
                'approver_name' => $isAutoApproved ? $creator->nombres : ($step['approver_name'] ?? null),
                'approver_cargo' => $isAutoApproved ? ($document->cargo ?? 'Solicitante') : null,
                'status' => $isAutoApproved ? 'APROBADO' : 'PENDIENTE',
                'signature_token' => $isAutoApproved ? strtoupper(bin2hex(random_bytes(6))) : null,
                'signed_at' => $isAutoApproved ? now() : null,
                'ip_address' => request()->ip(),
            ]);

            if (!$isAutoApproved && $firstPending === null) {
                $firstPending = $approval;
            }
        }

        // Notify the first pending approver
        if ($firstPending) {
            $this->notifyApprovers($firstPending, $document, $creator);
        }
    }

    /**
     * Get the defined steps according to the document type and official templates.
     */
    protected function getStepDefinitions(Model $document, ?Area $area, User $creator): array
    {
        $immediateHead      = $this->resolveImmediateHead($area, $creator);
        $adminHead          = $this->resolveAreaHead('ADM') ?? $this->resolveRoleUser('ADMINISTRACION');
        $directorGeneral    = $this->resolveAreaHead('DG') ?? $this->resolveRoleUser('DIRECTOR_GENERAL');
        $academicHead       = $this->resolveAreaHead('UA') ?? $this->resolveRoleUser('JEFE_AREA');
        $abastecimientoHead = $this->resolveAreaHead('ABASTECIMIENTO') ?? $this->resolveRoleUser('ABASTECIMIENTO');

        if ($document instanceof Requisition) {
            return [
                ['role_name' => 'SOLICITANTE', 'label' => 'Solicitante', 'approver_id' => $creator->id, 'approver_name' => $creator->nombres],
                ['role_name' => 'COORD_PE', 'label' => 'Coord. P.E. / Jefe Inmediato', 'approver_id' => $immediateHead?->id, 'approver_name' => $immediateHead?->nombres],
                ['role_name' => 'TRAMITE_DOCUMENTARIO', 'label' => 'Trámite Documentario', 'approver_id' => null, 'approver_name' => null],
                ['role_name' => 'ADMINISTRACION', 'label' => 'Administración / J. Unid. Adm.', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'DIRECTOR_GENERAL', 'label' => 'Director General', 'approver_id' => $directorGeneral?->id, 'approver_name' => $directorGeneral?->nombres],
            ];
        }

        if ($document instanceof ExpenseDeclaration) {
            return [
                ['role_name' => 'SOLICITANTE', 'label' => 'Solicitante', 'approver_id' => $creator->id, 'approver_name' => $creator->nombres],
                ['role_name' => 'COORD_PE', 'label' => 'Coordinador / Jefe Inmediato', 'approver_id' => $immediateHead?->id, 'approver_name' => $immediateHead?->nombres],
                ['role_name' => 'ADMINISTRACION', 'label' => 'Administrador I.E.S.T.P. "F.V.C."', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'DIRECTOR_GENERAL', 'label' => 'Director(a) I.E.S.T.P. "F.V.C."', 'approver_id' => $directorGeneral?->id, 'approver_name' => $directorGeneral?->nombres],
            ];
        }

        if ($document instanceof ExitSlip) {
            return [
                ['role_name' => 'SOLICITANTE', 'label' => 'Usuario', 'approver_id' => $creator->id, 'approver_name' => $creator->nombres],
                ['role_name' => 'JEFE_INMEDIATO', 'label' => 'Jefe de Área / Unidad / Docente', 'approver_id' => $immediateHead?->id, 'approver_name' => $immediateHead?->nombres],
                ['role_name' => 'ADMINISTRACION', 'label' => 'Jefe / Administrador I.E.S.T.P. "F.V.C."', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'DIRECTOR_GENERAL', 'label' => 'Director(a) I.E.S.T.P. "F.V.C."', 'approver_id' => $directorGeneral?->id, 'approver_name' => $directorGeneral?->nombres],
            ];
        }

        if ($document instanceof VehicleExitSlip) {
            return [
                ['role_name' => 'SOLICITANTE', 'label' => 'Firma de Solicitante', 'approver_id' => $creator->id, 'approver_name' => $creator->nombres],
                ['role_name' => 'CHOFER', 'label' => 'Firma del Chofer', 'approver_id' => null, 'approver_name' => $document->chofer_nombre],
                ['role_name' => 'ADMINISTRACION', 'label' => 'Firma del J.U. Adm. I.E.S.T.P. "F.V.C."', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
            ];
        }

        if ($document instanceof VacationExitSlip) {
            return [
                ['role_name' => 'SOLICITANTE', 'label' => 'Docente / Servidor', 'approver_id' => $creator->id, 'approver_name' => $creator->nombres],
                ['role_name' => 'JEFE_INMEDIATO', 'label' => 'Jefe Área / Administrador IESTP "FVC"', 'approver_id' => $immediateHead?->id, 'approver_name' => $immediateHead?->nombres],
                ['role_name' => 'UNIDAD_ACADEMICA', 'label' => 'Jefe de Unidad Académica IESTP "FVC"', 'approver_id' => $academicHead?->id, 'approver_name' => $academicHead?->nombres],
                ['role_name' => 'DIRECTOR_GENERAL', 'label' => 'V° B° Director General IESTP "FVC"', 'approver_id' => $directorGeneral?->id, 'approver_name' => $directorGeneral?->nombres],
            ];
        }

        if ($document instanceof FuelControlSlip) {
            return [
                ['role_name' => 'ABASTECIMIENTO', 'label' => 'V° B° Abastecimiento (Control)', 'approver_id' => $abastecimientoHead?->id, 'approver_name' => $abastecimientoHead?->nombres],
                ['role_name' => 'ADMINISTRACION', 'label' => 'V° B° Administración (Autorizado)', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'GRIFO', 'label' => 'Atendido por (Grifo)', 'approver_id' => null, 'approver_name' => $document->nombre_grifo],
                ['role_name' => 'RECIBIDO_POR', 'label' => 'Recibido por', 'approver_id' => $creator->id, 'approver_name' => $creator->nombres],
            ];
        }

        if ($document instanceof AssetInventory) {
            $areaHead = $document->area?->head;
            return [
                ['role_name' => 'JEFE_AREA', 'label' => 'Resp. del Área', 'approver_id' => $areaHead?->id, 'approver_name' => $document->responsable ?: $areaHead?->nombres],
                ['role_name' => 'ABASTECIMIENTO', 'label' => 'Resp. de Abastecimiento', 'approver_id' => $abastecimientoHead?->id, 'approver_name' => $abastecimientoHead?->nombres],
                ['role_name' => 'ADMINISTRACION', 'label' => 'Administrador', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'UNIDAD_ADMINISTRATIVA', 'label' => 'Jefe de Unidad Administrativa', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'DIRECTOR_GENERAL', 'label' => 'Director General', 'approver_id' => $directorGeneral?->id, 'approver_name' => $directorGeneral?->nombres],
            ];
        }

        if ($document instanceof ProductiveActivity) {
            return [
                ['role_name' => 'SOLICITANTE', 'label' => 'Responsable de la Actividad', 'approver_id' => $creator->id, 'approver_name' => $creator->nombres],
                ['role_name' => 'JEFE_AREA', 'label' => 'Jefe de Área / Coordinador', 'approver_id' => $immediateHead?->id, 'approver_name' => $immediateHead?->nombres],
                ['role_name' => 'ADMINISTRACION', 'label' => 'Administración IESTP "FVC"', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'DIRECTOR_GENERAL', 'label' => 'Director(a) General IESTP "FVC"', 'approver_id' => $directorGeneral?->id, 'approver_name' => $directorGeneral?->nombres],
            ];
        }

        if ($document instanceof ActivityPeriodClosure) {
            $contabilidadHead = $this->resolveRoleUser('CONTABILIDAD') ?? $adminHead;
            return [
                ['role_name' => 'SOLICITANTE', 'label' => 'Responsable del Cierre', 'approver_id' => $creator->id, 'approver_name' => $creator->nombres],
                ['role_name' => 'CONTABILIDAD', 'label' => 'Revisión Contable y Liquidación', 'approver_id' => $contabilidadHead?->id, 'approver_name' => $contabilidadHead?->nombres],
                ['role_name' => 'ADMINISTRACION', 'label' => 'Conformidad Administrativa', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'DIRECTOR_GENERAL', 'label' => 'Aprobación Final Director General', 'approver_id' => $directorGeneral?->id, 'approver_name' => $directorGeneral?->nombres],
            ];
        }

        if ($document instanceof AccountingPeriodClosure) {
            $contabilidadHead = $this->resolveRoleUser('CONTABILIDAD') ?? $this->resolveAreaHead('CONT') ?? $creator;
            return [
                ['role_name' => 'CONTABILIDAD', 'label' => 'Jefatura de Contabilidad', 'approver_id' => $contabilidadHead?->id, 'approver_name' => $contabilidadHead?->nombres],
                ['role_name' => 'ADMINISTRACION', 'label' => 'Jefatura de Administración', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'DIRECTOR_GENERAL', 'label' => 'Dirección General', 'approver_id' => $directorGeneral?->id, 'approver_name' => $directorGeneral?->nombres],
            ];
        }

        if ($document instanceof Budget || $document instanceof BudgetApproval) {
            $contabilidadHead = $this->resolveRoleUser('CONTABILIDAD') ?? $this->resolveAreaHead('CONT') ?? $creator;
            return [
                ['role_name' => 'CONTABILIDAD', 'label' => 'Jefatura de Contabilidad y Presupuesto', 'approver_id' => $contabilidadHead?->id, 'approver_name' => $contabilidadHead?->nombres],
                ['role_name' => 'ADMINISTRACION', 'label' => 'Jefatura de Administración', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'DIRECTOR_GENERAL', 'label' => 'Dirección General', 'approver_id' => $directorGeneral?->id, 'approver_name' => $directorGeneral?->nombres],
            ];
        }

        if ($document instanceof Agreement) {
            $areaHead = $document->area?->head ?? $this->resolveImmediateHead($area, $creator);
            return [
                ['role_name' => 'SOLICITANTE', 'label' => 'Coordinador del Convenio / Responsable Técnico', 'approver_id' => $creator->id, 'approver_name' => $creator->nombres],
                ['role_name' => 'JEFE_AREA', 'label' => 'Jefe de Área / Unidad Responsable', 'approver_id' => $areaHead?->id, 'approver_name' => $areaHead?->nombres],
                ['role_name' => 'ADMINISTRACION', 'label' => 'Jefatura de Administración IESTP "FVC"', 'approver_id' => $adminHead?->id, 'approver_name' => $adminHead?->nombres],
                ['role_name' => 'DIRECTOR_GENERAL', 'label' => 'Dirección General IESTP "FVC"', 'approver_id' => $directorGeneral?->id, 'approver_name' => $directorGeneral?->nombres],
            ];
        }

        return [];
    }

    protected function resolveImmediateHead(?Area $area, User $creator): ?User {
        if (!$area) {
            return null;
        }

        if ($area->head_user_id && $area->head_user_id !== $creator->id) {
            return $area->head;
        }

        if ($area->parent && $area->parent->head_user_id && $area->parent->head_user_id !== $creator->id) {
            return $area->parent->head;
        }

        return null;
    }

    protected function resolveAreaHead(string $areaCode): ?User {
        $area = Area::where('code', $areaCode)->first();
        return $area?->head;
    }

    protected function resolveRoleUser(string $roleName): ?User {
        $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
        if (!$role) {
            return null;
        }
        return User::role($roleName)->first();
    }

    /**
     * Send notification to the current approver(s).
     */
    public function notifyApprovers(DocumentApproval $approval, Model $document, ?User $requester = null): void {
        $requesterName = $requester?->nombres ?? ($document->user?->nombres ?? 'Usuario');
        $documentTitle = $this->getDocumentTitle($document);
        $url = route('approvals.show', $approval->id);

        $recipients = collect();

        if ($approval->approver_id) {
            $user = User::find($approval->approver_id);
            if ($user) {
                $recipients->push($user);
            }
        } else {
            // Find users matching role or administrators safely
            $candidateRoles = array_filter([$approval->role_name, 'SUPERADMIN', 'ADMIN']);
            $existingRoles = \Spatie\Permission\Models\Role::whereIn('name', $candidateRoles)->pluck('name')->toArray();
            if (!empty($existingRoles)) {
                $roleUsers = User::role($existingRoles)->get();
                $recipients = $recipients->merge($roleUsers);
            }
        }

        foreach ($recipients->unique('id') as $recipient) {
            $recipient->notify(new DocumentPendingApprovalNotification($approval, $documentTitle, $requesterName, $url));
        }
    }

    /**
     * Process digital signature & approval for a step.
     */
    public function signAndApprove(DocumentApproval $approval, User $user, ?string $signatureData = null, ?string $observations = null): bool {
        $approval->update([
            'status'            => 'APROBADO',
            'approver_id'       => $user->id,
            'approver_name'     => $user->nombres,
            'approver_cargo'    => $user->primaryAreaDetail?->cargo ?? optional($user->roles->first())->name,
            'observations'      => $observations,
            'signature_token'   => strtoupper(bin2hex(random_bytes(6))),
            'signature_data'    => $signatureData,
            'signed_at'         => now(),
            'ip_address'        => request()->ip(),
        ]);

        $document = $approval->document;

        // Check if there are remaining pending steps
        $nextPending = DocumentApproval::where('document_type', $approval->document_type)
            ->where('document_id', $approval->document_id)
            ->where('status', 'PENDIENTE')
            ->where('step_order', '>', $approval->step_order)
            ->orderBy('step_order')
            ->first();

        if ($nextPending) {
            if ($document instanceof Agreement) {
                $document->transitionStatus(
                    AgreementStatus::IN_APPROVAL,
                    "Visación de paso {$approval->step_order} ({$approval->label}) realizada por {$user->nombres}",
                    $user,
                    'STEP_APPROVED',
                    ['step_order' => $approval->step_order, 'role' => $approval->role_name]
                );
            } else {
                $document->update(['status' => 'EN_REVISION']);
            }
            $this->notifyApprovers($nextPending, $document, $document->user);
        } else {
            // All steps approved!
            if ($document instanceof Agreement) {
                $document->transitionStatus(
                    AgreementStatus::ACTIVE,
                    'Aprobación final de la cadena de firmas institucionales (Dirección General)',
                    $user,
                    'FINAL_APPROVAL',
                    ['approved_by' => $user->id, 'approved_at' => now()->toIso8601String()]
                );
            } else {
                $document->update(['status' => 'APROBADO']);
            }

            if ($document instanceof AccountingPeriodClosure) {
                app(\App\Services\Accounting\PeriodClosingService::class)->finalizeApprovedClosure($document);
            }

            if ($document instanceof Budget) {
                $document->update([
                    'status' => 'APPROVED',
                    'approved_at' => now(),
                    'approved_by_user_id' => $user->id,
                ]);
            }

            if ($document instanceof BudgetApproval) {
                $document->update([
                    'status' => 'APROBADO',
                    'approved_at' => now(),
                ]);
                $document->budget?->update([
                    'status' => 'APPROVED',
                    'approved_at' => now(),
                    'approved_by_user_id' => $user->id,
                ]);
            }
        }

        return true;
    }

    /**
     * Mark approval step as observed or rejected.
     */
    public function observeOrReject(DocumentApproval $approval, User $user, string $status, string $observations): bool {
        $approval->update([
            'status'            => $status,
            'approver_id'       => $user->id,
            'approver_name'     => $user->nombres,
            'approver_cargo'    => $user->primaryAreaDetail?->cargo ?? optional($user->roles->first())->name,
            'observations'      => $observations,
            'signed_at'         => now(),
            'ip_address'        => request()->ip(),
        ]);

        $document = $approval->document;
        if ($document instanceof Agreement) {
            $newStatus = ($status === 'RECHAZADO') ? AgreementStatus::REJECTED : AgreementStatus::DRAFT;
            $document->transitionStatus(
                $newStatus,
                "Paso {$approval->step_order} ({$approval->label}) {$status}: {$observations}",
                $user,
                $status === 'RECHAZADO' ? 'APPROVAL_REJECTED' : 'APPROVAL_OBSERVED',
                ['status' => $status, 'observations' => $observations]
            );
        } else {
            $document->update(['status' => $status]);
        }

        return true;
    }

    public function getDocumentTitle(Model $document): string {
        if ($document instanceof Requisition) {
            return "Requerimiento N° {$document->correlativo}";
        }
        if ($document instanceof ExpenseDeclaration) {
            return "Declaración Jurada N° {$document->correlativo}";
        }
        if ($document instanceof ExitSlip) {
            return "Papeleta de Salida N° {$document->correlativo}";
        }
        if ($document instanceof VehicleExitSlip) {
            return "Papeleta de Vehículo N° {$document->correlativo}";
        }
        if ($document instanceof VacationExitSlip) {
            return "Papeleta de Vacaciones N° {$document->correlativo}";
        }
        if ($document instanceof FuelControlSlip) {
            return "Vale de Control N° {$document->correlativo}";
        }
        if ($document instanceof AssetInventory) {
            return "Acta de Inventario - {$document->area?->name} ({$document->periodo})";
        }
        if ($document instanceof \App\Models\ProductiveActivity) {
            return "Apertura de Actividad Productiva - {$document->name} ({$document->code})";
        }
        if ($document instanceof \App\Models\ActivityPeriodClosure) {
            return "Cierre de Período {$document->period_month}/{$document->period_year} - {$document->activity?->name}";
        }
        if ($document instanceof AccountingPeriodClosure) {
            return "Cierre de Período Contable {$document->period?->period_code} ({$document->closure_type})";
        }
        if ($document instanceof Budget) {
            return "Aprobación de Presupuesto {$document->code} ({$document->fiscal_year})";
        }
        if ($document instanceof BudgetApproval) {
            return "Aprobación de Presupuesto {$document->budget?->code} ({$document->budget?->fiscal_year})";
        }
        if ($document instanceof Agreement) {
            return "Convenio {$document->code} - {$document->name}";
        }

        return "Documento N° {$document->id}";
    }
}
