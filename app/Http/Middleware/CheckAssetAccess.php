<?php

namespace App\Http\Middleware;

use App\Models\Area;
use App\Models\Asset;
use App\Models\AssetInventory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAssetAccess
{
    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Si es SUPERADMIN o ADMIN, tiene acceso total irrestricto
        if ($user->hasRole(['SUPERADMIN', 'ADMIN'])) {
            return $next($request);
        }

        // Si tiene rol de PATRIMONIO o ABASTECIMIENTO, tiene supervisión global de bienes
        if ($user->hasRole(['PATRIMONIO', 'ABASTECIMIENTO'])) {
            return $next($request);
        }

        // Si es DIRECTOR_GENERAL o ADMINISTRACION, puede consultar, supervisar y aprobar
        if ($user->hasRole(['DIRECTOR_GENERAL', 'ADMINISTRACION'])) {
            return $next($request);
        }

        // Verificar si tiene al menos permiso de lectura/gestión de activos
        if (!$user->can('assets.index')) {
            abort(403, 'No tiene permiso para acceder al módulo de Bienes Patrimoniales.');
        }

        // Determinar si el usuario es jefe de algún área o tiene asignación
        $allowedAreaIds = $this->getAllowedAreaIds($user);

        // Si se envió un area_id específico en la petición (formulario o query)
        $targetAreaId = $request->input('area_id') ?? $request->query('area_id');
        if ($targetAreaId && !in_array((int)$targetAreaId, $allowedAreaIds)) {
            abort(403, 'No tiene autorización para gestionar los bienes de este departamento.');
        }

        // Si la ruta hace referencia a un préstamo de activo
        if ($request->is('asset-loans*')) {
            $loanId = $request->route('id') ?? $request->route('loan');
            if ($loanId) {
                $loan = \App\Models\AssetLoan::find($loanId);
                if ($loan && !in_array((int)$loan->area_id, $allowedAreaIds)) {
                    abort(403, 'No tiene autorización para gestionar este préstamo de activo.');
                }
            }
        } else {
            // Si la ruta hace referencia a un activo específico
            $assetId = $request->route('id') ?? $request->route('asset');
            if ($assetId) {
                $asset = Asset::find($assetId);
                if ($asset && !in_array((int)$asset->area_id, $allowedAreaIds)) {
                    abort(403, 'No tiene autorización para modificar o ver este bien patrimonial.');
                }
            }
        }

        // Si la ruta hace referencia a un inventario específico
        $inventoryId = $request->route('inventory') ?? $request->route('inventory_id');
        if ($inventoryId) {
            $inventory = AssetInventory::find($inventoryId);
            if ($inventory && !in_array((int)$inventory->area_id, $allowedAreaIds)) {
                abort(403, 'No tiene autorización para acceder a este inventario departamental.');
            }
        }

        return $next($request);
    }

    /**
     * Obtener los IDs de áreas a las que tiene acceso un usuario no administrador.
     */
    protected function getAllowedAreaIds($user): array
    {
        $ids = [];

        // Áreas donde el usuario es el jefe (head_user_id)
        $headedAreas = Area::where('head_user_id', $user->id)->pluck('id')->toArray();
        $ids = array_merge($ids, $headedAreas);

        // Área primaria del usuario
        if ($user->primaryArea) {
            $ids[] = $user->primaryArea->id;
        }

        // Áreas asignadas en employeeAreaDetails
        $assignedAreas = $user->employeeAreaDetails()->pluck('area_id')->toArray();
        $ids = array_merge($ids, $assignedAreas);

        return array_unique($ids);
    }
}
