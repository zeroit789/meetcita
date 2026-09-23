<?php

namespace App\Livewire;

use App\Mail\AppointmentConfirmationToClient;
use App\Mail\AppointmentRequestedToOwner;
use App\Models\Appointment;
use App\Services\AvailabilityService;
use App\Services\TelegramNotifier;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

/*
|==============================================================================
| BookAppointment — public 4-step booking wizard (Livewire)
| BookAppointment — wizard público de reserva en 4 pasos (Livewire)
|==============================================================================
| ES: Paso 1 -> elegir DÍA (calendario mensual)
|     Paso 2 -> elegir DURACIÓN + HORA libre para esa duración
|     Paso 3 -> formulario (modalidad, contacto, asistentes, mensaje, honeypot)
|     Paso 4 -> pantalla de confirmación con el código público de la cita
|     Toda la disponibilidad delega en AvailabilityService. Una cita de 60 min
|     ocupa DOS medios-slots de 30 min consecutivos; el servicio ofrece solo
|     inicios válidos y bloquea ambos.
| EN: Step 1 -> pick DAY (monthly calendar)
|     Step 2 -> pick DURATION + free TIME slot for that duration
|     Step 3 -> details form (modality, contact, attendees, message, honeypot)
|     Step 4 -> confirmation screen with the public booking reference
|     All availability logic is delegated to AvailabilityService. A 60-min
|     meeting occupies TWO consecutive 30-min half-slots; the service offers
|     only valid starts and blocks both.
|
| INDEX / ÍNDICE
|   1. STATE ............... nav + selection + form fields / estado y campos
|   2. MOUNT / SERVICE ..... boot + DI resolver / arranque y resolución DI
|   3. CALENDAR ........... month grid + nav / rejilla y navegación de mes
|   4. NAVIGATION ......... selectDate/Time, setDuration/Modality, back
|   5. RESERVE ............ atomic booking + emails + notify / reserva atómica
|   6. RESET / RENDER ..... restart flow + view data / reinicio y datos vista
|==============================================================================
*/
class BookAppointment extends Component
{
    // ── 1. STATE EN / ES ─────────────────────────────────────────────────

    // ES: Paso actual del wizard (1..4; 4 = confirmación final).
    // EN: Current wizard step (1..4; 4 = final confirmation).
    public int $step = 1;

    // ES: Selección del usuario. / EN: User selection.
    public string $selectedDate = '';  // ES: día elegido / EN: chosen day "YYYY-MM-DD"

    public string $selectedTime = '';  // ES: hora de inicio / EN: chosen start "HH:MM"

    public int $duration = 30;         // ES: minutos / EN: minutes

    // ES: Modalidad de la cita: 'online' (Meet) o 'presencial'.
    // EN: Appointment modality: 'online' (Meet) or 'presencial' (in person).
    #[Validate('in:online,presencial')]
    public string $modality = 'online';

    // ── Form fields (with Livewire validation rules) / Campos del formulario ──
    #[Validate('required|string|min:2|max:80')]
    public string $name = '';

    #[Validate('required|email|max:120')]
    public string $email = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    // ES: Asistentes (obligatorio). / EN: People attending the meeting (required).
    #[Validate('required|string|min:2|max:255')]
    public string $attendees = '';

    // ES: Correos extra a invitar (OPCIONAL), separados por comas. Longitud
    //     validada aquí; cada email se valida uno a uno en reserve().
    // EN: Extra attendee emails to invite (OPTIONAL), comma-separated. Length
    //     checked here; each email is validated one by one in reserve().
    #[Validate('nullable|string|max:500')]
    public string $attendeeEmails = '';

    // ES: Qué tratar (obligatorio). / EN: What they want to discuss (required).
    #[Validate('required|string|min:5|max:1000')]
    public string $message = '';

    // ES: Honeypot anti-spam: campo oculto que un humano nunca rellena. Si llega
    //     con contenido, max:0 falla -> lo tratamos como bot.
    // EN: Anti-spam honeypot: a hidden field a human never fills. If it arrives
    //     with content, max:0 fails -> treated as a bot.
    #[Validate('max:0')]
    public string $website = '';

    // ── Confirmed-appointment data (for the final screen) / Datos confirmados ──
    public ?string $confirmedDate = null;

    public ?string $confirmedTime = null;

    public ?int $confirmedDuration = null;

    public ?string $confirmedRef = null;   // ES: código público / EN: public ref

