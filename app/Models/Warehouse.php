<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Warehouse extends Model
{
    use HasFactory;
    protected $table        = 'warehouses';
    protected $primaryKey   = 'id';
    protected $fillable     = 
    [
        'descripcion',
        'direccion'
    ];

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class, 'user_warehouse', 'warehouse_id', 'user_id')->withTimestamps();
    }
}
