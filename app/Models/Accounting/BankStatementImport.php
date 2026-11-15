<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends Model
{
    protected $table = 'bank_statement_imports';

    protected $fillable = [
        'business_id', 'chart_of_account_id', 'original_filename',
        'status', 'imported_by', 'row_count',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class, 'bank_statement_import_id');
    }
}
