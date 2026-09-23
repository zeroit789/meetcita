{{--
    ============================================================================
    OWNER EMAIL / EMAIL AL DUEÑO — New appointment request / Nueva solicitud
    ============================================================================
    ES: Email HTML para el DUEÑO del negocio: aviso de nueva solicitud de cita.
        Diseño profesional responsive con tablas + CSS inline (estándar email).
        Muestra TODOS los datos de la cita de un vistazo. La marca está desacoplada
        a config('appointments.brand.*'); se renderiza en el idioma por defecto del
        dueño (config 'appointments.default_locale') vía __('emails.owner_*', ..., $loc).

    EN: HTML email for the business OWNER: notifies a new appointment request.
        Professional responsive design with tables + inline CSS (email standard).
        Shows ALL the booking data at a glance. Branding is decoupled to
        config('appointments.brand.*'); rendered in the owner's default locale
        (config 'appointments.default_locale') via __('emails.owner_*', ..., $loc).

    INDEX / ÍNDICE
        1. bloque PHP ........ formatted values + branding / valores formateados + marca
        2. header ...... brand header / cabecera de marca
        3. details ..... appointment data table / tabla de datos de la cita
        4. message ..... client's message / mensaje del cliente
        5. cta ......... reply button / botón de respuesta
        6. footer ...... brand contact footer / pie de contacto de marca
    ============================================================================
--}}
@php
    // 1. ES: Preparamos los valores formateados una sola vez (sin repetir lógica).
    //    EN: Prepare formatted values once (no logic repeated in the HTML).

    // ES: Idioma del dueño = el idioma por defecto del negocio. Lo usa este email.
    // EN: Owner's locale = the business default locale. Owner emails use it.
    $loc = config('appointments.default_locale', 'es');

    // ES: Marca desde config (sin datos personales hardcodeados).
    // EN: Branding from config (no personal data hardcoded).
    $brandName  = config('appointments.brand.name');
    $ownerName  = config('appointments.brand.owner_name');
    $website    = config('appointments.brand.website');
    $ownerEmail = config('appointments.brand.owner_email');
    $websiteLabel = preg_replace('#^https?://#', '', (string) $website);

    // ES: Fecha larga en el idioma del dueño (ej: "lunes 16 de junio de 2026").
    // EN: Long date in the owner's locale (e.g. "lunes 16 de junio de 2026").
    $fechaCita = $appointment->date->locale($loc)->isoFormat(__('emails.date_format', [], $loc));

    // ES: Duración legible: 60 min → "1 hora", resto → "30 min".
    // EN: Readable duration: 60 min → "1 hour", otherwise "30 min".
    $duracion = $appointment->duration == 60
        ? __('emails.duration_one_hour', [], $loc)
        : __('emails.duration_minutes', ['min' => $appointment->duration], $loc);

    // ES: Estado con primera letra en mayúscula (ej: "Pendiente").
    // EN: Status with first letter uppercased (e.g. "Pending"/"Pendiente").
    $estado = ucfirst($appointment->status);

    // ES: Fecha en la que el cliente envió la solicitud (creación del registro).
    // EN: Date the client submitted the request (record creation time).
    $fechaSolicitud = $appointment->created_at
        ? $appointment->created_at->locale($loc)->isoFormat(__('emails.owner_requested_at_format', [], $loc))
        : '—';

    // ES: Teléfono: si no hay, mostramos un guion en vez de "null".
    // EN: Phone: show a dash instead of "null" if empty.
    $telefono = $appointment->phone ?: '—';
@endphp
<!DOCTYPE html>
<html lang="{{ $loc }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('emails.owner_title_tag', [], $loc) }}</title>
</head>
{{-- ES: Fondo gris claro neutro; tipografía sistema para máxima compatibilidad.
     EN: Neutral light-gray background; system font for max compatibility. --}}
