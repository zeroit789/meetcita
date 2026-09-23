<?php

namespace App\Livewire;

use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentRejected;
use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

/*
|==============================================================================
| PanelCitas — tabla privada de citas (Livewire)
| PanelCitas — private appointments table (Livewire)
|==============================================================================
| ES: Lista TODAS las citas (ordenadas por fecha/hora) y permite al dueño
|     confirmarlas o cancelarlas. El acceso ya está protegido por el
|     middleware de contraseña del panel en la ruta /panel.
|     Mismas reglas que el bot de Telegram:
|       - Solo se confirma una cita PENDIENTE (si no, error controlado).
|       - Cancelar envía al cliente el email de cancelación, con un motivo
|         opcional que el dueño escribe en el propio panel.
| EN: Lists ALL appointments (ordered by date/time) and lets the owner
|     confirm or cancel them. Access is already protected by the panel-password
|     middleware on the /panel route.
|     Same rules as the Telegram bot:
|       - Only a PENDING booking can be confirmed (otherwise, a handled error).
|       - Cancelling emails the client the cancellation email, with an optional
|         reason the owner types in the panel itself.
|
| ÍNDICE / INDEX
|   1. confirmar() ............ confirmar + calendario + email / confirm + calendar + email
|   2. pedirCancelacion() ..... abrir el formulario del motivo / open the reason form
|   3. cancelar() ............. cancelar + liberar hueco + email / cancel + free slot + email
|   4. render() ............... datos paginados de la tabla / paginated table data
|==============================================================================
*/
class PanelCitas extends Component
{
    // ES: Paginación de Livewire: sirve la tabla por páginas (30/pág) en vez de
    //     cargar todas de golpe. Mantiene el panel rápido con muchas citas.
    // EN: Livewire pagination: serve the table in pages (30/page) instead of
    //     loading all rows at once. Keeps the panel fast with many appointments.
    use WithPagination;

    // ES: Cita cuyo formulario de cancelación está abierto (null = ninguno).
    // EN: Booking whose cancellation form is open (null = none).
    public ?int $cancelandoId = null;

    // ES: Motivo opcional que se manda al cliente en el email de cancelación.
    // EN: Optional reason sent to the client in the cancellation email.
    public string $motivoCancelacion = '';

    // ── 1. confirmar() ───────────────────────────────────────────────────

    /**
     * ES: Confirma una cita PENDIENTE (evento de Google + email al cliente). Si
     *     ya estaba confirmada o cancelada muestra un error controlado: reconfirmar
     *     una cancelada podía chocar con el índice único si otra persona había
     *     cogido ese hueco (500).
     * EN: Confirms a PENDING booking (Google event + email to the client). If it
     *     was already confirmed or cancelled it shows a handled error: re-confirming
     *     a cancelled one could clash with the unique index if someone else had
     *     taken that slot (500).
     */
    public function confirmar(int $id): void
    {
        $this->resetErrorBag();

        // ES: Transición ATÓMICA pendiente -> confirmada con UPDATE condicional
        //     (igual que en Telegram). 0 filas = no existía o no estaba pendiente.
        // EN: ATOMIC pending -> confirmed transition via conditional UPDATE
        //     (same as Telegram). 0 rows = missing or not pending.
        $filas = Appointment::whereKey($id)
            ->where('status', 'pendiente')
            ->update(['status' => 'confirmada']);

        if ($filas === 0) {
            $this->addError('accion', __('citas.panel_err_only_pending'));

            return;
        }

        $cita = Appointment::find($id);

        // ES: Crea el evento de Google Calendar (+ Meet si es online). Degrada
        //     con gracia si Google no está configurado; envuelto para que un
        //     fallo de Google no devuelva un 500 al panel.
        // EN: Create the Google Calendar event (+ Meet if online). Degrades
        //     gracefully if Google isn't configured; wrapped so a Google error
        //     doesn't 500 the panel.
        try {
            app(GoogleCalendarService::class)->crearEvento($cita);
        } catch (\Throwable $e) {
            Log::error('Could not create the Google Calendar event on confirm: '.$e->getMessage());
        }

        // ES: Avisamos al cliente de que su cita queda confirmada (cliente en To
        //     + asistentes extra en Cc), en el idioma del cliente.
        // EN: Tell the client their appointment is confirmed (client in To +
        //     extra attendees in Cc), in the client's language.
        try {
            Mail::to($cita->email)
                ->cc($cita->emailsAsistentesExtra())
                ->locale($cita->locale ?? config('appointments.default_locale', 'es'))
                ->send(new AppointmentConfirmed($cita));
        } catch (\Throwable $e) {
            Log::error('Could not send the confirmed-appointment email: '.$e->getMessage());
        }
    }

