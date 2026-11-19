<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Services\AvailabilityService;
use App\Services\GestionCitas;
use App\Support\Duracion;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
|==============================================================================
| GestionarCita — «gestionar mi cita» del cliente (Livewire)
| GestionarCita — the client's "manage my booking" (Livewire)
|==============================================================================
| ES: Vive en la página del enlace FIRMADO (GestionarCitaController). La
|     carga GET solo muestra la cita; cancelar y reprogramar son acciones de
|     Livewire (POST), así que los escáneres de correo que abren el enlace no
|     cambian nada. El id de la cita se fija en mount() (petición ya firmada)
|     y es #[Locked]: el navegador no puede apuntar a otra cita. Cada acción
|     relee la cita y vuelve a comprobar estado y plazo (GestionCitas), y
|     cada petición (acciones y repintados) exige que el token del enlace
|     siga siendo el de la cita: si se canceló o reprogramó desde otro sitio,
|     esta página deja de servir (403).
|     Vistas: 'ver' (datos + botones), 'reprogramar' (días y horas libres),
|     'cancelar' (confirmación).
| EN: Lives on the SIGNED-link page (GestionarCitaController). The GET load
|     only shows the booking; cancel and reschedule are Livewire actions
|     (POST), so email scanners opening the link change nothing. The booking
|     id is set in mount() (an already signed request) and is #[Locked]: the
|     browser can't point at another booking. Every action re-reads the
|     booking and re-checks state and notice (GestionCitas), and every
|     request (actions and re-renders) requires the link's token to still be
|     the booking's: if it was cancelled or rescheduled elsewhere, this page
|     stops working (403).
|     Views: 'ver' (details + buttons), 'reprogramar' (free days and times),
|     'cancelar' (confirmation).
|
| INDEX / ÍNDICE
|   1. STATE ........... locked id + view + chosen slot / estado
|   2. VIEWS ........... open/close reschedule and cancel / abrir y cerrar
|   3. RESCHEDULE ...... pick day + time, confirm / reprogramar
|   4. CANCEL .......... confirm cancellation / cancelar
|   5. RENDER .......... data for the view / datos para la vista
|==============================================================================
*/
class GestionarCita extends Component
{
    // ── 1. STATE ─────────────────────────────────────────────────────────

    // ES: Cita que se gestiona (fijada por el servidor en mount()).
    // EN: Booking being managed (set by the server in mount()).
    #[Locked]
    public int $citaId;

    // ES: Token del enlace con el que se abrió la página (fijado por el servidor).
    // EN: Token of the link the page was opened with (set by the server).
    #[Locked]
    public string $token = '';

    // ES: Vista actual: 'ver' | 'reprogramar' | 'cancelar'.
    // EN: Current view: 'ver' | 'reprogramar' | 'cancelar'.
    #[Locked]
    public string $vista = 'ver';

    // ES: Día y hora nuevos elegidos (solo los fija el servidor tras comprobarlos).
    // EN: Chosen new day and time (only set by the server after checking them).
    #[Locked]
    public string $nuevaFecha = '';

    #[Locked]
    public string $nuevaHora = '';

    // ES: Aviso tras una acción: clave de traducción y si es un éxito.
    // EN: Notice after an action: translation key and whether it is a success.
    #[Locked]
    public ?string $aviso = null;

    #[Locked]
    public bool $avisoOk = false;

    /**
     * ES: Recibe el id de la cita y el token del enlace desde la página firmada.
     * EN: Receives the booking id and the link token from the signed page.
     */
    public function mount(int $citaId, string $token = ''): void
    {
        $this->citaId = $citaId;
        $this->token = $token;
    }

    /**
     * ES: Relee la cita en cada petición (estado y fecha siempre al día) y
     *     comprueba que el token del enlace sigue vigente (403 si no).
     * EN: Re-reads the booking on every request (state and date always
     *     fresh) and checks the link token is still current (403 if not).
     */
    protected function cita(): Appointment
    {
        // ES: Igual que el enlace: sin autogestión o con la cita ya empezada,
        //     una pestaña que siguiera abierta deja de servir.
        // EN: Like the link: without self-service or once the booking has
        //     started, a tab left open stops working.
        abort_unless(Appointment::autogestionActiva(), 403);

        $cita = Appointment::findOrFail($this->citaId);
        abort_unless($cita->tokenGestionValido($this->token) && $cita->inicioLocal()->isFuture(), 403);

        return $cita;
    }

