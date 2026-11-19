<?php

namespace App\Http\Controllers;

use App\Models\Appointment;

/*
|==============================================================================
| GestionarCitaController / Página «gestionar mi cita»
|==============================================================================
| ES: Sirve la página del enlace FIRMADO de los correos al cliente. La firma
|     la valida el middleware 'signed:relative' de la ruta ANTES de llegar
|     aquí (firma manipulada o ausente = 403, sin tocar la base de datos).
|     Este GET solo MUESTRA: los escáneres de correo abren los enlaces, así
|     que nada cambia aquí; cancelar y reprogramar van por POST (Livewire).
|     La respuesta no se guarda en cachés (no-store) ni se indexa (noindex).
| EN: Serves the page of the SIGNED link in the client emails. The signature
|     is validated by the route's 'signed:relative' middleware BEFORE getting
|     here (tampered or missing signature = 403, without touching the
|     database). This GET only SHOWS: email scanners open links, so nothing
|     changes here; cancel and reschedule go through POST (Livewire). The
|     response is not cached (no-store) nor indexed (noindex).
|==============================================================================
*/
class GestionarCitaController extends Controller
{
    /**
     * ES: Muestra la cita de esa referencia (404 si no existe o si la
     *     autogestión está desactivada).
     * EN: Shows the booking of that reference (404 if missing or if
     *     self-service is disabled).
     */
    public function show(string $reference)
    {
        abort_unless(Appointment::autogestionActiva(), 404);

        $cita = Appointment::where('reference', $reference)->firstOrFail();

        return response()
            ->view('cita.gestionar', ['cita' => $cita])
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
