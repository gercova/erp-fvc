<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetInventoryValidate;
use App\Models\Area;
use App\Models\AssetInventory;
use App\Services\DocumentApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssetInventoryController extends Controller
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService) {
        $this->approvalService = $approvalService;
    }

    public function store(AssetInventoryValidate $request): JsonResponse|RedirectResponse {
        $data = $request->validated();
        $user = Auth::user();

        return DB::transaction(function () use ($data, $user, $request) {
            $area = Area::findOrFail($data['area_id']);
            $periodo = $data['periodo'];

            // Correlativo ej: INV-2026-ADM-001
            $prefix         = 'INV-' . $periodo . '-' . ($area->code ?: 'AREA');
            $count          = AssetInventory::where('area_id', $area->id)->where('periodo', $periodo)->count();
            $correlativo    = sprintf('%s-%03d', $prefix, $count + 1);

            $inventory = AssetInventory::create([
                'correlativo'      => $correlativo,
                'area_id'          => $area->id,
                'titulo'           => $data['titulo'],
                'periodo'          => $periodo,
                'fecha_inventario' => $data['fecha_inventario'],
                'responsable'      => $data['responsable'],
                'realizado_por'    => $data['realizado_por'],
                'status'           => 'EN_REVISION',
                'observaciones'    => $data['observaciones'] ?? null,
                'user_id'          => $user->id,
            ]);

            // Generar el flujo de las 5 firmas institucionales (DocumentApprovalService)
            $this->approvalService->generateWorkflow($inventory, $user);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'   => true,
                    'message'   => 'El Acta de Inventario General fue generada e iniciada para el proceso de las 5 firmas.',
                    'inventory' => $inventory->load('approvals'),
                ]);
            }

            return redirect()->route('inventory.index', ['area_id' => $area->id])
                ->with('toast_success', 'El Acta de Inventario General fue enviada al flujo de aprobaciones oficiales.');
        });
    }

    public function show(int $id): View {
        $inventory = AssetInventory::with(['area', 'user', 'approvals.approver', 'assets'])->findOrFail($id);
        return view('admin.assets.inventory_show', compact('inventory'));
    }
}
