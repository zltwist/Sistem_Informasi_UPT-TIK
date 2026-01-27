<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'content',
        'source',
        'total_feedback',
        'positive_feedback',
        'negative_feedback',
        'confidence_score',
    ];

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
