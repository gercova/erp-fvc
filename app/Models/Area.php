<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Area extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'parent_id', 'type', 'head_user_id', 'level', 'is_advisory',
    ];

    protected $casts = [
        'is_advisory' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Area::class, 'parent_id');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function employeeDetails(): HasMany
    {
        return $this->hasMany(EmployeeAreaDetail::class);
    }

    /**
     * Sube por la jerarquía (parent_id) y arma la lista ordenada de aprobadores:
     * primero el jefe del área del solicitante, luego el de la unidad superior,
     * y así hasta llegar a Dirección General.
     *
     * - Si el propio solicitante es el jefe de un área de la cadena, ese paso se salta
     *   (no se auto-aprueba a sí mismo).
     * - Las áreas marcadas is_advisory (líneas punteadas del organigrama: Secretaría
     *   de Dirección, Consejo Asesor, etc.) no generan un paso de aprobación obligatorio.
     * - Un mismo jefe no se repite si encabeza más de un nivel de la cadena.
     */
    public function approvalChain(?User $requester = null): Collection
    {
        $chain = collect();
        $area = $this;

        while ($area) {
            $skip = $area->is_advisory
                || ! $area->head_user_id
                || ($requester && $area->head_user_id === $requester->id)
                || $chain->contains('id', $area->head_user_id);

            if (! $skip) {
                $chain->push($area->head);
            }

            $area = $area->parent;
        }

        return $chain;
    }
}