<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_logs', function (Blueprint $table) {
            $table->id();
            // Menghubungkan log chat ke sesi anonim di atas
            $table->foreignId('anonymous_session_id')
                  ->constrained('anonymous_sessions')
                  ->onDelete('cascade');
            
            $table->enum('sender', ['user', 'ai']); // Menentukan siapa yang mengetik [cite: 50, 53]
            $table->text('message');                // Isi teks pesan/curhatan [cite: 50, 53]
            $table->timestamp('created_at')->useCurrent(); // Waktu kirim pesan
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_logs');
    }
};