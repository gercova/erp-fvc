<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySalesAttribution extends Model
{
    use HasFactory;

    protected $table = 'activity_sales_attributions';

    protected $fillable = [
        'productive_activity_id',
        'produced_item_id',
        'production_campaign_id',
        'detail_sale_note_id',
        'detail_billing_id',
        'quantity_sold',
        'unit_sale_price',
        'revenue_amount',
        'sale_date',
        'notes',
    ];

    protected $casts = [
        'sale_date'         => 'date',
        'quantity_sold'     => 'decimal:3',
        'unit_sale_price'   => 'decimal:2',
        'revenue_amount'    => 'decimal:2',
    ];

    public function activity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function producedItem(): BelongsTo {
        return $this->belongsTo(ProducedItem::class, 'produced_item_id');
    }

    public function campaign(): BelongsTo {
        return $this->belongsTo(ProductionCampaign::class, 'production_campaign_id');
    }

    public function detailSaleNote(): BelongsTo {
        return $this->belongsTo(DetailSaleNote::class, 'detail_sale_note_id');
    }

    public function detailBilling(): BelongsTo {
        return $this->belongsTo(DetailBilling::class, 'detail_billing_id');
    }

    public function getAttributedAmountAttribute(): float {
        return (float) ($this->revenue_amount ?? 0);
    }
}
