<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\ChatSession;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'answer_id' => 'required|exists:answers,id',
            'helpful'   => 'required|boolean',
        ]);

        /**
         * Gunakan transaksi biar aman:
         * - feedback
         * - update statistik
         * dilakukan sebagai satu kesatuan
         */
        DB::transaction(function () use ($request) {

            // 2. Ambil jawaban
            $answer = Answer::lockForUpdate()->findOrFail($request->answer_id);

            // 3. Simpan feedback individual (log suara user)
            Feedback::create([
                'answer_id' => $answer->id,
                'is_helpful' => $request->helpful,
            ]);

            // 4. Update statistik jawaban
            $answer->total_feedback += 1;

            if ($request->helpful) {
                $answer->positive_feedback += 1;
            } else {
                $answer->negative_feedback += 1;
            }

            // 5. Hitung confidence score (probabilistik)
            $answer->confidence_score =
                $answer->positive_feedback / $answer->total_feedback;

            $answer->save();

            // Jawaban terverifikasi tidak dipengaruhi eskalasi berbasis feedback.
            if ($answer->is_verified) {
                return;
            }

            // ===== RULE ESKALASI BERBASIS FEEDBACK =====
            $chatSession = $answer->question->chatSession;

            // Rule 1: confidence rendah + cukup bukti
            if (
                $answer->confidence_score < 0.4 &&
                $answer->total_feedback >= 5
            ) {
                $chatSession->status = 'escalated';
                $chatSession->save();
                return;
            }

            // Rule 2: feedback negatif berturut-turut (3 terakhir)
            $lastFeedbacks = Feedback::where('answer_id', $answer->id)
                ->latest()
                ->take(3)
                ->pluck('is_helpful');

            if (
                $lastFeedbacks->count() === 3 &&
                $lastFeedbacks->every(fn ($v) => $v === false)
            ) {
                $chatSession->status = 'escalated';
                $chatSession->save();
            }

            // 6. Learning rule ditahan sampai ada verifikasi staff.
            // Feedback tetap dikumpulkan sebagai sinyal kualitas.
        });

        return response()->json([
            'message' => 'Feedback diterima dan diproses',
        ]);
    }
}
