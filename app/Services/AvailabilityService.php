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
| EN: Central home of ALL slot logic:
|       - which days are bookable (next N working days)
|       - which time slots exist (continuous open→close, base slot granularity)
|       - which half-slots are FREE on a day (all minus booked minus past)
|       - which valid STARTS exist for a given duration (e.g. 60 min needs the
|         next half-slot free too and must not run past closing time)
|     The Livewire component only orchestrates; the rules live here.
|     All business constants come from config('appointments.*') so the package
|     is reusable without editing code.
| ES: Hogar central de TODA la lógica de huecos:
|       - qué días se pueden reservar (próximos N días laborables)
|       - qué franjas existen (continuo apertura→cierre, granularidad base)
|       - qué medios-slots quedan LIBRES un día (todos menos reservados menos pasados)
|       - qué INICIOS válidos hay para una duración (p.ej. 60 min necesita el slot
|         siguiente libre y no salirse del cierre)
|     El componente Livewire solo orquesta; las reglas viven aquí. Todas las
|     constantes de negocio salen de config('appointments.*') para que el paquete
|     sea reutilizable sin tocar código.
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
     * EN: Business time zone. "Now"/"today" MUST be computed in this local zone:
     *     using the app zone (UTC) we could offer already-past slots at night
     *     (UTC runs ahead of e.g. Madrid). Read from config('appointments.timezone').
     * ES: Zona horaria del negocio. "Ahora"/"hoy" DEBEN calcularse en esta zona
     *     local: con la zona de la app (UTC) ofreceríamos huecos pasados de
     *     madrugada. Se lee de config('appointments.timezone').
     */
    protected function zona(): string
    {
        return config('appointments.timezone', 'UTC');
    }

    /**
     * EN: Number of working days to offer ahead in the picker.
     * ES: Número de días laborables a ofrecer hacia adelante en el selector.
     */
    protected function diasAOfrecer(): int
    {
        return (int) config('appointments.schedule.days_ahead', 14);
    }

    /**
     * EN: Slot granularity (half-slot) in minutes.
     * ES: Duración de cada slot (medio-slot) en minutos.
     */
    protected function slotMinutos(): int
    {
        return (int) config('appointments.schedule.slot_minutes', 30);
    }

    /**
     * EN: Opening time (inclusive). First possible start.
     * ES: Hora de apertura (inclusive). Primer inicio posible.
     */
    protected function horaApertura(): string
    {
        return (string) config('appointments.schedule.open', '09:30');
    }

    /**
     * EN: Closing time (EXCLUSIVE). No booking may end after this.
     * ES: Hora de cierre (EXCLUSIVA). Ninguna cita puede terminar después.
     */
    protected function horaCierre(): string
    {
        return (string) config('appointments.schedule.close', '18:00');
    }

    /**
     * EN: Durations (minutes) the client can pick.
     * ES: Duraciones (en minutos) que puede elegir el cliente.
     *
     * @return array<int, int>
     */
    protected function duraciones(): array
    {
        return (array) config('appointments.schedule.durations', [30, 60]);
    }

    /**
     * EN: Working weekdays in ISO format (1 = Mon … 7 = Sun).
     * ES: Días laborables en formato ISO (1 = lun … 7 = dom).
     *
     * @return array<int, int>
     */
    protected function diasLaborables(): array
    {
        return (array) config('appointments.schedule.weekdays', [1, 2, 3, 4, 5]);
    }

    /**
     * EN: Whether a Carbon date falls on a configured working weekday (ISO).
     *     Replaces the old hardcoded isWeekday(): we filter by dayOfWeekIso
     *     against the configured 'weekdays' list.
     * ES: Indica si una fecha Carbon cae en un día laborable configurado (ISO).
     *     Sustituye al antiguo isWeekday() hardcoded: filtramos por dayOfWeekIso
     *     contra la lista 'weekdays' de la config.
     */
    protected function esLaborable(Carbon $dia): bool
    {
        return in_array($dia->dayOfWeekIso, $this->diasLaborables(), true);
    }

    // ── 2. Caches — memoización por instancia ───────────────────────────────

    /**
     * EN: In-memory cache of blocked days (holidays). Filled once per instance to
     *     avoid one query per calendar cell.
     * ES: Caché en memoria de los días bloqueados (vacaciones). Se rellena una
     *     vez por instancia para no lanzar una query por cada celda del calendario.
     *
     * @var array<int, string>|null
     */
    protected ?array $bloqueados = null;

    /**
     * EN: In-memory cache of available days (next working days). Computed once per
     *     instance: a calendar render calls it dozens of times.
     * ES: Caché en memoria de los días disponibles (próximos laborables). Se
     *     calcula una vez por instancia: un render del calendario lo invoca decenas
     *     de veces.
     *
     * @var array<int, array{value:string, label:string, weekday:string, day:string, month:string}>|null
     */
    protected ?array $diasDisponibles = null;

    /**
     * EN: In-memory cache of the bookable range (min/max). Derived from
     *     diasDisponibles, memoized the same way.
     * ES: Caché en memoria del rango reservable (min/max). Derivado de
     *     diasDisponibles, memoizado igual.
     *
     * @var array{min:string, max:string}|null
     */
    protected ?array $rangoReservable = null;

    // ── 3. Days — bloqueados, disponibles y rango ───────────────────────────

    /**
     * EN: Returns the blocked dates ("Y-m-d") marked as non-operational.
     *     Cached per instance.
     * ES: Devuelve las fechas bloqueadas ("Y-m-d") marcadas como no operativas.
     *     Cacheado por instancia.
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
     * EN: Returns the next available working days to book. Starts TODAY (if it is
     *     a working day) and advances skipping non-working days and blocked days
     *     until it gathers days_ahead days.
     * ES: Devuelve los próximos días laborables disponibles para reservar. Empieza
     *     HOY (si es laborable) y avanza saltando días no laborables y bloqueados
     *     hasta reunir days_ahead días.
     *
     * @return array<int, array{value:string, label:string, weekday:string, day:string, month:string}>
     */
    public function diasDisponibles(): array
    {
        // EN: Per-instance memoization. ES: Memoización por instancia.
        if ($this->diasDisponibles !== null) {
            return $this->diasDisponibles;
        }

        $dias = [];
        // EN: "Today" in local business time (not UTC). ES: "Hoy" en hora local del negocio.
        $cursor = Carbon::today($this->zona());

        // EN: Advance day by day until we gather the working days to offer.
        // ES: Avanzamos día a día hasta juntar los laborables que queremos ofrecer.
        while (count($dias) < $this->diasAOfrecer()) {
            // EN: Working weekday (per config) and not a blocked day.
            // ES: Día laborable (según config) y no bloqueado.
            if ($this->esLaborable($cursor) && ! in_array($cursor->toDateString(), $this->diasBloqueados(), true)) {
                $dias[] = [
                    // EN: Labels the CLIENT sees in the wizard: in the active locale.
                    // ES: Etiquetas que VE EL CLIENTE en el wizard: en el idioma activo.
                    'value'   => $cursor->toDateString(),                                      // "2026-06-15"
                    'label'   => $cursor->locale(app()->getLocale())->isoFormat('ddd D MMM'),  // "lun 15 jun" / "Mon Jun 15"
                    'weekday' => $cursor->locale(app()->getLocale())->isoFormat('ddd'),        // "lun" / "Mon"
                    'day'     => $cursor->format('d'),                                          // "15"
                    'month'   => $cursor->locale(app()->getLocale())->isoFormat('MMM'),        // "jun" / "Jun"
                ];
            }
            $cursor->addDay();
        }

        // EN: Cache and return. ES: Guardamos en caché y devolvemos.
        return $this->diasDisponibles = $dias;
    }

    /**
     * EN: Bookable date range (first and last) per diasDisponibles(). Used by the
     *     calendar to know up to which month to navigate and which days to mark active.
     * ES: Rango de fechas reservables (primera y última) según diasDisponibles().
     *     Lo usa el calendario para saber hasta qué mes navegar y qué días activar.
     *
     * @return array{min:string, max:string}  EN: "YYYY-MM-DD" dates · ES: fechas "YYYY-MM-DD"
     */
    public function rangoReservable(): array
    {
        // EN: Per-instance memoization (derived from diasDisponibles).
        // ES: Memoización por instancia (derivado de diasDisponibles).
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
     * EN: Generates ALL possible half-slots of a day (continuous open→close). The
     *     close time is exclusive: the last generated half-slot is before close.
     * ES: Genera TODOS los medios-slots posibles de un día (continuo apertura→cierre).
     *     El cierre es exclusivo: el último medio-slot generado es anterior al cierre.
     *
     * @return array<int, string> EN: "HH:MM" list · ES: lista de horas "HH:MM"
     */
    public function slotsDelDia(): array
    {
        $slots = [];

        // EN: CarbonPeriod marks every slot_minutes between open and close.
        // ES: CarbonPeriod genera marcas cada slot_minutos entre apertura y cierre.
        $periodo = CarbonPeriod::create(
            Carbon::parse($this->horaApertura()),
            $this->slotMinutos() . ' minutes',
            Carbon::parse($this->horaCierre())
        );

        foreach ($periodo as $marca) {
            // EN: Close is exclusive: don't include the closing time as a start.
            // ES: El cierre es exclusivo: no incluimos la hora de cierre como inicio.
            if ($marca->format('H:i') === $this->horaCierre()) {
                continue;
            }
            $slots[] = $marca->format('H:i');
        }

        return $slots;
    }

    /**
     * EN: Returns the FREE half-slots of a given day (30-min granularity).
     *     Free = all day half-slots MINUS occupied (status != cancelada) MINUS
     *     already-past ones (if the day is today). A 60-min booking occupies TWO
     *     half-slots, so we expand each booking to all the half-slots it covers.
     * ES: Devuelve los medios-slots LIBRES de un día concreto (granularidad 30 min).
     *     Libre = todos los del día MENOS los ocupados (status != cancelada) MENOS
     *     los ya pasados (si el día es hoy). Una cita de 1h ocupa DOS medios-slots,
     *     por eso expandimos cada cita a todos los medios-slots que cubre.
     *
     * @param  string $fecha  EN: day "YYYY-MM-DD" · ES: día "YYYY-MM-DD"
     * @return array<int, string>  EN: free half-slots "HH:MM" · ES: medios-slots libres
     */
    public function huecosLibres(string $fecha): array
    {
        // EN: Defensive: not a valid/bookable date → no slots.
        // ES: Defensivo: si la fecha no es válida/reservable, no hay huecos.
        if (! $this->esFechaReservable($fecha)) {
            return [];
        }

        $todos = $this->slotsDelDia();

        // EN: Occupied half-slots that day (each non-cancelled booking expanded).
        // ES: Medios-slots ocupados ese día (cada cita no cancelada expandida).
        $ocupados = $this->mediosSlotsOcupados($fecha);

        $libres = array_diff($todos, $ocupados);

        // EN: If the day is TODAY (local), drop already-past hours. We compare
        //     with the current local time using explicit string comparison
        //     against today in the business zone (isToday() depends on Carbon's
        //     default zone and could be wrong).
        // ES: Si la fecha es HOY (local), quitamos las horas ya pasadas. Comparamos
        //     con la hora local actual con cadenas contra hoy en la zona del
        //     negocio (isToday() depende de la zona por defecto de Carbon).
        if (Carbon::today($this->zona())->toDateString() === $fecha) {
            $ahora = Carbon::now($this->zona())->format('H:i');
            $libres = array_filter($libres, fn (string $slot) => $slot > $ahora);
        }

        // EN: Reindex to a clean list (0..n). ES: Reindexamos a una lista limpia.
        return array_values($libres);
    }

    /**
     * EN: Returns the valid STARTS of a day for a given duration.
     *       - 30: any free half-slot works.
     *       - 60: the half-slot must be free AND the next half-slot too (without
     *         going past closing). So we never offer a 1h start that clashes with
     *         a following booking or ends after close.
     * ES: Devuelve los INICIOS válidos de un día para una duración concreta.
     *       - 30: cualquier medio-slot libre vale.
     *       - 60: el medio-slot debe estar libre Y el siguiente también (sin
     *         pasarse del cierre). Así nunca ofrecemos un inicio de 1h que choque
     *         o termine después del cierre.
     *
     * @param  string $fecha    EN: day "YYYY-MM-DD" · ES: día "YYYY-MM-DD"
     * @param  int    $duracion EN: minutes · ES: minutos
     * @return array<int, string> EN: valid start times "HH:MM" · ES: inicios válidos
     */
    public function huecosParaDuracion(string $fecha, int $duracion): array
    {
        // EN: Normalize to a supported duration. ES: Normalizamos a una duración soportada.
        $duracion = in_array($duracion, $this->duraciones(), true) ? $duracion : 30;

        $libres = $this->huecosLibres($fecha);

        // EN: For one slot length, every free slot is a valid start.
        // ES: Para una duración de un slot, todos los huecos libres son inicios válidos.
        if ($duracion === $this->slotMinutos()) {
            return $libres;
        }

        // EN: For 1 hour: we need two consecutive free half-slots.
        // ES: Para 1 hora: necesitamos dos medios-slots consecutivos libres.
        $libresSet = array_flip($libres);   // EN: "HH:MM" => index, O(1) lookup · ES: mapa para búsqueda O(1)
        $validos = [];

        foreach ($libres as $inicio) {
            $siguiente = $this->slotSiguiente($inicio);   // EN: +30 min half-slot · ES: medio-slot +30 min

            // EN: Next must exist (not past close) and be free.
            // ES: El siguiente debe existir (no pasarse del cierre) y estar libre.
            if ($siguiente !== null && isset($libresSet[$siguiente])) {
                $validos[] = $inicio;
            }
        }

        return $validos;
    }

    // ── 5. Validation — comprobaciones de fecha y hueco ─────────────────────

    /**
     * EN: Whether a date is valid to book: working day (per config), not past and
     *     within the offered range.
     * ES: Indica si una fecha es válida para reservar: laborable (según config),
     *     no pasada y dentro del rango ofertado.
     */
    public function esFechaReservable(string $fecha): bool
    {
        try {
            // EN: Parse the date in local business time so "past/today" is decided
            //     by the real business day, not the app's (UTC).
            // ES: Interpretamos la fecha en hora local del negocio para que
            //     "pasado/hoy" se decidan con el día real, no con el de la app (UTC).
            $dia = Carbon::parse($fecha, $this->zona())->startOfDay();
        } catch (\Exception $e) {
            return false; // EN: malformed date · ES: fecha mal formada
        }

        // EN: No non-working days. ES: No días no laborables.
        if (! $this->esLaborable($dia)) {
            return false;
        }

        // EN: No past days: compare against TODAY in the business zone.
        // ES: No días pasados: comparamos contra HOY en la zona del negocio.
        if ($dia->lt(Carbon::today($this->zona()))) {
            return false;
        }

        // EN: No blocked days (holidays / marked non-operational).
        // ES: No días bloqueados (vacaciones / marcados no operativos).
        if (in_array($dia->toDateString(), $this->diasBloqueados(), true)) {
            return false;
        }

        // EN: Within the offered range of days. ES: Dentro del rango de días que ofrecemos.
        $valores = array_column($this->diasDisponibles(), 'value');

        return in_array($dia->toDateString(), $valores, true);
    }

    /**
     * EN: Checks whether a booking (date + start time + duration) fits and is free.
     *     Used right before creating the booking (anti double-booking re-check).
     *       - 30 min: the start half-slot must be a valid start.
     *       - 60 min: the start must be a valid 1h start (two free half-slots).
     * ES: Comprueba si una cita (fecha + hora de inicio + duración) cabe y está
     *     libre. Se usa justo antes de crear la cita (re-verificación anti
     *     doble-reserva). 30 min: el inicio debe ser válido; 60 min: dos medios libres.
     */
    public function slotSigueLibre(string $fecha, string $hora, int $duracion = 30): bool
    {
        return in_array($hora, $this->huecosParaDuracion($fecha, $duracion), true);
    }

    /**
     * EN: Returns the list of half-slots a booking occupies given its start and
     *     duration. E.g. start "10:00" + 60 min → ["10:00","10:30"].
     * ES: Devuelve los medios-slots que ocupa una cita según su inicio y duración.
     *     Ej.: inicio "10:00" + 60 min → ["10:00","10:30"].
     *
     * @return array<int, string>
     */
    public function slotsCubiertos(string $hora, int $duracion): array
    {
        $duracion = in_array($duracion, $this->duraciones(), true) ? $duracion : 30;
        $numMedios = (int) ($duracion / $this->slotMinutos());   // EN: 30->1, 60->2 · ES: 30->1, 60->2

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
     * EN: Set of half-slots occupied on a day, expanding each NON-cancelled
     *     booking to all the half-slots it covers by its duration. Also merges
     *     half-slots occupied by the owner's own Google Calendar events so we
     *     don't offer hours already taken outside this module. If Google isn't
     *     configured, eventosOcupados() returns [] and nothing changes.
     * ES: Conjunto de medios-slots ocupados un día, expandiendo cada cita NO
     *     cancelada a todos los medios-slots que cubre por su duración. Además
     *     fusiona los ocupados por los eventos del propio Google Calendar para no
     *     ofrecer horas ya pilladas fuera de este módulo. Si Google no está
     *     configurado, eventosOcupados() devuelve [] y no cambia nada.
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
            // EN: Each booking marks its start and, if 1h, the next half-slot too.
            // ES: Cada cita marca su inicio y, si es de 1h, también el siguiente.
            foreach ($this->slotsCubiertos($cita->time, (int) $cita->duration) as $medio) {
                $ocupados[] = $medio;
            }
        }

        // EN: Merge with half-slots occupied by the owner's own Google Calendar.
        // ES: Fusionamos con los medios-slots ocupados por el propio Google Calendar.
        $ocupadosGoogle = app(\App\Services\GoogleCalendarService::class)->eventosOcupados($fecha);

        // EN: array_unique to avoid duplicating slots present in both sources.
        // ES: array_unique para no duplicar medios-slots que estén en ambas fuentes.
        return array_values(array_unique(array_merge($ocupados, $ocupadosGoogle)));
    }

    /**
     * EN: Half-slot immediately after (+30 min). Returns null if that next
     *     half-slot would fall outside hours (at or after close), because then a
     *     1h booking would not fit.
     * ES: Medio-slot inmediatamente posterior (+30 min). Devuelve null si ese
     *     siguiente medio-slot caería fuera del horario (en el cierre o después),
     *     porque entonces una cita de 1h no cabría.
     */
    protected function slotSiguiente(string $hora): ?string
    {
        $siguiente = Carbon::parse($hora)->addMinutes($this->slotMinutos())->format('H:i');

        // EN: The next half-slot must still be a valid start (< close).
        // ES: El siguiente medio-slot debe seguir siendo un inicio válido (< cierre).
        if ($siguiente >= $this->horaCierre()) {
            return null;
        }

        return $siguiente;
    }
}
