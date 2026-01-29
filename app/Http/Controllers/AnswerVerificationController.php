<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use Illuminate\Http\Request;

class AnswerVerificationController extends Controller
{
    public function verify(Request $request, $id)
    {
        $request->validate([
            'verified_by' => 'nullable|exists:users,id',
        ]);

        $answer = Answer::findOrFail($id);

        $answer->is_verified = true;
        $answer->verified_at = now();
        $answer->verified_by = $request->input('verified_by') ?? optional($request->user())->id;
        $answer->save();

        return response()->json([
            'message' => 'Jawaban berhasil diverifikasi',
            'status'  => 'verified',
            'answer_id' => $answer->id,
        ]);
    }
}
