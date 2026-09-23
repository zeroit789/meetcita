<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ES: Migración base de Laravel: tablas cache y cache_locks. Con CACHE_STORE=database
//     guardan el lock anti-doble-reserva por día, los rate limits, el token y los
//     huecos ocupados de Google y la cita que espera motivo en Telegram.
// EN: Laravel base migration: cache and cache_locks tables. With CACHE_STORE=database
//     they hold the per-day anti-double-booking lock, the rate limits, the Google
//     token and busy slots, and the booking awaiting a reason in Telegram.
return new class extends Migration
{
    /**
     * ES: Ejecuta la migración (crea las tablas).
     * EN: Run the migrations (create the tables).
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->bigInteger('expiration')->index();
        });
    }

    /**
     * ES: Revierte la migración (borra las tablas).
     * EN: Reverse the migrations (drop the tables).
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
