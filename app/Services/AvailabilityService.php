<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BlockedDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/*
|==============================================================================
| AvailabilityService / Servicio de disponibilidad
|==============================================================================
| ES: Hogar central de TODA la lógica de huecos:
|       - qué días se pueden reservar (próximos N días laborables)
|       - qué franjas existen (continuo apertura→cierre, granularidad base)
|       - qué medios-slots quedan LIBRES un día (todos menos reservados menos pasados)
|       - qué INICIOS válidos hay para una duración (p.ej. 60 min necesita el slot
|         siguiente libre y no salirse del cierre)
|     El componente Livewire solo orquesta; las reglas viven aquí. Todas las
|     constantes de negocio salen de config('appointments.*') para que el paquete
|     sea reutilizable sin tocar código.
| EN: Central home of ALL slot logic:
|       - which days are bookable (next N working days)
|       - which time slots exist (continuous open→close, base slot granularity)
|       - which half-slots are FREE on a day (all minus booked minus past)
|       - which valid STARTS exist for a given duration (e.g. 60 min needs the
|         next half-slot free too and must not run past closing time)
|     The Livewire component only orchestrates; the rules live here.
|     All business constants come from config('appointments.*') so the package
|     is reusable without editing code.
|
| INDEX / ÍNDICE
|   1. CONFIG ACCESSORS .... read schedule/timezone from config / leer config
|   2. CACHES .............. per-instance memoization / memoización por instancia
|   3. DAYS ................ blocked + available days + range / días y rango
|   4. SLOTS ............... all slots, free slots, valid starts / slots y libres
|   5. VALIDATION .......... date/slot checks / validaciones de fecha y hueco
|   6. INTERNAL HELPERS .... occupied slots, next slot / ocupados y siguiente
|==============================================================================
*/
class AvailabilityService
{
    // ── 1. Config accessors — horario y zona desde config ───────────────────

    /**
     * ES: Zona horaria del negocio. "Ahora"/"hoy" DEBEN calcularse en esta zona
     *     local: con la zona de la app (UTC) ofreceríamos huecos pasados de
     *     madrugada. Se lee de config('appointments.timezone').
     * EN: Business time zone. "Now"/"today" MUST be computed in this local zone:
     *     using the app zone (UTC) we could offer already-past slots at night
     *     (UTC runs ahead of e.g. Madrid). Read from config('appointments.timezone').
     */
    protected function zona(): string
    {
        return config('appointments.timezone', 'UTC');
    }

    /**
     * ES: Número de días laborables a ofrecer hacia adelante en el selector.
     * EN: Number of working days to offer ahead in the picker.
     */
    protected function diasAOfrecer(): int
    {
        return (int) config('appointments.schedule.days_ahead', 14);
    }

    /**
     * ES: Duración de cada slot (medio-slot) en minutos.
     * EN: Slot granularity (half-slot) in minutes.
     */
    protected function slotMinutos(): int
    {
        return (int) config('appointments.schedule.slot_minutes', 30);
    }

    /**
     * ES: Hora de apertura (inclusive). Primer inicio posible.
     * EN: Opening time (inclusive). First possible start.
     */
    protected function horaApertura(): string
    {
        return (string) config('appointments.schedule.open', '09:30');
    }

    /**
     * ES: Hora de cierre (EXCLUSIVA). Ninguna cita puede terminar después.
     * EN: Closing time (EXCLUSIVE). No booking may end after this.
     */
    protected function horaCierre(): string
    {
        return (string) config('appointments.schedule.close', '18:00');
    }

    /**
     * ES: Duraciones (en minutos) que puede elegir el cliente. Pública: el
     *     wizard la usa para validar la duración elegida.
     * EN: Durations (minutes) the client can pick. Public: the wizard uses it
     *     to validate the chosen duration.
     *
     * @return array<int, int>
     */
    public function duraciones(): array
    {
        return (array) config('appointments.schedule.durations', [30, 60]);
    }

    /**
     * ES: Días laborables en formato ISO (1 = lun … 7 = dom).
     * EN: Working weekdays in ISO format (1 = Mon … 7 = Sun).
     *
     * @return array<int, int>
     */
    protected function diasLaborables(): array
    {
        return (array) config('appointments.schedule.weekdays', [1, 2, 3, 4, 5]);
    }

