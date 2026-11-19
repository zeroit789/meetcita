<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BlockedDay;
use App\Support\Horario;
use Carbon\Carbon;

/*
|==============================================================================
| AvailabilityService / Servicio de disponibilidad
|==============================================================================
| ES: Hogar central de TODA la lógica de huecos:
|       - qué días se pueden reservar (próximos N días laborables)
|       - qué franjas existen (tramos de cada día: continuo apertura→cierre o
|         el horario partido/por día de schedule.hours, granularidad base)
|       - qué medios-slots quedan LIBRES un día (todos menos reservados menos pasados)
|       - qué INICIOS válidos hay para una duración (p.ej. 60 min necesita el slot
|         siguiente libre y no salirse del cierre)
|     El componente Livewire solo orquesta; las reglas viven aquí. Todas las
|     constantes de negocio salen de config('appointments.*') para que el paquete
|     sea reutilizable sin tocar código.
| EN: Central home of ALL slot logic:
|       - which days are bookable (next N working days)
|       - which time slots exist (each day's ranges: continuous open→close or
|         the split/per-day schedule.hours, base slot granularity)
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
        return max(1, (int) config('appointments.schedule.slot_minutes', 30));
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
        // ES: Solo enteros positivos: una duración 0 o negativa en la config se ignora.
        // EN: Positive integers only: a 0 or negative duration in the config is ignored.
        return array_values(array_filter(
            array_map('intval', (array) config('appointments.schedule.durations', [30, 60])),
            fn (int $d) => $d > 0
        ));
    }

    /**
     * ES: ¿Es una de las duraciones configuradas? Cualquier otra se rechaza
     *     (antes se convertía en 30 y una cita de 240 min solo bloqueaba media hora).
     * EN: Is it one of the configured durations? Anything else is rejected
     *     (it used to become 30, so a 240-min booking only blocked half an hour).
     */
    public function duracionValida(int $duracion): bool
    {
        return in_array($duracion, $this->duraciones(), true);
    }

    /**
     * ES: Días laborables del atajo, en formato ISO (1 = lun … 7 = dom). Solo
     *     cuenta si schedule.hours está vacío.
     * EN: Working weekdays of the shortcut, ISO format (1 = Mon … 7 = Sun).
     *     Only used when schedule.hours is empty.
     *
     * @return array<int, int>
     */
    protected function diasLaborables(): array
    {
        return array_map('intval', (array) config('appointments.schedule.weekdays', [1, 2, 3, 4, 5]));
    }

    /**
     * ES: Horario por día de la semana ya limpio (schedule.hours). Vacío = se
     *     usa el atajo open/close + weekdays.
     * EN: Cleaned per-weekday schedule (schedule.hours). Empty = the
     *     open/close + weekdays shortcut is used.
     *
     * @return array<int, array<int, array{0:string, 1:string}>>
     */
    protected function horasPorDia(): array
    {
        return Horario::normalizar(config('appointments.schedule.hours', []));
    }

    /**
     * ES: Tramos abiertos de un día de la semana ISO: [["09:00","14:00"], …].
     *     Con schedule.hours, los de ese día (ninguno = cerrado); sin él, el
     *     tramo único apertura→cierre si el día está en weekdays.
     * EN: Open ranges of an ISO weekday: [["09:00","14:00"], …]. With
     *     schedule.hours, that day's ranges (none = closed); without it, the
     *     single open→close range if the day is in weekdays.
     *
     * @return array<int, array{0:string, 1:string}>
     */
    public function tramosDelDiaSemana(int $iso): array
    {
        $horas = $this->horasPorDia();
        if ($horas !== []) {
            return $horas[$iso] ?? [];
        }

        return in_array($iso, $this->diasLaborables(), true)
            ? [[$this->horaApertura(), $this->horaCierre()]]
            : [];
    }

    /**
     * ES: Tramos abiertos de una fecha "YYYY-MM-DD" (según su día de la semana).
     * EN: Open ranges of a "YYYY-MM-DD" date (by its weekday).
     *
     * @return array<int, array{0:string, 1:string}>
     */
    public function tramosDelDia(string $fecha): array
    {
        try {
            $dia = Carbon::createFromFormat('!Y-m-d', $fecha, $this->zona());
        } catch (\Exception) {
            return [];
        }

        return $dia ? $this->tramosDelDiaSemana($dia->dayOfWeekIso) : [];
    }

    /**
     * ES: Un día es laborable si tiene algún tramo abierto (sustituye al antiguo
     *     isWeekday() fijo; con horario por día manda schedule.hours).
     * EN: A day is a working day if it has any open range (replaces the old
     *     hardcoded isWeekday(); with a per-day schedule, schedule.hours rules).
     */
    protected function esLaborable(Carbon $dia): bool
    {
        return $this->tramosDelDiaSemana($dia->dayOfWeekIso) !== [];
    }

    /**
     * ES: Texto del horario para la página, sacado de la config (antes estaba
     *     escrito a mano en las traducciones, F5). Agrupa los días que tienen el
     *     mismo horario: "lunes a jueves · 09:00–14:00 y 16:00–19:00; viernes ·
     *     09:00–14:00". Con el atajo sale igual que siempre: "lunes a viernes ·
     *     09:30–18:00".
     * EN: Schedule text for the page, built from the config (it used to be
     *     hand-written in the translations, F5). Groups days sharing the same
     *     hours: "Monday to Thursday · 09:00–14:00 and 16:00–19:00; Friday ·
     *     09:00–14:00". With the shortcut it reads as before: "Monday to
     *     Friday · 09:30–18:00".
     */
    public function textoHorario(): string
    {
        $nombre = fn (int $iso) => Carbon::create(2024, 1, $iso)->locale(app()->getLocale())->isoFormat('dddd');

        // ES: Días agrupados por su horario (clave = tramos), en orden lun→dom.
        // EN: Days grouped by their hours (key = ranges), in Mon→Sun order.
        $grupos = [];
        for ($iso = 1; $iso <= 7; $iso++) {
            $tramos = $this->tramosDelDiaSemana($iso);
            if ($tramos === []) {
                continue;
            }
            $clave = json_encode($tramos);
            $grupos[$clave]['tramos'] = $tramos;
            $grupos[$clave]['dias'][] = $iso;
        }

        $textos = [];
        foreach ($grupos as $grupo) {
            $dias = $grupo['dias'];

            // ES: Días seguidos (L-V) como rango; si no, lista.
            // EN: Consecutive days as a range; otherwise a list.
            $seguidos = count($dias) > 2 && $dias === range($dias[0], $dias[count($dias) - 1]);
            $textoDias = $seguidos
                ? __('citas.cal_days_range', ['from' => $nombre($dias[0]), 'to' => $nombre($dias[count($dias) - 1])])
                : implode(__('citas.cal_days_join'), array_map($nombre, $dias));

            // ES: Tramos del grupo: "09:00–14:00 y 16:00–19:00". EN: Group ranges.
            $textos[] = __('citas.cal_hours_value', [
                'days' => $textoDias,
                'hours' => implode(__('citas.cal_ranges_join'), array_map(
                    fn (array $t) => $t[0].'–'.$t[1],
                    $grupo['tramos']
                )),
            ]);
        }

        return implode(__('citas.cal_groups_join'), $textos);
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

        // ES: Avanzamos día a día hasta juntar los laborables que queremos
        //     ofrecer. Tope de dos años: con un horario sin ningún día abierto
        //     (o todo bloqueado) el bucle no terminaría nunca.
        // EN: Advance day by day until we gather the working days to offer.
        //     Two-year cap: with a schedule that has no open day (or everything
        //     blocked) the loop would never end.
        $limite = Carbon::today($this->zona())->addYears(2);
        while (count($dias) < $this->diasAOfrecer() && $cursor->lte($limite)) {
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

        // ES: Sin ningún día abierto, el rango es solo hoy (nada reservable).
        // EN: With no open day at all, the range is just today (nothing bookable).
        if ($dias === []) {
            $hoy = Carbon::today($this->zona())->toDateString();

            return $this->rangoReservable = ['min' => $hoy, 'max' => $hoy];
        }

        return $this->rangoReservable = [
            'min' => $dias[0]['value'],
            'max' => $dias[count($dias) - 1]['value'],
        ];
    }

    // ── 4. Slots — todos los slots, libres e inicios válidos ────────────────

    /**
     * ES: Genera TODOS los medios-slots posibles de un día: los de cada uno de
     *     sus tramos (apertura→cierre, cierre exclusivo). Sin fecha usa el
     *     tramo del atajo open/close.
     * EN: Generates ALL possible half-slots of a day: those of each of its
     *     ranges (open→close, close exclusive). Without a date it uses the
     *     open/close shortcut range.
     *
     * @param  string|null  $fecha  ES: día "YYYY-MM-DD" · EN: day "YYYY-MM-DD"
     * @return array<int, string> ES: lista de horas "HH:MM" · EN: "HH:MM" list
     */
    public function slotsDelDia(?string $fecha = null): array
    {
        $tramos = $fecha === null
            ? [[$this->horaApertura(), $this->horaCierre()]]
            : $this->tramosDelDia($fecha);

        $slots = [];
        foreach ($tramos as [$abre, $cierra]) {
            // ES: Marcas cada slot_minutos desde la apertura; el cierre no es inicio.
            // EN: Marks every slot_minutes from opening; closing is not a start.
            for ($m = $this->minutos($abre); $m < $this->minutos($cierra); $m += $this->slotMinutos()) {
                $slots[] = $this->hhmm($m);
            }
        }

        return array_values(array_unique($slots));
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
     * @param  int|null  $excluirId  ES: cita que no cuenta como ocupada (reprogramar)
     *                               EN: booking not counted as busy (rescheduling)
     * @return array<int, string> ES: medios-slots libres · EN: free half-slots "HH:MM"
     */
    public function huecosLibres(string $fecha, ?int $excluirId = null): array
    {
        // ES: Defensivo: si la fecha no es válida/reservable, no hay huecos.
        // EN: Defensive: not a valid/bookable date → no slots.
        if (! $this->esFechaReservable($fecha)) {
            return [];
        }

        $todos = $this->slotsDelDia($fecha);

        // ES: Medios-slots ocupados ese día (cada cita no cancelada expandida).
        // EN: Occupied half-slots that day (each non-cancelled booking expanded).
        $ocupados = $this->mediosSlotsOcupados($fecha, $excluirId);

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
     * ES: Devuelve los INICIOS válidos de un día para una duración concreta:
     *     todos los medios-slots que cubre la cita deben estar libres y la cita
     *     debe terminar como muy tarde a la hora de cierre. Sirve para cualquier
     *     duración múltiplo del hueco (30, 60, 90, 120…). Una duración que no
     *     esté en la config no tiene ningún inicio válido.
     * EN: Returns the valid STARTS of a day for a given duration: every
     *     half-slot the booking covers must be free and the booking must end at
     *     closing time at the latest. Works for any duration that is a multiple
     *     of the slot (30, 60, 90, 120…). A duration not in the config has no
     *     valid start at all.
     *
     * @param  string  $fecha  ES: día "YYYY-MM-DD" · EN: day "YYYY-MM-DD"
     * @param  int  $duracion  ES: minutos · EN: minutes
     * @param  int|null  $excluirId  ES: cita que no cuenta como ocupada (reprogramar)
     *                               EN: booking not counted as busy (rescheduling)
     * @return array<int, string> ES: inicios válidos · EN: valid start times "HH:MM"
     */
    public function huecosParaDuracion(string $fecha, int $duracion, ?int $excluirId = null): array
    {
        // ES: Duración desconocida: nada que ofrecer. EN: Unknown duration: nothing to offer.
        if (! $this->duracionValida($duracion)) {
            return [];
        }

        $libres = $this->huecosLibres($fecha, $excluirId);
        $libresSet = array_flip($libres);   // ES: búsqueda O(1) · EN: O(1) lookup
        $tramos = $this->tramosDelDia($fecha);
        $validos = [];

        foreach ($libres as $inicio) {
            // ES: La cita no puede terminar después del cierre de SU tramo (no
            //     cruza el descanso de un horario partido). En minutos desde
            //     medianoche: comparar "HH:MM" como texto fallaría pasadas las 24:00.
            // EN: The booking can't end after the close of ITS range (it never
            //     crosses the break of a split schedule). In minutes since
            //     midnight: comparing "HH:MM" as text would fail past 24:00.
            $cierre = $this->cierreDelTramo($tramos, $inicio);
            if ($cierre === null || $this->minutos($inicio) + $duracion > $cierre) {
                continue;
            }

            // ES: Todos los medios-slots que cubre deben estar libres.
            // EN: Every half-slot it covers must be free.
            $todosLibres = true;
            foreach ($this->slotsCubiertos($inicio, $duracion) as $medio) {
                if (! isset($libresSet[$medio])) {
                    $todosLibres = false;
                    break;
                }
            }

            if ($todosLibres) {
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
            $dia = Carbon::createFromFormat('!Y-m-d', $fecha, $this->zona());
        } catch (\Exception $e) {
            return false; // ES: fecha mal formada · EN: malformed date
        }

        // ES: Solo cuenta el día escrito EXACTO ("YYYY-MM-DD"). Con una cadena
        //     laxa (" 2026-11-06", "2026-9-6" o "2031-02-31", que desborda a
        //     marzo) whereDate no vería las citas de ese día y el bloqueo por día
        //     cambiaría de clave: era una vía a la doble reserva (F3).
        // EN: Only the EXACT written day ("YYYY-MM-DD") counts. With a loose
        //     string (" 2026-11-06", "2026-9-6" or "2031-02-31", which overflows
        //     into March) whereDate wouldn't see that day's bookings and the
        //     per-day lock would change key: a path to double booking (F3).
        if ($dia === false || $dia->format('Y-m-d') !== $fecha) {
            return false;
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
     *     ES: $excluirId deja fuera una cita (la que se reprograma).
     *     EN: $excluirId leaves one booking out (the one being rescheduled).
     */
    public function slotSigueLibre(string $fecha, string $hora, int $duracion = 30, ?int $excluirId = null): bool
    {
        return in_array($hora, $this->huecosParaDuracion($fecha, $duracion, $excluirId), true);
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
        // ES: Duración REAL de la cita (aunque ya no esté en la config): una cita
        //     de 60 min sigue ocupando 60 si el dueño quita esa duración.
        //     Se redondea hacia arriba al número de medios-slots que toca.
        // EN: The booking's REAL duration (even if no longer in the config): a
        //     60-min booking still takes 60 if the owner removes that duration.
        //     Rounded up to the number of half-slots it touches.
        $numMedios = max(1, (int) ceil($duracion / $this->slotMinutos()));   // ES: 30->1, 60->2, 90->3

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
    protected function mediosSlotsOcupados(string $fecha, ?int $excluirId = null): array
    {
        // ES: Tramos ocupados [inicio, fin) en minutos desde medianoche.
        // EN: Busy ranges [start, end) in minutes since midnight.
        $tramos = [];

        // ES: La cita excluida (la que se reprograma) no se ocupa a sí misma.
        // EN: The excluded booking (the one being rescheduled) doesn't block itself.
        $citas = Appointment::query()
            ->whereDate('date', $fecha)
            ->where('status', '!=', 'cancelada')
            ->when($excluirId !== null, fn ($q) => $q->whereKeyNot($excluirId))
            ->get(['time', 'duration']);

        // ES: Cada cita, con su duración REAL guardada.
        // EN: Each booking, with its REAL stored duration.
        foreach ($citas as $cita) {
            $inicio = $this->minutos((string) $cita->time);
            $tramos[] = [$inicio, $inicio + max(1, (int) $cita->duration)];
        }

        // ES: Los ocupados del propio Google Calendar (si está configurado),
        //     cada uno como un tramo de un hueco.
        // EN: Busy slots from the owner's own Google Calendar (if configured),
        //     each one as a one-slot range.
        //     El evento de Google de la cita excluida tampoco cuenta.
        //     The excluded booking's own Google event doesn't count either.
        $eventoExcluido = $excluirId !== null
            ? Appointment::whereKey($excluirId)->value('google_event_id')
            : null;
        foreach (app(GoogleCalendarService::class)->eventosOcupados($fecha, $eventoExcluido) as $medio) {
            $inicio = $this->minutos((string) $medio);
            $tramos[] = [$inicio, $inicio + $this->slotMinutos()];
        }

        // ES: Un medio-slot de la rejilla de HOY está ocupado si se solapa con
        //     algún tramo. Se compara por intervalos, no por texto: si el dueño
        //     cambia la apertura o el tamaño del hueco, las citas guardadas con
        //     la rejilla vieja siguen bloqueando su tramo.
        // EN: A half-slot of TODAY's grid is busy if it overlaps any range.
        //     Compared as intervals, not text: if the owner changes the opening
        //     time or the slot size, bookings stored on the old grid still block
        //     their range.
        $ocupados = [];
        foreach ($this->slotsDelDia($fecha) as $slot) {
            $a = $this->minutos($slot);
            $b = $a + $this->slotMinutos();
            foreach ($tramos as [$ini, $fin]) {
                if ($a < $fin && $b > $ini) {
                    $ocupados[] = $slot;
                    break;
                }
            }
        }

        return $ocupados;
    }

    /**
     * ES: "HH:MM" a minutos desde medianoche. EN: "HH:MM" to minutes since midnight.
     */
    protected function minutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($hora, 0, 5)) + [0, 0]);

        return $h * 60 + $m;
    }

    /**
     * ES: Minutos desde medianoche a "HH:MM". EN: Minutes since midnight to "HH:MM".
     */
    protected function hhmm(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    /**
     * ES: Cierre (en minutos) del tramo en el que empieza una hora, o null si
     *     esa hora no cae dentro de ningún tramo.
     * EN: Close (in minutes) of the range a start time falls in, or null if
     *     that time is inside no range.
     *
     * @param  array<int, array{0:string, 1:string}>  $tramos
     */
    protected function cierreDelTramo(array $tramos, string $inicio): ?int
    {
        $m = $this->minutos($inicio);
        foreach ($tramos as [$abre, $cierra]) {
            if ($m >= $this->minutos($abre) && $m < $this->minutos($cierra)) {
                return $this->minutos($cierra);
            }
        }

        return null;
    }
}
