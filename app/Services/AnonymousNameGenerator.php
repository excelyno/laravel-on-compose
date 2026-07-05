<?php

namespace App\Services;

class AnonymousNameGenerator
{
    /**
     * Daftar kata benda alam untuk nama depan.
     */
    protected static array $nouns = [
        'Bulan', 'Awan', 'Bintang', 'Ombak', 'Angin', 
        'Hujan', 'Embun', 'Daun', 'Rintik', 'Surya', 
        'Senja', 'Fajar', 'Langit', 'Samudra', 'Pohon'
    ];

    /**
     * Daftar kata sifat penenang untuk nama belakang.
     */
    protected static array $adjectives = [
        'Tenang', 'Teduh', 'Damai', 'Hangat', 'Sabar', 
        'Lembut', 'Jujur', 'Ikhlas', 'Bijak', 'Sentosa', 
        'Tenteram', 'Ningrat', 'Ramah', 'Cerah', 'Syahdu'
    ];

    /**
     * Meng-generate nama anonim acak.
     * Contoh output: "Bulan Tenang", "Awan Teduh"
     */
    public static function generate(): string
    {
        $randomNoun = self::$nouns[array_rand(self::$nouns)];
        $randomAdjective = self::$adjectives[array_rand(self::$adjectives)];

        return $randomNoun . ' ' . $randomAdjective;
    }
}