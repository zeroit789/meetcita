<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
|==============================================================================
| BlockedDay model / Modelo BlockedDay
|==============================================================================
| EN: A blocked day — a date on which the owner is NOT available (holidays,
|     time off, etc.). AvailabilityService excludes these from bookable days,
|     so the frontend calendar renders them disabled.
| ES: Un día bloqueado — una fecha en la que el dueño NO está disponible
|     (vacaciones, festivos, etc.). AvailabilityService los excluye de los días
|     reservables, así en el calendario del frontend salen deshabilitados.
|==============================================================================
*/
class BlockedDay extends Model
{
    // EN: Mass-assignable fields. ES: Campos asignables en masa.
    protected $fillable = ['date', 'reason'];

    // EN: 'date' as a Carbon object for easy formatting in views.
    // ES: 'date' como objeto Carbon para formatear cómodo en las vistas.
    protected $casts = [
        'date' => 'date',
    ];
}
