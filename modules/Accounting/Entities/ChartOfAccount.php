<?php

namespace Modules\Accounting\Entities;

use Modules\Accounting\Entities\Currency;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $table = "chart_of_accounts";
    protected $fillable = [
        'reconcile_opening_balance',
        'name',
        'parent_id',
        'gl_code',
        'account_type',
        'normal_balance',
        'allow_manual',
        'active',
        'notes',
        'business_id',
        'opening_balance',
        'has_opening_balance',
        'currency_id',
        'payment_type_id',
        'account_subtype_id',
        'detail_type_id',
    ];

    protected $casts = [
        'has_opening_balance' => 'boolean',
    ];

    protected $appends = ['name_with_subtype', 'current_balance', 'last_opening_balance', 'last_reconcile_transaction_id'];

    /**
     * Get all of the journal_entries for the ChartOfAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function journal_entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function journal_entries_not_reversed()
    {
        return $this->journal_entries()->notReversed();
    }

    /**
     * Get all of the budget for the ChartOfAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budget()
    {
        $year = request()->get('year') ?: (string) get_financial_year();
        $default_budget = [
            'quarterly' => [1 => 0, 2 => 0, 3 => 0, 4 => 0],
            'yearly' => 0,
        ];
        for ($i = 1; $i <= 12; $i++) {
            $default_budget["month_$i"] = 0;
        }

        return $this->hasOne(Budget::class)->where('financial_year', $year)->withDefault($default_budget);
    }

    /**
     * Get the parent that owns the ChartOfAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id')->withDefault([
            'name' => trans('accounting::lang.none')
        ]);
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    /**
     * Get the currency that owns the ChartOfAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    /**
     * Get the account_subtype associated with the ChartOfAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account_subtype()
    {
        return $this->belongsTo(AccountSubtype::class)->withDefault();
    }

    /**
     * Get the account_detail_type associated with the ChartOfAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account_detail_type()
    {
        return $this->belongsTo(AccountDetailType::class, 'detail_type_id')->withDefault();
    }

    public function getCurrentBalanceAttribute()
    {
        return $this->journal_entries_not_reversed->sum('credit') - $this->journal_entries_not_reversed->sum('debit');
    }

    public function getNameWithSubtypeAttribute()
    {
        return $this->name . ' (' . $this->account_subtype->name . ')';
    }

    public function getLastOpeningBalanceAttribute()
    {
        return !empty($this->reconcile_opening_balance) ? $this->reconcile_opening_balance : $this->opening_balance;
    }

    public function getLastReconcileTransactionIdAttribute()
    {
        $journal_entry = JournalEntry::reconcileEntry()
            ->where('chart_of_account_id', $this->id)
            ->notReversed()
            ->orderBy('id', 'DESC')
            ->first();
        return !empty($journal_entry) ? $journal_entry->id : null;
    }

    public static function getAccountTypes()
    {
        return ChartOfAccount::select('account_type')->distinct('account_type')->get()->pluck('account_type');
    }

    public function scopeForBusiness($query)
    {
        $bid = session('business.id');

        return $query->where(function ($q) use ($bid) {
            $q->where('chart_of_accounts.business_id', $bid)
                ->orWhere('chart_of_accounts.business_id', 0);
        });
    }
}
