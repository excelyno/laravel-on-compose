<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnonymousSession extends Model
{
    use HasFactory;

    // Daftarkan kolom yang boleh diisi secara massal (Mass Assignment)
    protected $fillable = [
        'session_token',
        'anonymous_name',
        'initial_mood',
        'selected_topics',
        'chat_count',
        'status'
    ];

    // Mengubah otomatis data array menjadi format JSON saat masuk ke MySQL, dan sebaliknya
    protected $casts = [
        'selected_topics' => 'array',
    ];

    /**
     * Relasi ke tabel chat_logs (Satu sesi bisa memiliki banyak log chat).
     */
    public function chatLogs()
    {
        return $this->hasMany(ChatLog::class, 'anonymous_session_id');
    }
}