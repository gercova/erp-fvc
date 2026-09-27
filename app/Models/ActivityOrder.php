<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ActivityOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'activity_orders';

    protected $fillable = [
        'uuid',
        'order_code',
        'productive_activity_id',
        'client_id',
        'produced_item_id',
        'order_date',
        'expected_delivery_date',
        'quantity',
        'unit_price',
        'total_amount',
        'advance_payment',
        'balance_pending',
        'status',
        'sale_note_id',
        'billing_id',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'advance_payment' => 'decimal:2',
        'balance_pending' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (ActivityOrder $order) {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }

            if (empty($order->order_code)) {
                $year = $order->order_date ? Carbon::parse($order->order_date)->format('Y') : date('Y');
                $count = static::whereYear('order_date', $year)->count() + 1;
                $order->order_code = sprintf('PED-%s-%04d', $year, $count);
            }

            if (empty($order->total_amount) && $order->quantity && $order->unit_price) {
                $order->total_amount = round($order->quantity * $order->unit_price, 2);
            }

            $order->balance_pending = max(0, ($order->total_amount ?? 0) - ($order->advance_payment ?? 0));
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function producedItem(): BelongsTo
    {
        return $this->belongsTo(ProducedItem::class, 'produced_item_id');
    }

    public function saleNote(): BelongsTo
    {
        return $this->belongsTo(SaleNote::class, 'sale_note_id');
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class, 'billing_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'   => '<span class="badge bg-warning text-dark">Pendiente</span>',
            'confirmed' => '<span class="badge bg-info text-white">Confirmado</span>',
            'delivered' => '<span class="badge bg-primary text-white">Entregado</span>',
            'invoiced'  => '<span class="badge bg-success text-white">Facturado / Pagado</span>',
            'cancelled' => '<span class="badge bg-danger text-white">Cancelado</span>',
            default     => '<span class="badge bg-light text-dark">' . e($this->status) . '</span>',
        };
    }
}
