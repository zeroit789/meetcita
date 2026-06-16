{{--
    ============================================================================
    CONFIRMED EMAIL / EMAIL CONFIRMADO — Appointment confirmed / Cita confirmada
    ============================================================================
    EN: Email to the client: their appointment has been CONFIRMED. Branding from
        config('appointments.brand.*') + brand signature. Rendered in the CLIENT's
        language (active locale set with ->locale($appointment->locale)).

    ES: Email al cliente: su cita ha sido CONFIRMADA. Marca desde
        config('appointments.brand.*') + firma de marca. Se renderiza en el idioma
        del CLIENTE (locale activo fijado con ->locale($appointment->locale)).

    INDEX / ÍNDICE
        1. @php ....... formatted values + branding / valores formateados + marca
        2. header ..... brand header / cabecera de marca
        3. ref ........ booking reference / nº de cita
        4. summary .... appointment summary / resumen de la cita
        5. meet ....... Google Meet link / enlace de Google Meet
        6. calendar ... add-to-calendar buttons / botones añadir a calendario
        7. signature .. brand signature / firma de marca
    ============================================================================
--}}
@php
    // 1. EN: Branding from config (no personal data hardcoded).
    //    ES: Marca desde config (sin datos personales hardcodeados).
    $brandName = config('appointments.brand.name');
    $ownerName = config('appointments.brand.owner_name');

    // EN: Date in the active email locale (set by the sender with ->locale()).
    // ES: Fecha en el idioma activo del email (lo fija quien envía con ->locale()).
    $fechaCita = $appointment->date->locale(app()->getLocale())->isoFormat(__('emails.date_format'));

    // EN: Readable duration (translated). ES: Duración legible (traducida).
    $duracion = $appointment->duration == 60
        ? __('emails.duration_one_hour')
        : __('emails.duration_minutes', ['min' => $appointment->duration]);

    // EN: Modality text translated (online = video call, presencial = in person).
    // ES: Texto de la modalidad traducido (online = videollamada, presencial = en persona).
    $modalidadTxt = $appointment->modality === 'presencial'
        ? __('emails.modality_presencial')
        : __('emails.modality_online');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.confirmed_title_tag') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f7;">
    {{-- EN: Hidden preheader. ES: Preheader oculto. --}}
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#f5f5f7;">
        {{ __('emails.confirmed_preheader', ['date' => $fechaCita, 'time' => $appointment->time]) }}
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f5f7;">
        <tr><td align="center" style="padding:24px 12px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.06);">

                {{-- 2. ===== HEADER / CABECERA ===== --}}
                <tr>
                    <td style="background-color:#7c3aed; background-image:linear-gradient(135deg,#7c3aed 0%,#9333ea 100%); padding:28px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr><td style="font-family:Arial,sans-serif; color:#ffffff; font-size:20px; font-weight:bold;">{{ $brandName }} <span style="font-weight:normal; opacity:0.85;">— {{ $ownerName }}</span></td></tr>
                            <tr><td style="font-family:Arial,sans-serif; color:#ede9fe; font-size:13px; padding-top:6px;">{{ __('emails.header_confirmed') }}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr><td style="height:4px; background-color:#6d28d9; line-height:4px; font-size:0;">&nbsp;</td></tr>

                {{-- ===== BODY / CUERPO ===== --}}
                <tr>
                    <td style="padding:32px;">
                        <h1 style="margin:0 0 12px 0; font-family:Arial,sans-serif; color:#15803d; font-size:22px; font-weight:bold;">{{ __('emails.confirmed_heading') }}</h1>
                        {{-- EN: The name is inserted bold-purple inside the translated greeting.
                             ES: El nombre se inserta en negrita morada dentro del saludo traducido. --}}
                        <p style="margin:0 0 20px 0; font-family:Arial,sans-serif; color:#374151; font-size:15px; line-height:1.65;">
                            {!! __('emails.confirmed_intro', ['name' => '<strong style="color:#7c3aed;">' . e($appointment->name) . '</strong>']) !!}
                        </p>

                        {{-- 3. ===== BOOKING REFERENCE / Nº DE CITA ===== --}}
                        @if($appointment->reference)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px 0;">
                                <tr><td style="padding:14px 18px; background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; font-family:Arial,sans-serif;">
                                    <span style="color:#6b7280; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">{{ __('emails.confirmed_ref_label') }}</span>
                                    <span style="color:#15803d; font-size:18px; font-weight:bold; font-family:'Courier New',monospace; margin-left:6px;">{{ $appointment->reference }}</span>
                                </td></tr>
                            </table>
                        @endif

                        {{-- 4. ===== APPOINTMENT SUMMARY / RESUMEN ===== --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #ece9f5; border-radius:10px; overflow:hidden;">
                            <tr>
                                <td width="35%" style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.label_date') }}</td>
                                <td style="padding:14px 16px; font-family:Arial,sans-serif; color:#1f2937; font-size:15px; border-bottom:1px solid #ece9f5;">{{ ucfirst($fechaCita) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.label_time') }}</td>
                                <td style="padding:14px 16px; font-family:Arial,sans-serif; color:#1f2937; font-size:15px; font-weight:bold; border-bottom:1px solid #ece9f5;">{{ $appointment->time }}</td>
                            </tr>
                            <tr>
                                <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.label_duration') }}</td>
                                <td style="padding:14px 16px; font-family:Arial,sans-serif; color:#1f2937; font-size:15px; border-bottom:1px solid #ece9f5;">{{ $duracion }}</td>
                            </tr>
                            {{-- EN: Modality (online video call or in person). ES: Modalidad (online videollamada o presencial). --}}
                            <tr>
                                <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px;">{{ __('emails.label_modality') }}</td>
                                <td style="padding:14px 16px; font-family:Arial,sans-serif; color:#1f2937; font-size:15px;">{{ $modalidadTxt }}</td>
                            </tr>
                        </table>

                        {{-- 5. ===== GOOGLE MEET LINK / ENLACE DE GOOGLE MEET =====
                             EN: Only if the appointment is online and the link is already generated.
                             ES: Solo si la cita es online y el enlace ya se ha generado. --}}
                        @if($appointment->modality === 'online' && $appointment->google_meet_url)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0 0;">
                                <tr>
                                    <td style="padding:16px 18px; background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; font-family:Arial,sans-serif;">
                                        <p style="margin:0 0 8px 0; color:#15803d; font-size:14px; font-weight:bold;">{{ __('emails.confirmed_meet_title') }}</p>
                                        <a href="{{ $appointment->google_meet_url }}" style="display:inline-block; background-color:#15803d; color:#ffffff; font-size:15px; font-weight:bold; text-decoration:none; padding:10px 18px; border-radius:8px;">{{ __('emails.confirmed_meet_button') }}</a>
                                        <p style="margin:8px 0 0 0; color:#6b7280; font-size:12px; word-break:break-all;">{{ $appointment->google_meet_url }}</p>
                                    </td>
                                </tr>
                            </table>
                        @endif

                        {{-- 6. ===== ADD TO CALENDAR / AÑADIR A CALENDARIO =====
                             EN: Two buttons (Google + Outlook/Apple .ics). $googleCalendarUrl and $icsUrl
                                 are passed by the AppointmentConfirmed mailable. Email-safe inline styles.
                             ES: Dos botones (Google + Outlook/Apple .ics). $googleCalendarUrl y $icsUrl los
                                 pasa el Mailable AppointmentConfirmed. Estilos inline email-safe. --}}
                        @isset($googleCalendarUrl)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0 0;">
                                <tr>
                                    <td style="padding:18px 20px; background-color:#faf9fd; border:1px solid #ece9f5; border-radius:10px; font-family:Arial,sans-serif;">
                                        <p style="margin:0 0 12px 0; color:#1f2937; font-size:15px; font-weight:bold;">{{ __('emails.calendar_title') }}</p>
                                        {{-- EN: Buttons side by side. ES: Botones uno al lado del otro. --}}
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                {{-- EN: Google Calendar / ES: Google Calendar --}}
                                                <td style="padding-right:10px;">
                                                    <a href="{{ $googleCalendarUrl }}" style="display:inline-block; background-color:#7c3aed; color:#ffffff; font-size:14px; font-weight:bold; text-decoration:none; padding:10px 16px; border-radius:8px;">{{ __('emails.calendar_google') }}</a>
                                                </td>
                                                {{-- EN: Outlook / Apple (.ics download) / ES: Outlook / Apple (descarga .ics) --}}
                                                <td>
                                                    <a href="{{ $icsUrl }}" style="display:inline-block; background-color:#ffffff; color:#7c3aed; font-size:14px; font-weight:bold; text-decoration:none; padding:10px 16px; border:1px solid #ddd6fe; border-radius:8px;">{{ __('emails.calendar_outlook') }}</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        @endisset

                        <p style="margin:24px 0 0 0; font-family:Arial,sans-serif; color:#374151; font-size:15px; line-height:1.65;">
                            {{ __('emails.confirmed_outro') }}
                        </p>

                        {{-- 7. ===== BRAND SIGNATURE / FIRMA DE MARCA ===== --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
                            <tr><td>@include('emails.partials.signature')</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
