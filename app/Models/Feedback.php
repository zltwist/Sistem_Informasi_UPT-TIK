<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'answer_id',
        'is_helpful',
    ];

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }
}
