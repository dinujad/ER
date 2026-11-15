<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $table = "journal_entries";
    protected $fillable = ['reversed'];

    public function chart_of_account()
    {
        return $this->hasOne(ChartOfAccount::class, 'id', 'chart_of_account_id')->withDefault();
    }

    /**
     * Dermas is a single-business app — no business_locations table.
     * Return a null-object relation so any ->business_location access doesn't throw.
     */
    public function business_location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id')->withDefault();
    }

    public function created_by()
    {
        return $this->hasOne(User::class, 'id', 'created_by_id')->withDefault();
    }

    public function scopeNotReversed($query)
    {
        return $query->where('reversed', 0);
    }

    public function scopeReconcileEntry($query)
    {
        return $query->where('transaction_type', 'reconcile_entry');
    }

    /**
     * Dermas is single-business: no business_locations join needed.
     * Return all entries (filtered by date or other criteria upstream).
     */
    public function scopeForBusiness($query)
    {
        return $query;
    }

    public function getAmountAttribute()
    {
        return $this->credit - $this->debit;
    }
}
