<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('active');
            // active | escalated | closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
