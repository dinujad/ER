<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariation;

class ProductLabel
{
    public static function productBarcode(?Product $product): string
    {
        if (!$product) {
            return '';
        }
        if (method_exists($product, 'barcodeValue')) {
            return $product->barcodeValue();
        }
        foreach ([$product->barcode ?? null, $product->sku ?? null] as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return (string) $product->id;
    }

    public static function variationBarcode(?ProductVariation $variation): string
    {
        if (!$variation) {
            return '';
        }
        if (method_exists($variation, 'barcodeValue')) {
            return $variation->barcodeValue();
        }
        foreach ([$variation->barcode ?? null, $variation->sub_sku ?? null] as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return (string) $variation->id;
    }

    public static function attributeValue(?ProductVariation $variation, string ...$keys): ?string
    {
        if (!$variation) {
            return null;
        }
        if (method_exists($variation, 'attributeValue')) {
            return $variation->attributeValue(...$keys);
        }

        $pairs = self::attributePairs($variation);
        $lower = [];
        foreach ($pairs as $key => $value) {
            $lower[strtolower((string) $key)] = $value;
        }
        foreach ($keys as $key) {
            $found = $lower[strtolower($key)] ?? null;
            if ($found !== null && $found !== '') {
                return $found;
            }
        }

        return null;
    }

    public static function attributesLabel(?ProductVariation $variation): string
    {
        if (!$variation) {
            return '';
        }
        if (method_exists($variation, 'attributesLabel')) {
            return $variation->attributesLabel();
        }
        $pairs = self::attributePairs($variation);
        if ($pairs !== []) {
            return collect($pairs)->map(fn ($value, $key) => $key . ': ' . $value)->implode(' · ');
        }

        return (string) ($variation->variation_name ?? '');
    }

    public static function searchHaystack(?ProductVariation $variation): string
    {
        if (!$variation) {
            return '';
        }
        if (method_exists($variation, 'searchHaystack')) {
            return $variation->searchHaystack();
        }

        return strtolower(trim(implode(' ', array_filter([
            (string) ($variation->variation_name ?? ''),
            (string) ($variation->sub_sku ?? ''),
            (string) ($variation->barcode ?? ''),
            self::attributesLabel($variation),
        ]))));
    }

    public static function sellPrice(?ProductVariation $variation): float
    {
        if (!$variation) {
            return 0.0;
        }
        if (method_exists($variation, 'labelSellPrice')) {
            return $variation->labelSellPrice();
        }
        foreach (['retail_price', 'default_sell_price'] as $field) {
            $price = (float) ($variation->{$field} ?? 0);
            if ($price > 0) {
                return $price;
            }
        }

        return (float) ($variation->product?->selling_price ?? 0);
    }

    /** @return array<string, string> */
    protected static function attributePairs(ProductVariation $variation): array
    {
        $combo = $variation->combo_variations ?? null;
        if (is_string($combo)) {
            $combo = json_decode($combo, true);
        }
        if (!is_array($combo) || $combo === []) {
            return [];
        }

        $pairs = [];
        foreach ($combo as $key => $value) {
            if (is_array($value)) {
                $value = implode(' / ', array_filter(array_map('strval', $value)));
            }
            $value = trim((string) $value);
            $key = trim((string) $key);
            if ($key === '' || $value === '') {
                continue;
            }
            $pairs[$key] = $value;
        }

        return $pairs;
    }
}