<body style="margin:0; padding:0; background-color:#f5f5f7; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">

    {{-- ES: Preheader oculto (texto de resumen previo en algunos clientes).
         EN: Hidden preheader (preview text in some clients). --}}
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#f5f5f7;">
        {{ __('emails.owner_preheader', ['name' => $appointment->name, 'date' => $fechaCita, 'time' => $appointment->time], $loc) }}
    </div>

    {{-- ES: Tabla exterior: centra el contenido y aplica el fondo en todo el ancho.
         EN: Outer table: centers content and applies the full-width background. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f5f7;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                {{-- ES: Tarjeta blanca central de 600px. EN: Central 600px white card. --}}
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.06);">

                    {{-- 2. ===== HEADER / CABECERA (brand + purple accent) ===== --}}
                    <tr>
                        <td style="background-color:#7c3aed; background-image:linear-gradient(135deg,#7c3aed 0%,#9333ea 100%); padding:28px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:20px; font-weight:bold; letter-spacing:0.3px;">
                                        {{ $brandName }}
                                        <span style="font-weight:normal; opacity:0.85;">— {{ $ownerName }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family:Arial,Helvetica,sans-serif; color:#ede9fe; font-size:13px; padding-top:6px;">
                                        {{ __('emails.owner_header', [], $loc) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ES: Banda fina de acento. EN: Thin accent band below the header. --}}
                    <tr><td style="height:4px; background-color:#6d28d9; line-height:4px; font-size:0;">&nbsp;</td></tr>

                    {{-- ===== BODY / CUERPO ===== --}}
                    <tr>
                        <td style="padding:32px;">

                            {{-- ES: Título. EN: Title. --}}
                            <h1 style="margin:0 0 8px 0; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:22px; font-weight:bold;">
                                {{ __('emails.owner_heading', [], $loc) }}
                            </h1>
                            <p style="margin:0 0 24px 0; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:15px; line-height:1.6;">
                                {!! __('emails.owner_intro', ['name' => '<strong style="color:#7c3aed;">' . e($appointment->name) . '</strong>'], $loc) !!}
                            </p>

                            {{-- 3. ===== DETAILS TABLE / TABLA DE DETALLES (label + value) ===== --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #ece9f5; border-radius:10px; overflow:hidden;">

                                {{-- ES: Cliente / EN: Client --}}
                                <tr>
                                    <td width="40%" style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.owner_label_client', [], $loc) }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; border-bottom:1px solid #ece9f5;">{{ $appointment->name }}</td>
                                </tr>
                                {{-- ES: Email / EN: Email --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.owner_label_email', [], $loc) }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; font-size:15px; border-bottom:1px solid #ece9f5;"><a href="mailto:{{ $appointment->email }}" style="color:#7c3aed; text-decoration:none;">{{ $appointment->email }}</a></td>
                                </tr>
                                {{-- ES: Teléfono / EN: Phone --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.owner_label_phone', [], $loc) }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; border-bottom:1px solid #ece9f5;">{{ $telefono }}</td>
                                </tr>
                                {{-- ES: Fecha / EN: Date --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.label_date', [], $loc) }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; border-bottom:1px solid #ece9f5;">{{ ucfirst($fechaCita) }}</td>
                                </tr>
                                {{-- ES: Hora / EN: Time --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.label_time', [], $loc) }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; font-weight:bold; border-bottom:1px solid #ece9f5;">{{ $appointment->time }}</td>
                                </tr>
                                {{-- ES: Duración / EN: Duration --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.label_duration', [], $loc) }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; border-bottom:1px solid #ece9f5;">{{ $duracion }}</td>
                                </tr>
                                {{-- ES: Personas asistentes / EN: Attendees --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.owner_label_attendees', [], $loc) }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; border-bottom:1px solid #ece9f5;">{{ $appointment->attendees }}</td>
                                </tr>
                                {{-- ES: Badge de estado / EN: Status badge --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.owner_label_status', [], $loc) }}</td>
                                    <td style="padding:14px 16px; border-bottom:1px solid #ece9f5;">
                                        <span style="display:inline-block; background-color:#ede9fe; color:#6d28d9; font-family:Arial,Helvetica,sans-serif; font-size:13px; font-weight:bold; padding:4px 12px; border-radius:999px;">{{ $estado }}</span>
                                    </td>
                                </tr>
                                {{-- ES: Solicitada el / EN: Requested at --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px;">{{ __('emails.owner_label_requested_at', [], $loc) }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px;">{{ $fechaSolicitud }}</td>
                                </tr>
                            </table>

                            {{-- 4. ===== CLIENT'S MESSAGE / MENSAJE DEL CLIENTE ===== --}}
                            <p style="margin:28px 0 10px 0; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px;">
                                {{ __('emails.owner_message_title', [], $loc) }}
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding:16px 18px; background-color:#faf9fd; border-left:4px solid #7c3aed; border-radius:0 8px 8px 0; font-family:Arial,Helvetica,sans-serif; color:#374151; font-size:15px; line-height:1.65;">
                                        {{ $appointment->message }}
                                    </td>
                                </tr>
                            </table>

                            {{-- 5. ===== REPLY BUTTON / BOTÓN DE RESPUESTA ===== --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
                                <tr>
                                    <td align="center">
                                        <a href="mailto:{{ $appointment->email }}" style="display:inline-block; background-color:#7c3aed; color:#ffffff; font-family:Arial,Helvetica,sans-serif; font-size:15px; font-weight:bold; text-decoration:none; padding:14px 32px; border-radius:8px;">
                                            {{ __('emails.owner_reply_button', [], $loc) }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0 0; font-family:Arial,Helvetica,sans-serif; color:#9ca3af; font-size:13px; line-height:1.6; text-align:center;">
                                {{ __('emails.owner_reminder', [], $loc) }}
                            </p>

                        </td>
                    </tr>

                    {{-- 6. ===== FOOTER / PIE ===== --}}
                    <tr>
                        <td style="background-color:#faf9fd; padding:24px 32px; border-top:1px solid #ece9f5;">
                            <p style="margin:0; font-family:Arial,Helvetica,sans-serif; color:#9ca3af; font-size:12px; line-height:1.7; text-align:center;">
                                <strong style="color:#6b7280;">{{ $ownerName }}</strong> · {{ $brandName }}<br>
                                <a href="mailto:{{ $ownerEmail }}" style="color:#7c3aed; text-decoration:none;">{{ $ownerEmail }}</a> ·
                                <a href="{{ $website }}" style="color:#7c3aed; text-decoration:none;">{{ $websiteLabel }}</a>
                            </p>
                        </td>
                    </tr>

                </table>
                {{-- /card / /tarjeta --}}

            </td>
        </tr>
    </table>

</body>
</html>
