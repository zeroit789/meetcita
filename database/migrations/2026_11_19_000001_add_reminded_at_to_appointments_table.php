<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|==============================================================================
| add_reminded_at_to_appointments_table / Columna reminded_at en 'appointments'
|==============================================================================
| ES: Cuándo se envió el recordatorio de la víspera (null = aún no). La orden
|     citas:recordar la rellena con un UPDATE condicional ANTES de enviar, así
|     dos procesos a la vez nunca mandan el mismo recordatorio. Si la cita se
|     reprograma vuelve a null. Columna nueva y nullable: no toca el índice
|     único parcial (date, time) de la tabla.
| EN: When the day-before reminder was sent (null = not yet). The
|     citas:recordar command fills it with a conditional UPDATE BEFORE
|     sending, so two processes at once never send the same reminder. If the
|     booking is rescheduled it goes back to null. New nullable column: it
|     doesn't touch the table's partial unique (date, time) index.
|==============================================================================
*/
return new class extends Migration
{
    /**
     * ES: Añade reminded_at (marca de tiempo, nullable).
     * EN: Adds reminded_at (nullable timestamp).
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable()->after('status');
        });
    }

    /**
     * ES: Quita la columna (reversible).
     * EN: Drops the column (reversible).
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });
    }
};
