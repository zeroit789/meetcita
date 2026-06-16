<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/*
|==============================================================================
| GoogleCalendarService / Servicio de Google Calendar + Meet
|==============================================================================
| EN: Integration with Google Calendar + Google Meet.
|       - Create the calendar event for a confirmed booking (auto Meet link if online).
|       - Delete that event if the booking is cancelled.
|       - Provide the half-slots occupied by the owner's own calendar so the
|         booking module doesn't offer hours already taken in Google.
|
|     GRACEFUL DEGRADATION (critical): if Google credentials are missing,
|     estaConfigurado() is false and every method is a silent no-op (logs a
|     warning and continues). Bookings NEVER break because of Google: any error
|     is caught and logged, never re-thrown.
| ES: Integración con Google Calendar + Google Meet.
|       - Crear el evento de una cita confirmada (enlace de Meet automático si online).
|       - Borrar ese evento si la cita se cancela.
|       - Aportar los huecos ocupados por el propio calendario del dueño para que
|         el módulo de citas no ofrezca horas ya pilladas en Google.
|
|     DEGRADACIÓN CON GRACIA (crítico): si faltan credenciales de Google,
|     estaConfigurado() es false y todos los métodos hacen no-op silencioso
|     (loguean un warning y siguen). Las citas NUNCA se rompen por Google:
|     cualquier fallo se captura y registra, sin relanzar la excepción.
|
| INDEX / ÍNDICE
|   1. CONFIG / CLIENT ..... is-configured + authenticated client / cliente
|   2. TOKEN CACHE ......... cached access token + invalidation / token cacheado
|   3. CREATE EVENT ........ create calendar event (+ Meet) / crear evento
|   4. INVITEES ............ build attendee email list / lista de invitados
|   5. DELETE EVENT ........ remove event on cancel / borrar evento
|   6. BUSY SLOTS .......... occupied slots from own calendar / huecos ocupados
|==============================================================================
*/
class GoogleCalendarService
{
    // ── 1. Config / client — configuración y cliente autenticado ────────────

    /**
     * EN: Business time zone (read from config('appointments.timezone')).
     * ES: Zona horaria del negocio (de config('appointments.timezone')).
     */
    protected function zona(): string
    {
        return config('appointments.timezone', 'UTC');
    }

    /**
     * EN: Whether the integration is configured (credentials in .env). True only
     *     if client_id, client_secret and refresh_token are all non-empty.
     * ES: Indica si la integración está configurada (credenciales en .env). Solo
     *     true si client_id, client_secret y refresh_token NO están vacíos.
     */
    public function estaConfigurado(): bool
    {
        return ! empty(config('services.google.client_id'))
            && ! empty(config('services.google.client_secret'))
            && ! empty(config('services.google.refresh_token'));
    }

    /**
     * EN: Builds and authenticates the Google client using the refresh_token.
     *     Returns the client ready to instantiate the Calendar service.
     * ES: Construye y autentica el cliente de Google usando el refresh_token.
     *     Devuelve el cliente listo para instanciar el servicio de Calendar.
     */
    protected function cliente(): \Google\Client
    {
        $client = new \Google\Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        // EN: offline access type → allows refreshing the token without re-auth.
        // ES: accessType offline → permite refrescar el token sin reautorizar.
        $client->setAccessType('offline');
        // EN: We only need to manage calendar events. ES: Solo gestionamos eventos.
        $client->addScope(\Google\Service\Calendar::CALENDAR_EVENTS);

        // ── 2. Token cache — token de acceso cacheado ─────────────────────────
        // EN: PERF: cache the access_token (lives ~1h in Google). Before, we asked
        //     Google for a new one on EVERY call. Now we cache it 3300s (55 min,
        //     margin under the real 3600s limit) and reuse it while valid.
        // ES: PERF: cacheamos el access_token (vive ~1h en Google). Antes pedíamos
        //     uno nuevo en CADA llamada. Ahora lo cacheamos 3300s (55 min, margen
        //     frente al límite real de 3600s) y lo reutilizamos mientras valga.
        $token = Cache::remember(
            'gcal_access_token',
            3300,
            fn () => $client->fetchAccessTokenWithRefreshToken(config('services.google.refresh_token'))
        );

        // EN: Apply the cached token to the client for this call.
        // ES: Aplicamos el token cacheado al cliente para esta llamada.
        $client->setAccessToken($token);

        return $client;
    }

