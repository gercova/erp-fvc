<?php

namespace App\Http\Controllers;

use App\Models\AccountingPostingFailure;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class AccountingPostingFailureController extends Controller
{
    public function __construct(
        protected JournalPostingService $postingService
    ) {}

    /**
     * Display the failures view.
     */
    public function index(): View
    {
        $pendingCount = AccountingPostingFailure::where('status', 'FAILED')->count();
        $reprocessedCount = AccountingPostingFailure::where('status', 'REPROCESSED')->count();

        return view('admin.accounting.failures.index', compact('pendingCount', 'reprocessedCount'));
    }

    /**
     * DataTables endpoint for failures list.
     */
    public function data(Request $request): JsonResponse
    {
        $query = AccountingPostingFailure::query()->latest('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return DataTables::of($query)
            ->addColumn('source_info', function (AccountingPostingFailure $failure) {
                if (!$failure->source_type) {
                    return '<span class="text-muted">N/A</span>';
                }
                $shortType = class_basename($failure->source_type);
                return "<strong>{$shortType}</strong> #{$failure->source_id}";
            })
            ->editColumn('status', function (AccountingPostingFailure $failure) {
                return match ($failure->status) {
                    'FAILED'      => '<span class="badge bg-danger">Fallido</span>',
                    'REPROCESSED' => '<span class="badge bg-success">Reprocesado</span>',
                    'IGNORED'     => '<span class="badge bg-secondary">Ignorado</span>',
                    default       => '<span class="badge bg-info">' . e($failure->status) . '</span>',
                };
            })
            ->editColumn('created_at', function (AccountingPostingFailure $failure) {
                return $failure->created_at?->format('d/m/Y H:i:s') ?? '-';
            })
            ->addColumn('actions', function (AccountingPostingFailure $failure) {
                if ($failure->status === 'REPROCESSED') {
                    return '<button class="btn btn-sm btn-outline-secondary" disabled><i class="fas fa-check"></i> Reprocesado</button>';
                }

                return '<button class="btn btn-sm btn-warning btn-reprocess" data-id="' . $failure->id . '">
                    <i class="fas fa-sync-alt"></i> Reprocesar
                </button>';
            })
            ->rawColumns(['source_info', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Reprocess a specific failed record.
     */
    public function reprocess(int $id): JsonResponse
    {
        try {
            $this->postingService->reprocessFailure($id);

            return response()->json([
                'success' => true,
                'message' => "Asiento contable #{$id} reprocesado y asentado con éxito.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reprocesar: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reprocess all pending failures.
     */
    public function reprocessAll(): JsonResponse
    {
        $failures = AccountingPostingFailure::where('status', 'FAILED')->get();
        $success = 0;
        $failed = 0;

        foreach ($failures as $failure) {
            try {
                $this->postingService->reprocessFailure($failure->id);
                $success++;
            } catch (Throwable) {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Reprocesados: {$success} exitosos, {$failed} fallidos.",
            'success_count' => $success,
            'failed_count'  => $failed,
        ]);
    }
}