    /**
     * ES: Indica si una fecha Carbon cae en un día laborable configurado (ISO).
     *     Sustituye al antiguo isWeekday() hardcoded: filtramos por dayOfWeekIso
     *     contra la lista 'weekdays' de la config.
     * EN: Whether a Carbon date falls on a configured working weekday (ISO).
     *     Replaces the old hardcoded isWeekday(): we filter by dayOfWeekIso
     *     against the configured 'weekdays' list.
     */
    protected function esLaborable(Carbon $dia): bool
    {
        return in_array($dia->dayOfWeekIso, $this->diasLaborables(), true);
    }

    // ── 2. Caches — memoización por instancia ───────────────────────────────

    /**
     * ES: Caché en memoria de los días bloqueados (vacaciones). Se rellena una
     *     vez por instancia para no lanzar una query por cada celda del calendario.
     * EN: In-memory cache of blocked days (holidays). Filled once per instance to
     *     avoid one query per calendar cell.
     *
     * @var array<int, string>|null
     */
    protected ?array $bloqueados = null;

    /**
     * ES: Caché en memoria de los días disponibles (próximos laborables). Se
     *     calcula una vez por instancia: un render del calendario lo invoca decenas
     *     de veces.
     * EN: In-memory cache of available days (next working days). Computed once per
     *     instance: a calendar render calls it dozens of times.
     *
     * @var array<int, array{value:string, label:string, weekday:string, day:string, month:string}>|null
     */
    protected ?array $diasDisponibles = null;

    /**
     * ES: Caché en memoria del rango reservable (min/max). Derivado de
     *     diasDisponibles, memoizado igual.
     * EN: In-memory cache of the bookable range (min/max). Derived from
     *     diasDisponibles, memoized the same way.
     *
     * @var array{min:string, max:string}|null
     */
    protected ?array $rangoReservable = null;

    // ── 3. Days — bloqueados, disponibles y rango ───────────────────────────

    /**
     * ES: Devuelve las fechas bloqueadas ("Y-m-d") marcadas como no operativas.
     *     Cacheado por instancia.
     * EN: Returns the blocked dates ("Y-m-d") marked as non-operational.
     *     Cached per instance.
     *
     * @return array<int, string>
     */
    public function diasBloqueados(): array
    {
        if ($this->bloqueados === null) {
            $this->bloqueados = BlockedDay::query()
                ->pluck('date')
                ->map(fn ($d) => $d->toDateString())
                ->all();
        }

        return $this->bloqueados;
    }

    /**
     * ES: Devuelve los próximos días laborables disponibles para reservar. Empieza
     *     HOY (si es laborable) y avanza saltando días no laborables y bloqueados
     *     hasta reunir days_ahead días.
     * EN: Returns the next available working days to book. Starts TODAY (if it is
     *     a working day) and advances skipping non-working days and blocked days
     *     until it gathers days_ahead days.
     *
     * @return array<int, array{value:string, label:string, weekday:string, day:string, month:string}>
     */
    public function diasDisponibles(): array
    {
        // ES: Memoización por instancia. EN: Per-instance memoization.
        if ($this->diasDisponibles !== null) {
            return $this->diasDisponibles;
        }

        $dias = [];
        // ES: "Hoy" en hora local del negocio. EN: "Today" in local business time (not UTC).
        $cursor = Carbon::today($this->zona());

        // ES: Avanzamos día a día hasta juntar los laborables que queremos ofrecer.
        // EN: Advance day by day until we gather the working days to offer.
        while (count($dias) < $this->diasAOfrecer()) {
            // ES: Día laborable (según config) y no bloqueado.
            // EN: Working weekday (per config) and not a blocked day.
            if ($this->esLaborable($cursor) && ! in_array($cursor->toDateString(), $this->diasBloqueados(), true)) {
                $dias[] = [
                    // ES: Etiquetas que VE EL CLIENTE en el wizard: en el idioma activo.
                    // EN: Labels the CLIENT sees in the wizard: in the active locale.
                    'value' => $cursor->toDateString(),                                      // "2026-06-15"
                    'label' => $cursor->locale(app()->getLocale())->isoFormat('ddd D MMM'),  // "lun 15 jun" / "Mon Jun 15"
                    'weekday' => $cursor->locale(app()->getLocale())->isoFormat('ddd'),        // "lun" / "Mon"
                    'day' => $cursor->format('d'),                                          // "15"
                    'month' => $cursor->locale(app()->getLocale())->isoFormat('MMM'),        // "jun" / "Jun"
                ];
            }
            $cursor->addDay();
        }

        // ES: Guardamos en caché y devolvemos. EN: Cache and return.
        return $this->diasDisponibles = $dias;
    }

