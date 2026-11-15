<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'production_stage_id',
        'parent_id',
        'supervisor_id',
        'description',
        'is_active',
    ];

    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    // Self-referencing relationship for Sub-Sections (e.g. Sewing -> Lines)
    public function children()
    {
        return $this->hasMany(ProductionSection::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(ProductionSection::class, 'parent_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'production_section_id');
    }

    public function productionStage()
    {
        return $this->belongsTo(ProductionStage::class);
    }
}
