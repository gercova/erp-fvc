<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductionRawMaterialValidate;
use App\Models\ProductionRawMaterial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductionRawMaterialController extends Controller
{
    public function index(): View
    {
        $totalItems = ProductionRawMaterial::count();
        $categoriesCount = ProductionRawMaterial::distinct('category')->count();
        $activeItems = ProductionRawMaterial::where('is_active', true)->count();

        return view('admin.production.raw_materials.index', compact('totalItems', 'categoriesCount', 'activeItems'));
    }

    public function get(Request $request): JsonResponse
    {
        $query = ProductionRawMaterial::query();

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search_term')) {
            $term = trim($request->input('search_term'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $query->orderBy('name');

        return datatables()->of($query)
            ->addColumn('category_col', function (ProductionRawMaterial $material) {
                $color = match ($material->category) {
                    'fertilizer'            => 'success',
                    'seed'                  => 'primary',
                    'medication'            => 'danger',
                    'feed'                  => 'warning text-dark',
                    'insecticide_fungicide' => 'info text-dark',
                    default                 => 'secondary',
                };
                return '<span class="badge bg-' . $color . '">' . e($material->category_label) . '</span>';
            })
            ->addColumn('name_col', function (ProductionRawMaterial $material) {
                $html = '<div class="fw-bold text-dark">' . e($material->name) . '</div>';
                if ($material->description) {
                    $html .= '<small class="text-muted">' . e($material->description) . '</small>';
                }
                return $html;
            })
            ->addColumn('unit_col', function (ProductionRawMaterial $material) {
                return '<span class="badge bg-light text-dark font-monospace border">' . e($material->unit_of_measurement) . '</span>';
            })
            ->addColumn('cost_col', function (ProductionRawMaterial $material) {
                return 'S/ ' . number_format($material->default_unit_cost, 2);
            })
            ->addColumn('status_col', function (ProductionRawMaterial $material) {
                return $material->is_active
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-secondary">Inactivo</span>';
            })
            ->addColumn('actions', function (ProductionRawMaterial $material) {
                return '
                    <div class="d-flex justify-content-end gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-material"
                            data-id="' . $material->id . '"
                            data-name="' . e($material->name) . '"
                            data-category="' . $material->category . '"
                            data-unit="' . e($material->unit_of_measurement) . '"
                            data-cost="' . $material->default_unit_cost . '"
                            data-desc="' . e($material->description) . '">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-material" data-id="' . $material->id . '">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['category_col', 'name_col', 'unit_col', 'cost_col', 'status_col', 'actions'])
            ->make(true);
    }

    public function store(ProductionRawMaterialValidate $request): RedirectResponse
    {
        ProductionRawMaterial::create($request->validated());

        return redirect()->back()->with('success', 'Insumo / Materia prima registrada exitosamente.');
    }

    public function update(ProductionRawMaterialValidate $request, $id): RedirectResponse
    {
        $material = ProductionRawMaterial::findOrFail($id);
        $material->update($request->validated());

        return redirect()->back()->with('success', 'Insumo / Materia prima actualizada correctamente.');
    }

    public function delete(Request $request): JsonResponse
    {
        $material = ProductionRawMaterial::findOrFail($request->input('id'));
        $material->delete();

        return response()->json(['success' => true, 'message' => 'Insumo eliminado del catálogo.']);
    }
}
