<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProducedItemValidate;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductiveActivity;
use App\Models\ProducedItem;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProducedItemController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id', $activities->first()?->id);

        $query = ProducedItem::query();
        if ($selectedActivityId) {
            $query->where('productive_activity_id', $selectedActivityId);
        }

        $totalProduced = (clone $query)->count();
        $publishedCount = (clone $query)->where('is_published_to_sales', true)->count();
        $unpublishedCount = (clone $query)->where('is_published_to_sales', false)->count();

        return view('admin.production.produced_items.index', compact(
            'activities',
            'selectedActivityId',
            'totalProduced',
            'publishedCount',
            'unpublishedCount'
        ));
    }

    public function get(Request $request): JsonResponse
    {
        $query = ProducedItem::with(['activity', 'product']);

        if ($request->filled('activity_id')) {
            $query->where('productive_activity_id', $request->input('activity_id'));
        }

        if ($request->filled('is_published')) {
            $isPub = $request->input('is_published') === '1';
            $query->where('is_published_to_sales', $isPub);
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
            ->addColumn('activity_col', function (ProducedItem $item) {
                return '<span class="badge bg-light text-dark border">' . e($item->activity->name ?? '-') . '</span>';
            })
            ->addColumn('name_col', function (ProducedItem $item) {
                $html = '<div class="fw-bold text-dark">' . e($item->name) . '</div>';
                if ($item->description) {
                    $html .= '<small class="text-muted">' . e($item->description) . '</small>';
                }
                return $html;
            })
            ->addColumn('unit_col', function (ProducedItem $item) {
                return '<span class="badge bg-light text-dark font-monospace border">' . e($item->unit_of_measurement) . '</span>';
            })
            ->addColumn('cost_col', function (ProducedItem $item) {
                return 'S/ ' . number_format($item->standard_cost, 2);
            })
            ->addColumn('status_col', function (ProducedItem $item) {
                return $item->published_badge;
            })
            ->addColumn('actions', function (ProducedItem $item) {
                $publishBtn = '';
                if (!$item->is_published_to_sales || !$item->product_id) {
                    $publishBtn = '
                        <button type="button" class="btn btn-sm btn-outline-success btn-publish-product"
                            data-id="' . $item->id . '"
                            data-name="' . e($item->name) . '"
                            data-cost="' . $item->standard_cost . '"
                            title="Publicar en Catálogo Central POS">
                            <i class="fas fa-upload me-1"></i> Publicar al POS
                        </button>
                    ';
                } else {
                    $publishBtn = '
                        <a href="' . route('admin.products') . '" class="btn btn-sm btn-outline-primary" title="Ver en Catálogo Central">
                            <i class="fas fa-box-open me-1"></i> Ver en Catálogo
                        </a>
                    ';
                }

                return '
                    <div class="d-flex justify-content-end gap-1">
                        ' . $publishBtn . '
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-item" data-id="' . $item->id . '">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['activity_col', 'name_col', 'unit_col', 'cost_col', 'status_col', 'actions'])
            ->make(true);
    }

    public function store(ProducedItemValidate $request): RedirectResponse
    {
        ProducedItem::create($request->validated());

        return redirect()->back()->with('success', 'Producto de actividad productiva registrado con éxito.');
    }

    public function publishToSalesCatalog(Request $request): JsonResponse
    {
        $request->validate([
            'id'          => 'required|integer|exists:produced_items,id',
            'sale_price'  => 'required|numeric|min:0',
        ]);

        $item = ProducedItem::findOrFail($request->input('id'));

        if ($item->is_published_to_sales && $item->product_id) {
            return response()->json(['success' => false, 'message' => 'Este producto ya se encuentra publicado en el catálogo central.']);
        }

        // Buscar o asignar unidad
        $unit = Unit::where('descripcion', 'like', '%' . $item->unit_of_measurement . '%')->first()
            ?? Unit::first();

        // Buscar categoría agropecuaria o general
        $category = Category::where('descripcion', 'like', '%AGRO%')->first()
            ?? Category::first();

        $code = 'AP-' . strtoupper(Str::random(6));

        $product = Product::create([
            'codigo_interno' => $code,
            'codigo_barras'  => null,
            'codigo_sunat'   => null,
            'descripcion'    => $item->name,
            'idunidad'       => $unit?->id ?? 1,
            'idcategoria'    => $category?->id ?? 1,
            'igv'            => 1,
            'idcodigo_igv'   => 1, // Gravado
            'precio_compra'  => $item->standard_cost ?? 0,
            'precio_venta'   => $request->input('sale_price'),
            'stock_actual'   => 0,
            'opcion'         => 1,
        ]);

        $item->update([
            'product_id'            => $product->id,
            'is_published_to_sales' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Producto publicado con éxito en el catálogo central de ventas! Ya está disponible en el POS y Comprobantes.',
            'product_id' => $product->id,
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $item = ProducedItem::findOrFail($request->input('id'));
        $item->delete();

        return response()->json(['success' => true, 'message' => 'Producto eliminado del catálogo de la actividad.']);
    }
}
