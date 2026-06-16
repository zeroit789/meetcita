<?php

namespace App\Livewire;

use App\Mail\AppointmentConfirmed;
use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

/*
|==============================================================================
| PanelCitas — private appointments table (Livewire)
| PanelCitas — tabla privada de citas (Livewire)
|==============================================================================
| EN: Lists ALL appointments (ordered by date/time) and lets the owner mark
|     each one confirmed or cancelled. Access is already protected by the
|     panel-password middleware on the /panel route.
| ES: Lista TODAS las citas (ordenadas por fecha/hora) y permite al dueño
|     marcarlas como confirmada o cancelada. El acceso ya está protegido por el
|     middleware de contraseña del panel en la ruta /panel.
|
| INDEX / ÍNDICE
|   1. confirmar() ........ confirm + calendar + email / confirmar + calendario
|   2. cancelar() ......... cancel + free the slot / cancelar + liberar hueco
|   3. render() ........... paginated table data / datos paginados de la tabla
|==============================================================================
*/
class PanelCitas extends Component
{
    // EN: Livewire pagination: serve the table in pages (30/page) instead of
    //     loading all rows at once. Keeps the panel fast with many appointments.
    // ES: Paginación de Livewire: sirve la tabla por páginas (30/pág) en vez de
    //     cargar todas de golpe. Mantiene el panel rápido con muchas citas.
    use WithPagination;

    // ── 1. confirmar() EN / ES ───────────────────────────────────────────

    /**
     * EN: Mark an appointment as confirmed (and notify the client).
     * ES: Marca una cita como confirmada (y avisa al cliente).
     */
    public function confirmar(int $id): void
    {
        $cita = Appointment::find($id);

        // EN: Only confirm (and email) if it was pending: avoids resending the
        //     confirmation email if it was already confirmed.
        // ES: Solo confirmamos (y enviamos email) si estaba pendiente: evita
        //     reenviar el email si ya estaba confirmada.
        if (! $cita || $cita->status === 'confirmada') {
            return;
        }

        $cita->status = 'confirmada';
        $cita->save();

        // EN: Create the Google Calendar event (+ Meet if online). Degrades
        //     gracefully if Google isn't configured; wrapped so a Google error
        //     doesn't 500 the panel.
        // ES: Crea el evento de Google Calendar (+ Meet si es online). Degrada
        //     con gracia si Google no está configurado; envuelto para que un
        //     fallo de Google no devuelva un 500 al panel.
        try {
            app(GoogleCalendarService::class)->crearEvento($cita);
        } catch (\Throwable $e) {
            Log::error('Could not create the Google Calendar event on confirm: ' . $e->getMessage());
        }

        // EN: Tell the client their appointment is confirmed (client in To +
        //     extra attendees in Cc), in the client's language.
        // ES: Avisamos al cliente de que su cita queda confirmada (cliente en To
        //     + asistentes extra en Cc), en el idioma del cliente.
        try {
            Mail::to($cita->email)
                ->cc($cita->emailsAsistentesExtra())
                ->locale($cita->locale ?? config('appointments.default_locale', 'es'))
                ->send(new AppointmentConfirmed($cita));
        } catch (\Throwable $e) {
            Log::error('Could not send the confirmed-appointment email: ' . $e->getMessage());
        }
    }

    // ── 2. cancelar() EN / ES ────────────────────────────────────────────

    /**
     * EN: Mark an appointment as cancelled (frees the slot(s) for new bookings).
     * ES: Marca una cita como cancelada (libera el/los hueco/s para reservas).
     */
    public function cancelar(int $id): void
    {
        $cita = Appointment::find($id);

        if ($cita) {
            $cita->status = 'cancelada';
            $cita->save();

            // EN: Delete the Google Calendar event (frees the slot, notifies the
            //     client). No-op if there's no event or Google isn't configured.
            // ES: Borra el evento de Google Calendar (libera el hueco, avisa al
            //     cliente). No-op si no hay evento o Google no está configurado.
            app(GoogleCalendarService::class)->borrarEvento($cita);
        }
    }

    // ── 3. render() EN / ES ──────────────────────────────────────────────

    /**
     * EN: Render: all appointments ordered by date+time desc (soonest/most
     *     recent first), paginated to 30 per page.
     * ES: Render: todas las citas ordenadas por fecha+hora desc (las más
     *     próximas/recientes arriba), paginadas a 30 por página.
     */
    public function render()
    {
        return view('livewire.panel-citas', [
            'citas' => Appointment::query()
                ->orderBy('date', 'desc')
                ->orderBy('time', 'desc')
                ->paginate(30),
        ]);
    }
}
