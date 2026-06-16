{{--
    ============================================================================
    REJECTED EMAIL / EMAIL RECHAZADO — Appointment declined / Cita rechazada
    ============================================================================
    EN: Email to the client: the owner cannot take the appointment. Includes the
        reason ($motivo) + brand signature. Branding from config('appointments.brand.*').
        Rendered in the CLIENT's language (active locale set with ->locale($appointment->locale)).

    ES: Email al cliente: el dueño no puede atender la cita. Incluye el motivo
        ($motivo) + firma de marca. Marca desde config('appointments.brand.*').
        Se renderiza en el idioma del CLIENTE (locale activo fijado con ->locale($appointment->locale)).

    INDEX / ÍNDICE
        1. @php ....... formatted values + branding / valores formateados + marca
        2. header ..... brand header / cabecera de marca
        3. intro ...... greeting + date/time + reference / saludo + fecha/hora + referencia
        4. reason ..... rejection reason / motivo del rechazo
        5. outro ...... re-book link / enlace para volver a reservar
        6. signature .. brand signature / firma de marca
    ============================================================================
--}}
@php
    // 1. EN: Branding from config (no personal data hardcoded).
    //    ES: Marca desde config (sin datos personales hardcodeados).
    $brandName = config('appointments.brand.name');
    $ownerName = config('appointments.brand.owner_name');
    $website   = config('appointments.brand.website');

    // EN: Re-book URL = brand website + the public booking path (/citas).
    //     Display label without scheme (e.g. "example.com/citas").
    // ES: URL para volver a reservar = web de marca + ruta pública de reservas (/citas).
    //     Etiqueta sin esquema (ej: "example.com/citas").
    $bookingUrl   = rtrim((string) $website, '/') . '/citas';
    $bookingLabel = preg_replace('#^https?://#', '', $bookingUrl);

    // EN: Date in the active email locale (set by the sender with ->locale()).
    // ES: Fecha en el idioma activo del email (lo fija quien envía con ->locale()).
    $fechaCita = $appointment->date->locale(app()->getLocale())->isoFormat(__('emails.date_format'));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.rejected_title_tag') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f7;">
    {{-- EN: Hidden preheader. ES: Preheader oculto. --}}
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#f5f5f7;">
        {{ __('emails.rejected_preheader', ['date' => $fechaCita]) }}
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f5f7;">
        <tr><td align="center" style="padding:24px 12px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.06);">

                {{-- 2. ===== HEADER / CABECERA ===== --}}
                <tr>
                    <td style="background-color:#7c3aed; background-image:linear-gradient(135deg,#7c3aed 0%,#9333ea 100%); padding:28px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr><td style="font-family:Arial,sans-serif; color:#ffffff; font-size:20px; font-weight:bold;">{{ $brandName }} <span style="font-weight:normal; opacity:0.85;">— {{ $ownerName }}</span></td></tr>
                            <tr><td style="font-family:Arial,sans-serif; color:#ede9fe; font-size:13px; padding-top:6px;">{{ __('emails.header_appointment') }}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr><td style="height:4px; background-color:#6d28d9; line-height:4px; font-size:0;">&nbsp;</td></tr>

                {{-- ===== BODY / CUERPO ===== --}}
                <tr>
                    <td style="padding:32px;">
                        <h1 style="margin:0 0 12px 0; font-family:Arial,sans-serif; color:#1f2937; font-size:22px; font-weight:bold;">{{ __('emails.rejected_heading') }}</h1>
                        {{-- 3. EN: Translated greeting: name, date and time are injected as variables.
                                 The reference (optional) is appended inline only if it exists.
                             ES: Saludo traducido: el nombre, la fecha y la hora se inyectan como variables.
                                 La referencia (opcional) se añade en línea solo si existe. --}}
                        <p style="margin:0 0 16px 0; font-family:Arial,sans-serif; color:#374151; font-size:15px; line-height:1.65;">
                            {!! __('emails.rejected_intro', [
                                'name' => '<strong style="color:#7c3aed;">' . e($appointment->name) . '</strong>',
                                'date' => e($fechaCita),
                                'time' => e($appointment->time),
                            ]) !!}@if($appointment->reference) {!! __('emails.rejected_ref_inline', ['reference' => '<strong style="font-family:\'Courier New\',monospace; color:#7c3aed;">' . e($appointment->reference) . '</strong>']) !!}@endif.
                        </p>

                        {{-- 4. ===== REJECTION REASON / MOTIVO ===== --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px 0;">
                            <tr>
                                <td style="padding:16px 18px; background-color:#faf9fd; border-left:4px solid #7c3aed; border-radius:0 8px 8px 0; font-family:Arial,sans-serif; color:#374151; font-size:15px; line-height:1.65;">
                                    {!! nl2br(e($motivo)) !!}
                                </td>
                            </tr>
                        </table>

                        {{-- 5. ===== RE-BOOK LINK / ENLACE PARA VOLVER A RESERVAR =====
                             EN: The booking link is injected already built as the :link variable.
                             ES: El enlace de reserva se inyecta ya montado como variable :link. --}}
                        <p style="margin:0 0 8px 0; font-family:Arial,sans-serif; color:#374151; font-size:15px; line-height:1.65;">
                            {!! __('emails.rejected_outro', [
                                'link' => '<a href="' . e($bookingUrl) . '" style="color:#7c3aed; text-decoration:none;">' . e($bookingLabel) . '</a>',
                            ]) !!}
                        </p>

                        {{-- 6. ===== BRAND SIGNATURE / FIRMA DE MARCA ===== --}}
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
