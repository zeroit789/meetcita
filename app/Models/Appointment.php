<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/*
|==============================================================================
| Appointment model / Modelo Appointment
|==============================================================================
| ES: Una cita solicitada por un cliente desde /citas. Guarda los datos de
|     contacto, el hueco elegido, duracion, modalidad, idioma y (al confirmar)
|     las referencias de Google Calendar / Meet. Los valores de marca/hora
|     salen de config('appointments.*') para que el paquete sea reutilizable.
| EN: One booking requested by a client from the /citas page. Holds contact
|     data, the chosen slot, duration, modality, locale and (once confirmed)
|     the Google Calendar / Meet references. Most branding/time values are
|     pulled from config('appointments.*') so the package is reusable.
|
| INDEX / ÍNDICE
|   1. FILLABLE / DEFAULTS / CASTS ... mass assignment + defaults + tipos
|   2. REFERENCE ..................... unique public booking code / código único
|   3. TIME HELPERS .................. start/end in UTC / inicio y fin en UTC
|   4. CALENDAR HELPERS .............. title/desc/location/url for calendars
|   5. ATTENDEES ..................... extra invitee emails / emails de invitados
|==============================================================================
|
| @property int         $id
| @property string      $reference        Public booking code ("APT-K7P-3QX") / código público
| @property string      $name             Client name / nombre del cliente
| @property string      $email            Client email / email del cliente
| @property string|null $phone            Phone (optional) / teléfono (opcional)
| @property string      $date             Booking day (YYYY-MM-DD) / día de la cita
| @property string      $time             Slot "HH:MM" / hora del slot
| @property int         $duration         Minutes (30/60) / duración en minutos
| @property string      $attendees        Attendee names / nombres de asistentes
| @property string|null $attendee_emails  Extra invitee emails (comma list) / correos extra
| @property string      $message          Meeting topic / asunto de la reunión
| @property string      $reason           Reason (kept for compat; = message) / motivo (compat)
| @property string      $status           pendiente|confirmada|cancelada
| @property string      $modality         online|presencial (online = Meet link)
| @property string      $locale           Client language (es|en) / idioma del cliente
| @property string|null $google_event_id  Google Calendar event id / id del evento
| @property string|null $google_meet_url  Google Meet link (online only) / enlace Meet
*/
class Appointment extends Model
{
    // ES: Factoría para los tests (database/factories/AppointmentFactory.php).
    // EN: Factory for the tests (database/factories/AppointmentFactory.php).
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    // ── 1. Fillable / Defaults / Casts — asignación masiva, defaults y tipos ──

    /**
     * ES: Campos asignables en masa. SEGURIDAD (fix mass assignment): los campos
     *     que fija el sistema quedan FUERA del fillable, NUNCA el input del
     *     usuario (reference, status, google_*). Así un create() manipulado no
     *     puede inyectar esos valores.
     * EN: Mass-assignable fields. SECURITY (mass assignment fix): system-set
     *     fields stay OUT of $fillable — never user input:
     *       - 'reference'        set by the creating() hook below.
     *       - 'status'           system-set (default 'pendiente' in $attributes;
     *                            changed via property/save or conditional update).
     *       - 'google_event_id'  set by GoogleCalendarService via property.
     *       - 'google_meet_url'  idem.
     *     So a create() with tampered data cannot inject these values.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'date',
        'time',
        'duration',         // ES: minutos 30/60 · EN: minutes 30/60
        'attendees',        // ES: nombres de asistentes · EN: attendee names
        'attendee_emails',  // ES: correos extra (separados por comas) · EN: extra invitee emails (comma list)
        'message',          // ES: asunto de la reunión · EN: meeting topic
        'reason',           // ES: compatibilidad (= message) · EN: kept for compat (= message)
        'modality',         // ES: 'online' (Meet) o 'presencial' · EN: 'online' (Meet) or 'presencial'
        'locale',           // ES: idioma del cliente para los emails · EN: client language for emails
    ];

    /**
     * ES: Valores por defecto. Garantiza 'status' = 'pendiente' también en el
     *     objeto en memoria (no solo en BD), evitando un null al usarlo en los
     *     emails justo después. Duración/modalidad por defecto desde config.
     * EN: Model defaults. Guarantees 'status' = 'pendiente' on the in-memory
     *     object too (not only in DB), avoiding a null when used in emails
     *     right after creation. Duration/modality defaulted from config.
     *
     * ES: La duración por defecto cae a la primera configurada (o 30).
     * EN: Default duration falls back to the first configured duration (or 30).
     */
    protected $attributes = [
        'status' => 'pendiente',
        'duration' => 30,
        'modality' => 'online',   // ES: online por defecto · EN: online (videocall) by default
    ];