    // ── CALENDAR state (step 1) / Estado del CALENDARIO (paso 1) ──
    // ES: Mes mostrado (1-12) y su año. Se inicializan en mount().
    // EN: Month currently shown (1-12) and its year. Set in mount().
    public int $viewMonth = 0;

    public int $viewYear = 0;

    // ── 2. MOUNT / SERVICE EN / ES ───────────────────────────────────────

    /**
     * ES: Al montar, situamos el calendario en el mes del primer día reservable.
     * EN: On boot, place the calendar on the month of the first bookable day.
     */
    public function mount(): void
    {
        $first = Carbon::parse($this->availability()->rangoReservable()['min']);
        $this->viewMonth = (int) $first->month;
        $this->viewYear = (int) $first->year;

        // ES: Respetamos el contrato de modalidades de config. El valor por
        //     defecto es 'online'; si online está desactivado pero presencial
        //     activo, forzamos 'presencial' para que el valor guardado sea
        //     siempre una modalidad válida y activa.
        // EN: Honour the config modality contract. The default is 'online'; if
        //     online is disabled but in-person is on, force 'presencial' so the
        //     stored value is always a valid, enabled modality.
        if (! config('appointments.modalities.online', true)
            && config('appointments.modalities.in_person', true)) {
            $this->modality = 'presencial';
        }
    }

    /**
     * ES: Resuelve el servicio de disponibilidad desde el contenedor.
     * EN: Resolve the availability service from the container.
     */
    protected function availability(): AvailabilityService
    {
        return app(AvailabilityService::class);
    }

    /**
     * ES: Zona horaria del negocio (config). EN: Business time zone (from config).
     */
    protected function tz(): string
    {
        return config('appointments.timezone', 'Europe/Madrid');
    }

    // ── 3. CALENDAR EN / ES ──────────────────────────────────────────────

    /**
     * ES: Mes anterior (sin pasar del mes del primer día reservable).
     * EN: Go to the previous month (never before the first bookable month).
     */
    public function prevMonth(): void
    {
        // ES: Anclamos el mes visible a la zona del negocio para que la
        //     comparación de meses sea coherente con el resto del módulo.
        // EN: Anchor the visible month to the business tz so month comparisons
        //     stay coherent with the rest of the module.
        $current = Carbon::create($this->viewYear, $this->viewMonth, 1, 0, 0, 0, $this->tz());
        $minMonth = Carbon::parse($this->availability()->rangoReservable()['min'])->startOfMonth();

        if ($current->copy()->subMonth()->gte($minMonth)) {
            $new = $current->subMonth();
            $this->viewMonth = (int) $new->month;
            $this->viewYear = (int) $new->year;
        }
    }

    /**
     * ES: Mes siguiente (sin pasar del mes del último día reservable).
     * EN: Go to the next month (never past the last bookable month).
     */
    public function nextMonth(): void
    {
        $current = Carbon::create($this->viewYear, $this->viewMonth, 1, 0, 0, 0, $this->tz());
        $maxMonth = Carbon::parse($this->availability()->rangoReservable()['max'])->startOfMonth();

        // ES: Solo avanza si el mes siguiente aún tiene días reservables.
        // EN: Only move forward if next month still has bookable days.
        if ($current->copy()->addMonth()->lte($maxMonth)) {
            $new = $current->addMonth();
            $this->viewMonth = (int) $new->month;
            $this->viewYear = (int) $new->year;
        }
    }

    /**
     * ES: Construye la rejilla del mes: matriz de semanas (cada una = 7 celdas
     *     L->D). Cada celda trae los metadatos que la vista necesita.
     * EN: Build the month grid: an array of weeks (each = 7 cells Mon->Sun).
     *     Every cell carries the metadata the view needs to paint it.
     *
     * @return array<int, array<int, array>>
     */
    public function calendarWeeks(): array
    {
        $service = $this->availability();

        // ES: Primer día del mes mostrado + el lunes que inicia su semana.
        // EN: First day of the shown month + the Monday that starts its week.
        $firstDayMonth = Carbon::create($this->viewYear, $this->viewMonth, 1, 0, 0, 0, $this->tz())->startOfDay();
        $start = $firstDayMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endMonth = $firstDayMonth->copy()->endOfMonth();
        $end = $endMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $week = [];
        $cursor = $start->copy();

        // ES: Recorremos día a día desde el lunes inicial hasta el domingo final.
        // EN: Walk day by day from the starting Monday to the final Sunday.
        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $week[] = [
                'date' => $date,
                'day' => (int) $cursor->day,
                'isCurrent' => (int) $cursor->month === $this->viewMonth, // ES: del mes mostrado / EN: shown month
                'isToday' => $cursor->isToday(),
                'isWeekend' => ! $cursor->isWeekday(),
                'reservable' => $service->esFechaReservable($date),         // ES: ¿clicable? / EN: clickable?
                'isSelected' => $this->selectedDate === $date,
            ];

            // ES: Cerramos la semana cada 7. / EN: Close the week every 7 days.
            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
            $cursor->addDay();
        }

