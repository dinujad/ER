<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class StockChannelService
{
    public const CHANNEL_WHOLESALE = 'wholesale';

    public const CHANNEL_RETAIL = 'retail';

    public function balance(int $productId, string $channel, ?int $productVariationId = null): float
    {
        $query = StockLedger::query()
            ->where('product_id', $productId)
            ->where('channel', $channel);

        if ($productVariationId) {
            $query->where('product_variation_id', $productVariationId);
        } else {
            $query->whereNull('product_variation_id');
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Available qty for an order line — uses the order's channel (wholesale vs retail)
     * and sums all variation rows when the product is variable but no variation was picked.
     */
    public function availableForOrderLine(int $productId, string $channel, ?int $productVariationId = null): float
    {
        $query = StockLedger::query()
            ->where('product_id', $productId)
            ->where('channel', $channel);

        if ($productVariationId) {
            $query->where('product_variation_id', $productVariationId);
        } elseif ($this->productHasVariations($productId)) {
            // Variable product without a line variation — total stock across all sizes.
        } else {
            $query->whereNull('product_variation_id');
        }

        return max(0, (float) $query->sum('quantity'));
    }

    /**
     * @return array{available: float, ordered: float, required: float, needs_manufacturing: bool, channel: string}
     */
    public function orderLineInsight(int $productId, string $channel, ?int $productVariationId, float $orderedQty): array
    {
        $available = $this->availableForOrderLine($productId, $channel, $productVariationId);
        $required = max($orderedQty - $available, 0);

        return [
            'available' => $available,
            'ordered' => $orderedQty,
            'required' => $required,
            'needs_manufacturing' => $required > 0,
            'channel' => $channel,
        ];
    }

    protected function productHasVariations(int $productId): bool
    {
        return Product::query()
            ->whereKey($productId)
            ->where(function ($q) {
                $q->where('product_type', 'variable')
                    ->orWhereHas('variations');
            })
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function record(
        int $productId,
        string $channel,
        string $transactionType,
        float $quantity,
        ?int $productVariationId = null,
        array $extra = []
    ): StockLedger {
        if (!in_array($channel, [self::CHANNEL_WHOLESALE, self::CHANNEL_RETAIL], true)) {
            throw new RuntimeException('Invalid stock channel.');
        }

        return StockLedger::create(array_merge([
            'product_id' => $productId,
            'product_variation_id' => $productVariationId,
            'channel' => $channel,
            'transaction_type' => $transactionType,
            'quantity' => $quantity,
            'created_by' => auth()->id(),
        ], $extra));
    }

    public function transferWholesaleToRetail(
        Product $product,
        float $quantity,
        ?int $productVariationId = null,
        ?string $notes = null
    ): string {
        return $this->transferBetweenChannels(
            $product,
            self::CHANNEL_WHOLESALE,
            self::CHANNEL_RETAIL,
            $quantity,
            $productVariationId,
            $notes ?: 'Wholesale → Retail transfer'
        );
    }

    public function transferRetailToWholesale(
        Product $product,
        float $quantity,
        ?int $productVariationId = null,
        ?string $notes = null
    ): string {
        return $this->transferBetweenChannels(
            $product,
            self::CHANNEL_RETAIL,
            self::CHANNEL_WHOLESALE,
            $quantity,
            $productVariationId,
            $notes ?: 'Retail → Wholesale transfer'
        );
    }

    public function transferBetweenChannels(
        Product $product,
        string $fromChannel,
        string $toChannel,
        float $quantity,
        ?int $productVariationId = null,
        ?string $notes = null
    ): string {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }

        if ($fromChannel === $toChannel) {
            throw new RuntimeException('Source and destination channels must be different.');
        }

        if (!in_array($fromChannel, [self::CHANNEL_WHOLESALE, self::CHANNEL_RETAIL], true)
            || !in_array($toChannel, [self::CHANNEL_WHOLESALE, self::CHANNEL_RETAIL], true)) {
            throw new RuntimeException('Invalid stock channel.');
        }

        $available = $this->balance($product->id, $fromChannel, $productVariationId);
        if ($quantity > $available) {
            throw new RuntimeException(
                'Insufficient ' . $fromChannel . ' stock. Available: ' . number_format($available, 2)
            );
        }

        $group = Str::uuid()->toString();
        $fromLabel = ucfirst($fromChannel);
        $toLabel = ucfirst($toChannel);
        $note = $notes ?: "{$fromLabel} → {$toLabel} transfer";

        return DB::transaction(function () use ($product, $quantity, $productVariationId, $group, $note, $fromChannel, $toChannel) {
            $this->record(
                $product->id,
                $fromChannel,
                'transfer_out',
                -$quantity,
                $productVariationId,
                [
                    'notes' => "{$note} (#{$group})",
                    'reference_type' => 'channel_transfer',
                    'reference_id' => null,
                ]
            );

            $this->record(
                $product->id,
                $toChannel,
                'transfer_in',
                $quantity,
                $productVariationId,
                [
                    'notes' => "{$note} (#{$group})",
                    'reference_type' => 'channel_transfer',
                    'reference_id' => null,
                ]
            );

            return $group;
        });
    }
}
