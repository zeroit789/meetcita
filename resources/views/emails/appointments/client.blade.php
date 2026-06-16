{{--
    ============================================================================
    CLIENT EMAIL / EMAIL AL CLIENTE — Request received / Solicitud recibida
    ============================================================================
    EN: Confirmation HTML email for the CLIENT: their request was received.
        Professional, warm tone. Booking summary + what happens next. Branding is
        decoupled to config('appointments.brand.*'). Rendered in the CLIENT's
        language: the active locale (set by the sender with ->locale($appointment->locale)).

    ES: Email HTML de acuse de recibo para el CLIENTE: su solicitud se ha recibido.
        Tono profesional y cercano. Resumen de la cita + qué pasa ahora. La marca
        está desacoplada a config('appointments.brand.*'). Se renderiza en el idioma
        del CLIENTE: el locale activo (fijado por quien envía con ->locale($appointment->locale)).

    INDEX / ÍNDICE
        1. @php ........ formatted values + branding / valores formateados + marca
        2. header ...... brand header / cabecera de marca
        3. ref ......... booking reference / nº de cita
        4. summary ..... appointment summary / resumen de la cita
        5. whatnow ..... what happens now / qué pasa ahora
        6. calendar .... add-to-calendar buttons / botones añadir a calendario
        7. signature ... brand signature / firma de marca
        8. footer ...... contact footer / pie de contacto
    ============================================================================
--}}
@php
    // 1. EN: Formatted values once. ES: Valores formateados una sola vez.

    // EN: Branding from config (no personal data hardcoded).
    // ES: Marca desde config (sin datos personales hardcodeados).
    $brandName    = config('appointments.brand.name');
    $ownerName    = config('appointments.brand.owner_name');
    $website      = config('appointments.brand.website');
    $ownerEmail   = config('appointments.brand.owner_email');
    $websiteLabel = preg_replace('#^https?://#', '', (string) $website);

    // EN: Long date in the active email locale (set by the sender via ->locale()).
    // ES: Fecha larga en el idioma activo del email (lo fija quien envía con ->locale()).
    $fechaCita = $appointment->date->locale(app()->getLocale())->isoFormat(__('emails.date_format'));

    // EN: Readable duration (translated). ES: Duración legible (traducida).
    $duracion = $appointment->duration == 60
        ? __('emails.duration_one_hour')
        : __('emails.duration_minutes', ['min' => $appointment->duration]);

    // EN: Readable modality (online = video call, presencial = in person).
    // ES: Modalidad legible (online = videollamada, presencial = en persona).
    $modalidadTxt = $appointment->modality === 'presencial'
        ? __('emails.modality_presencial')
        : __('emails.modality_online');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('emails.client_title_tag') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f7; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">

    {{-- EN: Hidden preheader. ES: Preheader oculto. --}}
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#f5f5f7;">
        {{ __('emails.client_preheader', ['date' => $fechaCita, 'time' => $appointment->time]) }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f5f7;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                {{-- EN: Central card. ES: Tarjeta central. --}}
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.06);">

                    {{-- 2. ===== HEADER / CABECERA ===== --}}
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
                                        {{ __('emails.brand_tagline') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr><td style="height:4px; background-color:#6d28d9; line-height:4px; font-size:0;">&nbsp;</td></tr>

                    {{-- ===== BODY / CUERPO ===== --}}
                    <tr>
                        <td style="padding:32px;">

                            <h1 style="margin:0 0 8px 0; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:22px; font-weight:bold;">
                                {{ __('emails.client_heading') }}
                            </h1>
                            {{-- EN: The name is inserted bold-purple inside the translated greeting.
                                 ES: El nombre se inserta en negrita morada dentro del saludo traducido. --}}
                            <p style="margin:0 0 20px 0; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:15px; line-height:1.6;">
                                {!! __('emails.client_intro', ['name' => '<strong style="color:#7c3aed;">' . e($appointment->name) . '</strong>']) !!}
                            </p>

                            {{-- 3. ===== BOOKING REFERENCE / Nº DE CITA ===== --}}
                            @if($appointment->reference)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px 0;">
                                    <tr>
                                        <td style="padding:14px 18px; background-color:#f5f3ff; border:1px solid #ddd6fe; border-radius:10px; font-family:Arial,Helvetica,sans-serif;">
                                            <span style="color:#6b7280; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">{{ __('emails.client_ref_label') }}</span>
                                            <span style="color:#7c3aed; font-size:18px; font-weight:bold; font-family:'Courier New',monospace; margin-left:6px;">{{ $appointment->reference }}</span>
                                            <div style="color:#9ca3af; font-size:12px; padding-top:4px;">{{ __('emails.client_ref_hint') }}</div>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            {{-- 4. ===== APPOINTMENT SUMMARY / RESUMEN DE LA CITA ===== --}}
                            <p style="margin:0 0 12px 0; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px;">
                                {{ __('emails.client_summary_title') }}
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #ece9f5; border-radius:10px; overflow:hidden;">
                                {{-- EN: Date / ES: Fecha --}}
                                <tr>
                                    <td width="35%" style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.label_date') }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; border-bottom:1px solid #ece9f5;">{{ ucfirst($fechaCita) }}</td>
                                </tr>
                                {{-- EN: Time / ES: Hora --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.label_time') }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; font-weight:bold; border-bottom:1px solid #ece9f5;">{{ $appointment->time }}</td>
                                </tr>
                                {{-- EN: Duration / ES: Duración --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px; border-bottom:1px solid #ece9f5;">{{ __('emails.label_duration') }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; border-bottom:1px solid #ece9f5;">{{ $duracion }}</td>
                                </tr>
                                {{-- EN: Modality (online video call or in person). The Meet link is sent
                                     in the confirmation email.
                                     ES: Modalidad (online videollamada o presencial). El enlace de Meet
                                     se enviará en el email de confirmación. --}}
                                <tr>
                                    <td style="padding:14px 16px; background-color:#faf9fd; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:0.4px;">{{ __('emails.label_modality') }}</td>
                                    <td style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px;">{{ $modalidadTxt }}</td>
                                </tr>
                            </table>

                            {{-- 5. ===== WHAT HAPPENS NOW / QUÉ PASA AHORA ===== --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;">
                                <tr>
                                    <td style="padding:18px 20px; background-color:#faf9fd; border-left:4px solid #7c3aed; border-radius:0 8px 8px 0;">
                                        <p style="margin:0 0 6px 0; font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; font-weight:bold;">
                                            {{ __('emails.client_whatnow_title') }}
                                        </p>
                                        <p style="margin:0; font-family:Arial,Helvetica,sans-serif; color:#4b5563; font-size:15px; line-height:1.65;">
                                            {{ __('emails.client_whatnow_text') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- 6. ===== ADD TO CALENDAR (tentative) / AÑADIR A CALENDARIO (tentativo) =====
                                 EN: Two buttons Google + Outlook/Apple. $googleCalendarUrl and $icsUrl are
                                     passed by the AppointmentConfirmationToClient mailable. The booking is
                                     not confirmed yet; it lets the client pencil it in.
                                 ES: Dos botones Google + Outlook/Apple. $googleCalendarUrl y $icsUrl los pasa
                                     el Mailable AppointmentConfirmationToClient. La cita aún no está confirmada;
                                     sirve para que el cliente la apunte. --}}
                            @isset($googleCalendarUrl)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;">
                                    <tr>
                                        <td style="padding:18px 20px; background-color:#faf9fd; border:1px solid #ece9f5; border-radius:10px; font-family:Arial,Helvetica,sans-serif;">
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

                            <p style="margin:28px 0 0 0; font-family:Arial,Helvetica,sans-serif; color:#374151; font-size:15px; line-height:1.6;">
                                {{ __('emails.client_signoff') }}
                            </p>

                            {{-- 7. ===== BRAND SIGNATURE / FIRMA DE MARCA ===== --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:14px;">
                                <tr><td>@include('emails.partials.signature')</td></tr>
                            </table>

                        </td>
                    </tr>

                    {{-- 8. ===== CONTACT FOOTER / PIE CON DATOS DE CONTACTO ===== --}}
                    <tr>
                        <td style="background-color:#faf9fd; padding:24px 32px; border-top:1px solid #ece9f5;">
                            <p style="margin:0; font-family:Arial,Helvetica,sans-serif; color:#9ca3af; font-size:12px; line-height:1.7; text-align:center;">
                                <strong style="color:#6b7280;">{{ $ownerName }}</strong> · {{ __('emails.footer_role') }}<br>
                                <a href="mailto:{{ $ownerEmail }}" style="color:#7c3aed; text-decoration:none;">{{ $ownerEmail }}</a> ·
                                <a href="{{ $website }}" style="color:#7c3aed; text-decoration:none;">{{ $websiteLabel }}</a>
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