    /**
     * EN: If the exception looks like an auth failure (401 / expired or revoked
     *     token), invalidate the cached access_token so the next call fetches a
     *     fresh one with the refresh_token. Other errors don't touch the cache.
     * ES: Si la excepción parece un fallo de autorización (401 / token caducado o
     *     revocado), invalida el access_token cacheado para que la siguiente
     *     llamada pida uno fresco con el refresh_token. Otros errores no tocan caché.
     */
    protected function olvidarTokenSiNoAutorizado(\Throwable $e): void
    {
        // EN: The Google SDK usually exposes the HTTP code in getCode(); we also
        //     cover by text in case it arrives as a plain message.
        // ES: El SDK de Google suele exponer el código HTTP en getCode(); además
        //     cubrimos por texto por si llega como mensaje plano.
        $codigo = $e->getCode();
        $mensaje = strtolower($e->getMessage());

        $noAutorizado = $codigo === 401
            || str_contains($mensaje, '401')
            || str_contains($mensaje, 'unauthorized')
            || str_contains($mensaje, 'invalid_grant')
            || str_contains($mensaje, 'invalid credentials');

        if ($noAutorizado) {
            Cache::forget('gcal_access_token');
        }
    }

    /**
     * EN: Returns the target calendar id (default 'primary').
     * ES: Devuelve el id del calendario destino (por defecto 'primary').
     */
    protected function calendarId(): string
    {
        return config('services.google.calendar_id', 'primary');
    }

    // ── 3. Create event — crear el evento de la cita ────────────────────────

    /**
     * EN: Creates the Google Calendar event for a booking.
     *       - Online: adds conferenceData so Google generates a Meet link.
     *       - In-person: normal event without videocall.
     *       - Both invite the client by email (sendUpdates => 'all').
     *     Stores google_event_id and, if online, google_meet_url on the booking.
     *     Never re-throws: if Google fails it's logged and the confirmation goes on.
     * ES: Crea en Google Calendar el evento de una cita.
     *       - Online: añade conferenceData para que Google genere un enlace de Meet.
     *       - Presencial: evento normal sin videollamada.
     *       - En ambos invita al cliente por email (sendUpdates => 'all').
     *     Guarda google_event_id y, si online, google_meet_url en la cita. No
     *     relanza errores: si Google falla, se registra y la confirmación sigue.
     */
    public function crearEvento(Appointment $cita): void
    {
        // EN: No credentials → silent no-op (graceful degradation).
        // ES: Sin credenciales → no-op silencioso (degradación con gracia).
        if (! $this->estaConfigurado()) {
            Log::warning('GoogleCalendar: integración no configurada; no se crea evento para la cita ' . $cita->reference);

            return;
        }

        try {
            $service = new \Google\Service\Calendar($this->cliente());

            // EN: Event start/end (local business time). $cita->date is a Carbon
            //     (cast 'date'); we add the time and the duration in minutes.
            // ES: Inicio/fin del evento (hora local del negocio). $cita->date es
            //     un Carbon (cast 'date'); le incrustamos la hora y la duración.
            $inicio = Carbon::parse($cita->date->toDateString() . ' ' . $cita->time, $this->zona());
            $fin = $inicio->copy()->addMinutes((int) $cita->duration);

            // EN: Brand name + public site host for the event text (decoupled).
            // ES: Marca + host de la web pública para el texto del evento (desacoplado).
            $marca = config('appointments.brand.name');
            // EN: We show the website HOST (e.g. "example.com"), not the full URL.
            // ES: Mostramos el HOST de la web (p.ej. "example.com"), no la URL completa.
            $host = parse_url((string) config('appointments.brand.website'), PHP_URL_HOST)
                ?: config('appointments.brand.website');

            // EN: Event description text. ES: Texto descriptivo del evento.
            $modalidadTxt = $cita->modality === 'online' ? 'Online (videollamada)' : 'Presencial';
            $descripcion = "Cita solicitada desde {$host}\n\n"
                . "Asunto: {$cita->message}\n"
                . "Asistentes: {$cita->attendees}\n"
                . "Modalidad: {$modalidadTxt}\n"
                . "Referencia: {$cita->reference}";

            // EN: Invitee list — always the client; plus extra valid emails.
            // ES: Lista de invitados — siempre el cliente; más los emails extra válidos.
            $invitados = $this->construirInvitados($cita);

            // EN: Build the event. ES: Construimos el evento.
            $event = new \Google\Service\Calendar\Event([
                'summary'     => "{$marca} — {$cita->name} [{$cita->reference}]",
                'description' => $descripcion,
                'start'       => [
                    'dateTime' => $inicio->toRfc3339String(),
                    'timeZone' => $this->zona(),
                ],
                'end'         => [
                    'dateTime' => $fin->toRfc3339String(),
                    'timeZone' => $this->zona(),
                ],
                // EN: Invite client + extra attendees (each as {email}).
                // ES: Invitamos al cliente + asistentes extra (cada uno como {email}).
                'attendees'   => array_map(fn (string $correo) => ['email' => $correo], $invitados),
            ]);

            // EN: Common insert options: notify invitees by email.
            // ES: Opciones de inserción comunes: avisar a los invitados por email.
            $opts = ['sendUpdates' => 'all'];

            // EN: Only online bookings generate a Meet link.
            // ES: Solo las citas online generan enlace de Meet.
            if ($cita->modality === 'online') {
                $event->setConferenceData(new \Google\Service\Calendar\ConferenceData([
                    'createRequest' => new \Google\Service\Calendar\CreateConferenceRequest([
                        // EN: unique requestId per booking → use its reference.
                        // ES: requestId único por cita → usamos su referencia.
                        'requestId'             => $cita->reference,
                        'conferenceSolutionKey' => new \Google\Service\Calendar\ConferenceSolutionKey([
                            'type' => 'hangoutsMeet',
                        ]),
                    ]),
                ]));

                // EN: Required for Google to process conferenceData (Meet).
                // ES: Necesario para que Google procese la conferenceData (Meet).
                $opts['conferenceDataVersion'] = 1;
            }

            // EN: Insert the event in the calendar. ES: Insertamos el evento.
            $created = $service->events->insert($this->calendarId(), $event, $opts);

            // EN: Store references on the booking. ES: Guardamos las referencias en la cita.
            $cita->google_event_id = $created->getId();
            if ($cita->modality === 'online') {
                // EN: getHangoutLink() returns the Meet link generated by Google.
                // ES: getHangoutLink() devuelve el enlace de Meet generado por Google.
                $cita->google_meet_url = $created->getHangoutLink();
            }
            $cita->save();

            // EN: New event occupies a slot: invalidate the range cache so
            //     availability reflects it right away (no 10-min TTL wait).
            // ES: El nuevo evento ocupa un hueco: invalidamos la caché del rango
            //     para que la disponibilidad lo refleje ya (sin esperar el TTL).
            Cache::forget('gcal_ocupados_rango');
        } catch (\Throwable $e) {
            // EN: If it failed due to expired/revoked token (401), forget it so
            //     the next call fetches a fresh one with the refresh_token.
            // ES: Si falló por token caducado/revocado (401), lo olvidamos para
            //     que la siguiente llamada pida uno fresco con el refresh_token.
            $this->olvidarTokenSiNoAutorizado($e);
            // EN: Never break the booking confirmation due to a Google failure.
            // ES: Nunca rompemos la confirmación de la cita por un fallo de Google.
            Log::error('GoogleCalendar: error creando evento para la cita ' . $cita->reference . ': ' . $e->getMessage());
        }
    }

