<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Stub base class — satisfies use App\Business in accounting controllers.
 * Returns safe defaults; Dermas does not use a multi-tenancy business table.
 */
class Business extends Model
{
    protected $table = 'business';

    protected $guarded = ['id'];

    public static function getDefault(): ?self
    {
        return static::query()->first();
    }

    public function join(string $table, ...$args)
    {
        return static::query()->join($table, ...$args);
    }

    /** January (1) when the stub table is missing or empty. */
    public static function financialYearStartMonth(): int
    {
        try {
            if (!Schema::hasTable('business')) {
                return 1;
            }

            $id = session('business.id') ?: 1;
            $row = static::find($id) ?? static::firstOrCreate(
                ['id' => 1],
                ['fy_start_month' => 1]
            );
            $month = (int) ($row->fy_start_month ?? 1);

            return ($month >= 1 && $month <= 12) ? $month : 1;
        } catch (\Throwable $e) {
            return 1;
        }
    }

    public static function setFinancialYearStartMonth(int $month): self
    {
        $month = max(1, min(12, $month));
        if (!Schema::hasTable('business')) {
            throw new \RuntimeException('Business settings table is missing. Run database migrations.');
        }

        return static::updateOrCreate(
            ['id' => session('business.id') ?: 1],
            ['fy_start_month' => $month]
        );
    }
}
