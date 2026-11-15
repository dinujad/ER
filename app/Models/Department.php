<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /** Resolve a Hik-Connect department path to an ERP department, creating it if needed. */
    public static function resolveFromImportPath(?string $path): ?self
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $existing = static::query()->where('name', $path)->first();
        if ($existing) {
            return $existing;
        }

        $base = \Illuminate\Support\Str::slug($path) ?: 'dept';
        $code = $base;
        $n = 1;
        while (static::query()->where('code', $code)->exists()) {
            $code = $base . '-' . (++$n);
        }

        return static::create([
            'name' => $path,
            'code' => $code,
        ]);
    }
}
