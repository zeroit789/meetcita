<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|==============================================================================
| create_blocked_days_table / Tabla de días bloqueados (vacaciones)
|==============================================================================
| ES: Días bloqueados (vacaciones / no operativo). El dueño marca aquí, desde el
|     panel, los días en los que NO estará disponible. AvailabilityService los
|     trata como NO reservables, así en el calendario del frontend salen
|     deshabilitados.
| EN: Blocked days (holidays / non-operational). The owner marks here, from the
|     panel, the days they will NOT be available. AvailabilityService treats them
|     as NOT bookable, so on the frontend calendar those days are disabled.
|==============================================================================
*/
return new class extends Migration
{
    /**
     * ES: Crea la tabla de días bloqueados.
     * EN: Create the blocked days table.
     */
    public function up(): void
    {
        Schema::create('blocked_days', function (Blueprint $table) {
            $table->id();
            // ES: Día bloqueado. Único: no tiene sentido bloquear dos veces el mismo.
            // EN: Blocked day. Unique: no point blocking the same day twice.
            $table->date('date')->unique();
            // ES: Motivo opcional (p. ej. "Vacaciones", "Festivo local") — informativo.
            // EN: Optional reason (e.g. "Holidays", "Local festivity") — informational.
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * ES: Elimina la tabla de días bloqueados.
     * EN: Drop the blocked days table.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_days');
    }
};
