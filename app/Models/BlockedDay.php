<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
|==============================================================================
| BlockedDay model / Modelo BlockedDay
|==============================================================================
| ES: Un día bloqueado — una fecha en la que el dueño NO está disponible
|     (vacaciones, festivos, etc.). AvailabilityService los excluye de los días
|     reservables, así en el calendario del frontend salen deshabilitados.
| EN: A blocked day — a date on which the owner is NOT available (holidays,
|     time off, etc.). AvailabilityService excludes these from bookable days,
|     so the frontend calendar renders them disabled.
|==============================================================================
*/
class BlockedDay extends Model
{
    // ES: Campos asignables en masa. EN: Mass-assignable fields.
    protected $fillable = ['date', 'reason'];

    // ES: 'date' como objeto Carbon para formatear cómodo en las vistas.
    // EN: 'date' as a Carbon object for easy formatting in views.
    protected $casts = [
        'date' => 'date',
    ];
}
