<?php

namespace App\Livewire;

use App\Http\Middleware\AdminPanelPassword;
use App\Models\Appointment;
use App\Services\AvailabilityService;
use App\Services\GestionCitas;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
|==============================================================================
| PanelNuevaCita — alta manual de una cita en el panel (Livewire)
| PanelNuevaCita — manual booking entry in the panel (Livewire)
|==============================================================================
| ES: Para el cliente que llama por teléfono: nombre obligatorio; correo y
|     teléfono opcionales (sin correo no se envía nada). Día, duración y hora
|     salen de la misma disponibilidad que la reserva pública, y al guardar
|     se vuelve a comprobar con el mismo bloqueo por día (GestionCitas). La
|     cita queda CONFIRMADA. Protegido como el resto del panel: boot() exige
|     una sesión válida del panel en CADA petición.
| EN: For the client who phones: name required; email and phone optional
|     (without an email nothing is sent). Day, duration and time come from
|     the same availability as the public booking, and on save it is
|     re-checked with the same per-day lock (GestionCitas). The booking is
|     CONFIRMED. Protected like the rest of the panel: boot() requires a
|     valid panel session on EVERY request.
|
| INDEX / ÍNDICE
|   1. STATE ........... form fields / campos del formulario
|   2. GUARD ........... panel session on every request / guarda del panel
|   3. OPEN / CLOSE .... show or hide the form / mostrar u ocultar
|   4. SAVE ............ validate + GestionCitas::altaManual / guardar
|   5. RENDER .......... days, durations and free times / datos de la vista
|==============================================================================
*/
class PanelNuevaCita extends Component
{
    // ── 1. STATE ─────────────────────────────────────────────────────────

    // ES: ¿Formulario abierto? (lo fija el servidor). EN: Form open? (set by the server).
    #[Locked]
    public bool $abierto = false;

    // ES: Referencia de la última cita guardada, para el aviso. EN: Last saved booking reference, for the notice.
    #[Locked]
    public ?string $guardada = null;

    public string $nombre = '';

    public string $email = '';

    public string $telefono = '';

    public string $fecha = '';

    public int $duracion = 30;

    public string $hora = '';

    public string $modalidad = 'online';

    public string $idioma = 'es';

    public string $mensaje = '';

    // ── 2. GUARD ─────────────────────────────────────────────────────────

    /**
     * ES: Sin una sesión válida del panel, 403 en cualquier petición.
     * EN: Without a valid panel session, 403 on any request.
     */
    public function boot(): void
    {
        abort_unless(AdminPanelPassword::sesionValida(), 403);
    }

    /**
     * ES: Valores iniciales desde la config (primera duración, modalidad activa, idioma por defecto).
     * EN: Initial values from the config (first duration, enabled modality, default language).
     */
    public function mount(): void
    {
        $this->reiniciar();
    }

    // ── 3. OPEN / CLOSE ──────────────────────────────────────────────────

    /**
     * ES: Muestra el formulario. EN: Shows the form.
     */
    public function abrir(): void
    {
        $this->abierto = true;
        $this->guardada = null;
    }

    /**
     * ES: Oculta el formulario y lo vacía. EN: Hides the form and clears it.
     */
    public function cerrar(): void
    {
        $this->abierto = false;
        $this->reiniciar();
        $this->resetErrorBag();
    }

    /**
     * ES: Al cambiar el día o la duración, la hora elegida deja de valer.
     * EN: When the day or the duration changes, the chosen time no longer applies.
     */
    public function updated(string $propiedad): void
    {
        if (in_array($propiedad, ['fecha', 'duracion'], true)) {
            $this->hora = '';
        }
    }

    // ── 4. SAVE ──────────────────────────────────────────────────────────