    /**
     * ES: Rango de fechas reservables (primera y última) según diasDisponibles().
     *     Lo usa el calendario para saber hasta qué mes navegar y qué días activar.
     * EN: Bookable date range (first and last) per diasDisponibles(). Used by the
     *     calendar to know up to which month to navigate and which days to mark active.
     *
     * @return array{min:string, max:string} ES: fechas "YYYY-MM-DD" · EN: "YYYY-MM-DD" dates
     */
    public function rangoReservable(): array
    {
        // ES: Memoización por instancia (derivado de diasDisponibles).
        // EN: Per-instance memoization (derived from diasDisponibles).
        if ($this->rangoReservable !== null) {
            return $this->rangoReservable;
        }

        $dias = $this->diasDisponibles();

        return $this->rangoReservable = [
            'min' => $dias[0]['value'],
            'max' => $dias[count($dias) - 1]['value'],
        ];
    }

    // ── 4. Slots — todos los slots, libres e inicios válidos ────────────────

    /**
     * ES: Genera TODOS los medios-slots posibles de un día (continuo apertura→cierre).
     *     El cierre es exclusivo: el último medio-slot generado es anterior al cierre.
     * EN: Generates ALL possible half-slots of a day (continuous open→close). The
     *     close time is exclusive: the last generated half-slot is before close.
     *
     * @return array<int, string> ES: lista de horas "HH:MM" · EN: "HH:MM" list
     */
    public function slotsDelDia(): array
    {
        $slots = [];

        // ES: CarbonPeriod genera marcas cada slot_minutos entre apertura y cierre.
        // EN: CarbonPeriod marks every slot_minutes between open and close.
        $periodo = CarbonPeriod::create(
            Carbon::parse($this->horaApertura()),
            $this->slotMinutos().' minutes',
            Carbon::parse($this->horaCierre())
        );

        foreach ($periodo as $marca) {
            // ES: El cierre es exclusivo: no incluimos la hora de cierre como inicio.
            // EN: Close is exclusive: don't include the closing time as a start.
            if ($marca->format('H:i') === $this->horaCierre()) {
                continue;
            }
            $slots[] = $marca->format('H:i');
        }

        return $slots;
    }

    /**
     * ES: Devuelve los medios-slots LIBRES de un día concreto (granularidad 30 min).
     *     Libre = todos los del día MENOS los ocupados (status != cancelada) MENOS
     *     los ya pasados (si el día es hoy). Una cita de 1h ocupa DOS medios-slots,
     *     por eso expandimos cada cita a todos los medios-slots que cubre.
     * EN: Returns the FREE half-slots of a given day (30-min granularity).
     *     Free = all day half-slots MINUS occupied (status != cancelada) MINUS
     *     already-past ones (if the day is today). A 60-min booking occupies TWO
     *     half-slots, so we expand each booking to all the half-slots it covers.
     *
     * @param  string  $fecha  ES: día "YYYY-MM-DD" · EN: day "YYYY-MM-DD"
     * @return array<int, string> ES: medios-slots libres · EN: free half-slots "HH:MM"
     */
    public function huecosLibres(string $fecha): array
    {
        // ES: Defensivo: si la fecha no es válida/reservable, no hay huecos.
        // EN: Defensive: not a valid/bookable date → no slots.
        if (! $this->esFechaReservable($fecha)) {
            return [];
        }

        $todos = $this->slotsDelDia();

        // ES: Medios-slots ocupados ese día (cada cita no cancelada expandida).
        // EN: Occupied half-slots that day (each non-cancelled booking expanded).
        $ocupados = $this->mediosSlotsOcupados($fecha);

        $libres = array_diff($todos, $ocupados);

        // ES: Si la fecha es HOY (local), quitamos las horas ya pasadas. Comparamos
        //     con la hora local actual con cadenas contra hoy en la zona del
        //     negocio (isToday() depende de la zona por defecto de Carbon).
        // EN: If the day is TODAY (local), drop already-past hours. We compare
        //     with the current local time using explicit string comparison
        //     against today in the business zone (isToday() depends on Carbon's
        //     default zone and could be wrong).
        if (Carbon::today($this->zona())->toDateString() === $fecha) {
            $ahora = Carbon::now($this->zona())->format('H:i');
            $libres = array_filter($libres, fn (string $slot) => $slot > $ahora);
        }

        // ES: Reindexamos a una lista limpia. EN: Reindex to a clean list (0..n).
        return array_values($libres);
    }

