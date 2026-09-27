<?php

namespace App\Http\Middleware;

use App\Models\ActivityTransaction;
use App\Models\Area;
use App\Models\ProductiveActivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckProductiveActivityAccess
{
    /**
     * Middleware de Control de Acceso por Actividad Productiva (APE)
     * Equivalente funcional a CheckAssetAccess para bienes patrimoniales.
     * Restringe las operaciones de registro, edición y consulta a los responsables
     * o colaboradores expresamente asignados a la actividad productiva.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // 1. SUPERADMIN y ADMIN: Acceso total irrestricto
        if ($user->hasRole(['SUPERADMIN', 'ADMIN'])) {
            return $next($request);
        }

        // 2. Roles Supervisores con Acceso Consolidado Institucional
        // DIRECTOR_GENERAL, ADMINISTRACION y CONTABILIDAD tienen acceso consolidado
        // de lectura y supervisión a todo el módulo (equivalente a su acceso en ventas/reportes).
        if ($user->hasRole(['DIRECTOR_GENERAL', 'ADMINISTRACION', 'CONTABILIDAD'])) {
            // Si es Contabilidad y está intentando mutaciones que no sean conciliación bancaria
            if ($user->hasRole('CONTABILIDAD') && in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
                if (!$request->is('*reconciliations*') && !$request->routeIs('productive_activities.rdr.reconciliations.store')) {
                    abort(403, 'El rol CONTABILIDAD cuenta con acceso consolidado de solo lectura y auditoría.');
                }
            }
            return $next($request);
        }

        // 3. Determinar IDs de Actividades Autorizadas para el Usuario
        $allowedActivityIds = $this->getAllowedActivityIds($user);

        // Almacenar en la petición para que los controladores puedan filtrar colecciones
        $request->attributes->set('allowed_activity_ids', $allowedActivityIds);

        // 4. Identificar la Actividad Productiva Objetivo de la Solicitud
        $targetActivityId = $this->resolveTargetActivityId($request);

        // 5. Validar Acceso si hay una Actividad Específica Involucrada
        if ($targetActivityId !== null) {
            if (!in_array((int) $targetActivityId, $allowedActivityIds, true)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene autorización para registrar o modificar operaciones de esta Actividad Productiva.'
                    ], 403);
                }
                abort(403, 'No tiene autorización para registrar o modificar operaciones de esta Actividad Productiva.');
            }
        }

        return $next($request);
    }

    /**
     * Resuelve el ID de la Actividad Productiva asociada a la petición actual.
     */
    protected function resolveTargetActivityId(Request $request): ?int
    {
        // A. Enviado explícitamente en el cuerpo o query string
        if ($request->filled('productive_activity_id')) {
            return (int) $request->input('productive_activity_id');
        }
        if ($request->filled('activity_id')) {
            return (int) $request->input('activity_id');
        }

        // B. Parámetro de ruta en rutas de Actividades (ej. /productive-activities/{id})
        if ($request->route('activity')) {
            $act = $request->route('activity');
            return is_numeric($act) ? (int) $act : (int) $act->id;
        }

        if ($request->is('productive-activities/*') && !str_contains($request->path(), 'transactions') && !str_contains($request->path(), 'cost-center') && !str_contains($request->path(), 'rdr')) {
            $id = $request->route('id');
            if (is_numeric($id)) {
                return (int) $id;
            }
        }

        // C. Parámetro de ruta en Transacciones (ej. /transactions/{id})
        if ($request->is('*transactions*')) {
            $trxId = $request->route('id');
            if (is_numeric($trxId)) {
                $trx = ActivityTransaction::find($trxId);
                return $trx ? (int) $trx->productive_activity_id : null;
            }
        }

        return null;
    }

    /**
     * Obtiene el listado de IDs de actividades productivas a las que tiene acceso el usuario.
     */
    protected function getAllowedActivityIds($user): array
    {
        $activityIds = [];

        // A. Actividades donde el usuario es el Responsable directo (head_user_id)
        $headedActivities = ProductiveActivity::where('head_user_id', $user->id)->pluck('id')->toArray();
        $activityIds = array_merge($activityIds, $headedActivities);

        // B. Actividades donde el usuario está asignado como Colaborador Activo
        $collaboratorActivities = DB::table('activity_collaborators')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('productive_activity_id')
            ->toArray();
        $activityIds = array_merge($activityIds, $collaboratorActivities);

        // C. Actividades asociadas a departamentos donde el usuario es Jefe de Área
        $headedAreaIds = Area::where('head_user_id', $user->id)->pluck('id')->toArray();
        if (!empty($headedAreaIds)) {
            $areaActivities = ProductiveActivity::whereIn('area_id', $headedAreaIds)->pluck('id')->toArray();
            $activityIds = array_merge($activityIds, $areaActivities);
        }

        return array_values(array_unique(array_map('intval', $activityIds)));
    }
}
