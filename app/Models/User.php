<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Cash;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'nombres',
        'user',
        'correo',
        'telefono',
        'password',
        'estado',
        'idcaja',
        'idalmacen',
        'firma_digital',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getFirmaDigitalUrlAttribute(): ?string {
        if (! $this->firma_digital) {
            return null;
        }
        if (str_starts_with($this->firma_digital, 'http') || str_starts_with($this->firma_digital, 'data:')) {
            return $this->firma_digital;
        }
        return asset($this->firma_digital);
    }

    public function cash(): BelongsTo {
        return $this->belongsTo(Cash::class, 'idcaja');
    }

    public function activeWarehouse(): BelongsTo {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }

    public function getNameAttribute(): string {
        return $this->nombres ?: ($this->user ?: 'Usuario');
    }

    public function warehouses(): BelongsToMany {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse', 'user_id', 'warehouse_id')->withTimestamps();
    }

    public function employeeAreaDetails(): HasMany {
        return $this->hasMany(EmployeeAreaDetail::class);
    }

    public function primaryAreaDetail(): HasOne {
        return $this->hasOne(EmployeeAreaDetail::class)->where('is_primary', true);
    }

    public function primaryArea(): HasOneThrough {
        return $this->hasOneThrough(Area::class, EmployeeAreaDetail::class, 'user_id', 'id', 'id', 'area_id')
            ->where('employee_area_details.is_primary', true);
    }

    public function approvals(): HasMany {
        return $this->hasMany(DocumentApproval::class, 'approver_id');
    }

    public function pendingApprovalsCount(): int {
        $userRoles = $this->roles->pluck('name')->toArray();
        $isSuper = in_array('SUPERADMIN', $userRoles) || in_array('ADMIN', $userRoles);

        return DocumentApproval::query()
            ->where('status', 'PENDIENTE')
            ->where(function ($q) use ($userRoles, $isSuper) {
                $q->where('approver_id', $this->id);
                if (!empty($userRoles)) {
                    $q->orWhere(function ($sub) use ($userRoles) {
                        $sub->whereNull('approver_id')->whereIn('role_name', $userRoles);
                    });
                }
                if ($isSuper) {
                    $q->orWhereNull('approver_id');
                }
            })
            ->count();
    }
}

