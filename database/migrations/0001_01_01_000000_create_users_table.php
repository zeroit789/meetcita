<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ES: Migración base de Laravel: tablas users, password_reset_tokens y sessions.
//     'sessions' la usa SESSION_DRIVER=database (login del panel). 'users' no la usa
//     el panel: su acceso es una contraseña única de config.
// EN: Laravel base migration: users, password_reset_tokens and sessions tables.
//     'sessions' is used by SESSION_DRIVER=database (panel login). 'users' is not
//     used by the panel: its access is a single password from config.
return new class extends Migration
{
    /**
     * ES: Ejecuta la migración (crea las tablas).
     * EN: Run the migrations (create the tables).
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * ES: Revierte la migración (borra las tablas).
     * EN: Reverse the migrations (drop the tables).
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
