<?php

namespace App\Http\Middleware;

use App\Models\Agreement;
use App\Models\Area;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAgreementAccess
{
    /**
     * Middleware de Control de Acceso por Convenio Institucional.
     * Roles con alcance institucional amplio (SUPERADMIN, ADMIN, DIRECTOR_GENERAL, ADMINISTRACION, CONTABILIDAD).
     * Acceso restringido por departamento/responsable para otros usuarios (análogo a activity.access).
     */
    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // 1. Roles con Acceso Total Irrestricto
        if ($user->hasRole(['SUPERADMIN', 'ADMIN'])) {
            return $next($request);
        }

        // 2. Roles Institucionales de Supervisión Amplia
        $broadScopeRoles = [
            'DIRECTOR_GENERAL',
            'ADMINISTRACION',
            'ADMINISTRATION',
            'CONTABILIDAD',
            'ACCOUNTING',
        ];

        if ($user->hasRole($broadScopeRoles)) {
            // El rol CONTABILIDAD tiene acceso consolidado de lectura y auditoría (salvo firmas en cadena)
            if ($user->hasRole(['CONTABILIDAD', 'ACCOUNTING']) && in_array($request->method(), ['POST', 'PUT', 'DELETE'], true)) {
                if (!$request->is('*approvals*') && !$request->is('*reconciliations*') && !$user->can('agreements.manage')) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'El rol CONTABILIDAD cuenta con acceso consolidado de solo lectura y auditoría.'
                        ], 403);
                    }
                    abort(403, 'El rol CONTABILIDAD cuenta con acceso consolidado de solo lectura y auditoría.');
                }
            }

            return $next($request);
        }

        // 3. Determinar IDs de Convenios Autorizados para el Usuario Restringido
        $allowedAgreementIds = $this->getAllowedAgreementIds($user);

        // Almacenar en atributos de la petición para que los controladores puedan filtrar colecciones
        $request->attributes->set('allowed_agreement_ids', $allowedAgreementIds);

        // 4. Identificar el Convenio Objetivo de la Solicitud
        $targetAgreementId = $this->resolveTargetAgreementId($request);

        // 5. Validar Acceso si hay un Convenio Específico Involucrado
        if ($targetAgreementId !== null) {
            if (!in_array((int) $targetAgreementId, $allowedAgreementIds, true)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene autorización para visualizar o gestionar este convenio fuera de su departamento.'
                    ], 403);
                }
                abort(403, 'No tiene autorización para visualizar o gestionar este convenio fuera de su departamento.');
            }
        }

        // 6. Validar creación de convenio para usuarios restringidos
        if ($request->isMethod('POST') && $request->is('agreements') && $request->filled('area_id')) {
            $userAreaIds = $this->getUserAreaIds($user);
            $requestedAreaId = (int) $request->input('area_id');
            if (!empty($userAreaIds) && !in_array($requestedAreaId, $userAreaIds, true) && !$user->can('agreements.manage')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No puede registrar convenios para departamentos fuera de su ámbito de gestión.'
                    ], 403);
                }
                abort(403, 'No puede registrar convenios para departamentos fuera de su ámbito de gestión.');
            }
        }

        return $next($request);
    }

    /**
     * Resuelve el ID del convenio asociado a la petición actual.
     */
    protected function resolveTargetAgreementId(Request $request): ?int
    {
        if ($request->filled('agreement_id')) {
            return (int) $request->input('agreement_id');
        }

        if ($request->route('agreement')) {
            $param = $request->route('agreement');
            return is_numeric($param) ? (int) $param : (int) $param->id;
        }

        if ($request->route('id') && $request->is('agreements/*')) {
            $id = $request->route('id');
            if (is_numeric($id)) {
                return (int) $id;
            }
        }

        return null;
    }

    /**
     * Obtiene los IDs de convenios permitidos para el usuario.
     */
    public function getAllowedAgreementIds($user): array
    {
        $agreementIds = [];

        // A. Convenios donde el usuario es Coordinador Técnico o Creador
        $personalIds = Agreement::where('coordinator_user_id', $user->id)
            ->orWhere('created_by_user_id', $user->id)
            ->pluck('id')
            ->toArray();
        $agreementIds = array_merge($agreementIds, $personalIds);

        // B. Departamentos donde el usuario es Jefe de Área o Miembro/Empleado
        $userAreaIds = $this->getUserAreaIds($user);
        if (!empty($userAreaIds)) {
            $areaAgreementIds = Agreement::whereIn('area_id', $userAreaIds)->pluck('id')->toArray();
            $agreementIds = array_merge($agreementIds, $areaAgreementIds);
        }

        return array_values(array_unique(array_map('intval', $agreementIds)));
    }

    /**
     * Obtiene los IDs de áreas/departamentos asociados al usuario.
     */
    protected function getUserAreaIds($user): array
    {
        $areaIds = [];

        // 1. Áreas donde el usuario es Jefe de Área
        $headedAreaIds = Area::where('head_user_id', $user->id)->pluck('id')->toArray();
        $areaIds = array_merge($areaIds, $headedAreaIds);

        // 2. Áreas donde el usuario es empleado
        $employeeAreaIds = $user->employeeAreaDetails()->pluck('area_id')->toArray();
        $areaIds = array_merge($areaIds, $employeeAreaIds);

        // 3. Área primaria si existiese
        if ($user->primaryArea) {
            $areaIds[] = $user->primaryArea->id;
        }

        return array_values(array_unique(array_map('intval', $areaIds)));
    }
}
