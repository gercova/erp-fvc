<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChartOfAccountRequest;
use App\Models\ChartOfAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ChartOfAccountController extends Controller
{
    /**
     * Display the Chart of Accounts index page.
     */
    public function index(): View
    {
        $elements = [
            1 => 'Elemento 1: Activo Disponible y Exigible',
            2 => 'Elemento 2: Activo Realizable',
            3 => 'Elemento 3: Activo Inmovilizado',
            4 => 'Elemento 4: Pasivo',
            5 => 'Elemento 5: Patrimonio Neto',
            6 => 'Elemento 6: Gastos por Naturaleza',
            7 => 'Elemento 7: Ingresos',
            8 => 'Elemento 8: Saldos Intermediarios de Gestión',
            9 => 'Elemento 9: Costos de Producción y Función',
            0 => 'Elemento 0: Cuentas de Orden',
        ];

        return view('admin.accounting.chart_of_accounts.index', compact('elements'));
    }

    /**
     * Return JSON for Yajra DataTables.
     */
    public function data(Request $request): JsonResponse
    {
        $query = ChartOfAccount::query()->with('parent');

        if ($request->filled('element') && $request->element !== 'ALL') {
            $query->where('element', (int) $request->element);
        }

        if ($request->filled('accepts_movements') && $request->accepts_movements !== 'ALL') {
            $val = (bool) $request->accepts_movements;
            $query->where('accepts_movements', $val);
        }

        if ($request->filled('active') && $request->active !== 'ALL') {
            $val = (bool) $request->active;
            $query->where('active', $val);
        }

        return DataTables::of($query)
            ->addColumn('level_badge', function (ChartOfAccount $account) {
                $labels = [
                    1 => 'Elemento (1d)',
                    2 => 'Cuenta (2d)',
                    3 => 'Subcuenta (3d)',
                    4 => 'Divisionaria (4d)',
                    5 => 'Subdivisionaria (5d)',
                ];
                $color = match ($account->level) {
                    1 => 'bg-dark text-white',
                    2 => 'bg-primary text-white',
                    3 => 'bg-info text-white',
                    4 => 'bg-secondary text-white',
                    default => 'bg-light text-dark border',
                };
                return '<span class="badge ' . $color . '">' . ($labels[$account->level] ?? "Nivel {$account->level}") . '</span>';
            })
            ->addColumn('nature_badge', function (ChartOfAccount $account) {
                return $account->nature?->value === 'DEBIT'
                    ? '<span class="badge bg-success-soft text-success border border-success">Deudora</span>'
                    : '<span class="badge bg-danger-soft text-danger border border-danger">Acreedora</span>';
            })
            ->addColumn('movements_badge', function (ChartOfAccount $account) {
                return $account->accepts_movements
                    ? '<span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i> Imputable</span>'
                    : '<span class="badge bg-light text-muted border">Título / Resumen</span>';
            })
            ->addColumn('status_badge', function (ChartOfAccount $account) {
                return $account->active
                    ? '<span class="badge bg-success-soft text-success border border-success fw-bold">Activo</span>'
                    : '<span class="badge bg-secondary text-white">Inactivo</span>';
            })
            ->addColumn('actions', function (ChartOfAccount $account) {
                $editBtn = '<button class="btn btn-sm btn-outline-primary btn-edit-account me-1" data-id="' . $account->id . '" title="Editar"><i class="fas fa-edit"></i></button>';
                $deleteBtn = '<button class="btn btn-sm btn-outline-danger btn-delete-account" data-id="' . $account->id . '" data-code="' . e($account->code) . '" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';
                return '<div class="btn-group btn-group-sm">' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['level_badge', 'nature_badge', 'movements_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Return hierarchical tree in JSON format.
     */
    public function tree(Request $request): JsonResponse
    {
        $accounts = ChartOfAccount::orderBy('code')->get();

        $tree = [];
        $accountsById = [];

        foreach ($accounts as $acc) {
            $accountsById[$acc->id] = [
                'id'                => $acc->id,
                'code'              => $acc->code,
                'name'              => $acc->name,
                'element'           => $acc->element,
                'level'             => $acc->level,
                'nature'            => $acc->nature?->value,
                'classification'    => $acc->classification?->value,
                'accepts_movements' => (bool) $acc->accepts_movements,
                'active'            => (bool) $acc->active,
                'parent_id'         => $acc->parent_id,
                'children'          => [],
            ];
        }

        foreach ($accountsById as $id => &$node) {
            if ($node['parent_id'] && isset($accountsById[$node['parent_id']])) {
                $accountsById[$node['parent_id']]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }

        return response()->json([
            'success' => true,
            'tree'    => $tree,
            'total'   => count($accounts),
        ]);
    }

    /**
     * Store a newly created account.
     */
    public function store(ChartOfAccountRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['accepts_movements'] = $request->boolean('accepts_movements');
        $data['allows_movement']   = $data['accepts_movements'];
        $data['active']            = $request->boolean('active', true);
        $data['is_active']         = $data['active'];

        $account = ChartOfAccount::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable creada exitosamente.',
            'account' => $account,
        ], 201);
    }

    /**
     * Display account details.
     */
    public function show(int $id): JsonResponse
    {
        $account = ChartOfAccount::with('parent')->findOrFail($id);
        return response()->json([
            'success' => true,
            'account' => $account,
        ]);
    }

    /**
     * Update an existing account.
     */
    public function update(ChartOfAccountRequest $request, int $id): JsonResponse
    {
        $account = ChartOfAccount::findOrFail($id);

        $data = $request->validated();
        if ($request->has('accepts_movements')) {
            $data['accepts_movements'] = $request->boolean('accepts_movements');
            $data['allows_movement']   = $data['accepts_movements'];
        }
        if ($request->has('active')) {
            $data['active']    = $request->boolean('active');
            $data['is_active'] = $data['active'];
        }

        $account->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable actualizada exitosamente.',
            'account' => $account,
        ]);
    }

    /**
     * Soft delete an account if it has no posted journal lines or active child accounts.
     */
    public function destroy(int $id): JsonResponse
    {
        $account = ChartOfAccount::findOrFail($id);

        if ($account->lines()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la cuenta porque registra movimientos en el Libro Diario.',
            ], 422);
        }

        if ($account->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la cuenta porque contiene subcuentas asociadas.',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable eliminada correctamente.',
        ]);
    }
}
