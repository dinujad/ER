<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_name',
        'contact',
        'commission_type',
        'commission_rate',
        'is_active',
    ];
}