    /**
     * ES: Valida y crea la cita confirmada (o explica por qué no se pudo).
     * EN: Validates and creates the confirmed booking (or explains why not).
     */
    public function guardar(): void
    {
        $this->guardada = null;
        $servicio = app(AvailabilityService::class);

        // ES: Mensajes propios y traducidos (el repo no trae lang/*/validation.php).
        // EN: Own translated messages (the repo ships no lang/*/validation.php).
        $this->validate([
            'nombre' => 'required|string|min:2|max:80',
            'email' => 'nullable|email|max:120',
            'telefono' => 'nullable|string|max:30',
            'fecha' => ['required', 'string', fn ($a, $v, $fallo) => $servicio->esFechaReservable((string) $v) ? null : $fallo(__('citas.new_err_day'))],
            'duracion' => ['required', 'integer', fn ($a, $v, $fallo) => $servicio->duracionValida((int) $v) ? null : $fallo(__('citas.new_err_invalid'))],
            'hora' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'modalidad' => ['required', 'in:'.implode(',', $this->modalidades())],
            'idioma' => ['required', 'in:'.implode(',', (array) config('appointments.locales', ['es']))],
            'mensaje' => 'nullable|string|max:1000',
        ], [
            'nombre.*' => __('citas.new_err_name'),
            'email.*' => __('citas.new_err_email'),
            'telefono.*' => __('citas.new_err_phone'),
            'fecha.required' => __('citas.new_err_day'),
            'hora.*' => __('citas.new_err_time'),
            'duracion.*' => __('citas.new_err_invalid'),
            'modalidad.*' => __('citas.new_err_invalid'),
            'idioma.*' => __('citas.new_err_invalid'),
            'mensaje.*' => __('citas.new_err_message'),
        ]);

        $resultado = app(GestionCitas::class)->altaManual([
            'name' => $this->nombre,
            'email' => $this->email,
            'phone' => $this->telefono,
            'date' => $this->fecha,
            'time' => $this->hora,
            'duration' => $this->duracion,
            'modality' => $this->modalidad,
            'locale' => $this->idioma,
            'message' => $this->mensaje,
        ]);

        if (! $resultado instanceof Appointment) {
            $this->hora = '';
            $this->addError('hora', __($resultado === GestionCitas::REINTENTAR ? 'citas.new_err_retry' : 'citas.new_err_slot'));

            return;
        }

        // ES: Guardada: aviso, formulario vacío y la tabla se repinta.
        // EN: Saved: notice, empty form and the table re-renders.
        $this->reiniciar();
        $this->abierto = false;
        $this->guardada = $resultado->reference;
        $this->dispatch('cita-creada');
    }

    /**
     * ES: Vacía el formulario con los valores por defecto de la config.
     * EN: Clears the form with the config defaults.
     */
    protected function reiniciar(): void
    {
        $this->nombre = $this->email = $this->telefono = $this->fecha = $this->hora = $this->mensaje = '';
        $this->duracion = app(AvailabilityService::class)->duraciones()[0] ?? 30;
        $this->modalidad = $this->modalidades()[0] ?? 'online';
        $this->idioma = (string) config('appointments.default_locale', 'es');
    }

    /**
     * ES: Modalidades activas en la config. EN: Modalities enabled in the config.
     *
     * @return array<int, string>
     */
    protected function modalidades(): array
    {
        return array_values(array_filter([
            config('appointments.modalities.online', true) ? 'online' : null,
            config('appointments.modalities.in_person', true) ? 'presencial' : null,
        ]));
    }

    // ── 5. RENDER ────────────────────────────────────────────────────────

    /**
     * ES: Días ofrecidos, duraciones y horas libres del día y duración elegidos.
     * EN: Offered days, durations and free times for the chosen day and duration.
     */
    public function render()
    {
        $servicio = app(AvailabilityService::class);

        return view('livewire.panel-nueva-cita', [
            'dias' => $this->abierto ? $servicio->diasDisponibles() : [],
            'duraciones' => $servicio->duraciones(),
            'huecos' => $this->abierto && $this->fecha !== '' && $servicio->esFechaReservable($this->fecha)
                ? $servicio->huecosParaDuracion($this->fecha, $this->duracion)
                : [],
            'modalidades' => $this->modalidades(),
        ]);
    }
}
