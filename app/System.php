<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Stub for App\System — used by InstallController.
 * Delegates to Modules\Accounting\Entities\System which has the real implementation.
 */
class System extends Model
{
    protected $table = 'system';

    public $timestamps = false;

    protected $guarded = ['id'];

    public static function getProperty(string $key): mixed
    {
        $row = static::where('key', $key)->first();
        return $row->value ?? null;
    }

    public static function setProperty(string $key, mixed $value): void
    {
        static::where('key', $key)->update(['value' => $value]);
    }

    public static function addProperty(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
