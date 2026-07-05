<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnonymousSession; // Pastikan nanti kita buat Modelnya
use App\Services\AnonymousNameGenerator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class AnonymousSessionController extends Controller
{
    /**
     * Menyimpan sesi anonim baru dari form Landing Page / Check-In.
     */
    public function store(Request $request)
    {
        // 1. Validasi input form awal (Mood 1-5 dan Topik pilihan)
        $request->validate([
            'initial_mood' => 'required|string', // Contoh: "Berat banget, susah gerak"
            'selected_topics' => 'nullable|array', // Contoh: ["Kuliah", "Keluarga"]
        ]);

        // 2. Generate Session Token unik untuk ditanam di browser/session user
        $sessionToken = Str::uuid()->toString();

        // 3. Ambil nama anonim acak dari Service Generator kita (e.g., "Bulan Tenang")
        $anonymousName = AnonymousNameGenerator::generate();

        // 4. Simpan ke database MySQL via Model
        $anonymousSession = AnonymousSession::create([
            'session_token' => $sessionToken,
            'anonymous_name' => $anonymousName,
            'initial_mood' => $request->initial_mood,
            'selected_topics' => $request->selected_topics, // Otomatis dicasting jadi JSON oleh Laravel
            'chat_count' => 0,
            'status' => 'active',
        ]);

        // 5. Simpan token ke dalam Session Laravel agar user terautentikasi secara anonim
        Session::put('anonymous_token', $sessionToken);

        // 6. Kembalikan respons berupa data sesi anonim (bisa untuk redirect ke halaman chat)
        return response()->json([
            'success' => true,
            'message' => 'Sesi ruang aman berhasil dibuat.',
            'data' => [
                'name' => $anonymousSession->anonymous_name,
                'token' => $anonymousSession->session_token,
            ]
        ], 201);
    }
}