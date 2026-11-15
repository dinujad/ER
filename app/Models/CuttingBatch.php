<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuttingBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_issue_id',
        'pieces_cut',
        'style_no',
    ];

    public function materialIssue()
    {
        return $this->belongsTo(MaterialIssue::class);
    }
}
