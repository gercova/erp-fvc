<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductionBatchValidate;
use App\Http\Requests\ProductionLaborCostValidate;
use App\Models\ProductionBatch;
use App\Models\ProductionLaborCost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductionBatchController extends Controller
{
    public function store(ProductionBatchValidate $request): RedirectResponse
    {
        $batch = ProductionBatch::create($request->validated());

        return redirect()->back()->with('success', 'Fase / Lote ' . $batch->batch_code . ' registrado correctamente.');
    }

    public function update(ProductionBatchValidate $request, $id): RedirectResponse
    {
        $batch = ProductionBatch::findOrFail($id);
        $batch->update($request->validated());

        return redirect()->back()->with('success', 'Fase / Lote actualizado correctamente.');
    }

    public function delete(Request $request): JsonResponse
    {
        $batch = ProductionBatch::findOrFail($request->input('id'));
        $batch->delete();

        return response()->json(['success' => true, 'message' => 'Fase eliminada correctamente.']);
    }

    public function storeLaborCost(ProductionLaborCostValidate $request): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['labor_cost']) && $data['hours_worked'] && $data['hourly_rate']) {
            $data['labor_cost'] = round($data['hours_worked'] * $data['hourly_rate'], 2);
        }

        ProductionLaborCost::create($data);

        return redirect()->back()->with('success', 'Labor y costo de mano de obra registrado exitosamente.');
    }

    public function deleteLaborCost(Request $request): JsonResponse
    {
        $labor = ProductionLaborCost::findOrFail($request->input('id'));
        $labor->delete();

        return response()->json(['success' => true, 'message' => 'Registro de labor eliminado correctamente.']);
    }
}
