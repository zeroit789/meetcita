<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/*
|==============================================================================
| GestionarCitaController / Página «gestionar mi cita»
|==============================================================================
| ES: Sirve la página del enlace FIRMADO de los correos al cliente.
|       - Firma manipulada o ausente: se comprueba ANTES de tocar la base de
|         datos.
|       - El enlace caduca a la hora de la cita y lleva el token revocable de
|         la cita: si no coincide con el guardado (la cita se canceló o se
|         reprogramó después de enviarlo) o la cita está cancelada, no vale.
|     En todos esos casos responde 403 con una página propia y amable
|     (cita/enlace-no-valido) que explica por qué puede pasar y da el
|     contacto del negocio, en el idioma de la cita cuando la firma es
|     auténtica (si no, en el de la visita). Nunca enseña datos de la cita.
|     Este GET solo MUESTRA: los escáneres de correo abren los enlaces, así
|     que nada cambia aquí; cancelar y reprogramar van por POST (Livewire).
|     Las respuestas no se guardan en cachés (no-store) ni se indexan (noindex).
| EN: Serves the page of the SIGNED link in the client emails.
|       - Tampered or missing signature: checked BEFORE touching the database.
|       - The link expires at the booking time and carries the booking's
|         revocable token: if it doesn't match the stored one (the booking was
|         cancelled or rescheduled after sending it) or the booking is
|         cancelled, it isn't valid.
|     In all those cases it answers 403 with its own friendly page
|     (cita/enlace-no-valido) that explains why it can happen and gives the
|     business contact, in the booking's language when the signature is
|     authentic (otherwise, in the visitor's). It never shows booking data.
|     This GET only SHOWS: email scanners open links, so nothing changes
|     here; cancel and reschedule go through POST (Livewire).
|     Responses are not cached (no-store) nor indexed (noindex).
|==============================================================================
*/
class GestionarCitaController extends Controller
{
    /**
     * ES: Muestra la cita de esa referencia (404 si la autogestión está
     *     desactivada o, con firma auténtica, si la cita no existe; 403 con la
     *     página amable si el enlace ya no vale).
     * EN: Shows the booking of that reference (404 if self-service is
     *     disabled or, with an authentic signature, if the booking doesn't
     *     exist; 403 with the friendly page if the link is no longer valid).
     */
    public function show(Request $request, string $reference)
    {
        abort_unless(Appointment::autogestionActiva(), 404);

        // ES: 1) Firma (sobre la ruta relativa) sin mirar la base de datos.
        // EN: 1) Signature (over the relative path) without querying the database.
        if (! URL::hasCorrectSignature($request, false)) {
            return $this->enlaceNoValido(null);
        }

        // ES: 2) Firma auténtica: caducidad, token vigente y cita no cancelada.
        // EN: 2) Authentic signature: expiry, current token and booking not cancelled.
        $cita = Appointment::where('reference', $reference)->firstOrFail();
        $token = (string) $request->query('token', '');
        if (! URL::signatureHasNotExpired($request)
            || ! $cita->tokenGestionValido($token)
            || $cita->status === 'cancelada') {
            return $this->enlaceNoValido($cita);
        }

        return $this->sinCache(response()->view('cita.gestionar', ['cita' => $cita, 'token' => $token]));
    }

    /**
     * ES: Página amable de «este enlace ya no es válido» (403), en el idioma
     *     de la cita si se conoce. No recibe la cita: no enseña nada de ella.
     * EN: Friendly "this link is no longer valid" page (403), in the
     *     booking's language if known. It doesn't get the booking: it shows
     *     nothing about it.
     */
    protected function enlaceNoValido(?Appointment $cita)
    {
        if ($cita) {
            app()->setLocale($cita->idiomaCita());
        }

        return $this->sinCache(response()->view('cita.enlace-no-valido', [], 403));
    }

    /**
     * ES: Cabeceras comunes: sin caché y sin indexar.
     * EN: Common headers: not cached and not indexed.
     */
    protected function sinCache($respuesta)
    {
        return $respuesta
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
