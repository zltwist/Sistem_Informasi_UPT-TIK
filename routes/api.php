<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AskController;

Route::post('/ask', [AskController::class, 'ask']);
