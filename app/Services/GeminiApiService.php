<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiApiService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;

    public function __construct()
    {
        // Mengambil konfigurasi dari file .env yang sudah kita setup
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY'));
        $this->model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-1.5-flash'));
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * Memeriksa apakah input user mengandung kata kunci krisis (Input Guardrail).
     */
    public function checkCrisisTrigger(string $message): bool
    {
        // Kamus kata kunci sensitif terkait tindakan membahayakan diri (self-harm/suicide)
        $crisisKeywords = [
            'ingin mati', 'bunuh diri', 'akhiri hidup', 'nyerah sama hidup', 
            'pengen mati', 'bunuh_diri', 'melukai diri', 'potong nadi'
        ];

        foreach ($crisisKeywords as $keyword) {
            if (str_contains(strtolower($message), $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Menembak Gemini API dengan menyuntikkan System Instruction (Behavior Guardrail).
     */
    public function chat(string $userMessage, array $chatHistory = []): string
    {
        // 1. Menyusun instruksi ketat untuk mengunci perilaku Gemini AI [cite: 65, 66, 67, 68, 70, 71, 72, 73]
        $systemInstruction = "
        Role: Anda adalah 'Teman Cerita' anonim di platform RuangJujur untuk Gen Z Indonesia. [cite: 6, 9]
        Gaya: Empati, hangat, memvalidasi emosi, menggunakan bahasa santai/casual (kamu, aku, ya, kok).

        ATURAN KETAT (GUARDRAILS):
        1. BATASAN MEDIS: Anda BUKAN psikolog/psikiater. DILARANG KERAS memberikan diagnosis (misal: 'Kamu mengalami depresi/anxiety/Bipolar'). DILARANG KERAS menyarankan obat atau dosis. 
        2. METODE: Gunakan 'Reflective Listening'. Validasi perasaan mereka dulu dengan hangat, baru berikan pertanyaan reflektif pendek untuk memicu kesadaran mandiri. [cite: 71, 72]
        3. BATAS PANJANG: Maksimal respons adalah 2-3 kalimat pendek. Jangan berikan teks paragraf panjang yang melelahkan pembaca. 
        4. RUANG LINGKUP: Fokus hanya pada keluh kesah emosional, akademis, keluarga, dan relasi. Tolak permintaan di luar itu dengan halus.
        ";

        // 2. Format payload sesuai dokumentasi Gemini API v1beta
        $contents = [];

        // Masukkan riwayat chat masa lalu jika ada (untuk ingatan jangka pendek AI)
        foreach ($chatHistory as $chat) {
            $contents[] = [
                'role' => $chat['sender'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $chat['message']]]
            ];
        }

        // Masukkan pesan terbaru dari user
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];

        try {
            // 3. Eksekusi HTTP Request ke Google AI Studio dengan Safety Settings Ketat
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}?key={$this->apiKey}", [
                'contents' => $contents,
                'systemInstruction' => [
                    'parts' => [['text' => $systemInstruction]]
                ],
                'safetySettings' => [
                    ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_LOW_AND_ABOVE'],
                    ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_LOW_AND_ABOVE'],
                    ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_LOW_AND_ABOVE'],
                    ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_LOW_AND_ABOVE']
                ]
            ]);

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text') ?? 'Aku di sini siap mendengarkanmu.';
            }

            Log::error('Gemini API Error: ' . $response->body());
            return 'Maaf, kepalaku sedang sedikit ramai. Bisa ulangi sekali lagi?';

        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            return 'Sepertinya ada gangguan koneksi di ruang aman kita. Tunggu sebentar ya.';
        }
    }
}