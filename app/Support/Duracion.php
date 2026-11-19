<?php

namespace App\Support;

/*
|==============================================================================
| Duracion — etiqueta legible de una duración / readable duration label
|==============================================================================
| ES: Convierte minutos en texto para la página, los correos y Telegram:
|     30 → "30 min", 60 → "1 hora", 90 → "1 h 30 min", 120 → "2 horas".
|     Antes la etiqueta solo conocía 30 y 60 («1 hora» o «30 min» fijos), así
|     que una duración configurada de 90 salía como «30 min» (F4).
| EN: Turns minutes into text for the page, the emails and Telegram:
|     30 → "30 min", 60 → "1 hour", 90 → "1 h 30 min", 120 → "2 hours".
|     The label used to know only 30 and 60 (a fixed "1 hour" or "30 min"), so
|     a configured duration of 90 was shown as "30 min" (F4).
|==============================================================================
*/
class Duracion
{
    /**
     * ES: Etiqueta de una duración en el idioma pedido (o el activo).
     * EN: Label of a duration in the requested locale (or the active one).
     */
    public static function etiqueta(int $minutos, ?string $idioma = null): string
    {
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        // ES: Menos de una hora: "45 min". EN: Under an hour: "45 min".
        if ($horas === 0) {
            return __('citas.dur_minutes', ['min' => $minutos], $idioma);
        }

        // ES: Horas justas: "1 hora" / "2 horas". EN: Whole hours: "1 hour" / "2 hours".
        if ($resto === 0) {
            return trans_choice('citas.dur_hours', $horas, ['h' => $horas], $idioma);
        }

        // ES: Horas y minutos: "1 h 30 min". EN: Hours and minutes: "1 h 30 min".
        return __('citas.dur_hours_minutes', ['h' => $horas, 'min' => $resto], $idioma);
    }
}