    /**
     * ES: Tras cancelar o reprogramar desde ESTA página, el token de la cita
     *     cambia: la página se queda con el que generó su propio cambio (el
     *     que devuelve GestionCitas), no con el que haya en la base de datos,
     *     que otra operación pudo cambiar ya otra vez.
     * EN: After cancelling or rescheduling from THIS page, the booking's
     *     token changes: the page keeps the one its own change generated
     *     (returned by GestionCitas), not whatever is in the database, which
     *     another operation may have changed again already.
     */
    protected function seguirConElTokenNuevo(GestionCitas $servicio): void
    {
        $this->token = (string) $servicio->tokenNuevo;
    }

    // ── 2. VIEWS ─────────────────────────────────────────────────────────

    /**
     * ES: Abre el selector de día y hora (solo si aún se puede cambiar).
     * EN: Opens the day and time picker (only if it can still be changed).
     */
    public function abrirReprogramar(): void
    {
        $this->aviso = null;
        if (! $this->cita()->puedeAutogestionarse()) {
            return;
        }

        $this->vista = 'reprogramar';
        $this->nuevaFecha = '';
        $this->nuevaHora = '';
    }

    /**
     * ES: Abre la confirmación de cancelar.
     * EN: Opens the cancellation confirmation.
     */
    public function abrirCancelar(): void
    {
        $this->aviso = null;
        if ($this->cita()->puedeAutogestionarse()) {
            $this->vista = 'cancelar';
        }
    }

    /**
     * ES: Vuelve a la vista de la cita sin cambiar nada.
     * EN: Goes back to the booking view without changing anything.
     */
    public function volver(): void
    {
        $this->vista = 'ver';
        $this->nuevaFecha = '';
        $this->nuevaHora = '';
    }

    // ── 3. RESCHEDULE ────────────────────────────────────────────────────

    /**
     * ES: Elige el día nuevo (solo días ofrecidos).
     * EN: Picks the new day (offered days only).
     */
    public function elegirDia(string $fecha): void
    {
        if ($this->vista !== 'reprogramar' || ! app(AvailabilityService::class)->esFechaReservable($fecha)) {
            return;
        }

        $this->nuevaFecha = $fecha;
        $this->nuevaHora = '';
        $this->aviso = null;
    }

    /**
     * ES: Elige la hora nueva: debe estar libre para la duración de la cita
     *     sin contar la propia cita.
     * EN: Picks the new time: it must be free for the booking's duration not
     *     counting the booking itself.
     */
    public function elegirHora(string $hora): void
    {
        $cita = $this->cita();
        if ($this->nuevaFecha === ''
            || ! app(AvailabilityService::class)->slotSigueLibre($this->nuevaFecha, $hora, (int) $cita->duration, $cita->id)) {
            $this->nuevaHora = '';

            return;
        }

        $this->nuevaHora = $hora;
        $this->aviso = null;
    }

    /**
     * ES: Guarda el cambio (con todas las comprobaciones en GestionCitas).
     *     Tope de 5 cambios por hora y cita: cada cambio avisa al dueño y al
     *     cliente, no debe servir para mandar correos sin fin.
     * EN: Saves the change (with every check in GestionCitas). Cap of 5
     *     changes per hour and booking: each change notifies the owner and
     *     the client, it must not be a way to send endless emails.
     */
    public function confirmarReprogramacion(): void
    {
        if ($this->nuevaFecha === '' || $this->nuevaHora === '') {
            return;
        }

        $clave = 'autogestion:'.$this->citaId;
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $this->mostrarAviso('citas.manage_err_too_many', false);

            return;
        }
        RateLimiter::hit($clave, 3600);

        $servicio = app(GestionCitas::class);
        $resultado = $servicio->reprogramar($this->cita(), $this->nuevaFecha, $this->nuevaHora, $this->token);
        if ($resultado === GestionCitas::OK) {
            $this->seguirConElTokenNuevo($servicio);
        }