    /**
     * ES: Devuelve los INICIOS válidos de un día para una duración concreta.
     *       - 30: cualquier medio-slot libre vale.
     *       - 60: el medio-slot debe estar libre Y el siguiente también (sin
     *         pasarse del cierre). Así nunca ofrecemos un inicio de 1h que choque
     *         o termine después del cierre.
     * EN: Returns the valid STARTS of a day for a given duration.
     *       - 30: any free half-slot works.
     *       - 60: the half-slot must be free AND the next half-slot too (without
     *         going past closing). So we never offer a 1h start that clashes with
     *         a following booking or ends after close.
     *
     * @param  string  $fecha  ES: día "YYYY-MM-DD" · EN: day "YYYY-MM-DD"
     * @param  int  $duracion  ES: minutos · EN: minutes
     * @return array<int, string> ES: inicios válidos · EN: valid start times "HH:MM"
     */
    public function huecosParaDuracion(string $fecha, int $duracion): array
    {
        // ES: Normalizamos a una duración soportada. EN: Normalize to a supported duration.
        $duracion = in_array($duracion, $this->duraciones(), true) ? $duracion : 30;

        $libres = $this->huecosLibres($fecha);

        // ES: Para una duración de un slot, todos los huecos libres son inicios válidos.
        // EN: For one slot length, every free slot is a valid start.
        if ($duracion === $this->slotMinutos()) {
            return $libres;
        }

        // ES: Para 1 hora: necesitamos dos medios-slots consecutivos libres.
        // EN: For 1 hour: we need two consecutive free half-slots.
        $libresSet = array_flip($libres);   // ES: mapa para búsqueda O(1) · EN: "HH:MM" => index, O(1) lookup
        $validos = [];

        foreach ($libres as $inicio) {
            $siguiente = $this->slotSiguiente($inicio);   // ES: medio-slot +30 min · EN: +30 min half-slot

            // ES: El siguiente debe existir (no pasarse del cierre) y estar libre.
            // EN: Next must exist (not past close) and be free.
            if ($siguiente !== null && isset($libresSet[$siguiente])) {
                $validos[] = $inicio;
            }
        }

        return $validos;
    }

    // ── 5. Validation — comprobaciones de fecha y hueco ─────────────────────

    /**
     * ES: Indica si una fecha es válida para reservar: laborable (según config),
     *     no pasada y dentro del rango ofertado.
     * EN: Whether a date is valid to book: working day (per config), not past and
     *     within the offered range.
     */
    public function esFechaReservable(string $fecha): bool
    {
        try {
            // ES: Interpretamos la fecha en hora local del negocio para que
            //     "pasado/hoy" se decidan con el día real, no con el de la app (UTC).
            // EN: Parse the date in local business time so "past/today" is decided
            //     by the real business day, not the app's (UTC).
            $dia = Carbon::parse($fecha, $this->zona())->startOfDay();
        } catch (\Exception $e) {
            return false; // ES: fecha mal formada · EN: malformed date
        }

        // ES: No días no laborables. EN: No non-working days.
        if (! $this->esLaborable($dia)) {
            return false;
        }

        // ES: No días pasados: comparamos contra HOY en la zona del negocio.
        // EN: No past days: compare against TODAY in the business zone.
        if ($dia->lt(Carbon::today($this->zona()))) {
            return false;
        }

        // ES: No días bloqueados (vacaciones / marcados no operativos).
        // EN: No blocked days (holidays / marked non-operational).
        if (in_array($dia->toDateString(), $this->diasBloqueados(), true)) {
            return false;
        }

        // ES: Dentro del rango de días que ofrecemos. EN: Within the offered range of days.
        $valores = array_column($this->diasDisponibles(), 'value');

        return in_array($dia->toDateString(), $valores, true);
    }

