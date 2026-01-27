<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['content', 'chat_session_id'];

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
    
    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class);
    }
}
