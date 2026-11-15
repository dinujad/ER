<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'washing_transfer_id',
        'total_checked',
        'finished_good_qty',
        'damaged_qty',
    ];

    public function washingTransfer()
    {
        return $this->belongsTo(WashingTransfer::class);
    }
}
