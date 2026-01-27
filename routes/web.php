<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AskController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\FeedbackController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-ask', function () {
    return '
        <form method="POST" action="/ask">
            <input type="hidden" name="_token" value="' . csrf_token() . '">
            <input type="text" name="question" placeholder="Tulis pertanyaan..." style="width:300px">
            <button type="submit">Kirim</button>
        </form>
    ';
});


Route::post('/ask', [AskController::class, 'ask']);
Route::post('/feedback', [FeedbackController::class, 'store']);
Route::post('/session/{id}/close', [ChatSessionController::class, 'close']);