    /**
     * ES: Casts de tipos. 'date' como fecha (formato Y-m-d para que el índice
     *     único parcial y los whereDate(...) comparen exactamente el mismo
     *     formato). 'duration' como entero para operar con los minutos.
     * EN: Type casts. 'date' cast to date (formatted as Y-m-d so the partial
     *     unique index and whereDate(...) compare the exact same format).
     *     'duration' cast to integer to operate on minutes.
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'duration' => 'integer',
        ];
    }

    /**
     * ES: Zona horaria del negocio. Todas las horas de las citas son en esta
     *     zona local. Se lee de config('appointments.timezone').
     * EN: Business time zone. All booking hours are in this local zone.
     */
    protected function zona(): string
    {
        return config('appointments.timezone', 'UTC');
    }

    // ── 2. Reference — código público único de la cita ──────────────────────

    /**
     * ES: Hook de modelo — al crear una cita sin referencia, le asignamos una única.
     * EN: Model hook — when creating a booking with no reference, assign a unique one.
     */
    protected static function booted(): void
    {
        static::creating(function (Appointment $cita) {
            if (empty($cita->reference)) {
                $cita->reference = self::generarReferencia();
            }
        });
    }

    /**
     * ES: Genera una referencia pública única y fácil de dictar, tipo
     *     "APT-K7P-3QX": dos grupos de tres y un alfabeto sin caracteres que se
     *     confunden al leer o al decirlos por teléfono (0/O, 1/I/L, 2/Z, 5/S,
     *     8/B, U/V). 23^6 ≈ 148 millones de combinaciones. Las referencias
     *     antiguas ("APT-K7P3Q") siguen valiendo: se buscan tal cual.
     * EN: Generates a unique public reference that is easy to read out, like
     *     "APT-K7P-3QX": two groups of three and an alphabet without characters
     *     that get mixed up when read or said over the phone (0/O, 1/I/L, 2/Z,
     *     5/S, 8/B, U/V). 23^6 ≈ 148 million combinations. Old references
     *     ("APT-K7P3Q") keep working: they are looked up as they are.
     */
    public static function generarReferencia(): string
    {
        $alfabeto = 'ACDEFGHJKMNPQRTWXY34679';

        // ES: Prefijo de marca configurable. EN: Configurable brand prefix (e.g. "APT").
        $prefijo = config('appointments.reference_prefix', 'APT').'-';

        do {
            $codigo = '';
            for ($i = 0; $i < 6; $i++) {
                // ES: Guion en medio: "K7P-3QX". EN: Hyphen in the middle: "K7P-3QX".
                $codigo .= ($i === 3 ? '-' : '').$alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
            $referencia = $prefijo.$codigo;
        } while (self::where('reference', $referencia)->exists()); // ES: garantiza unicidad · EN: guarantees uniqueness

        return $referencia;
    }

    // ── 3. Time helpers — inicio y fin en UTC ───────────────────────────────

    /**
     * ES: Inicio de la cita como Carbon en UTC (para los enlaces de calendario).
     *     Combina date + time (zona local del negocio) y lo convierte a UTC.
     * EN: Booking start as a UTC Carbon (for calendar links). Combines date +
     *     time (local business zone) and converts to UTC.
     */
    public function inicioUtc(): Carbon
    {
        return Carbon::parse($this->date->toDateString().' '.$this->time, $this->zona())
            ->setTimezone('UTC');
    }

    /**
     * ES: Fin de la cita como Carbon en UTC (inicio + duración en minutos).
     * EN: Booking end as a UTC Carbon (start + duration in minutes).
     */
    public function finUtc(): Carbon
    {
        return $this->inicioUtc()->addMinutes((int) $this->duration);
    }

    // ── 4. Calendar helpers — título/descripción/ubicación/URL ──────────────

    /**
     * ES: Ubicación legible para los calendarios: el enlace de Meet si es online
     *     y existe, "Presencial" en cualquier otro caso.
     * EN: Human-readable location for calendars: the Meet link if online and it
     *     exists, otherwise "Presencial".
     */
    public function ubicacionCalendario(): string
    {
        // ES: Textos en el idioma de la cita. Una cita online sin enlace de Meet
        //     (sin Google) sale «Online», no «Presencial».
        // EN: Texts in the booking's language. An online booking without a
        //     Meet link (no Google) shows "Online", not "In person".
        if ($this->modality === 'online') {
            return $this->google_meet_url ?: __('citas.cal_location_online', [], $this->idiomaCita());
        }

        return __('citas.cal_location_in_person', [], $this->idiomaCita());
    }

    /**
     * ES: Descripción para los calendarios: el asunto + (si online y hay enlace)
     *     la URL de la videollamada. Saltos "\n" reales; cada destino los escapa.
     * EN: Calendar description: the topic + (if online with a link) the videocall
     *     URL. Real "\n" line breaks; each target escapes them as needed.
     */
    public function descripcionCalendario(): string
    {
        $idioma = $this->idiomaCita();
        $lineas = [];

        // ES: Sin mensaje (es opcional) no sale la línea del asunto.
        // EN: Without a message (it is optional) the topic line is left out.
        if (trim((string) $this->message) !== '') {
            $lineas[] = __('citas.cal_desc_topic', ['text' => $this->message], $idioma);
        }
        if ($this->modality === 'online' && $this->google_meet_url) {
            $lineas[] = __('citas.cal_desc_video', ['url' => $this->google_meet_url], $idioma);
        }
        $lineas[] = __('citas.cal_desc_reference', ['ref' => $this->reference], $idioma);

        return implode("\n", $lineas);
    }

    /**
     * ES: Enlace FIRMADO de descarga del .ics (HMAC con APP_KEY). La firma va
     *     sobre la ruta relativa, así vale igual con o sin www y detrás del
     *     proxy, pero nadie puede fabricar el de otra cita con su referencia.
     *     Sin caducidad: el cliente puede necesitarlo hasta el día de la cita.
     * EN: SIGNED .ics download link (HMAC with APP_KEY). The signature covers
     *     the relative path, so it works with or without www and behind the
     *     proxy, yet nobody can forge another booking's link from its
     *     reference. No expiry: the client may need it until the meeting day.
     */
    public function urlIcs(): string
    {
        return url(URL::signedRoute('cita.ics', ['reference' => $this->reference], absolute: false));
    }

    /**
     * ES: Idioma de la cita (el del cliente al reservar) o el de por defecto.
     * EN: The booking's language (the client's when booking) or the default.
     */
    public function idiomaCita(): string
    {
        return $this->locale ?: (string) config('appointments.default_locale', 'es');
    }

    /**
     * ES: Título del evento de calendario: "<Marca> [APT-XXX-XXX]". Marca desde config.
     * EN: Calendar event title: "<Brand> [APT-XXX-XXX]". Brand from config.
     */
    public function tituloCalendario(): string
    {
        // ES: Nombre de marca desde config. EN: Brand name from config.
        $marca = config('appointments.brand.name');

        return "{$marca} [{$this->reference}]";
    }

    /**
     * ES: URL "Añadir a Google Calendar" con la cita precargada (action=TEMPLATE).
     *     Fechas en UTC (YYYYMMDDTHHMMSSZ); valores de texto urlencoded.
     * EN: "Add to Google Calendar" URL with the booking prefilled (action=TEMPLATE).
     *     Dates in UTC (YYYYMMDDTHHMMSSZ); text values urlencoded.
     */
    public function urlGoogleCalendar(): string
    {
        $fmt = 'Ymd\THis\Z'; // ES: formato fecha-hora UTC · EN: UTC date-time format Google expects

        $params = http_build_query([
            'action' => 'TEMPLATE',
            'text' => $this->tituloCalendario(),
            'dates' => $this->inicioUtc()->format($fmt).'/'.$this->finUtc()->format($fmt),
            'details' => $this->descripcionCalendario(),
            'location' => $this->ubicacionCalendario(),
        ]);

        return 'https://calendar.google.com/calendar/render?'.$params;
    }

    // ── 5. Attendees — emails de los asistentes extra ───────────────────────

    /**
     * ES: Devuelve los correos de los asistentes EXTRA (campo attendee_emails)
     *     como array limpio para Cc de los correos de la cita, de modo que los
     *     asistentes reciban también la solicitud / confirmación / rechazo, no
     *     solo la invitación de Google. Limpieza: separa por comas + trim,
     *     descarta inválidos, deduplica sin distinguir mayúsculas y EXCLUYE el
     *     email principal del cliente (ya va en "To"). Devuelve [] si no hay.
     * EN: Returns the EXTRA attendee emails (attendee_emails field) as a clean
     *     array to Cc in the booking emails, so invitees also receive the
     *     request / confirmation / rejection, not just the Google invite.
     *     Cleanup: split by comma + trim, drop invalid, dedupe case-insensitively,
     *     and EXCLUDE the client's main email (already in "To"). Returns [] if none.
     */
    public function emailsAsistentesExtra(): array
    {
        if (empty($this->attendee_emails)) {
            return [];
        }

        // ES: Email principal en minúsculas para comparar y excluirlo de la lista.
        // EN: Main email lowercased to compare and exclude it from the list.
        $principal = strtolower(trim((string) $this->email));
        $emails = [];

        foreach (explode(',', $this->attendee_emails) as $raw) {
            $email = trim($raw);
            // ES: Solo emails con formato válido y distintos del principal.
            // EN: Only valid emails different from the main one.
            if ($email !== ''
                && filter_var($email, FILTER_VALIDATE_EMAIL)
                && strtolower($email) !== $principal
            ) {
                // ES: clave en minúsculas = dedup insensible a mayúsculas; valor = original.
                // EN: lowercase key = case-insensitive dedupe; value = original.
                $emails[strtolower($email)] = $email;
            }
        }

        return array_values($emails);
    }
}
