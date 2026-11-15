<?php

namespace App\Models;

use App\Services\CostReportService;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'code', 'description', 'is_active'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function costSheetTopicLabel(): string
    {
        $topic = app(CostReportService::class)->resolveExpenseTopic(
            (string) ($this->code ?? ''),
            (string) $this->name
        );

        return app(CostReportService::class)->topicLabels()[$topic] ?? 'Other';
    }
}
