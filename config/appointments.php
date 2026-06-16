<?php

/*
|==============================================================================
| APPOINTMENTS — Central configuration / Configuración central
|==============================================================================
| EN: This is the ONLY file you need to edit to make the booking system yours.
|     Branding, time zone, working hours, durations and modalities all live
|     here. Most values can also be overridden from your .env file.
| ES: Este es el ÚNICO archivo que necesitas editar para personalizar el
|     sistema de reservas. Marca, zona horaria, horario, duraciones y
|     modalidades viven aquí. Casi todo se puede sobreescribir desde tu .env.
|
| INDEX / ÍNDICE
|   1. BRANDING ............. brand name, owner, emails, links / marca y dueño
|   2. REFERENCE ........... public booking code prefix / prefijo del código
|   3. TIMEZONE ............ business time zone / zona horaria del negocio
|   4. SCHEDULE ............ working hours, slots, durations / horario y huecos
|   5. MODALITIES .......... online (Meet) / in-person / modalidades
|   6. PANEL ............... admin panel password / contraseña del panel
|   7. LOCALES ............. supported languages / idiomas soportados
|==============================================================================
*/

return [

    // ──────────────────────────────────────────────────────────────────────
    // 1. BRANDING / MARCA
    // EN: Who you are. Shown in emails, calendar events and the booking page.
    // ES: Quién eres. Aparece en emails, eventos de calendario y la página.
    // ──────────────────────────────────────────────────────────────────────
    'brand' => [
        // EN: Company / brand name. ES: Nombre de empresa / marca.
        'name'        => env('APPOINTMENTS_BRAND', 'Acme Inc.'),

        // EN: Person who attends the meetings. ES: Persona que atiende las citas.
        'owner_name'  => env('APPOINTMENTS_OWNER_NAME', 'Jane Doe'),

        // EN: Role/title shown in the email signature. ES: Cargo en la firma.
        'owner_role'  => env('APPOINTMENTS_OWNER_ROLE', 'Founder'),

        // EN: Email that RECEIVES the "new appointment" notifications and is used
        //     as Reply-To in the emails sent to clients.
        // ES: Email que RECIBE los avisos de "nueva cita" y se usa como Reply-To
        //     en los correos que se envían a los clientes.
        'owner_email' => env('APPOINTMENTS_OWNER_EMAIL', 'hello@example.com'),

        // EN: Public website (used in emails/calendar description).
        // ES: Web pública (se usa en los emails y la descripción del calendario).
        'website'     => env('APPOINTMENTS_WEBSITE', 'https://example.com'),

        // EN: Optional LinkedIn (or any) URL for the email signature. Empty = hidden.
        // ES: LinkedIn (o cualquier) URL opcional para la firma. Vacío = oculto.
        'linkedin'    => env('APPOINTMENTS_LINKEDIN', ''),
    ],

    // ──────────────────────────────────────────────────────────────────────
    // 2. REFERENCE / REFERENCIA
    // EN: Every booking gets a public code like "APT-K7P3Q". Change the prefix
    //     to your brand initials (e.g. "ACM"). Keep it short (2-4 letters).
    // ES: Cada cita recibe un código público tipo "APT-K7P3Q". Cambia el prefijo
    //     por las iniciales de tu marca (p. ej. "ACM"). Córtalo (2-4 letras).
    // ──────────────────────────────────────────────────────────────────────
    'reference_prefix' => env('APPOINTMENTS_REF_PREFIX', 'APT'),

    // ──────────────────────────────────────────────────────────────────────
    // 3. TIMEZONE / ZONA HORARIA
    // EN: Business time zone. ALL hours (slots, "today", calendar) are computed
    //     here, regardless of the server/app timezone. Use a valid PHP tz id.
    // ES: Zona horaria del negocio. TODAS las horas (huecos, "hoy", calendario)
    //     se calculan aquí, sin importar la zona del servidor. Usa un id válido.
    // ──────────────────────────────────────────────────────────────────────
    'timezone' => env('APPOINTMENTS_TIMEZONE', 'Europe/Madrid'),

    // ──────────────────────────────────────────────────────────────────────
    // 4. SCHEDULE / HORARIO
    // EN: Your availability rules. The booking calendar is built from these.
    // ES: Tus reglas de disponibilidad. El calendario se construye con esto.
    // ──────────────────────────────────────────────────────────────────────
    'schedule' => [
        // EN: How many working days ahead to offer. ES: Días laborables a ofrecer.
        'days_ahead'    => (int) env('APPOINTMENTS_DAYS_AHEAD', 14),

        // EN: Slot granularity in minutes (base unit). ES: Granularidad del hueco.
        'slot_minutes'  => 30,

        // EN: Opening time (first possible start). ES: Hora de apertura (1er inicio).
        'open'          => env('APPOINTMENTS_OPEN', '09:30'),

        // EN: Closing time (EXCLUSIVE — no meeting ends after this).
        // ES: Hora de cierre (EXCLUSIVA — ninguna cita termina después).
        'close'         => env('APPOINTMENTS_CLOSE', '18:00'),

        // EN: Meeting durations (minutes) the client can pick. Multiples of slot.
        // ES: Duraciones (minutos) que puede elegir el cliente. Múltiplos del hueco.
        'durations'     => [30, 60],

        // EN: Working weekdays, ISO format (1 = Mon … 7 = Sun). Default Mon-Fri.
        // ES: Días laborables, formato ISO (1 = lun … 7 = dom). Por defecto L-V.
        'weekdays'      => [1, 2, 3, 4, 5],

        // EN: Max extra attendees a client may invite. ES: Máx. asistentes extra.
        'max_attendees' => (int) env('APPOINTMENTS_MAX_ATTENDEES', 10),
    ],

    // ──────────────────────────────────────────────────────────────────────
    // 5. MODALITIES / MODALIDADES
    // EN: Offer online meetings (auto Google Meet link) and/or in-person ones.
    //     At least one must be true. If Google isn't configured, "online" still
    //     works but without an auto Meet link.
    // ES: Ofrece citas online (enlace de Google Meet automático) y/o presenciales.
    //     Al menos una debe ser true. Si Google no está configurado, "online"
    //     funciona igual pero sin enlace de Meet automático.
    // ──────────────────────────────────────────────────────────────────────
    'modalities' => [
        'online'    => (bool) env('APPOINTMENTS_MODALITY_ONLINE', true),
        'in_person' => (bool) env('APPOINTMENTS_MODALITY_IN_PERSON', true),
    ],

    // ──────────────────────────────────────────────────────────────────────
    // 6. PANEL / PANEL
    // EN: Single password to access the private admin panel at /panel.
    //     SET THIS in your .env — there is no default on purpose.
    // ES: Contraseña única para el panel privado en /panel.
    //     DEFÍNELA en tu .env — no hay valor por defecto a propósito.
    // ──────────────────────────────────────────────────────────────────────
    'panel' => [
        'password' => env('APPOINTMENTS_PANEL_PASSWORD'),
    ],

    // ──────────────────────────────────────────────────────────────────────
    // 7. LOCALES / IDIOMAS
    // EN: Languages the booking UI and emails support. The client's language is
    //     auto-detected and stored per booking. First one is the default.
    // ES: Idiomas que soportan la UI y los emails. El idioma del cliente se
    //     autodetecta y se guarda por cita. El primero es el de por defecto.
    // ──────────────────────────────────────────────────────────────────────
    'locales'        => ['es', 'en'],
    'default_locale' => 'es',

    // ──────────────────────────────────────────────────────────────────────
    // 8. THEME / TEMA
    // EN: Default colour scheme. The booking page and panel ship with BOTH a
    //     dark and a light theme; the visitor can switch with the sun/moon
    //     toggle (their choice is remembered in their browser). This only sets
    //     the DEFAULT shown before they choose:
    //       'dark'  → dark by default
    //       'light' → light by default (black text on white)
    //       'auto'  → follow the visitor's OS setting (prefers-color-scheme)
    // ES: Esquema de color por defecto. La página de reserva y el panel traen
    //     AMBOS temas, oscuro y claro; el visitante cambia con el botón sol/luna
    //     (su elección se recuerda en su navegador). Esto solo fija el que se
    //     muestra ANTES de que elija:
    //       'dark'  → oscuro por defecto
    //       'light' → claro por defecto (texto negro sobre blanco)
    //       'auto'  → seguir el ajuste del sistema del visitante
    // ──────────────────────────────────────────────────────────────────────
    'theme' => env('APPOINTMENTS_THEME', 'dark'),

];