    // ── 2. pedirCancelacion() ────────────────────────────────────────────

    /**
     * ES: Abre, bajo la fila de la cita, el formulario con el motivo opcional.
     * EN: Opens, under the booking row, the form with the optional reason.
     */
    public function pedirCancelacion(int $id): void
    {
        $this->resetErrorBag();
        $this->cancelandoId = $id;
        $this->motivoCancelacion = '';
    }

    /**
     * ES: Cierra el formulario de cancelación sin hacer nada.
     * EN: Closes the cancellation form without doing anything.
     */
    public function cerrarCancelacion(): void
    {
        $this->cancelandoId = null;
        $this->motivoCancelacion = '';
    }

    // ── 3. cancelar() ────────────────────────────────────────────────────

    /**
     * ES: Cancela una cita (libera el hueco), borra su evento de Google y manda
     *     al cliente el mismo email de cancelación que el rechazo por Telegram,
     *     con el motivo escrito (opcional; máx. 1000 caracteres).
     * EN: Cancels a booking (frees the slot), deletes its Google event and sends
     *     the client the same cancellation email as the Telegram rejection, with
     *     the typed reason (optional; max 1000 characters).
     */
    public function cancelar(int $id): void
    {
        $this->resetErrorBag();
        $this->validate(['motivoCancelacion' => 'nullable|string|max:1000']);

        // ES: Transición ATÓMICA a cancelada: 0 filas = no existía o ya estaba
        //     cancelada, así no se reenvía el email.
        // EN: ATOMIC transition to cancelled: 0 rows = missing or already
        //     cancelled, so the email is not sent again.
        $filas = Appointment::whereKey($id)
            ->where('status', '!=', 'cancelada')
            ->update(['status' => 'cancelada']);

        if ($filas === 0) {
            $this->addError('accion', __('citas.panel_err_already_cancelled'));
            $this->cerrarCancelacion();

            return;
        }

        $cita = Appointment::find($id);
        $motivo = trim($this->motivoCancelacion);
        $this->cerrarCancelacion();

        // ES: Borra el evento de Google Calendar (libera el hueco en Google).
        //     No-op si no hay evento o Google no está configurado.
        // EN: Delete the Google Calendar event (frees the slot in Google).
        //     No-op if there's no event or Google isn't configured.
        try {
            app(GoogleCalendarService::class)->borrarEvento($cita);
        } catch (\Throwable $e) {
            Log::error('Could not delete the Google Calendar event on cancel: '.$e->getMessage());
        }

        // ES: Email de cancelación al cliente (+ asistentes extra en Cc), en su idioma.
        // EN: Cancellation email to the client (+ extra attendees in Cc), in their language.
        try {
            Mail::to($cita->email)
                ->cc($cita->emailsAsistentesExtra())
                ->locale($cita->locale ?? config('appointments.default_locale', 'es'))
                ->send(new AppointmentRejected($cita, $motivo));
        } catch (\Throwable $e) {
            Log::error('Could not send the cancellation email: '.$e->getMessage());
        }
    }

    // ── 4. render() ──────────────────────────────────────────────────────

    /**
     * ES: Render: todas las citas ordenadas por fecha+hora desc (las más
     *     próximas/recientes arriba), paginadas a 30 por página.
     * EN: Render: all appointments ordered by date+time desc (soonest/most
     *     recent first), paginated to 30 per page.
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
