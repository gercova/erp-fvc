<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityOrderValidate;
use App\Models\ActivityOrder;
use App\Models\Client;
use App\Models\ProducedItem;
use App\Models\ProductiveActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityOrderController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id', $activities->first()?->id);

        $clients = Client::orderBy('nombres')->get();
        $producedItems = ProducedItem::when($selectedActivityId, fn($q) => $q->where('productive_activity_id', $selectedActivityId))
            ->orderBy('name')
            ->get();

        $queryBase = ActivityOrder::query();
        if ($selectedActivityId) {
            $queryBase->where('productive_activity_id', $selectedActivityId);
        }

        $totalOrders = (clone $queryBase)->count();
        $pendingOrders = (clone $queryBase)->where('status', 'pending')->count();
        $confirmedOrders = (clone $queryBase)->where('status', 'confirmed')->count();
        $totalAmount = (clone $queryBase)->where('status', '!=', 'cancelled')->sum('total_amount');

        return view('admin.commercialization.orders.index', compact(
            'activities',
            'selectedActivityId',
            'clients',
            'producedItems',
            'totalOrders',
            'pendingOrders',
            'confirmedOrders',
            'totalAmount'
        ));
    }

    public function get(Request $request): JsonResponse
    {
        $query = ActivityOrder::with(['client', 'activity', 'producedItem', 'creator']);

        if ($request->filled('activity_id')) {
            $query->where('productive_activity_id', $request->input('activity_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search_term')) {
            $term = trim($request->input('search_term'));
            $query->where(function ($q) use ($term) {
                $q->where('order_code', 'like', "%{$term}%")
                  ->orWhereHas('client', fn($c) => $c->where('nombres', 'like', "%{$term}%")->orWhere('nro_documento', 'like', "%{$term}%"))
                  ->orWhereHas('producedItem', fn($p) => $p->where('name', 'like', "%{$term}%"));
            });
        }

        $query->orderBy('order_date', 'desc');

        return datatables()->of($query)
            ->addColumn('order_code_col', function (ActivityOrder $order) {
                return '<span class="font-monospace fw-bold text-primary">' . e($order->order_code) . '</span>';
            })
            ->addColumn('client_col', function (ActivityOrder $order) {
                $html = '<div class="fw-bold text-dark">' . e($order->client->nombres ?? 'Cliente') . '</div>';
                if ($order->client?->nro_documento) {
                    $html .= '<small class="text-muted font-monospace">' . e($order->client->nro_documento) . '</small>';
                }
                return $html;
            })
            ->addColumn('item_col', function (ActivityOrder $order) {
                $html = '<div class="fw-semibold">' . e($order->producedItem->name ?? 'Producto') . '</div>';
                if ($order->activity) {
                    $html .= '<small class="badge bg-light text-dark border">' . e($order->activity->name) . '</small>';
                }
                return $html;
            })
            ->addColumn('quantity_col', function (ActivityOrder $order) {
                return '<span class="fw-bold">' . number_format($order->quantity, 2) . '</span> <small class="text-muted">' . e($order->producedItem->unit_of_measurement ?? '') . '</small>';
            })
            ->addColumn('price_col', function (ActivityOrder $order) {
                return 'S/ ' . number_format($order->unit_price, 2);
            })
            ->addColumn('total_col', function (ActivityOrder $order) {
                $html = '<div class="fw-bold text-dark">S/ ' . number_format($order->total_amount, 2) . '</div>';
                if ($order->advance_payment > 0) {
                    $html .= '<small class="text-success d-block">Adelanto: S/ ' . number_format($order->advance_payment, 2) . '</small>';
                    $html .= '<small class="text-danger d-block">Resta: S/ ' . number_format($order->balance_pending, 2) . '</small>';
                }
                return $html;
            })
            ->addColumn('date_col', function (ActivityOrder $order) {
                $orderDate = $order->order_date ? $order->order_date->format('d/m/Y') : '-';
                $delivery = $order->expected_delivery_date ? $order->expected_delivery_date->format('d/m/Y') : 'Por definir';
                return '<div><small class="text-muted">Pedido: ' . $orderDate . '</small></div><div><small class="text-muted">Entrega: ' . $delivery . '</small></div>';
            })
            ->addColumn('status_col', function (ActivityOrder $order) {
                return $order->status_badge;
            })
            ->addColumn('actions', function (ActivityOrder $order) {
                return '
                    <div class="d-flex justify-content-end gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-update-status"
                            data-id="' . $order->id . '"
                            data-code="' . e($order->order_code) . '"
                            data-status="' . $order->status . '"
                            title="Cambiar Estado">
                            <i class="fas fa-tasks"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-order" data-id="' . $order->id . '">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['order_code_col', 'client_col', 'item_col', 'quantity_col', 'price_col', 'total_col', 'date_col', 'status_col', 'actions'])
            ->make(true);
    }

    public function store(ActivityOrderValidate $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by_user_id'] = Auth::id() ?? 1;

        if (empty($data['total_amount']) && isset($data['quantity'], $data['unit_price'])) {
            $data['total_amount'] = round($data['quantity'] * $data['unit_price'], 2);
        }

        ActivityOrder::create($data);

        return redirect()->back()->with('success', 'Pedido / Preventa registrada exitosamente.');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'id'     => 'required|integer|exists:activity_orders,id',
            'status' => 'required|string|in:pending,confirmed,delivered,invoiced,cancelled',
        ]);

        $order = ActivityOrder::findOrFail($request->input('id'));
        $order->update(['status' => $request->input('status')]);

        return response()->json(['success' => true, 'message' => 'Estado del pedido actualizado a ' . $order->status . '.']);
    }

    public function delete(Request $request): JsonResponse
    {
        $order = ActivityOrder::findOrFail($request->input('id'));
        $order->delete();

        return response()->json(['success' => true, 'message' => 'Pedido eliminado correctamente.']);
    }
}