    // ── 4. Invitees — lista de emails de invitados ──────────────────────────

    /**
     * EN: Builds the invitee email list for a booking event:
     *       - Always includes the client's email (first invitee).
     *       - Adds each 'attendee_emails' email (comma list): trims, drops empties,
     *         validates with FILTER_VALIDATE_EMAIL and avoids duplicating the client.
     *     Graceful degradation: invalid/empty entries are simply not added.
     *     Second safety barrier: at most client + 10 invitees = 11.
     * ES: Construye la lista de emails de invitados al evento de una cita:
     *       - Siempre incluye el email del cliente (primer invitado).
     *       - Añade cada email de 'attendee_emails' (separados por comas): trim,
     *         descarta vacíos, valida con FILTER_VALIDATE_EMAIL y evita duplicar el del cliente.
     *     Degradación con gracia: lo inválido/vacío simplemente no se añade.
     *     Segunda barrera de seguridad: como mucho cliente + 10 invitados = 11.
     *
     * @return array<int, string>  EN: unique emails to invite · ES: emails únicos a invitar
     */
    protected function construirInvitados(Appointment $cita): array
    {
        // EN: The client always goes first. ES: El cliente siempre va el primero.
        $invitados = [$cita->email];

        // EN: Lowercase control set to avoid dups (including the client's).
        // ES: Set de control en minúsculas para no duplicar (incluido el del cliente).
        $vistos = [strtolower(trim($cita->email))];

        // EN: No extra emails → just the client. ES: Sin correos extra → solo el cliente.
        if (empty($cita->attendee_emails)) {
            return $invitados;
        }

        // EN: Iterate the comma-separated extra emails. ES: Recorremos los correos extra.
        foreach (explode(',', $cita->attendee_emails) as $correo) {
            $correo = trim($correo);

            // EN: Skip empties and invalid emails (graceful degradation).
            // ES: Saltamos vacíos y los que no sean un email válido.
            if ($correo === '' || ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // EN: Avoid duplicates (same email even if case differs).
            // ES: Evitamos duplicados (mismo email aunque cambie mayús/minús).
            $clave = strtolower($correo);
            if (in_array($clave, $vistos, true)) {
                continue;
            }

            $invitados[] = $correo;
            $vistos[] = $clave;
        }

        // EN: Cap: at most client + 10 = 11 invitees. The Livewire component also
        //     validates this; we reinforce it here in case the booking arrives by
        //     another path (tampered data, import, etc.).
        // ES: Tope: como mucho cliente + 10 = 11 invitados. El componente Livewire
        //     ya lo valida; lo reforzamos aquí por si la cita llega por otra vía.
        // EN: max_attendees from config (+1 for the client). ES: max_attendees de config (+1 cliente).
        $max = (int) config('appointments.schedule.max_attendees', 10) + 1;

        return array_slice($invitados, 0, $max);
    }

    // ── 5. Delete event — borrar el evento al cancelar ──────────────────────

    /**
     * EN: Deletes the Google Calendar event linked to a booking (on cancel).
     *     Clears google_event_id and google_meet_url. No-op if not configured or
     *     the booking has no linked event.
     * ES: Borra el evento de Google Calendar asociado a una cita (al cancelarla).
     *     Limpia google_event_id y google_meet_url. No-op si no está configurado
     *     o si la cita no tiene evento asociado.
     */
    public function borrarEvento(Appointment $cita): void
    {
        // EN: No credentials or no event → nothing to delete.
        // ES: Sin credenciales o sin evento → nada que borrar.
        if (! $this->estaConfigurado() || empty($cita->google_event_id)) {
            return;
        }

        try {
            $service = new \Google\Service\Calendar($this->cliente());

            // EN: Delete the event and notify invitees of the cancellation.
            // ES: Borramos el evento y avisamos a los invitados de la cancelación.
            $service->events->delete($this->calendarId(), $cita->google_event_id, ['sendUpdates' => 'all']);

            // EN: Clear the references on the booking (the event no longer exists).
            // ES: Limpiamos las referencias en la cita (ya no existe el evento).
            $cita->google_event_id = null;
            $cita->google_meet_url = null;
            $cita->save();

            // EN: The slot is free again: invalidate the range cache.
            // ES: El hueco vuelve a quedar libre: invalidamos la caché del rango.
            Cache::forget('gcal_ocupados_rango');
        } catch (\Throwable $e) {
            // EN: Expired/revoked token → forget it to refresh next time.
            // ES: Token caducado/revocado → lo olvidamos para refrescar a la próxima.
            $this->olvidarTokenSiNoAutorizado($e);
            // EN: A delete failure must not break the booking cancellation.
            // ES: Un fallo al borrar no debe romper la cancelación de la cita.
            Log::error('GoogleCalendar: error borrando evento de la cita ' . $cita->reference . ': ' . $e->getMessage());
        }
    }

    // ── 6. Busy slots — huecos ocupados por el propio calendario ────────────

    /**
     * EN: Returns the half-slots "HH:MM" (30 min) occupied by the owner's own
     *     calendar on a date. Same format as AvailabilityService so the booking
     *     module can merge both lists and not offer hours already taken in Google.
     *     No-op ([]) if not configured or on error. Ignores all-day events.
     *     Caches the whole range 10 minutes (see ocupadosDelRango()).
     * ES: Devuelve los medios-slots "HH:MM" (30 min) ocupados por el propio
     *     calendario en una fecha. Mismo formato que AvailabilityService para que
     *     el módulo de citas pueda fusionar ambas listas. No-op ([]) si no está
     *     configurado o si hay error. Ignora los eventos de día completo.
     *     Cachea todo el rango 10 minutos (ver ocupadosDelRango()).
     *
     * @param  string $fecha  EN: day "YYYY-MM-DD" · ES: día "YYYY-MM-DD"
     * @return array<int, string>  EN: occupied half-slots · ES: medios-slots ocupados
     */
    public function eventosOcupados(string $fecha): array
    {
        // EN: No credentials → Google contributes no occupancy.
        // ES: Sin credenciales → el calendario de Google no aporta ocupación.
        if (! $this->estaConfigurado()) {
            return [];
        }

        // EN: Read from the whole-range map (one cached call) and return the
        //     half-slots of the requested day. So selecting a day in the calendar
        //     does NOT trigger a new Google call (it's instant).
        // ES: Leemos del mapa de TODO el rango (una sola llamada cacheada) y
        //     devolvemos los medios-slots del día pedido. Así seleccionar un día
        //     NO dispara una llamada nueva a Google (es instantáneo).
        return $this->ocupadosDelRango()[$fecha] ?? [];
    }

    /**
     * EN: Loads the next ~16 days of events in ONE call and groups them by date
     *     into occupied half-slots "HH:MM". Caches 10 min. Before, one API call
     *     per consulted day added a perceptible delay; with a single range call
     *     the first render pays the latency once and the rest are served from cache.
     * ES: Carga en UNA sola llamada los eventos de los próximos ~16 días y los
     *     agrupa por fecha en medios-slots ocupados "HH:MM". Cachea 10 min. Antes
     *     se hacía una llamada por día (retardo perceptible); con una sola llamada
     *     de rango el primer render paga la latencia una vez y el resto va de caché.
     *
     * @return array<string, array<int, string>>  EN/ES: ["YYYY-MM-DD" => ["HH:MM", ...]]
     */
    protected function ocupadosDelRango(): array
    {
        return Cache::remember('gcal_ocupados_rango', 600, function () {
            try {
                $service = new \Google\Service\Calendar($this->cliente());

                // EN: Range: from the start of today to 16 days later (covers the
                //     14 the module offers, with margin).
                // ES: Rango: desde el inicio de hoy hasta 16 días después (cubre
                //     los 14 que ofrece el módulo, con margen).
                $inicio = Carbon::now($this->zona())->startOfDay();
                $fin = Carbon::now($this->zona())->addDays(16)->endOfDay();

                // EN: Already-expanded events (singleEvents), ordered by start.
                // ES: Eventos ya expandidos (singleEvents), ordenados por inicio.
                $eventos = $service->events->listEvents($this->calendarId(), [
                    'timeMin'      => $inicio->toRfc3339String(),
                    'timeMax'      => $fin->toRfc3339String(),
                    'singleEvents' => true,
                    'orderBy'      => 'startTime',
                    'timeZone'     => $this->zona(),
                ]);

                $porFecha = [];

                foreach ($eventos->getItems() as $evento) {
                    $start = $evento->getStart();
                    $end = $evento->getEnd();

                    // EN: Ignore all-day events (only have 'date', no 'dateTime').
                    // ES: Ignoramos eventos de día completo (solo 'date', no 'dateTime').
                    if (! $start || ! $start->getDateTime() || ! $end || ! $end->getDateTime()) {
                        continue;
                    }

                    // EN: Event start/end in local business time.
                    // ES: Inicio y fin del evento en hora local del negocio.
                    $eInicio = Carbon::parse($start->getDateTime())->setTimezone($this->zona());
                    $eFin = Carbon::parse($end->getDateTime())->setTimezone($this->zona());

                    // EN: Expand [start, end) to 30-min half-slots aligned to the
                    //     half hour (00 and 30) and group them by date.
                    // ES: Expandimos [inicio, fin) a medios-slots de 30 min
                    //     alineados a la media hora (00 y 30) y los agrupamos por fecha.
                    $cursor = $eInicio->copy();
                    $cursor->minute($cursor->minute < 30 ? 0 : 30)->second(0);

                    while ($cursor->lt($eFin)) {
                        $porFecha[$cursor->toDateString()][] = $cursor->format('H:i');
                        $cursor->addMinutes(30);
                    }
                }

                // EN: No duplicates within each date. ES: Sin duplicados dentro de cada fecha.
                foreach ($porFecha as $f => $slots) {
                    $porFecha[$f] = array_values(array_unique($slots));
                }

                return $porFecha;
            } catch (\Throwable $e) {
                // EN: Expired/revoked token → forget it to refresh next time.
                // ES: Token caducado/revocado → lo olvidamos para refrescar a la próxima.
                $this->olvidarTokenSiNoAutorizado($e);
                // EN: Any Google error → no occupancy contributed.
                // ES: Cualquier error de Google → el calendario no aporta ocupación.
                Log::error('GoogleCalendar: error listando eventos del rango: ' . $e->getMessage());

                return [];
            }
        });
    }
}
