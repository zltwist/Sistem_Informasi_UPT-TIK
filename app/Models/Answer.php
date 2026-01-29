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
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