        match ($resultado) {
            GestionCitas::OK => $this->terminar('citas.manage_rescheduled_done'),
            GestionCitas::SIN_CAMBIOS => $this->terminar('citas.manage_same_slot'),
            GestionCitas::OCUPADO => $this->errorDeHora('citas.manage_err_taken'),
            GestionCitas::REINTENTAR => $this->mostrarAviso('citas.manage_err_retry', false),
            default => $this->terminar('citas.manage_err_invalid', false),
        };
    }

    // ── 4. CANCEL ────────────────────────────────────────────────────────

    /**
     * ES: Cancela la cita (GestionCitas comprueba el plazo y el estado).
     * EN: Cancels the booking (GestionCitas checks the notice and the state).
     */
    public function cancelar(): void
    {
        $servicio = app(GestionCitas::class);
        $resultado = $servicio->cancelarPorCliente($this->cita(), $this->token);
        if ($resultado === GestionCitas::OK) {
            $this->seguirConElTokenNuevo($servicio);
        }

        $resultado === GestionCitas::OK
            ? $this->terminar('citas.manage_cancelled_done')
            : $this->terminar('citas.manage_err_invalid', false);
    }

    /**
     * ES: Vuelve a la vista de la cita con un aviso.
     * EN: Goes back to the booking view with a notice.
     */
    protected function terminar(string $clave, bool $ok = true): void
    {
        $this->volver();
        $this->mostrarAviso($clave, $ok);
    }

    /**
     * ES: La hora elegida ya no vale: se quita y se avisa (sigue en reprogramar).
     * EN: The chosen time is no longer valid: cleared and notified (stays in reschedule).
     */
    protected function errorDeHora(string $clave): void
    {
        $this->nuevaHora = '';
        $this->mostrarAviso($clave, false);
    }

    /**
     * ES: Fija el aviso que se muestra (role="status" o role="alert").
     * EN: Sets the notice shown (role="status" or role="alert").
     */
    protected function mostrarAviso(string $clave, bool $ok): void
    {
        $this->aviso = $clave;
        $this->avisoOk = $ok;
    }

    // ── 5. RENDER ────────────────────────────────────────────────────────

    /**
     * ES: Datos para la vista: la cita, si se puede cambiar, el plazo, los
     *     días ofrecidos y las horas libres del día elegido (sin contar la
     *     propia cita) y el contacto del negocio.
     * EN: Data for the view: the booking, whether it can be changed, the
     *     notice deadline, the offered days and the free times of the chosen
     *     day (not counting the booking itself) and the business contact.
     */
    public function render()
    {
        $cita = $this->cita();
        $servicio = app(AvailabilityService::class);
        $locale = app()->getLocale();
        $formatoFecha = $locale === 'en' ? 'dddd, MMMM D' : 'dddd D [de] MMMM';
        $limite = $cita->inicioLocal()->subHours(Appointment::horasPlazoAutogestion());

        return view('livewire.gestionar-cita', [
            'cita' => $cita,
            'puede' => $cita->puedeAutogestionarse(),
            'duracionOfrecida' => $servicio->duracionValida((int) $cita->duration),
            'fechaCita' => $cita->date->locale($locale)->isoFormat($formatoFecha),
            'duracion' => Duracion::etiqueta((int) $cita->duration),
            'limiteFecha' => $limite->locale($locale)->isoFormat($formatoFecha),
            'limiteHora' => $limite->format('H:i'),
            'horas' => Appointment::horasPlazoAutogestion(),
            'dias' => $this->vista === 'reprogramar' ? $servicio->diasDisponibles() : [],
            'huecos' => $this->vista === 'reprogramar' && $this->nuevaFecha !== ''
                ? $servicio->huecosParaDuracion($this->nuevaFecha, (int) $cita->duration, $cita->id)
                : [],
            'nuevaFechaTexto' => $this->nuevaFecha !== ''
                ? Carbon::parse($this->nuevaFecha)->locale($locale)->isoFormat($formatoFecha)
                : '',
        ]);
    }
}
