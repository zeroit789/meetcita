<?php

namespace App\Livewire;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentConfirmed as CitaConfirmada;
use App\Http\Middleware\AdminPanelPassword;
use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentRejected;
use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use App\Support\Eventos;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
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
|   4. verVista() ............. Hoy / Próximas / Todas / Today / Upcoming / All
|   5. render() ............... datos paginados de la tabla / paginated table data
|==============================================================================
*/
class PanelCitas extends Component
{
    // ES: Paginación de Livewire: sirve la tabla por páginas (30/pág) en vez de
    //     cargar todas de golpe. Mantiene el panel rápido con muchas citas.
    // EN: Livewire pagination: serve the table in pages (30/page) instead of
    //     loading all rows at once. Keeps the panel fast with many appointments.
    use WithPagination;

    // ES: Qué citas se ven: 'hoy' (por hora), 'proximas' (de hoy en adelante,
    //     la vista por defecto) o 'todas' (las más recientes primero). La fija
    //     el servidor con verVista().
    // EN: Which bookings are shown: 'hoy' (by time), 'proximas' (today
    //     onwards, the default view) or 'todas' (most recent first). Set by
    //     the server with verVista().
    #[Locked]
    public string $vista = 'proximas';

    // ES: Cita cuyo formulario de cancelación está abierto (null = ninguno).
    // EN: Booking whose cancellation form is open (null = none).
    public ?int $cancelandoId = null;

    // ES: Motivo opcional que se manda al cliente en el email de cancelación.
    // EN: Optional reason sent to the client in the cancellation email.
    public string $motivoCancelacion = '';

    /**
     * ES: Guarda del panel en CADA petición del componente (montaje, acciones,
     *     paginación y repintados): sin una sesión válida del panel, 403. Va en
     *     boot() y no en cada acción porque repintar también enseña los datos.
     *     Es la defensa en profundidad del middleware persistente.
     * EN: Panel guard on EVERY request of the component (mount, actions,
     *     pagination and re-renders): without a valid panel session, 403. It
     *     lives in boot() rather than in each action because re-rendering also
     *     shows the data. Defence in depth on top of the persistent middleware.
     */
    public function boot(): void
    {
        abort_unless(AdminPanelPassword::sesionValida(), 403);
    }

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
    public function confirmar(int $id, ?string $version = null): void
    {
        $this->resetErrorBag();

        // ES: $version = revisión de la fila que ve el dueño. Si el cliente
        //     cambió la cita después de cargar la tabla (aunque sea en el mismo
        //     segundo), no se confirma la hora nueva sin verla. Sin versión
        //     (una página del panel abierta antes de actualizar): solo si la
        //     cita no se ha tocado nunca (revisión 0).
        // EN: $version = revision of the row the owner sees. If the client
        //     changed the booking after the table loaded (even in the same
        //     second), the new time isn't confirmed unseen. Without a version
        //     (a panel page opened before upgrading): only if the booking was
        //     never touched (revision 0).
        $version ??= '0';
        $revision = ctype_digit($version) ? (int) $version : -1;
        $actual = Appointment::find($id);
        if ($actual && $actual->revision !== $revision) {
            $this->addError('accion', __('citas.panel_err_changed'));

            return;
        }

        // ES: Transición ATÓMICA pendiente -> confirmada con UPDATE condicional
        //     (igual que en Telegram) y la misma revisión: 0 filas = no existía,
        //     no estaba pendiente o cambió entre medias.
        // EN: ATOMIC pending -> confirmed transition via conditional UPDATE
        //     (same as Telegram) and the same revision: 0 rows = missing, not
        //     pending or changed meanwhile.
        $filas = Appointment::whereKey($id)
            ->where('status', 'pendiente')
            ->where('revision', $revision)
            ->update(['status' => 'confirmada', 'revision' => Appointment::revisionSiguiente()]);

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
            Log::error('Could not create the Google Calendar event on confirm ('.$e::class.').');
        }

        // ES: Avisamos al cliente de que su cita queda confirmada (cliente en To
        //     + asistentes extra en Cc), en el idioma del cliente. Sin correo
        //     (alta manual por teléfono) no se envía nada.
        // EN: Tell the client their appointment is confirmed (client in To +
        //     extra attendees in Cc), in the client's language. Without an
        //     email (manual phone entry) nothing is sent.
        if ($cita->tieneEmail()) {
            try {
                Mail::to($cita->email)
                    ->cc($cita->emailsAsistentesExtra())
                    ->locale($cita->locale ?? config('appointments.default_locale', 'es'))
                    ->send(new AppointmentConfirmed($cita));
            } catch (\Throwable $e) {
                Log::error('Could not send the confirmed-appointment email ('.$e::class.').');
            }
        }

