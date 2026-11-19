<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|==============================================================================
| add_revision_to_appointments_table / Columna revision en 'appointments'
|==============================================================================
| ES: Número de revisión de la cita: sube en 1 con cada cambio que importa
|     (confirmar, cancelar, reprogramar). Los botones del panel y de Telegram
|     llevan este número y el UPDATE exige que siga igual, así un botón viejo
|     nunca confirma una hora que el dueño no ha visto. Sustituye a updated_at,
|     que solo tiene segundos: dos cambios en el mismo segundo daban la misma
|     versión. Las citas que ya existen empiezan en 0.
| EN: The booking's revision number: it goes up by 1 on every change that
|     matters (confirm, cancel, reschedule). The panel and Telegram buttons
|     carry this number and the UPDATE requires it to be unchanged, so an old
|     button never confirms a time the owner hasn't seen. It replaces
|     updated_at, which only has seconds: two changes in the same second gave
|     the same version. Existing bookings start at 0.
|==============================================================================
*/
return new class extends Migration
{
    /**
     * ES: Añade revision (entero sin signo, 0 por defecto).
     * EN: Adds revision (unsigned integer, 0 by default).
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(0)->after('status');
        });
    }

    /**
     * ES: Quita la columna (reversible).
     * EN: Drops the column (reversible).
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('revision');
        });
    }
};
