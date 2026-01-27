<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'question_id',
        'content',
        'source'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