    /**
     * ES: Comprueba si una cita (fecha + hora de inicio + duración) cabe y está
     *     libre. Se usa justo antes de crear la cita (re-verificación anti
     *     doble-reserva). 30 min: el inicio debe ser válido; 60 min: dos medios libres.
     * EN: Checks whether a booking (date + start time + duration) fits and is free.
     *     Used right before creating the booking (anti double-booking re-check).
     *       - 30 min: the start half-slot must be a valid start.
     *       - 60 min: the start must be a valid 1h start (two free half-slots).
     */
    public function slotSigueLibre(string $fecha, string $hora, int $duracion = 30): bool
    {
        return in_array($hora, $this->huecosParaDuracion($fecha, $duracion), true);
    }

    /**
     * ES: Devuelve los medios-slots que ocupa una cita según su inicio y duración.
     *     Ej.: inicio "10:00" + 60 min → ["10:00","10:30"].
     * EN: Returns the list of half-slots a booking occupies given its start and
     *     duration. E.g. start "10:00" + 60 min → ["10:00","10:30"].
     *
     * @return array<int, string>
     */
    public function slotsCubiertos(string $hora, int $duracion): array
    {
        $duracion = in_array($duracion, $this->duraciones(), true) ? $duracion : 30;
        $numMedios = (int) ($duracion / $this->slotMinutos());   // ES: 30->1, 60->2 · EN: 30->1, 60->2

        $slots = [];
        $cursor = Carbon::parse($hora);

        for ($i = 0; $i < $numMedios; $i++) {
            $slots[] = $cursor->format('H:i');
            $cursor->addMinutes($this->slotMinutos());
        }

        return $slots;
    }

    // ── 6. Internal helpers — ocupados y siguiente ──────────────────────────

    /**
     * ES: Conjunto de medios-slots ocupados un día, expandiendo cada cita NO
     *     cancelada a todos los medios-slots que cubre por su duración. Además
     *     fusiona los ocupados por los eventos del propio Google Calendar para no
     *     ofrecer horas ya pilladas fuera de este módulo. Si Google no está
     *     configurado, eventosOcupados() devuelve [] y no cambia nada.
     * EN: Set of half-slots occupied on a day, expanding each NON-cancelled
     *     booking to all the half-slots it covers by its duration. Also merges
     *     half-slots occupied by the owner's own Google Calendar events so we
     *     don't offer hours already taken outside this module. If Google isn't
     *     configured, eventosOcupados() returns [] and nothing changes.
     *
     * @return array<int, string>
     */
    protected function mediosSlotsOcupados(string $fecha): array
    {
        $citas = Appointment::query()
            ->whereDate('date', $fecha)
            ->where('status', '!=', 'cancelada')
            ->get(['time', 'duration']);

        $ocupados = [];

        foreach ($citas as $cita) {
            // ES: Cada cita marca su inicio y, si es de 1h, también el siguiente.
            // EN: Each booking marks its start and, if 1h, the next half-slot too.
            foreach ($this->slotsCubiertos($cita->time, (int) $cita->duration) as $medio) {
                $ocupados[] = $medio;
            }
        }

        // ES: Fusionamos con los medios-slots ocupados por el propio Google Calendar.
        // EN: Merge with half-slots occupied by the owner's own Google Calendar.
        $ocupadosGoogle = app(GoogleCalendarService::class)->eventosOcupados($fecha);

        // ES: array_unique para no duplicar medios-slots que estén en ambas fuentes.
        // EN: array_unique to avoid duplicating slots present in both sources.
        return array_values(array_unique(array_merge($ocupados, $ocupadosGoogle)));
    }

    /**
     * ES: Medio-slot inmediatamente posterior (+30 min). Devuelve null si ese
     *     siguiente medio-slot caería fuera del horario (en el cierre o después),
     *     porque entonces una cita de 1h no cabría.
     * EN: Half-slot immediately after (+30 min). Returns null if that next
     *     half-slot would fall outside hours (at or after close), because then a
     *     1h booking would not fit.
     */
    protected function slotSiguiente(string $hora): ?string
    {
        $siguiente = Carbon::parse($hora)->addMinutes($this->slotMinutos())->format('H:i');

        // ES: El siguiente medio-slot debe seguir siendo un inicio válido (< cierre).
        // EN: The next half-slot must still be a valid start (< close).
        if ($siguiente >= $this->horaCierre()) {
            return null;
        }

        return $siguiente;
    }
}
