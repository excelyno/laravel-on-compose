<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    use HasFactory;

    // Menonaktifkan default updated_at karena tabel chat_logs hanya butuh created_at
    const UPDATED_AT = null;

    protected $fillable = [
        'anonymous_session_id',
        'sender',
        'message'
    ];

    /**
     * Relasi balik ke tabel anonymous_sessions.
     */
    public function anonymousSession()
    {
        return $this->belongsTo(AnonymousSession::class, 'anonymous_session_id');
    }
}