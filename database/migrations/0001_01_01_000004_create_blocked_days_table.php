<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|==============================================================================
| create_blocked_days_table / Tabla de días bloqueados (vacaciones)
|==============================================================================
| EN: Blocked days (holidays / non-operational). The owner marks here, from the
|     panel, the days they will NOT be available. AvailabilityService treats them
|     as NOT bookable, so on the frontend calendar those days are disabled.
| ES: Días bloqueados (vacaciones / no operativo). El dueño marca aquí, desde el
|     panel, los días en los que NO estará disponible. AvailabilityService los
|     trata como NO reservables, así en el calendario del frontend salen
|     deshabilitados.
|==============================================================================
*/
return new class extends Migration
{
    /**
     * EN: Create the blocked days table.
     * ES: Crea la tabla de días bloqueados.
     */
    public function up(): void
    {
        Schema::create('blocked_days', function (Blueprint $table) {
            $table->id();
            // EN: Blocked day. Unique: no point blocking the same day twice.
            // ES: Día bloqueado. Único: no tiene sentido bloquear dos veces el mismo.
            $table->date('date')->unique();
            // EN: Optional reason (e.g. "Holidays", "Local festivity") — informational.
            // ES: Motivo opcional (p. ej. "Vacaciones", "Festivo local") — informativo.
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * EN: Drop the blocked days table.
     * ES: Elimina la tabla de días bloqueados.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_days');
    }
};
