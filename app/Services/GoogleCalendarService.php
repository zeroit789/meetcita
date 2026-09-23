<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/*
|==============================================================================
| GoogleCalendarService / Servicio de Google Calendar + Meet
|==============================================================================
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
     * ES: Zona horaria del negocio (de config('appointments.timezone')).
     * EN: Business time zone (read from config('appointments.timezone')).
     */
    protected function zona(): string
    {
        return config('appointments.timezone', 'UTC');
    }

    /**
     * ES: Indica si la integración está configurada (credenciales en .env). Solo
     *     true si client_id, client_secret y refresh_token NO están vacíos.
     * EN: Whether the integration is configured (credentials in .env). True only
     *     if client_id, client_secret and refresh_token are all non-empty.
     */
    public function estaConfigurado(): bool
    {
        return ! empty(config('services.google.client_id'))
            && ! empty(config('services.google.client_secret'))
            && ! empty(config('services.google.refresh_token'));
    }

    /**
     * ES: Construye y autentica el cliente de Google usando el refresh_token.
     *     Devuelve el cliente listo para instanciar el servicio de Calendar.
     * EN: Builds and authenticates the Google client using the refresh_token.
     *     Returns the client ready to instantiate the Calendar service.
     */
    protected function cliente(): Client
    {
        $client = new Client;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        // ES: accessType offline → permite refrescar el token sin reautorizar.
        // EN: offline access type → allows refreshing the token without re-auth.
        $client->setAccessType('offline');
        // ES: Solo gestionamos eventos. EN: We only need to manage calendar events.
        $client->addScope(Calendar::CALENDAR_EVENTS);

        // ── 2. Token cache — token de acceso cacheado ─────────────────────────
        // ES: PERF: cacheamos el access_token (vive ~1h en Google). Antes pedíamos
        //     uno nuevo en CADA llamada. Ahora lo cacheamos 3300s (55 min, margen
        //     frente al límite real de 3600s) y lo reutilizamos mientras valga.
        // EN: PERF: cache the access_token (lives ~1h in Google). Before, we asked
        //     Google for a new one on EVERY call. Now we cache it 3300s (55 min,
        //     margin under the real 3600s limit) and reuse it while valid.
        $token = Cache::remember(
            'gcal_access_token',
            3300,
            fn () => $client->fetchAccessTokenWithRefreshToken(config('services.google.refresh_token'))
        );

        // ES: Aplicamos el token cacheado al cliente para esta llamada.
        // EN: Apply the cached token to the client for this call.
        $client->setAccessToken($token);

        return $client;
    }

    /**
     * ES: Servicio de Calendar ya autenticado. Aislado en un método para que los
     *     tests puedan sustituirlo por uno simulado sin red.
     * EN: Authenticated Calendar service. Isolated in a method so tests can
     *     swap it for a mocked one without network access.
     */
    protected function servicioCalendar(): Calendar
    {
        return new Calendar($this->cliente());
    }

    /**
     * ES: Si la excepción parece un fallo de autorización (401 / token caducado o
     *     revocado), invalida el access_token cacheado para que la siguiente
     *     llamada pida uno fresco con el refresh_token. Otros errores no tocan caché.
     * EN: If the exception looks like an auth failure (401 / expired or revoked
     *     token), invalidate the cached access_token so the next call fetches a
     *     fresh one with the refresh_token. Other errors don't touch the cache.
     */
    protected function olvidarTokenSiNoAutorizado(\Throwable $e): void
    {
        // ES: El SDK de Google suele exponer el código HTTP en getCode(); además
        //     cubrimos por texto por si llega como mensaje plano.
        // EN: The Google SDK usually exposes the HTTP code in getCode(); we also
        //     cover by text in case it arrives as a plain message.
        $codigo = $e->getCode();
        $mensaje = strtolower($e->getMessage());

        $noAutorizado = $codigo === 401
            || str_contains($mensaje, '401')
            || str_contains($mensaje, 'unauthorized')
            || str_contains($mensaje, 'invalid_grant')
            || str_contains($mensaje, 'invalid credentials');

        // ES: Credencial rechazada: se borra el token cacheado para pedir uno nuevo.
        // EN: Credential rejected: drop the cached token so a fresh one is requested.
        if ($noAutorizado) {
            Cache::forget('gcal_access_token');
        }
    }

    /**
     * ES: Devuelve el id del calendario destino (por defecto 'primary').
     * EN: Returns the target calendar id (default 'primary').
     */
    protected function calendarId(): string
    {
        return config('services.google.calendar_id', 'primary');
    }

    // ── 3. Create event — crear el evento de la cita ────────────────────────

    /**
     * ES: Crea en Google Calendar el evento de una cita.
     *       - Online: añade conferenceData para que Google genere un enlace de Meet.
     *       - Presencial: evento normal sin videollamada.
     *       - En ambos invita al cliente por email (sendUpdates => 'all').
     *     Guarda google_event_id y, si online, google_meet_url en la cita. No
     *     relanza errores: si Google falla, se registra y la confirmación sigue.
     * EN: Creates the Google Calendar event for a booking.
     *       - Online: adds conferenceData so Google generates a Meet link.
     *       - In-person: normal event without videocall.
     *       - Both invite the client by email (sendUpdates => 'all').
     *     Stores google_event_id and, if online, google_meet_url on the booking.
     *     Never re-throws: if Google fails it's logged and the confirmation goes on.
     */
    public function crearEvento(Appointment $cita): void
    {
        // ES: Sin credenciales → no-op silencioso (degradación con gracia).
        // EN: No credentials → silent no-op (graceful degradation).
        if (! $this->estaConfigurado()) {
            Log::warning('GoogleCalendar: integración no configurada; no se crea evento para la cita '.$cita->reference);

            return;
        }

        // ES: Cualquier error de Google se registra y no rompe la confirmación.
        // EN: Any Google error is logged and does not break the confirmation.
        try {
            $service = $this->servicioCalendar();

            // ES: Inicio/fin del evento (hora local del negocio). $cita->date es
            //     un Carbon (cast 'date'); le incrustamos la hora y la duración.
            // EN: Event start/end (local business time). $cita->date is a Carbon
            //     (cast 'date'); we add the time and the duration in minutes.
            $inicio = Carbon::parse($cita->date->toDateString().' '.$cita->time, $this->zona());
            $fin = $inicio->copy()->addMinutes((int) $cita->duration);

            // ES: Marca + host de la web pública para el texto del evento (desacoplado).
            // EN: Brand name + public site host for the event text (decoupled).
            $marca = config('appointments.brand.name');
            // ES: Mostramos el HOST de la web (p.ej. "example.com"), no la URL completa.
            // EN: We show the website HOST (e.g. "example.com"), not the full URL.
            $host = parse_url((string) config('appointments.brand.website'), PHP_URL_HOST)
                ?: config('appointments.brand.website');

            // ES: Texto descriptivo del evento. EN: Event description text.
            $modalidadTxt = $cita->modality === 'online' ? 'Online (videollamada)' : 'Presencial';
            $descripcion = "Cita solicitada desde {$host}\n\n"
                ."Asunto: {$cita->message}\n"
                ."Asistentes: {$cita->attendees}\n"
                ."Modalidad: {$modalidadTxt}\n"
                ."Referencia: {$cita->reference}";

            // ES: Lista de invitados — siempre el cliente; más los emails extra válidos.
            // EN: Invitee list — always the client; plus extra valid emails.
            $invitados = $this->construirInvitados($cita);

            // ES: Construimos el evento. EN: Build the event.
            $event = new Event([
                'summary' => "{$marca} — {$cita->name} [{$cita->reference}]",
                'description' => $descripcion,
                'start' => [
                    'dateTime' => $inicio->toRfc3339String(),
                    'timeZone' => $this->zona(),
                ],
                'end' => [
                    'dateTime' => $fin->toRfc3339String(),
                    'timeZone' => $this->zona(),
                ],
                // ES: Invitamos al cliente + asistentes extra (cada uno como {email}).
                // EN: Invite client + extra attendees (each as {email}).
                'attendees' => array_map(fn (string $correo) => ['email' => $correo], $invitados),
            ]);

            // ES: Opciones de inserción comunes: avisar a los invitados por email.
            // EN: Common insert options: notify invitees by email.
            $opts = ['sendUpdates' => 'all'];

            // ES: Solo las citas online generan enlace de Meet.
            // EN: Only online bookings generate a Meet link.
            if ($cita->modality === 'online') {
                $event->setConferenceData(new ConferenceData([
                    'createRequest' => new CreateConferenceRequest([
                        // ES: requestId único por cita → usamos su referencia.
                        // EN: unique requestId per booking → use its reference.
                        'requestId' => $cita->reference,
                        'conferenceSolutionKey' => new ConferenceSolutionKey([
                            'type' => 'hangoutsMeet',
                        ]),
                    ]),
                ]));

                // ES: Necesario para que Google procese la conferenceData (Meet).
                // EN: Required for Google to process conferenceData (Meet).
                $opts['conferenceDataVersion'] = 1;
            }

            // ES: Insertamos el evento. EN: Insert the event in the calendar.
            $created = $service->events->insert($this->calendarId(), $event, $opts);

            // ES: Guardamos las referencias en la cita. EN: Store references on the booking.
            $cita->google_event_id = $created->getId();
            if ($cita->modality === 'online') {
                // ES: getHangoutLink() devuelve el enlace de Meet generado por Google.
                // EN: getHangoutLink() returns the Meet link generated by Google.
                $cita->google_meet_url = $created->getHangoutLink();
            }
            $cita->save();

            // ES: El nuevo evento ocupa un hueco: invalidamos la caché del rango
            //     para que la disponibilidad lo refleje ya (sin esperar el TTL).
            // EN: New event occupies a slot: invalidate the range cache so
            //     availability reflects it right away (no 10-min TTL wait).
            Cache::forget('gcal_ocupados_rango');
        } catch (\Throwable $e) {
            // ES: Si falló por token caducado/revocado (401), lo olvidamos para
            //     que la siguiente llamada pida uno fresco con el refresh_token.
            // EN: If it failed due to expired/revoked token (401), forget it so
            //     the next call fetches a fresh one with the refresh_token.
            $this->olvidarTokenSiNoAutorizado($e);
            // ES: Nunca rompemos la confirmación de la cita por un fallo de Google.
            // EN: Never break the booking confirmation due to a Google failure.
            Log::error('GoogleCalendar: error creando evento para la cita '.$cita->reference.': '.$e->getMessage());
        }
    }

    // ── 4. Invitees — lista de emails de invitados ──────────────────────────

    /**
     * ES: Construye la lista de emails de invitados al evento de una cita:
     *       - Siempre incluye el email del cliente (primer invitado).
     *       - Añade cada email de 'attendee_emails' (separados por comas): trim,
     *         descarta vacíos, valida con FILTER_VALIDATE_EMAIL y evita duplicar el del cliente.
     *     Degradación con gracia: lo inválido/vacío simplemente no se añade.
     *     Segunda barrera de seguridad: como mucho cliente + 10 invitados = 11.
     * EN: Builds the invitee email list for a booking event:
     *       - Always includes the client's email (first invitee).
     *       - Adds each 'attendee_emails' email (comma list): trims, drops empties,
     *         validates with FILTER_VALIDATE_EMAIL and avoids duplicating the client.
     *     Graceful degradation: invalid/empty entries are simply not added.
     *     Second safety barrier: at most client + 10 invitees = 11.
     *
     * @return array<int, string> ES: emails únicos a invitar · EN: unique emails to invite
     */
    protected function construirInvitados(Appointment $cita): array
    {
        // ES: El cliente siempre va el primero. EN: The client always goes first.
        $invitados = [$cita->email];

        // ES: Set de control en minúsculas para no duplicar (incluido el del cliente).
        // EN: Lowercase control set to avoid dups (including the client's).
        $vistos = [strtolower(trim($cita->email))];

        // ES: Sin correos extra → solo el cliente. EN: No extra emails → just the client.
        if (empty($cita->attendee_emails)) {
            return $invitados;
        }

        // ES: Recorremos los correos extra. EN: Iterate the comma-separated extra emails.
        foreach (explode(',', $cita->attendee_emails) as $correo) {
            $correo = trim($correo);

            // ES: Saltamos vacíos y los que no sean un email válido.
            // EN: Skip empties and invalid emails (graceful degradation).
            if ($correo === '' || ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // ES: Evitamos duplicados (mismo email aunque cambie mayús/minús).
            // EN: Avoid duplicates (same email even if case differs).
            $clave = strtolower($correo);
            if (in_array($clave, $vistos, true)) {
                continue;
            }

            $invitados[] = $correo;
            $vistos[] = $clave;
        }

        // ES: Tope: como mucho cliente + 10 = 11 invitados. El componente Livewire
        //     ya lo valida; lo reforzamos aquí por si la cita llega por otra vía.
        // EN: Cap: at most client + 10 = 11 invitees. The Livewire component also
        //     validates this; we reinforce it here in case the booking arrives by
        //     another path (tampered data, import, etc.).
        // ES: max_attendees de config (+1 cliente). EN: max_attendees from config (+1 for the client).
        $max = (int) config('appointments.schedule.max_attendees', 10) + 1;

        return array_slice($invitados, 0, $max);
    }

    // ── 5. Delete event — borrar el evento al cancelar ──────────────────────

    /**
     * ES: Borra el evento de Google Calendar asociado a una cita (al cancelarla).
     *     Limpia google_event_id y google_meet_url. No-op si no está configurado
     *     o si la cita no tiene evento asociado.
     * EN: Deletes the Google Calendar event linked to a booking (on cancel).
     *     Clears google_event_id and google_meet_url. No-op if not configured or
     *     the booking has no linked event.
     */
    public function borrarEvento(Appointment $cita): void
    {
        // ES: Sin credenciales o sin evento → nada que borrar.
        // EN: No credentials or no event → nothing to delete.
        if (! $this->estaConfigurado() || empty($cita->google_event_id)) {
            return;
        }

        // ES: Si el borrado falla se registra; la cancelación sigue adelante.
        // EN: If deletion fails it is logged; the cancellation still goes ahead.
        try {
            $service = $this->servicioCalendar();

            // ES: Borramos el evento y avisamos a los invitados de la cancelación.
            // EN: Delete the event and notify invitees of the cancellation.
            $service->events->delete($this->calendarId(), $cita->google_event_id, ['sendUpdates' => 'all']);

            // ES: Limpiamos las referencias en la cita (ya no existe el evento).
            // EN: Clear the references on the booking (the event no longer exists).
            $cita->google_event_id = null;
            $cita->google_meet_url = null;
            $cita->save();

            // ES: El hueco vuelve a quedar libre: invalidamos la caché del rango.
            // EN: The slot is free again: invalidate the range cache.
            Cache::forget('gcal_ocupados_rango');
        } catch (\Throwable $e) {
            // ES: Token caducado/revocado → lo olvidamos para refrescar a la próxima.
            // EN: Expired/revoked token → forget it to refresh next time.
            $this->olvidarTokenSiNoAutorizado($e);
            // ES: Un fallo al borrar no debe romper la cancelación de la cita.
            // EN: A delete failure must not break the booking cancellation.
            Log::error('GoogleCalendar: error borrando evento de la cita '.$cita->reference.': '.$e->getMessage());
        }
    }

    // ── 6. Busy slots — huecos ocupados por el propio calendario ────────────

    /**
     * ES: Devuelve los medios-slots "HH:MM" (30 min) ocupados por el propio
     *     calendario en una fecha. Mismo formato que AvailabilityService para que
     *     el módulo de citas pueda fusionar ambas listas. No-op ([]) si no está
     *     configurado o si hay error. Ignora los eventos de día completo.
     *     Cachea todo el rango 10 minutos (ver ocupadosDelRango()).
     * EN: Returns the half-slots "HH:MM" (30 min) occupied by the owner's own
     *     calendar on a date. Same format as AvailabilityService so the booking
     *     module can merge both lists and not offer hours already taken in Google.
     *     No-op ([]) if not configured or on error. Ignores all-day events.
     *     Caches the whole range 10 minutes (see ocupadosDelRango()).
     *
     * @param  string  $fecha  ES: día "YYYY-MM-DD" · EN: day "YYYY-MM-DD"
     * @return array<int, string> ES: medios-slots ocupados · EN: occupied half-slots
     */
    public function eventosOcupados(string $fecha): array
    {
        // ES: Sin credenciales → el calendario de Google no aporta ocupación.
        // EN: No credentials → Google contributes no occupancy.
        if (! $this->estaConfigurado()) {
            return [];
        }

        // ES: Leemos del mapa de TODO el rango (una sola llamada cacheada) y
        //     devolvemos los medios-slots del día pedido. Así seleccionar un día
        //     NO dispara una llamada nueva a Google (es instantáneo).
        // EN: Read from the whole-range map (one cached call) and return the
        //     half-slots of the requested day. So selecting a day in the calendar
        //     does NOT trigger a new Google call (it's instant).
        return $this->ocupadosDelRango()[$fecha] ?? [];
    }

    /**
     * ES: Carga en UNA sola llamada los eventos de TODOS los días que ofrece el
     *     calendario de reservas y los agrupa por fecha en medios-slots ocupados
     *     "HH:MM". Cachea 10 min. La ventana sale de
     *     AvailabilityService::rangoReservable(), la misma función que genera los
     *     días: 14 laborables son unos 20 naturales (más si hay días bloqueados),
     *     y con una ventana fija de 16 días los últimos días ofrecidos se quedaban
     *     sin la ocupación de Google.
     * EN: Loads in ONE call the events of ALL the days the booking calendar
     *     offers and groups them by date into occupied half-slots "HH:MM".
     *     Caches 10 min. The window comes from
     *     AvailabilityService::rangoReservable(), the same function that builds
     *     the days: 14 working days are about 20 calendar days (more with
     *     blocked days), and a fixed 16-day window left the last offered days
     *     without their Google occupancy.
     *
     * @return array<string, array<int, string>> EN/ES: ["YYYY-MM-DD" => ["HH:MM", ...]]
     */
    protected function ocupadosDelRango(): array
    {
        return Cache::remember('gcal_ocupados_rango', 600, function () {
            try {
                $service = $this->servicioCalendar();

                // ES: Rango: del inicio del primer día ofrecido al final del último.
                // EN: Range: from the start of the first offered day to the end of the last.
                $rango = app(AvailabilityService::class)->rangoReservable();
                $inicio = Carbon::parse($rango['min'], $this->zona())->startOfDay();
                $fin = Carbon::parse($rango['max'], $this->zona())->endOfDay();

                // ES: Eventos ya expandidos (singleEvents), ordenados por inicio.
                // EN: Already-expanded events (singleEvents), ordered by start.
                $eventos = $service->events->listEvents($this->calendarId(), [
                    'timeMin' => $inicio->toRfc3339String(),
                    'timeMax' => $fin->toRfc3339String(),
                    'singleEvents' => true,
                    'orderBy' => 'startTime',
                    'timeZone' => $this->zona(),
                ]);

                // ES: Horas ocupadas agrupadas por fecha "Y-m-d".
                // EN: Busy times grouped by "Y-m-d" date.
                $porFecha = [];

                foreach ($eventos->getItems() as $evento) {
                    $start = $evento->getStart();
                    $end = $evento->getEnd();

                    // ES: Ignoramos eventos de día completo (solo 'date', no 'dateTime').
                    // EN: Ignore all-day events (only have 'date', no 'dateTime').
                    if (! $start || ! $start->getDateTime() || ! $end || ! $end->getDateTime()) {
                        continue;
                    }

                    // ES: Inicio y fin del evento en hora local del negocio.
                    // EN: Event start/end in local business time.
                    $eInicio = Carbon::parse($start->getDateTime())->setTimezone($this->zona());
                    $eFin = Carbon::parse($end->getDateTime())->setTimezone($this->zona());

                    // ES: Expandimos [inicio, fin) a medios-slots de 30 min
                    //     alineados a la media hora (00 y 30) y los agrupamos por fecha.
                    // EN: Expand [start, end) to 30-min half-slots aligned to the
                    //     half hour (00 and 30) and group them by date.
                    $cursor = $eInicio->copy();
                    $cursor->minute($cursor->minute < 30 ? 0 : 30)->second(0);

                    while ($cursor->lt($eFin)) {
                        $porFecha[$cursor->toDateString()][] = $cursor->format('H:i');
                        $cursor->addMinutes(30);
                    }
                }

                // ES: Sin duplicados dentro de cada fecha. EN: No duplicates within each date.
                foreach ($porFecha as $f => $slots) {
                    $porFecha[$f] = array_values(array_unique($slots));
                }

                return $porFecha;
            } catch (\Throwable $e) {
                // ES: Token caducado/revocado → lo olvidamos para refrescar a la próxima.
                // EN: Expired/revoked token → forget it to refresh next time.
                $this->olvidarTokenSiNoAutorizado($e);
                // ES: Cualquier error de Google → el calendario no aporta ocupación.
                // EN: Any Google error → no occupancy contributed.
                Log::error('GoogleCalendar: error listando eventos del rango: '.$e->getMessage());

                return [];
            }
        });
    }
}