        // ES: Evento para el CRM (solo si la confirmamos nosotros).
        // EN: Event for the CRM (only if we confirmed it).
        Eventos::disparar(new CitaConfirmada($cita, CitaConfirmada::SOURCE_PANEL));
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
        //     Sube la revisión y cambia el token: el enlace de gestionar del
        //     cliente deja de valer.
        //     Bumps the revision and changes the token: the client's manage
        //     link stops working.
        $filas = Appointment::whereKey($id)
            ->where('status', '!=', 'cancelada')
            ->update(['status' => 'cancelada', 'revision' => Appointment::revisionSiguiente(), 'manage_token' => Appointment::nuevoTokenGestion()]);

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
            Log::error('Could not delete the Google Calendar event on cancel ('.$e::class.').');
        }

        // ES: Email de cancelación al cliente (+ asistentes extra en Cc), en su
        //     idioma. Sin correo no se envía nada.
        // EN: Cancellation email to the client (+ extra attendees in Cc), in
        //     their language. Without an email nothing is sent.
        if ($cita->tieneEmail()) {
            try {
                Mail::to($cita->email)
                    ->cc($cita->emailsAsistentesExtra())
                    ->locale($cita->locale ?? config('appointments.default_locale', 'es'))
                    ->send(new AppointmentRejected($cita, $motivo));
            } catch (\Throwable $e) {
                Log::error('Could not send the cancellation email ('.$e::class.').');
            }
        }

        // ES: Evento para el CRM: la canceló el dueño, con su motivo.
        // EN: Event for the CRM: the owner cancelled it, with their reason.
        Eventos::disparar(new AppointmentCancelled($cita, AppointmentCancelled::BY_OWNER, $motivo !== '' ? $motivo : null));
    }

    // ── 4. verVista() ────────────────────────────────────────────────────

    /**
     * ES: Cambia la vista (solo valores conocidos) y vuelve a la página 1.
     * EN: Switches the view (known values only) and goes back to page 1.
     */
    public function verVista(string $vista): void
    {
        if (! in_array($vista, ['hoy', 'proximas', 'todas'], true)) {
            return;
        }

        $this->vista = $vista;
        $this->cerrarCancelacion();
        $this->resetPage();
    }

    /**
     * ES: Repinta la tabla cuando se da de alta una cita a mano (PanelNuevaCita).
     * EN: Re-renders the table when a booking is added by hand (PanelNuevaCita).
     */
    #[On('cita-creada')]
    public function refrescar(): void
    {
        // ES: Basta con repintar. EN: Re-rendering is enough.
    }

    // ── 5. render() ──────────────────────────────────────────────────────

    /**
     * ES: Render según la vista, con «hoy» en la zona del negocio:
     *       - hoy: las de hoy por hora;
     *       - proximas: de hoy en adelante, la más cercana arriba;
     *       - todas: fecha+hora desc (las más recientes arriba).
     *     Paginadas a 30 por página.
     * EN: Render by view, with "today" in the business zone:
     *       - hoy: today's, by time;
     *       - proximas: today onwards, the nearest first;
     *       - todas: date+time desc (most recent first).
     *     Paginated to 30 per page.
     */
    public function render()
    {
        $hoy = Carbon::today(config('appointments.timezone', 'UTC'));
        $consulta = Appointment::query();

        match ($this->vista) {
            'hoy' => $consulta->whereDate('date', $hoy->toDateString())->orderBy('time'),
            'todas' => $consulta->orderBy('date', 'desc')->orderBy('time', 'desc'),
            default => $consulta->whereDate('date', '>=', $hoy->toDateString())->orderBy('date')->orderBy('time'),
        };

        return view('livewire.panel-citas', [
            'citas' => $consulta->paginate(30),
            'hoyTexto' => $hoy->locale(app()->getLocale())->isoFormat(app()->getLocale() === 'en' ? 'dddd, MMMM D' : 'dddd D [de] MMMM'),
        ]);
    }
}
