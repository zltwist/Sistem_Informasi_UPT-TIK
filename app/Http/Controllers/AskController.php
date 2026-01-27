<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Answer;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AskController extends Controller
{
    public function ask(Request $request)
    {
        // 0. Validasi input
        $request->validate([
            'question' => 'required|string|min:5'
        ]);

        /**
         * 1. Ambil / buat chat session aktif
         * (versi sederhana: satu sesi aktif global)
         */
        $chatSession = ChatSession::where('status', 'active')->first();

        if (!$chatSession) {
            $chatSession = ChatSession::create([
                'status' => 'active'
            ]);
        }
        /**
         * 2. Kalau session sudah closed -> STOP AI
         */
        if ($chatSession->status === 'closed') {
            return response()->json([
                'message' => 'Sesi telah ditutup. Silakan mulai sesi baru.',
                'status'  => 'closed',
                'session_id' => $chatSession->id
            ]);
        }


        /**
         * 2. Kalau session sudah eskalasi → STOP AI
         */
        if ($chatSession->status === 'escalated') {
            return response()->json([
                'message' => 'Pertanyaan Anda akan diteruskan ke petugas UPT TIK.',
                'status'  => 'escalated',
                'session_id' => $chatSession->id
            ]);
        }

        /**
         * 3. Simpan pertanyaan + kaitkan ke session
         */
        $question = Question::create([
            'chat_session_id' => $chatSession->id,
            'content' => $request->question
        ]);

        /**
         * 4. Cek knowledge base (database-first, confidence-aware)
         */
        $existingAnswer = Answer::where('source', 'db')
            ->where('content', 'LIKE', '%' . $request->question . '%')
            ->orderByDesc('confidence_score')
            ->first();

        if ($existingAnswer) {
            return response()->json([
                'answer'     => $existingAnswer->content,
                'source'     => 'database',
                'confidence' => $existingAnswer->confidence_score,
                'session_id' => $chatSession->id
            ]);
        }

        /**
         * 5. Fallback ke AI
         */
        try {
            $response = Http::withToken(config('services.openai.key'))
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.model'),
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' =>
                                        'Anda adalah asisten pusat informasi UPT TIK universitas.
                                         Jawab secara ringkas, formal, dan faktual.
                                         Jika ragu, arahkan ke petugas.'
                                ]
                            ]
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' => $request->question
                                ]
                            ]
                        ]
                    ],
                    'max_output_tokens' => 180,
                    'temperature' => 0.2,
                ]);

            // 6. Ambil jawaban AI (aman)
            Log::info('OPENAI RAW RESPONSE', $response->json());
            $output = $response->json('output') ?? [];

            $aiAnswer = collect($output)
                ->flatMap(fn ($item) => $item['content'] ?? [])
                ->firstWhere('type', 'output_text')['text'] ?? null;

            if (!$aiAnswer) {
                throw new \Exception('AI tidak mengembalikan jawaban');
            }

            // 7. Simpan jawaban AI
            Answer::create([
                'question_id' => $question->id,
                'content'     => $aiAnswer,
                'source'      => 'ai'
            ]);

            return response()->json([
                'answer' => $aiAnswer,
                'source' => 'ai',
                'session_id' => $chatSession->id
            ]);

        } catch (\Exception $e) {
            Log::error('AI ERROR: ' . $e->getMessage());

            /**
             * 8. Eskalasi session jika AI gagal
             */
            $chatSession->status = 'escalated';
            $chatSession->save();

            return response()->json([
                'message' => 'Sistem sedang gangguan. Pertanyaan akan diteruskan ke petugas.',
                'status'  => 'escalated',
                'session_id' => $chatSession->id
            ], 500);
        }
    }
}
