<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Answer;
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

        // 1. Simpan pertanyaan
        $question = Question::create([
            'content' => $request->question
        ]);

        // 2. Cek knowledge base (database-first)
        $existingAnswer = Answer::where('content', 'LIKE', '%' . $request->question . '%')
            ->where('source', 'db')
            ->first();

        if ($existingAnswer) {
            return response()->json([
                'answer' => $existingAnswer->content,
                'source' => 'database'
            ]);
        }

        // 3. Fallback ke AI
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
                                    'text' => 'Anda adalah asisten pusat informasi UPT TIK universitas. Jawab secara ringkas, formal, dan faktual. Maksimal 3–5 kalimat.'
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
            
            // 4. Ambil jawaban AI (AMAN)
            Log::info('OPENAI RAW RESPONSE', $response->json());
            $output = $response->json('output') ?? [];

            $aiAnswer = collect($output)
                ->flatMap(fn ($item) => $item['content'] ?? [])
                ->firstWhere('type', 'output_text')['text'] ?? null;

            if (!$aiAnswer) {
                throw new \Exception('AI tidak mengembalikan jawaban');
            }

            // 5. Simpan jawaban AI
            Answer::create([
                'question_id' => $question->id,
                'content' => $aiAnswer,
                'source' => 'ai'
            ]);

            return response()->json([
                'answer' => $aiAnswer,
                'source' => 'ai'
            ]);

        } catch (\Exception $e) {
            Log::error('AI ERROR: ' . $e->getMessage());

            return response()->json([
                'message' => 'Sistem sedang gangguan. Pertanyaan akan diteruskan ke petugas.'
            ], 500);
        }
    }
}
