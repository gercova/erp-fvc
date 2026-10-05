<?php

namespace App\Http\Controllers;

use App\Http\Requests\AreaValidate;
use App\Models\Area;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(): View {
        return view('admin.areas.list', [
            'areas' => Area::query()->orderBy('name')->get(['id', 'code', 'name']),
            'heads' => User::query()->where('estado', 1)->orderBy('nombres')->get(['id', 'nombres', 'user']),
            'types' => $this->areaTypes(),
        ]);
    }

    public function get(): JsonResponse {
        $areas = Area::query()
            ->with(['parent:id,code,name', 'head:id,nombres,user'])
            ->orderBy('level')
            ->orderBy('name');

        return datatables()
            ->of($areas)
            ->addColumn('checkbox', function (Area $area) {
                return '<div class="form-check d-flex justify-content-center m-0">
                            <input class="form-check-input check-area-row" type="checkbox" value="' . (int) $area->id . '" data-name="' . e((string) $area->name) . '">
                        </div>';
            })
            ->addColumn('codigo', function (Area $area) {
                return '<span class="badge bg-primary-subtle text-primary font-monospace fw-bold">'
                    . e((string) $area->code)
                    . '</span>';
            })
            ->addColumn('nombre', function (Area $area) {
                return '<div class="fw-semibold text-dark">' . e((string) $area->name) . '</div>';
            })
            ->addColumn('tipo', function (Area $area) {
                $labels = [
                    'direccion_general' => ['label' => 'Dirección General', 'class' => 'bg-dark-subtle text-dark'],
                    'area' => ['label' => 'Área', 'class' => 'bg-primary-subtle text-primary'],
                    'unidad' => ['label' => 'Unidad', 'class' => 'bg-info-subtle text-info'],
                    'programa_academico' => ['label' => 'Prog. Académico', 'class' => 'bg-success-subtle text-success'],
                    'organo_consultivo' => ['label' => 'Órgano Consultivo', 'class' => 'bg-warning-subtle text-warning'],
                ];

                $meta = $labels[$area->type] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $area->type)), 'class' => 'bg-light text-dark border'];

                return '<span class="badge ' . $meta['class'] . '">' . e($meta['label']) . '</span>';
            })
            ->addColumn('parent', function (Area $area) {
                if (! $area->parent) {
                    return '<span class="text-muted"><i class="ri-git-commit-line me-1"></i>Nivel Superior</span>';
                }

                return '<span class="fw-medium text-dark">' . e((string) $area->parent->name) . '</span>'
                    . '<small class="text-muted d-block">(' . e((string) $area->parent->code) . ')</small>';
            })
            ->addColumn('head', function (Area $area) {
                if (! $area->head) {
                    return '<span class="text-muted fst-italic">Sin asignar</span>';
                }

                return '<div class="small fw-medium text-dark">' . e((string) $area->head->nombres) . '</div>'
                    . '<small class="text-muted">@' . e((string) $area->head->user) . '</small>';
            })
            ->addColumn('level', function (Area $area) {
                return '<span class="badge bg-light text-dark border">Nivel ' . (int) $area->level . '</span>';
            })
            ->addColumn('is_advisory', function (Area $area) {
                return $area->is_advisory
                    ? '<span class="badge bg-warning-subtle text-warning"><i class="ri-information-line me-1"></i>Asesoría</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary"><i class="ri-flow-chart me-1"></i>Línea</span>';
            })
            ->addColumn('acciones', function (Area $area) {
                return '<div class="dropdown">
                            <a href="#" role="button" id="dropdownArea' . (int) $area->id . '" data-bs-toggle="dropdown" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M2 18H9V20H2V18ZM2 11H11V13H2V11ZM2 4H22V6H2V4ZM20.674 13.0251L21.8301 12.634L22.8301 14.366L21.914 15.1711C21.9704 15.4386 22 15.7158 22 16C22 16.2842 21.9704 16.5614 21.914 16.8289L22.8301 17.634L21.8301 19.366L20.674 18.9749C20.2635 19.3441 19.7763 19.6295 19.2391 19.8044L19 21H17L16.7609 19.8044C16.2237 19.6295 15.7365 19.3441 15.326 18.9749L14.1699 19.366L13.1699 17.634L14.086 16.8289C14.0296 16.5614 14 16.2842 14 16C14 15.7158 14.0296 15.4386 14.086 15.1711L13.1699 14.366L14.1699 12.634L15.326 13.0251C15.7365 12.6559 16.2237 12.3705 16.7609 12.1956L17 11H19L19.2391 12.1956C19.7763 12.3705 20.2635 12.6559 20.674 13.0251ZM18 18C19.1046 18 20 17.1046 20 16C20 14.8954 19.1046 14 18 14C16.8954 14 16 14.8954 16 16C16 17.1046 16.8954 18 18 18Z"></path></svg>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="dropdownArea' . (int) $area->id . '">
                                <a class="dropdown-item btn-detail" data-id="' . (int) $area->id . '" href="javascript:void(0);">
                                    <i class="ri-edit-line me-2"></i>
                                    <span> Editar</span>
                                </a>
                                <a class="dropdown-item btn-confirm" data-id="' . (int) $area->id . '" href="javascript:void(0);">
                                    <i class="ri-delete-bin-line me-2"></i>
                                    <span> Eliminar</span>
                                </a>
                            </div>
                        </div>';
            })
            ->rawColumns(['checkbox', 'codigo', 'nombre', 'tipo', 'parent', 'head', 'level', 'is_advisory', 'acciones'])
            ->toJson();
    }

    public function save(AreaValidate $request): JsonResponse {
        $data = $request->validated();
        $area = Area::create([
            'code'          => mb_strtoupper(trim((string) $data['code'])),
            'name'          => trim((string) $data['name']),
            'type'          => (string) $data['type'],
            'parent_id'     => ! empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'head_user_id'  => ! empty($data['head_user_id']) ? (int) $data['head_user_id'] : null,
            'level'         => ! empty($data['level']) ? (int) $data['level'] : 1,
            'is_advisory'   => $request->boolean('is_advisory'),
        ]);

        return response()->json([
            'status'    => true,
            'msg'       => 'Área registrada correctamente.',
            'type'      => 'success',
            'area'      => $area,
        ]);
    }

    public function detail(Request $request): JsonResponse {
        if (! $request->ajax() && ! $request->wantsJson()) {
            return response()->json([
                'status'    => false, 
                'msg'       => 'Intente de nuevo', 
                'type'      => 'warning'
            ]);
        }

        $area = Area::query()->with(['parent:id,name', 'head:id,nombres'])->find((int) $request->input('id'));

        if (! $area) {
            return response()->json([
                'status'    => false, 
                'msg'       => 'El área solicitada no existe.', 
                'type'      => 'warning'
            ], 404);
        }

        return response()->json([
            'status'    => true,
            'area'      => $area,
        ]);
    }

    public function store(AreaValidate $request): JsonResponse {
        $area = Area::query()->find((int) $request->input('id'));

        if (! $area) {
            return response()->json(['status' => false, 'msg' => 'El área solicitada no existe.', 'type' => 'warning'], 404);
        }

        $data = $request->validated();

        $area->update([
            'code'          => mb_strtoupper(trim((string) $data['code'])),
            'name'          => trim((string) $data['name']),
            'type'          => (string) $data['type'],
            'parent_id'     => ! empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'head_user_id'  => ! empty($data['head_user_id']) ? (int) $data['head_user_id'] : null,
            'level'         => ! empty($data['level']) ? (int) $data['level'] : 1,
            'is_advisory'   => $request->boolean('is_advisory'),
        ]);

        return response()->json([
            'status'    => true,
            'msg'       => 'Área actualizada correctamente.',
            'type'      => 'success',
            'area'      => $area,
        ]);
    }

    public function delete(Request $request): JsonResponse {
        if (! $request->ajax() && ! $request->wantsJson()) {
            return response()->json([
                'status'    => false, 
                'msg'       => 'Intente de nuevo', 
                'type'      => 'warning'
            ]);
        }

        $area = Area::query()->find((int) $request->input('id'));

        if (! $area) {
            return response()->json(['status' => false, 'msg' => 'El área solicitada no existe.', 'type' => 'warning'], 404);
        }

        if ($area->children()->exists()) {
            return response()->json([
                'status'    => false,
                'msg'       => 'No se puede eliminar el área porque tiene áreas o unidades dependientes asociadas.',
                'type'      => 'warning',
            ], 422);
        }

        if ($area->employeeDetails()->exists()) {
            return response()->json([
                'status'    => false,
                'msg'       => 'No se puede eliminar el área porque tiene usuarios asignados en su organigrama.',
                'type'      => 'warning',
            ], 422);
        }

        $area->delete();

        return response()->json([
            'status'    => true,
            'msg'       => 'Área eliminada correctamente.',
            'type'      => 'success',
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse {
        if (! $request->ajax() && ! $request->wantsJson()) {
            return response()->json([
                'status' => false,
                'msg'    => 'Intente de nuevo',
                'type'   => 'warning'
            ]);
        }

        $all = $request->boolean('all');
        if ($all) {
            $ids = Area::query()->pluck('id')->all();
        } else {
            $ids = $request->input('ids');
        }

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'status' => false,
                'msg'    => 'No se han seleccionado áreas para eliminar.',
                'type'   => 'warning'
            ], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return response()->json([
                'status' => false,
                'msg'    => 'Identificadores inválidos.',
                'type'   => 'warning'
            ], 422);
        }

        $areas = Area::query()
            ->whereIn('id', $ids)
            ->orderByDesc('level')
            ->get();

        if ($areas->isEmpty()) {
            return response()->json([
                'status' => false,
                'msg'    => 'No se encontraron las áreas seleccionadas.',
                'type'   => 'warning'
            ], 404);
        }

        $deletedCount = 0;
        $skipped = [];

        foreach ($areas as $area) {
            // Check if it has active child areas that are NOT in the deletion list
            if ($area->children()->whereNotIn('id', $ids)->exists()) {
                $skipped[] = "{$area->name}: Tiene áreas dependientes no seleccionadas.";
                continue;
            }

            // Check if it has assigned employees
            if ($area->employeeDetails()->exists()) {
                $skipped[] = "{$area->name}: Tiene usuarios asignados en su organigrama.";
                continue;
            }

            // Check if it has assets assigned
            if ($area->assets()->exists()) {
                $skipped[] = "{$area->name}: Tiene bienes patrimoniales asignados.";
                continue;
            }

            try {
                $area->delete();
                $deletedCount++;
            } catch (\Throwable $e) {
                $skipped[] = "{$area->name}: Restricción de base de datos.";
            }
        }

        if ($deletedCount === 0) {
            $msg = 'No se pudo eliminar ninguna de las áreas seleccionadas.';
            if (! empty($skipped)) {
                $msg .= ' ' . implode(' ', array_slice($skipped, 0, 3));
            }
            return response()->json([
                'status'  => false,
                'msg'     => $msg,
                'skipped' => $skipped,
                'type'    => 'warning'
            ], 422);
        }

        $msg = "Se eliminaron {$deletedCount} área(s) correctamente.";
        if (! empty($skipped)) {
            $msg .= " (" . count($skipped) . " omitida(s) por dependencias o personal asignado).";
        }

        return response()->json([
            'status'        => true,
            'msg'           => $msg,
            'deleted_count' => $deletedCount,
            'skipped_count' => count($skipped),
            'skipped'       => $skipped,
            'type'          => 'success',
        ]);
    }

    private function areaTypes(): array {
        return [
            'direccion_general'     => 'Dirección General',
            'area'                  => 'Área',
            'unidad'                => 'Unidad',
            'programa_academico'    => 'Programa Académico',
            'organo_consultivo'     => 'Órgano Consultivo / Asesor',
        ];
    }
}
