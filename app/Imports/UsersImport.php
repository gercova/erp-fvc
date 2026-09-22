<?php

namespace App\Imports;

use App\Models\Area;
use App\Models\Cash;
use App\Models\EmployeeAreaDetail;
use App\Models\User;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Role;

class UsersImport implements ToCollection, WithHeadingRow
{
    protected int $createdCount = 0;
    protected int $updatedCount = 0;

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            throw new Exception('El archivo Excel no contiene registros para importar.');
        }

        $allRoles = Role::query()->pluck('name')->all();
        $defaultCashId = (int) (Cash::query()->orderBy('id')->value('id') ?? 1);
        $defaultWarehouseId = (int) (Warehouse::query()->orderBy('id')->value('id') ?? 1);

        DB::transaction(function () use ($rows, $allRoles, $defaultCashId, $defaultWarehouseId) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                $nombres = trim((string) ($row['nombres'] ?? ''));
                $userLogin = mb_strtolower(trim((string) ($row['usuario'] ?? ($row['user'] ?? ''))));

                if ($nombres === '' && $userLogin === '') {
                    continue;
                }

                if ($nombres === '') {
                    throw new Exception("Fila {$rowNumber}: El campo 'Nombres' es obligatorio.");
                }

                if ($userLogin === '') {
                    throw new Exception("Fila {$rowNumber}: El campo 'Usuario' es obligatorio.");
                }

                $email = trim((string) ($row['correo'] ?? ($row['email'] ?? '')));
                $phone = trim((string) ($row['telefono'] ?? ($row['phone'] ?? '')));
                $password = trim((string) ($row['contrasena'] ?? ($row['password'] ?? '')));
                $rawRole = trim((string) ($row['rol'] ?? ($row['role'] ?? '')));
                $rawCash = trim((string) ($row['caja'] ?? ''));
                $rawWarehouse = trim((string) ($row['almacen'] ?? ''));
                $rawArea = trim((string) ($row['codigo_area'] ?? ($row['area'] ?? '')));
                $cargo = trim((string) ($row['cargo'] ?? ''));
                $condicionLaboral = trim((string) ($row['condicion_laboral'] ?? ''));
                $rawEstado = trim((string) ($row['estado'] ?? '1'));

                // Determine estado
                $estado = (in_array(mb_strtolower($rawEstado), ['1', 'activo', 'active', 'si', 'sí', 'true'], true)) ? 1 : 0;

                // Resolve Role
                $matchedRole = null;
                if ($rawRole !== '') {
                    foreach ($allRoles as $r) {
                        if (strcasecmp($r, $rawRole) === 0) {
                            $matchedRole = $r;
                            break;
                        }
                    }
                    if (! $matchedRole) {
                        throw new Exception("Fila {$rowNumber}: El rol '{$rawRole}' no existe en el sistema.");
                    }
                } else {
                    $matchedRole = 'VENDEDOR';
                }

                // Resolve Cash
                $cashId = $defaultCashId;
                if ($rawCash !== '') {
                    if (is_numeric($rawCash)) {
                        $foundCash = Cash::query()->find((int) $rawCash);
                    } else {
                        $foundCash = Cash::query()->whereRaw('UPPER(descripcion) = ?', [mb_strtoupper($rawCash)])->first();
                    }
                    if ($foundCash) {
                        $cashId = (int) $foundCash->id;
                    }
                }

                // Resolve Warehouse
                $warehouseId = $defaultWarehouseId;
                if ($rawWarehouse !== '') {
                    if (is_numeric($rawWarehouse)) {
                        $foundWarehouse = Warehouse::query()->find((int) $rawWarehouse);
                    } else {
                        $foundWarehouse = Warehouse::query()->whereRaw('UPPER(descripcion) = ?', [mb_strtoupper($rawWarehouse)])->first();
                    }
                    if ($foundWarehouse) {
                        $warehouseId = (int) $foundWarehouse->id;
                    }
                }

                // Create or update User
                $user = User::query()->where('user', $userLogin)->first();

                if ($user) {
                    $updateData = [
                        'nombres' => mb_strtoupper($nombres),
                        'estado' => $estado,
                        'idcaja' => $cashId,
                        'idalmacen' => $warehouseId,
                    ];
                    if ($email !== '') {
                        $updateData['correo'] = mb_strtolower($email);
                    }
                    if ($phone !== '') {
                        $updateData['telefono'] = $phone;
                    }
                    if ($password !== '') {
                        $updateData['password'] = $password;
                    }

                    $user->update($updateData);
                    $this->updatedCount++;
                } else {
                    $initialPassword = $password !== '' ? $password : '12345678';
                    $user = User::create([
                        'nombres' => mb_strtoupper($nombres),
                        'user' => $userLogin,
                        'correo' => $email !== '' ? mb_strtolower($email) : null,
                        'telefono' => $phone !== '' ? $phone : null,
                        'password' => $initialPassword,
                        'estado' => $estado,
                        'idcaja' => $cashId,
                        'idalmacen' => $warehouseId,
                    ]);
                    $this->createdCount++;
                }

                // Sync Role & Warehouse
                $user->syncRoles([$matchedRole]);
                $user->warehouses()->syncWithoutDetaching([$warehouseId]);

                // Resolve Area and EmployeeAreaDetail
                if ($rawArea !== '') {
                    $area = Area::query()
                        ->whereRaw('UPPER(code) = ?', [mb_strtoupper($rawArea)])
                        ->orWhereRaw('UPPER(name) = ?', [mb_strtoupper($rawArea)])
                        ->first();

                    if (! $area) {
                        throw new Exception("Fila {$rowNumber}: El área '{$rawArea}' no fue encontrada.");
                    }

                    $effectiveCargo = $cargo !== '' ? $cargo : $matchedRole;

                    // Check existing primary detail
                    $detail = EmployeeAreaDetail::query()
                        ->where('user_id', $user->id)
                        ->where('is_primary', true)
                        ->first();

                    if ($detail) {
                        $detail->update([
                            'area_id' => $area->id,
                            'cargo' => $effectiveCargo,
                            'condicion_laboral' => $condicionLaboral !== '' ? mb_strtoupper($condicionLaboral) : $detail->condicion_laboral,
                        ]);
                    } else {
                        // Check if user already has an entry for this exact area
                        $existingEntry = EmployeeAreaDetail::query()
                            ->where('user_id', $user->id)
                            ->where('area_id', $area->id)
                            ->first();

                        if ($existingEntry) {
                            $existingEntry->update([
                                'cargo' => $effectiveCargo,
                                'condicion_laboral' => $condicionLaboral !== '' ? mb_strtoupper($condicionLaboral) : null,
                                'is_primary' => true,
                            ]);
                        } else {
                            EmployeeAreaDetail::create([
                                'user_id' => $user->id,
                                'area_id' => $area->id,
                                'cargo' => $effectiveCargo,
                                'condicion_laboral' => $condicionLaboral !== '' ? mb_strtoupper($condicionLaboral) : null,
                                'is_primary' => true,
                            ]);
                        }
                    }
                }
            }
        });
    }

    public function getSummary(): array
    {
        return [
            'created' => $this->createdCount,
            'updated' => $this->updatedCount,
            'total' => $this->createdCount + $this->updatedCount,
        ];
    }
}