        return $weeks;
    }

    /**
     * ES: Etiqueta "junio 2026" del mes mostrado (cabecera del calendario).
     * EN: "June 2026" label of the shown month (calendar header).
     */
    protected function monthLabel(): string
    {
        return Carbon::create($this->viewYear, $this->viewMonth, 1, 0, 0, 0, $this->tz())
            ->locale(app()->getLocale())->isoFormat('MMMM YYYY');
    }

    /**
     * ES: ¿Se puede retroceder de mes? (para deshabilitar la flecha izquierda).
     * EN: Can we go back a month? (to disable the left arrow).
     */
    protected function canPrev(): bool
    {
        $current = Carbon::create($this->viewYear, $this->viewMonth, 1, 0, 0, 0, $this->tz());
        $minMonth = Carbon::parse($this->availability()->rangoReservable()['min'])->startOfMonth();

        return $current->copy()->subMonth()->gte($minMonth);
    }

    /**
     * ES: ¿Se puede avanzar de mes? (para deshabilitar la flecha derecha).
     * EN: Can we go forward a month? (to disable the right arrow).
     */
    protected function canNext(): bool
    {
        $current = Carbon::create($this->viewYear, $this->viewMonth, 1, 0, 0, 0, $this->tz());
        $maxMonth = Carbon::parse($this->availability()->rangoReservable()['max'])->startOfMonth();

        return $current->copy()->addMonth()->lte($maxMonth);
    }

    // ── 4. NAVIGATION EN / ES ────────────────────────────────────────────

    /**
     * ES: Paso 1 -> 2: el usuario elige un día.
     * EN: Step 1 -> 2: the user picks a day.
     */
    public function selectDate(string $date): void
    {
        // ES: Solo fechas reservables. / EN: Only accept bookable dates.
        if (! $this->availability()->esFechaReservable($date)) {
            return;
        }

        $this->selectedDate = $date;
        $this->selectedTime = '';   // ES: resetea hora / EN: reset time
        $this->step = 2;
    }

    /**
     * ES: Paso 2: cambiar duración. Resetea la hora elegida porque los inicios
     *     válidos cambian según la duración.
     * EN: Step 2: change duration. Resets the chosen time because valid starts
     *     change with the duration.
     */
    public function setDuration(int $duration): void
    {
        // ES: Solo duraciones soportadas (las de config, vía el servicio).
        // EN: Only supported durations (the configured ones, via the service).
        if (! in_array($duration, $this->availability()->duraciones(), true)) {
            return;
        }

        $this->duration = $duration;
        $this->selectedTime = '';
    }

    /**
     * ES: Paso 3: elegir la modalidad (online / presencial). No afecta a la
     *     disponibilidad, solo a si se genera enlace de Meet.
     * EN: Step 3: choose the modality (online / presencial). Does not affect
     *     availability, only whether a Meet link is generated.
     */
    public function setModality(string $modality): void
    {
        if (! in_array($modality, ['online', 'presencial'], true)) {
            return;
        }

        $this->modality = $modality;
    }

    /**
     * ES: Paso 2 -> 3: el usuario elige una hora de inicio válida.
     * EN: Step 2 -> 3: the user picks a valid start time for the duration.
     */
    public function selectTime(string $time): void
    {
        // ES: El inicio debe seguir siendo válido para la duración elegida.
        // EN: The start must still be valid for the chosen duration.
        if (! $this->availability()->slotSigueLibre($this->selectedDate, $time, $this->duration)) {
            $this->selectedTime = '';   // ES: justo se ocupó / EN: just got taken

            return;
        }

        $this->selectedTime = $time;
        $this->step = 3;
    }

    /**
     * ES: Vuelve al paso anterior (botón "atrás" del flujo).
     * EN: Go back one step (the flow's "back" button).
     */
    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    // ── 5. RESERVE EN / ES ───────────────────────────────────────────────

    /**
     * ES: Paso 3: crea la cita, dispara los emails y pasa a confirmación.
     * EN: Step 3: create the appointment, fire emails and move to confirmation.
     */
    public function reserve(): void
    {
        // ES: 0) Rate limit por IP: máx 5 reservas/hora desde la misma IP. Aquí
        //     solo COMPROBAMOS el límite; el hit() que consume un intento se
        //     hace más abajo, tras validar, para no gastar intentos en errores
        //     honestos del usuario.
        // EN: 0) Rate limit per IP: max 5 bookings/hour from the same IP. We
        //     only CHECK the limit here; the hit() that consumes an attempt is
        //     done below, after validation, so honest user errors don't burn
        //     attempts.
        $rlKey = 'reserva:'.request()->ip();
        if (RateLimiter::tooManyAttempts($rlKey, 5)) {
            $this->addError('selectedTime', __('citas.err_too_many_attempts'));

            return;
        }

        // ES: 1) Validamos todo (incluido el honeypot 'website').
        // EN: 1) Validate everything (including the 'website' honeypot).
        $this->validate();

        // ES: 1b) Validación EXTRA de correos de asistentes (campo opcional).
        //     Separados por comas; cada uno debe ser válido. Vacío = no hay nada.
        // EN: 1b) EXTRA validation of attendee emails (optional field). They are
        //     comma-separated; each must be a valid email. Empty = nothing to do.
        if (trim($this->attendeeEmails) !== '') {
            $totalEmails = 0; // ES: contador / EN: count of non-empty emails
            foreach (explode(',', $this->attendeeEmails) as $mail) {
                $mail = trim($mail);
                if ($mail === '') {
                    continue; // ES: comas sueltas / EN: skip stray commas
                }
                if (! filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                    $this->addError('attendeeEmails', __('citas.err_email_invalid', ['email' => $mail]));

                    return;
                }
                $totalEmails++;
            }

            // ES: Límite de asistentes (config). / EN: Attendee limit from config.
            $maxAttendees = (int) config('appointments.schedule.max_attendees', 10);
            if ($totalEmails > $maxAttendees) {
                $this->addError('attendeeEmails', __('citas.err_max_attendees', ['max' => $maxAttendees]));

                return;
            }
        }

        // ES: 1c) Consumimos AHORA un intento del rate limit (ventana de 1 hora),
        //     ya pasadas las validaciones y antes de tocar la BD.
        // EN: 1c) Consume a rate-limit attempt NOW (1-hour window), after all
        //     validations and before touching the DB.
        RateLimiter::hit($rlKey, 3600);

        // ES: 2-3) Creamos la cita de forma ATÓMICA (anti-doble-reserva):
        //   - Lock de aplicación por día ('reserva-dia:FECHA'): serializa de
        //     verdad las reservas concurrentes del mismo día (cubre el solape de
        //     1h sobre dos medios-slots que el índice único no detecta solo).
        //   - Dentro del lock: tx, re-verificamos el hueco, solo entonces
        //     creamos. El finally libera el lock pase lo que pase.
        // EN: 2-3) Create the appointment ATOMICALLY (anti-double-booking):
        //   - Take an app lock per day ('reserva-dia:DATE'): it truly serializes
        //     concurrent same-day bookings (covers the 1h-over-two-half-slots
        //     overlap that a partial unique index alone can't detect).
        //   - Inside the lock: open a tx, re-check the slot, only then create.
        //     The finally releases the lock no matter what.
        $lock = Cache::lock('reserva-dia:'.$this->selectedDate, 10);

        // ES: Si en 5s no conseguimos el lock, pedimos reintentar en un momento.
        // EN: If we can't get the lock in 5s, ask the user to retry shortly.
        if (! $lock->block(5)) {
            $this->addError('time', __('citas.err_retry_soon'));

            return;
        }

        // ES: try externo = liberar siempre el lock (finally); try interno =
        //     capturar el choque con el índice único (QueryException).
        // EN: Outer try = always release the lock (finally); inner try =
        //     catch the unique-index clash (QueryException).
        try {
            try {
                $appointment = DB::transaction(function () {
                    // ES: lockForUpdate como segunda barrera (motores que lo
                    //     respetan refuerzan la exclusión dentro de la tx).
                    // EN: lockForUpdate as a second barrier (engines that honour
                    //     it reinforce exclusion within the tx).
                    Appointment::whereDate('date', $this->selectedDate)
                        ->where('status', '!=', 'cancelada')
                        ->lockForUpdate()
                        ->get();

                    // ES: Revalidamos el hueco DENTRO de la transacción (otra reserva pudo entrar antes).
                    // EN: Re-check the slot INSIDE the transaction (another booking may have landed first).
                    if (! $this->availability()->slotSigueLibre($this->selectedDate, $this->selectedTime, $this->duration)) {
                        return null; // ES: el hueco se ocupó / EN: slot got taken
                    }

                    // ES: Hueco libre: creamos la cita (estado 'pendiente' por defecto).
                    // EN: Slot free: create the booking ('pendiente' status by default).
                    return Appointment::create([
                        'name' => $this->name,
                        'email' => $this->email,
                        'phone' => $this->phone ?: null,
                        'date' => $this->selectedDate,
                        'time' => $this->selectedTime,
                        'duration' => $this->duration,
                        'modality' => $this->modality,        // 'online' | 'presencial'
                        'locale' => app()->getLocale(),     // ES: idioma del cliente / EN: client language
                        'attendees' => $this->attendees,
                        'attendee_emails' => trim($this->attendeeEmails) ?: null,
                        'message' => $this->message,
                        'reason' => $this->message,         // ES: columna antigua / EN: legacy column
                    ]);
                });
            } catch (QueryException $e) {
                // ES: El índice único parcial (date, time) saltó: una carrera
                //     real ocupó el hueco a la vez. Avisamos sin romper.
                // EN: The partial unique (date, time) index fired: a real race
                //     took the slot at the same time. Warn without breaking.
                Log::warning('Booking rejected by slot collision (unique index): '.$e->getMessage());
                $this->selectedTime = '';
                $this->step = 2;
                $this->addError('time', __('citas.err_slot_taken'));

                return;
            } catch (\Throwable $e) {
                Log::error('Error creating the appointment: '.$e->getMessage());
                $appointment = null;
            }
        } finally {
            // ES: Siempre liberamos el lock del día para no bloquear otras.
            // EN: Always release the day lock so other bookings aren't blocked.
            $lock->release();
        }

        // ES: Hueco ocupado (o error): volvemos al paso 2 con aviso.
        // EN: Slot taken (or error): back to step 2 with a notice.
        if (! $appointment) {
            $this->selectedTime = '';
            $this->step = 2;
            $this->addError('selectedTime', __('citas.err_slot_taken_step2'));

            return;
        }

        // ES: 4) Emails. La cita YA está creada: si algo falla lo registramos
        //     pero NO rompemos la reserva. El email del dueño viene de config
        //     (sin dirección hardcodeada).
        // EN: 4) Emails. The appointment is ALREADY created: if any of this
        //     fails we log it but DON'T break the booking. The owner email comes
        //     from config (no hardcoded address).
        try {
            Mail::to(config('appointments.brand.owner_email'))
                ->send(new AppointmentRequestedToOwner($appointment));

            // ES: Cliente en To + asistentes extra en Cc: todos reciben el
            //     "solicitud recibida", en el idioma del cliente.
            // EN: Client in To + extra attendees in Cc: everyone gets the
            //     "request received" email, in the client's language.
            Mail::to($appointment->email)
                ->cc($appointment->emailsAsistentesExtra())
                ->locale($appointment->locale)
                ->send(new AppointmentConfirmationToClient($appointment));
        } catch (\Throwable $e) {
            Log::error('Appointment '.$appointment->reference.' created, but the email failed: '.$e->getMessage());
        }

        // ES: 4b) Aviso opcional por Telegram con botones confirmar/rechazar.
        //     Degrada con gracia si Telegram no está configurado.
        // EN: 4b) Optional Telegram heads-up with confirm/reject buttons.
        //     Degrades gracefully if Telegram isn't configured.
        try {
            $this->notifyTelegram($appointment);
        } catch (\Throwable $e) {
            Log::error('Appointment '.$appointment->reference.' created, but Telegram failed: '.$e->getMessage());
        }

        // ES: 5) Guardamos datos para la pantalla de confirmación y avanzamos.
        //     La fecha se formatea en el idioma activo (patrón natural por idioma).
        // EN: 5) Save data for the confirmation screen and advance. The date is
        //     formatted in the active language (natural pattern per locale).
        $this->confirmedDate = app()->getLocale() === 'en'
            ? $appointment->date->locale('en')->isoFormat('dddd, MMMM D')
            : $appointment->date->locale('es')->isoFormat('dddd D [de] MMMM');
        $this->confirmedTime = $appointment->time;
        $this->confirmedDuration = (int) $appointment->duration;
        $this->confirmedRef = $appointment->reference;
        $this->step = 4;
    }

    /**
     * ES: Avisa al dueño por Telegram de una nueva cita, con botones para
     *     confirmarla o rechazarla desde el móvil. El texto es neutro (sin
     *     nombres personales): usa los datos del cliente y la marca de config.
     * EN: Heads-up the owner via Telegram about a new appointment, with buttons
     *     to confirm or reject it from the phone. Text is neutral (no personal
     *     names): it uses the data the client typed plus the config brand.
     */
    protected function notifyTelegram(Appointment $cita): void
    {
        $date = $cita->date->locale(app()->getLocale())->isoFormat('dddd D [de] MMMM');
        $dur = $cita->duration == 60 ? '1h' : $cita->duration.' min';

        // ES: Texto del aviso (HTML). e() escapa lo que escribe el cliente.
        // EN: Notice text (HTML). e() escapes whatever the client typed.
        $text = "🗓 <b>New appointment</b> · <code>{$cita->reference}</code>\n\n"
            .'👤 <b>'.e($cita->name)."</b>\n"
            ."📅 {$date} · <b>{$cita->time}</b> ({$dur})\n"
            .'✉️ '.e($cita->email)."\n"
            .'👥 '.e($cita->attendees)."\n\n"
            .'💬 '.e($cita->message);

        // ES: Botones: confirmar (ac:ID) / rechazar (ar:ID).
        // EN: Buttons: confirm (ac:ID) / reject (ar:ID).
        $buttons = [[
            ['text' => '✅ Confirm', 'callback_data' => "ac:{$cita->id}"],
            ['text' => '❌ Reject',  'callback_data' => "ar:{$cita->id}"],
        ]];

        app(TelegramNotifier::class)->enviar($text, $buttons);
    }

    // ── 6. RESET / RENDER EN / ES ────────────────────────────────────────

    /**
     * ES: Reinicia todo el flujo para pedir otra cita.
     * EN: Restart the whole flow to book another appointment.
     */
    public function resetFlow(): void
    {
        $this->reset([
            'step', 'selectedDate', 'selectedTime', 'duration', 'modality',
            'name', 'email', 'phone', 'attendees', 'attendeeEmails', 'message', 'website',
            'confirmedDate', 'confirmedTime', 'confirmedDuration', 'confirmedRef',
        ]);
        $this->step = 1;
        $this->duration = 30;       // ES: por defecto tras reset / EN: default after reset

        // ES: Modalidad por defecto tras el reset, respetando el contrato de
        //     config (misma regla que mount(): cae a presencial si online está
        //     desactivado).
        // EN: Default modality after reset, honouring the config contract (same
        //     rule as mount(): fall back to in-person if online is disabled).
        $this->modality = config('appointments.modalities.online', true)
            ? 'online'
            : 'presencial';
    }

    /**
     * ES: Render: pasa las semanas del calendario y los inicios válidos para la
     *     duración elegida a la vista.
     * EN: Render: pass the calendar weeks and the valid starts for the chosen
     *     duration to the view.
     */
    public function render()
    {
        $service = $this->availability();

        return view('livewire.book-appointment', [
            // ES: Calendario del paso 1: semanas del mes mostrado + cabecera + flechas.
            // EN: Step 1 calendar: weeks of the shown month + header + arrows.
            'weeks' => $this->calendarWeeks(),
            'monthLabel' => $this->monthLabel(),
            'canPrev' => $this->canPrev(),
            'canNext' => $this->canNext(),
            // ES: Inicios válidos del día elegido para la duración elegida. OJO:
            //     no usar 'slots' como nombre -> en Livewire 4 'slots' es
            //     reservada (SlotProxy) y colisiona. Usamos 'freeSlots'.
            // EN: Valid starts of the chosen day for the chosen duration. NOTE:
            //     don't name it 'slots' — in Livewire 4 'slots' is reserved
            //     (SlotProxy) and collides. We use 'freeSlots'.
            'freeSlots' => $this->selectedDate
                ? $service->huecosParaDuracion($this->selectedDate, $this->duration)
                : [],
        ]);
    }
}
