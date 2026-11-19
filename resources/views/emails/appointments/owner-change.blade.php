{{--
    ============================================================================
    OWNER CHANGE EMAIL / EMAIL AL DUEÑO — the client cancelled or rescheduled
    ============================================================================
    ES: Aviso al DUEÑO: el cliente ha cancelado su cita o la ha cambiado de día
        u hora con su enlace de «gestionar mi cita». En el idioma por defecto
        del negocio. Tablas + estilos en línea (aptos para correo).
    EN: Notice to the OWNER: the client cancelled their booking or moved it to
        another day or time with their "manage my booking" link. In the
        business default language. Tables + inline styles (email-safe).

    INDEX / ÍNDICE
        1. bloque PHP ... formatted values / valores formateados
        2. header ....... brand header / cabecera de marca
        3. summary ...... what changed + booking / qué cambió + cita
        4. cta .......... link to the panel / enlace al panel
    ============================================================================
--}}
@php
    // 1. ES: Idioma del dueño y valores formateados. EN: Owner locale and formatted values.
    $loc = config('appointments.default_locale', 'es');
    $brandName = config('appointments.brand.name');
    $cancelada = $cambio === 'cancelada';
    $fechaCita = $appointment->date->locale($loc)->isoFormat(__('emails.date_format', [], $loc));
    $fechaAntes = $fechaAnterior
        ? \Carbon\Carbon::parse($fechaAnterior)->locale($loc)->isoFormat(__('emails.date_format', [], $loc))
        : null;
@endphp
<!DOCTYPE html>
<html lang="{{ $loc }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __($cancelada ? 'emails.owner_change_cancelled_heading' : 'emails.owner_change_rescheduled_heading', [], $loc) }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f7;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f5f7;">
        <tr><td align="center" style="padding:24px 12px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:14px; overflow:hidden;">

                {{-- 2. ===== HEADER / CABECERA ===== --}}
                <tr>
                    <td style="background-color:#1f2937; padding:24px 32px; font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:18px; font-weight:bold;">
                        {{ $brandName }}
                    </td>
                </tr>

                {{-- 3. ===== SUMMARY / RESUMEN ===== --}}
                <tr>
                    <td style="padding:28px 32px; font-family:Arial,Helvetica,sans-serif;">
                        <h1 style="margin:0 0 12px 0; color:{{ $cancelada ? '#b91c1c' : '#1d4ed8' }}; font-size:20px;">
                            {{ __($cancelada ? 'emails.owner_change_cancelled_heading' : 'emails.owner_change_rescheduled_heading', [], $loc) }}
                        </h1>
                        <p style="margin:0 0 18px 0; color:#374151; font-size:15px; line-height:1.6;">
                            {{ __($cancelada ? 'emails.owner_change_cancelled_intro' : 'emails.owner_change_rescheduled_intro', ['name' => $appointment->name], $loc) }}
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb; border-radius:10px;">
                            <tr>
                                <td width="35%" style="padding:12px 16px; color:#6b7280; font-size:13px; font-weight:bold; border-bottom:1px solid #e5e7eb;">{{ __('emails.owner_label_ref', [], $loc) }}</td>
                                <td style="padding:12px 16px; color:#111827; font-size:15px; font-family:'Courier New',monospace; font-weight:bold; border-bottom:1px solid #e5e7eb;">{{ $appointment->reference }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px; color:#6b7280; font-size:13px; font-weight:bold; border-bottom:1px solid #e5e7eb;">{{ __('emails.owner_label_client', [], $loc) }}</td>
                                <td style="padding:12px 16px; color:#111827; font-size:15px; border-bottom:1px solid #e5e7eb;">{{ $appointment->name }}</td>
                            </tr>
                            {{-- ES: Reprogramada: antes y ahora. Cancelada: el día que se libera.
                                 EN: Rescheduled: before and now. Cancelled: the day that is freed. --}}
                            @if($fechaAntes)
                                <tr>
                                    <td style="padding:12px 16px; color:#6b7280; font-size:13px; font-weight:bold; border-bottom:1px solid #e5e7eb;">{{ __('emails.owner_change_before', [], $loc) }}</td>
                                    <td style="padding:12px 16px; color:#6b7280; font-size:15px; text-decoration:line-through; border-bottom:1px solid #e5e7eb;">{{ ucfirst($fechaAntes) }} · {{ $horaAnterior }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td style="padding:12px 16px; color:#6b7280; font-size:13px; font-weight:bold;">{{ __($fechaAntes ? 'emails.owner_change_now' : 'emails.label_date', [], $loc) }}</td>
                                <td style="padding:12px 16px; color:#111827; font-size:15px; font-weight:bold;">{{ ucfirst($fechaCita) }} · {{ $appointment->time }}</td>
                            </tr>
                        </table>

                        {{-- 4. ===== CTA: panel ===== --}}
                        <p style="margin:22px 0 0 0;">
                            <a href="{{ $panelUrl }}" style="display:inline-block; background-color:#1d4ed8; color:#ffffff; font-size:14px; font-weight:bold; text-decoration:none; padding:10px 16px; border-radius:8px;">{{ __('emails.owner_change_panel', [], $loc) }}</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
