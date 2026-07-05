<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anonymous_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token')->unique(); // Token acak untuk mengenali device user anonim [cite: 8, 10]
            $table->string('anonymous_name');          // Nama samaran acak, misal: "Bulan Tenang" 
            $table->string('initial_mood');           // Menampung mood 1-5 (kalimat deskriptif) [cite: 18]
            $table->json('selected_topics')->nullable(); // Menyimpan topik (Kuliah, Keluarga, dll) [cite: 43, 44]
            $table->integer('chat_count')->default(0);  // Penghitung batas obrolan (soft-limit) [cite: 69]
            $table->enum('status', ['active', 'completed', 'crisis'])->default('active'); // Status sesi [cite: 69, 73]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anonymous_sessions');
    }
};