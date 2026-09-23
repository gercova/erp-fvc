<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetValidate;
use App\Exports\AssetTemplateExport;
use App\Imports\AssetsImport;
use App\Models\Area;
use App\Models\Asset;
use App\Models\Business;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetController extends Controller
{
    public static function generateQrSvg(string $content, int $size = 150): string {
        $prev = error_reporting(error_reporting() & ~E_DEPRECATED);
        try {
            return (string) QrCode::size($size)->margin(1)->generate($content);
        } finally {
            error_reporting($prev);
        }
    }

    public function index(Request $request): View {
        $user = Auth::user();
        $isGlobalSupervisor = $user->hasRole(['SUPERADMIN', 'ADMIN', 'PATRIMONIO', 'ABASTECIMIENTO', 'DIRECTOR_GENERAL']);

        // Determinar áreas accesibles
        if ($isGlobalSupervisor) {
            $areas = Area::orderBy('name')->get();
        } else {
            $headedAreas    = Area::where('head_user_id', $user->id)->pluck('id')->toArray();
            $assignedAreas  = $user->employeeAreaDetails()->pluck('area_id')->toArray();
            if ($user->primaryArea) {
                $assignedAreas[] = $user->primaryArea->id;
            }
            $allowedAreaIds = array_unique(array_merge($headedAreas, $assignedAreas));
            $areas = Area::whereIn('id', $allowedAreaIds)->orderBy('name')->get();
        }

        // Área seleccionada actualmente
        $selectedAreaId = $request->input('area_id');
        if (!$selectedAreaId || !$areas->contains('id', $selectedAreaId)) {
            $selectedAreaId = $areas->first()?->id ?? null;
        }

        $selectedArea = $selectedAreaId ? Area::with('head')->find($selectedAreaId) : null;

        // Métricas de activos para el área o globales
        $queryBase = Asset::query();
        if ($selectedAreaId) {
            $queryBase->where('area_id', $selectedAreaId);
        }

        $totalCount        = (clone $queryBase)->count();
        $totalCost         = (clone $queryBase)->sum('costo');
        $goodCount         = (clone $queryBase)->where('condicion', 'B')->count();
        $fairCount         = (clone $queryBase)->where('condicion', 'R')->count();
        $poorCount         = (clone $queryBase)->where('condicion', 'M')->count();
        $writtenOffCount   = (clone $queryBase)->where('condicion', 'BAJA')->count();
        $reconciledCount   = (clone $queryBase)->where('is_reconciled', true)->count();
        $reconciledPercent = $totalCount > 0 ? round(($reconciledCount / $totalCount) * 100, 1) : 0;

        return view('admin.assets.index', compact(
            'areas',
            'selectedArea',
            'selectedAreaId',
            'isGlobalSupervisor',
            'totalCount',
            'totalCost',
            'goodCount',
            'fairCount',
            'poorCount',
            'writtenOffCount',
            'reconciledCount',
            'reconciledPercent'
        ));
    }

    public function get(Request $request): JsonResponse {
        $areaId = $request->input('area_id');
        $query  = Asset::with(['area', 'user', 'reconciledBy'])
            ->when($areaId, function ($q) use ($areaId) {
                $q->where('area_id', $areaId);
            })
            ->when($request->filled('filter_condicion'), function ($q) use ($request) {
                $cond = $request->input('filter_condicion');
                $q->where('condicion', $cond);
            })
            ->when($request->filled('filter_tipo_adquisicion'), function ($q) use ($request) {
                $tipo = $request->input('filter_tipo_adquisicion');
                $q->where('tipo_adquisicion', $tipo);
            })
            ->when($request->filled('filter_reconciled'), function ($q) use ($request) {
                $rec = $request->input('filter_reconciled');
                $q->where('is_reconciled', $rec === '1');
            })
            ->when($request->filled('filter_search'), function ($q) use ($request) {
                $search = trim($request->input('filter_search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('codigo_producto', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%")
                        ->orWhere('marca', 'like', "%{$search}%")
                        ->orWhere('modelo', 'like', "%{$search}%")
                        ->orWhere('serie', 'like', "%{$search}%")
                        ->orWhere('ubicacion', 'like', "%{$search}%")
                        ->orWhere('custodio', 'like', "%{$search}%")
                        ->orWhere('observaciones', 'like', "%{$search}%");
                });
            })
            ->orderBy('orden', 'asc');

        return datatables()->of($query)
            ->addColumn('orden_fmt', function (Asset $asset) {
                return e($asset->formatted_orden);
            })
            ->addColumn('codigo_col', function (Asset $asset) {
                $codigo = $asset->codigo ? '<div class="text-dark">' . e($asset->codigo) . '</div>' : '';
                if ($asset->codigo_producto) {
                    $codigo .= '<small class="text-muted"><i class="fas fa-barcode me-1"></i>' . e($asset->codigo_producto) . '</small>';
                }
                if (! $codigo) {
                    $codigo = '<span class="text-muted fst-italic">-</span>';
                }
                return $codigo;
            })
            ->addColumn('descripcion_col', function (Asset $asset) {
                $html = '<div class="fw-semibold text-dark">' . e($asset->descripcion) . '</div>';
                if ($asset->foto_path) {
                    $html .= '<small class="text-info"><i class="fas fa-camera me-1"></i>Foto adjunta</small>';
                }
                return $html;
            })
            ->addColumn('tecnico_col', function (Asset $asset) {
                $marca  = ($asset->marca && $asset->marca   !== 'SIN MARCA') ? e($asset->marca) : 'SIN MARCA';
                $modelo = ($asset->modelo && $asset->modelo !== 'SIN MODELO') ? ' / ' . e($asset->modelo) : '';
                $serie  = ($asset->serie && $asset->serie   !== 'SIN SERIE') ? e($asset->serie) : 'SIN SERIE';
                return '<div class="text-dark fw-medium small">' . $marca . $modelo . '</div><div class="small text-muted font-monospace">' . $serie . '</div>';
            })
            ->addColumn('condicion_badge', function (Asset $asset) {
                return $asset->condicion_badge;
            })
            ->addColumn('ubicacion_col', function (Asset $asset) {
                $html = '<div>' . e($asset->ubicacion) . '</div>';
                if ($asset->custodio) {
                    $html .= '<small class="text-muted"><i class="fas fa-user-tag me-1"></i>' . e($asset->custodio) . '</small>';
                }
                return $html;
            })
            ->addColumn('reconciled_badge', function (Asset $asset) {
                if ($asset->is_reconciled) {
                    return '<button class="btn btn-sm btn-outline-success py-0 px-2 btn-reconcile-toggle" data-id="' . $asset->id . '" title="Auditado físicamente"><i class="fas fa-check me-1"></i>Auditado</button>';
                }
                return '<button class="btn btn-sm btn-outline-warning py-0 px-2 btn-reconcile-toggle" data-id="' . $asset->id . '" title="Pendiente de verificación física"><i class="fas fa-clock me-1"></i>Pendiente</button>';
            })
            ->addColumn('acciones', function (Asset $asset) {
                $id         = $asset->id;
                $showUrl    = route('inventory.show', $id);
                $editUrl    = route('inventory.edit', $id);
                $labelUrl   = route('inventory.qr_labels', ['asset_id' => $id]);
                return '<div class="d-flex align-items-center justify-content-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-dark py-1 px-2 btn-show-qr" data-id="' . $asset->id . '" data-uuid="' . $asset->uuid . '" data-codigo="' . e($asset->codigo ?? $asset->codigo_producto ?? 'ACT-' . $asset->id) . '" data-desc="' . e($asset->descripcion) . '" title="Código QR"><i class="fas fa-qrcode"></i></button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle py-1 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-1">
                            <li><a class="dropdown-item py-1" href="' . $showUrl . '"><i class="fas fa-eye me-2"></i>Ficha Técnica</a></li>
                            <li><button class="dropdown-item py-1 btn-quick-detail" data-id="' . $asset->id . '"><i class="fas fa-info-circle me-2"></i>Vista Rápida</button></li>
                            <li><a class="dropdown-item py-1" href="' . $labelUrl . '" target="_blank"><i class="fas fa-tag me-2"></i>Imprimir Etiqueta</a></li>
                            <li><a class="dropdown-item py-1" href="' . $editUrl . '"><i class="fas fa-edit me-2"></i>Editar</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><button class="dropdown-item py-1 btn-delete-asset" data-id="' . $id . '"><i class="fas fa-trash-alt me-2"></i>Dar de Baja / Eliminar</button></li>
                        </ul>
                    </div>
                </div>';
            })
            ->rawColumns([
                'orden_fmt',
                'codigo_col',
                'descripcion_col',
                'tecnico_col',
                'condicion_badge',
                'ubicacion_col',
                'reconciled_badge',
                'acciones'
            ])
            ->make(true);
    }

    public function downloadTemplate(Request $request) {
        $areaId = $request->input('area_id');
        $area = $areaId ? Area::find($areaId) : null;
        $filename = 'plantilla_inventario_' . Str::slug($area?->name ?? 'institucional') . '.xlsx';

        return Excel::download(new AssetTemplateExport($area?->name), $filename);
    }

    public function uploadExcel(Request $request): JsonResponse {
        $excel = $request->file('excel');

        if (! $excel) {
            return response()->json([
                'status' => false,
                'msg'    => 'Seleccione un archivo Excel para importar.',
                'type'   => 'warning',
            ], 422);
        }

        $extension = strtolower((string) $excel->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            return response()->json([
                'status' => false,
                'msg'    => 'El archivo debe estar en formato .xlsx o .xls.',
                'type'   => 'warning',
            ], 422);
        }

        $targetAreaId = $request->input('area_id') ? (int) $request->input('area_id') : null;
        $overwrite = $request->boolean('overwrite', false);
        $userId = Auth::id();

        try {
            $import = new AssetsImport($targetAreaId, $userId, $overwrite);
            Excel::import($import, $excel);
            $summary = $import->getSummary();

            return response()->json([
                'status'  => true,
                'msg'     => "Importación completada: {$summary['created']} bien(es) registrado(s), {$summary['updated']} actualizado(s).",
                'type'    => 'success',
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'msg'    => 'Observación al importar archivo Excel: ' . $e->getMessage(),
                'type'   => 'warning',
            ], 422);
        }
    }

    public function quickDetail(int $id): JsonResponse {
        $asset = Asset::with(['area', 'user', 'reconciledBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'asset'   => [
                'id'                => $asset->id,
                'uuid'              => $asset->uuid,
                'orden'             => $asset->formatted_orden,
                'codigo'            => $asset->codigo ?: 'NO ASIGNADO',
                'codigo_producto'   => $asset->codigo_producto ?: 'NO ASIGNADO',
                'descripcion'       => $asset->descripcion,
                'marca'             => $asset->marca ?: 'SIN MARCA',
                'modelo'            => $asset->modelo ?: 'SIN MODELO',
                'serie'             => $asset->serie ?: 'SIN SERIE',
                'costo'             => number_format((float) $asset->costo, 2),
                'condicion'         => $asset->condicion_text,
                'condicion_badge'   => $asset->condicion_badge,
                'tipo_adquisicion'  => $asset->tipo_adquisicion_text,
                'fecha_adquisicion' => $asset->fecha_adquisicion?->format('d/m/Y') ?? ($asset->anio_adquisicion ?: '-'),
                'ubicacion'         => $asset->ubicacion,
                'custodio'          => $asset->custodio ?: 'ENC. ÁREA',
                'observaciones'     => $asset->observaciones ?: 'Sin observaciones',
                'is_reconciled'     => $asset->is_reconciled,
                'reconciled_at'     => $asset->reconciled_at?->format('d/m/Y H:i'),
                'reconciled_by'     => $asset->reconciledBy?->nombres,
                'foto_url'          => $asset->foto_url,
                'area_name'         => $asset->area?->name,
                'show_url'          => route('inventory.show', $asset->id),
                'edit_url'          => route('inventory.edit', $asset->id),
                'qr_url'            => route('inventory.qr_svg', $asset->uuid),
            ]
        ]);
    }

    public function create(Request $request): View {
        $user = Auth::user();
        $isGlobalSupervisor = $user->hasRole(['SUPERADMIN', 'ADMIN', 'PATRIMONIO', 'ABASTECIMIENTO', 'DIRECTOR_GENERAL']);

        if ($isGlobalSupervisor) {
            $areas = Area::orderBy('name')->get();
        } else {
            $headedAreas = Area::where('head_user_id', $user->id)->pluck('id')->toArray();
            $assignedAreas = $user->employeeAreaDetails()->pluck('area_id')->toArray();
            if ($user->primaryArea) {
                $assignedAreas[] = $user->primaryArea->id;
            }
            $allowedAreaIds = array_unique(array_merge($headedAreas, $assignedAreas));
            $areas = Area::whereIn('id', $allowedAreaIds)->orderBy('name')->get();
        }

        $selectedAreaId = $request->input('area_id') ?: ($areas->first()?->id ?? null);
        $nextOrden = 1;
        if ($selectedAreaId) {
            $maxOrden = Asset::where('area_id', $selectedAreaId)->max('orden') ?? 0;
            $nextOrden = $maxOrden + 1;
        }

        return view('admin.assets.create', compact('areas', 'selectedAreaId', 'nextOrden'));
    }

    public function store(AssetValidate $request): JsonResponse|RedirectResponse {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        // Subida de imagen si aplica
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = 'asset_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/assets');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }
            $file->move($destinationPath, $filename);
            $data['foto_path'] = 'uploads/assets/' . $filename;
        }

        $asset = Asset::create($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'El bien patrimonial fue registrado exitosamente.',
                'asset'   => $asset,
            ]);
        }

        return redirect()->route('inventory.index', ['area_id' => $asset->area_id])
            ->with('toast_success', 'El bien patrimonial fue registrado correctamente.');
    }

    public function show(int $id): View {
        $asset = Asset::with(['area', 'user', 'reconciledBy'])->findOrFail($id);
        $qrSvg = self::generateQrSvg($asset->verification_url, 180);

        return view('admin.assets.show', compact('asset', 'qrSvg'));
    }

    public function edit(int $id): View {
        $asset = Asset::findOrFail($id);
        $user = Auth::user();
        $isGlobalSupervisor = $user->hasRole(['SUPERADMIN', 'ADMIN', 'PATRIMONIO', 'ABASTECIMIENTO', 'DIRECTOR_GENERAL']);

        if ($isGlobalSupervisor) {
            $areas = Area::orderBy('name')->get();
        } else {
            $headedAreas = Area::where('head_user_id', $user->id)->pluck('id')->toArray();
            $assignedAreas = $user->employeeAreaDetails()->pluck('area_id')->toArray();
            if ($user->primaryArea) {
                $assignedAreas[] = $user->primaryArea->id;
            }
            $allowedAreaIds = array_unique(array_merge($headedAreas, $assignedAreas));
            $areas = Area::whereIn('id', $allowedAreaIds)->orderBy('name')->get();
        }

        return view('admin.assets.edit', compact('asset', 'areas'));
    }

    public function update(AssetValidate $request, int $id): JsonResponse|RedirectResponse {
        $asset = Asset::findOrFail($id);
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            // Eliminar foto previa si existe
            if ($asset->foto_path && File::exists(public_path($asset->foto_path))) {
                File::delete(public_path($asset->foto_path));
            }

            $file = $request->file('foto');
            $filename = 'asset_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/assets');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }
            $file->move($destinationPath, $filename);
            $data['foto_path'] = 'uploads/assets/' . $filename;
        }

        $asset->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'El bien patrimonial fue actualizado exitosamente.',
                'asset'   => $asset,
            ]);
        }

        return redirect()->route('inventory.index', ['area_id' => $asset->area_id])
            ->with('toast_success', 'El bien patrimonial fue actualizado correctamente.');
    }

    public function delete(Request $request): JsonResponse {
        $id = $request->input('id');
        $asset = Asset::findOrFail($id);
        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'El bien fue dado de baja / eliminado del inventario.',
        ]);
    }

    public function toggleReconcile(Request $request, int $id): JsonResponse {
        $asset = Asset::findOrFail($id);
        $asset->is_reconciled = !$asset->is_reconciled;
        $asset->reconciled_at = $asset->is_reconciled ? now() : null;
        $asset->reconciled_by = $asset->is_reconciled ? Auth::id() : null;
        $asset->save();

        return response()->json([
            'success'       => true,
            'is_reconciled' => $asset->is_reconciled,
            'message'       => $asset->is_reconciled
                ? 'Activo verificado y conciliado físicamente.'
                : 'Se ha revertido la verificación física del activo.',
        ]);
    }

    public function qrSvg(string $uuid): Response {
        $asset = Asset::where('uuid', $uuid)->firstOrFail();
        $svg = self::generateQrSvg($asset->verification_url, 220);

        return response($svg, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="qr_' . $asset->uuid . '.svg"',
        ]);
    }

    public function printQrLabels(Request $request): View {
        $assetId = $request->input('asset_id');
        $areaId  = $request->input('area_id');

        if ($assetId) {
            $assets = Asset::with('area')->where('id', $assetId)->get();
        } elseif ($areaId) {
            $assets = Asset::with('area')->where('area_id', $areaId)->orderBy('orden')->get();
        } else {
            $assets = Asset::with('area')->take(50)->orderBy('orden')->get();
        }

        $business = Business::first();

        // Pre-render QR SVGs for efficiency
        $labels = $assets->map(function (Asset $a) {
            return [
                'asset'  => $a,
                'qr_svg' => self::generateQrSvg($a->verification_url, 130),
            ];
        });

        return view('admin.assets.qr_sheet', compact('labels', 'business'));
    }

    public function publicVerify(string $uuid): View {
        $asset = Asset::with('area')->where('uuid', $uuid)->firstOrFail();
        $business = Business::first();

        return view('admin.assets.public_verify', compact('asset', 'business'));
    }

    public function printInventoryPdf(Request $request): Response {
        $areaId = $request->input('area_id');
        if (!$areaId) {
            $areaId = Area::first()?->id;
        }

        $area = Area::with(['head'])->findOrFail($areaId);
        $assets = Asset::where('area_id', $areaId)->orderBy('orden')->get();

        $business = Business::first();

        // Obtener datos del encabezado oficial (Screenshot 2 & 3)
        $responsableArea = $request->input('responsable') ?: ($area->head?->nombres ?? 'NO ASIGNADO');
        $realizadoPor    = $request->input('realizado_por') ?: 'Tec. OSORIO SANCHEZ, CECILIA ISABEL';
        $fechaInventario = $request->input('fecha_inventario') ?: date('Y-m-d');
        $periodo         = $request->input('periodo') ?: date('Y');

        // Buscar si existe un AssetInventory registrado con firmas digitales
        $inventoryDoc = \App\Models\AssetInventory::with('approvals')
            ->where('area_id', $areaId)
            ->where('periodo', $periodo)
            ->first();

        $pdf = Pdf::loadView('admin.assets.pdf.inventory_format', compact(
            'area',
            'assets',
            'business',
            'responsableArea',
            'realizadoPor',
            'fechaInventario',
            'periodo',
            'inventoryDoc'
        ))->setPaper('a4', 'landscape');

        $filename = 'INVENTARIO_' . Str::slug($area->name) . '_' . $periodo . '.pdf';
        return $pdf->stream($filename);
    }
}
