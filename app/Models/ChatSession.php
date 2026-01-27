<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = ['status'];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
