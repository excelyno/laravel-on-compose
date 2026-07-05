<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnonymousSession;
use App\Models\ChatLog;
use App\Services\GeminiApiService;
use Illuminate\Support\Facades\Session;

class ChatController extends Controller
{
    protected GeminiApiService $geminiService;

    public function __construct(GeminiApiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Mengirim pesan di Ruang Curhat dan mendapatkan balasan dari AI.
     */
    public function sendMessage(Request $request)
    {
        // 1. Validasi input pesan dari user
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        // 2. Ambil token sesi anonim yang sedang aktif dari Session Laravel
        $sessionToken = Session::get('anonymous_token');
        if (!$sessionToken) {
            return response()->json(['error' => 'Sesi tidak ditemukan. Silakan masuk kembali.'], 401);
        }

        // 3. Cari data sesi anonim di MySQL
        $anonymousSession = AnonymousSession::where('session_token', $sessionToken)->first();
        if (!$anonymousSession || $anonymousSession->status !== 'active') {
            return response()->json(['error' => 'Sesi sudah tidak aktif atau tidak valid.'], 403);
        }

        $userMessage = $request->message;

        // 4. [GUARDRAIL LAPIS 1] Saringan Krisis (Deteksi Self-Harm / Suicide)
        if ($this->geminiService->checkCrisisTrigger($userMessage)) {
            // Ubah status sesi menjadi 'crisis' di database
            $anonymousSession->update(['status' => 'crisis']);

            return response()->json([
                'success' => true,
                'trigger_crisis' => true,
                'message' => 'Bulan Tenang, aku mendengar kamu dan aku peduli. Tapi kondisi saat ini sepertinya butuh teman nyata. Kamu tidak harus menghadapi ini sendirian.',
                'action_route' => 'Direktori Bantuan Profesional' // Di frontend akan memicu pop-up hotline
            ]);
        }

        // 5. [GUARDRAIL LAPIS 2] Soft-Limit Check (Maksimal Chat)
        $maxChat = env('RUANGJUJUR_MAX_CHAT', 15);
        if ($anonymousSession->chat_count >= $maxChat) {
            $anonymousSession->update(['status' => 'completed']);

            return response()->json([
                'success' => true,
                'limit_reached' => true,
                'message' => 'Aku senang bisa menemanimu berproses hari ini. Sekarang, yuk coba istirahatkan pikiranmu dulu dan lakukan micro-action ringan. Ruang ini akan selalu ada saat kamu butuh kembali.'
            ]);
        }

        // 6. Catat pesan USER ke database tabel `chat_logs`
        ChatLog::create([
            'anonymous_session_id' => $anonymousSession->id,
            'sender' => 'user',
            'message' => $userMessage
        ]);

        // 7. Ambil riwayat chat sebelumnya dari MySQL sebagai konteks ingatan Gemini (Maksimal 10 log terakhir agar tidak boros token)
        $chatHistory = ChatLog::where('anonymous_session_id', $anonymousSession->id)
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get(['sender', 'message'])
            ->toArray();

        // 8. Tembak Gemini API melalui Service
        $aiResponse = $this->geminiService->chat($userMessage, $chatHistory);

        // 9. Catat balasan AI ke database tabel `chat_logs`
        ChatLog::create([
            'anonymous_session_id' => $anonymousSession->id,
            'sender' => 'ai',
            'message' => $aiResponse
        ]);

        // 10. Update jumlah chat_count di sesi anonim
        $anonymousSession->increment('chat_count');

        // 11. Kembalikan balasan AI ke frontend
        return response()->json([
            'success' => true,
            'chat_count' => $anonymousSession->chat_count,
            'data' => [
                'reply' => $aiResponse
            ]
        ]);
    }
}