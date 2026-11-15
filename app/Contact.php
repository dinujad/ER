<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Stub base class — satisfies Modules\Accounting\Entities\Contact extends App\Contact.
 */
class Contact extends Model
{
    protected $table = 'contacts';

    protected $guarded = ['id'];

    protected $appends = [];

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function scopeForBusiness($query, $business_id = null)
    {
        return $query->where('contacts.business_id', $business_id ?? session('business.id'));
    }

    public function scopeForDropdown($query)
    {
        return $query->select('id', 'first_name', 'last_name');
    }

    public static function customersDropdown($businessId = null, $prepend_none = true, $closed_ledger = false)
    {
        try {
            $bid = $businessId ?? session('business.id');
            $customers = static::where(function ($q) use ($bid) {
                    if ($bid) {
                        $q->where('business_id', $bid);
                    }
                })
                ->select('id', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(function ($c) {
                    return [$c->id => trim($c->first_name . ' ' . $c->last_name)];
                });
        } catch (\Exception $e) {
            $customers = collect();
        }

        if ($prepend_none) {
            $customers = collect(['' => 'All'])->merge($customers);
        }

        return $customers;
    }

    public static function forDropdown($businessId = null, $prepend_none = true, $closed_ledger = false)
    {
        return static::customersDropdown($businessId, $prepend_none, $closed_ledger);
    }

    /**
     * Stub for Accounting ExpenseUtil (UltimatePOS-compatible signature).
     *
     * @return \Illuminate\Support\Collection<int|string, string>
     */
    public static function contactDropdown($businessId = null, $suppliersOnly = false, $prepend_none = false)
    {
        try {
            if (! Schema::hasTable((new static)->getTable())) {
                return collect();
            }

            return static::customersDropdown($businessId, $prepend_none, false);
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Suppliers for Accounting purchase filters (UltimatePOS-style; filters by contact_id).
     *
     * @return \Illuminate\Support\Collection<int|string, string>
     */
    public static function suppliersDropdown($businessId = null, $prepend_none = true)
    {
        try {
            if (! Schema::hasTable((new static)->getTable())) {
                return $prepend_none ? collect(['' => 'All']) : collect();
            }

            $q = static::query();
            if (Schema::hasColumn('contacts', 'type')) {
                $q->whereIn('type', ['supplier', 'Supplier', 'both', 'Both']);
            }
            if ($businessId && Schema::hasColumn('contacts', 'business_id')) {
                $q->where('business_id', $businessId);
            }

            $orderCol = Schema::hasColumn('contacts', 'name') ? 'name' : 'first_name';
            $rows = $q->orderBy($orderCol)->get();

            $map = $rows->mapWithKeys(function ($c) {
                $label = '';
                if (! empty($c->supplier_business_name)) {
                    $label = (string) $c->supplier_business_name;
                } elseif (! empty($c->name)) {
                    $label = (string) $c->name;
                } else {
                    $label = trim(($c->first_name ?? '').' '.($c->last_name ?? ''));
                }

                return [$c->id => $label !== '' ? $label : ('#'.$c->id)];
            });

            if ($prepend_none) {
                return collect(['' => 'All'])->merge($map);
            }

            return $map;
        } catch (\Throwable $e) {
            return $prepend_none ? collect(['' => 'All']) : collect();
        }
    }
}
