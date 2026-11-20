<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductVariation; // Added this import

class OrderItem extends Model
{
    use \App\Models\Concerns\Auditable;

    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variation_id', // Added this fillable attribute
        'quantity',
        'ordered_quantity',
        'quantity_returned',
        'unit_price',
        'total_price',
        'checked_at',
        'checked_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'ordered_quantity' => 'decimal:2',
        'quantity_returned' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'checked_at' => 'datetime',
    ];

    public function isChecked(): bool
    {
        return $this->checked_at !== null;
    }

    /** Qty the customer ordered. Stays put after Order Check changes the issued qty. */
    public function orderedQuantity(): float
    {
        if ($this->ordered_quantity !== null) {
            return (float) $this->ordered_quantity;
        }

        return (float) $this->quantity;
    }

    public function returnableQuantity(): float
    {
        return max(0, round((float) $this->quantity - (float) $this->quantity_returned, 2));
    }

    public function netQuantity(): float
    {
        return $this->returnableQuantity();
    }

    public function netLineTotal(): float
    {
        $netQty = $this->netQuantity();

        return round($netQty * (float) $this->unit_price, 2);
    }

    /**
     * Effective unit price for sale returns: invoice merchandise discount × cash/payment discount.
     * Example: list 1000 with 15% cash next-payment discount → ~850.
     */
    public function effectiveReturnUnitPrice(): float
    {
        $list = (float) $this->unit_price;
        $order = $this->relationLoaded('order') ? $this->order : $this->order()->first();
        if (!$order) {
            return round($list, 2);
        }

        $invoiceAdj = $list * $order->invoiceMerchandiseFactor();
        $effective = $invoiceAdj * $order->returnSettlementCashFactor();

        return round(max(0, $effective), 2);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function auditLabel(): string
    {
        return 'Order line #' . $this->getKey() . ' (order #' . $this->order_id . ')';
    }
}
