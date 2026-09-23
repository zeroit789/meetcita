{{--
    ============================================================================
    SIGNATURE / FIRMA — Brand email signature ("Dark terminal" style)
    ============================================================================
    ES: Firma de marca email-safe: tabla + CSS inline (Gmail, Outlook, Apple
        Mail...). Bloque oscuro de marca que destaca sobre el fondo claro.
        TODOS los datos personales están desacoplados a config('appointments.brand.*'):
          · letra del logo .. primera letra de brand.name
          · línea de marca .. "> {brand.name}"
          · nombre .......... brand.owner_name
          · rol ............. brand.owner_role
          · web ............. brand.website
          · email ........... brand.owner_email
          · linkedin ........ brand.linkedin (la línea se oculta si está vacía)

    EN: Email-safe brand signature: table + inline CSS (Gmail, Outlook, Apple
        Mail...). A dark branded block that stands out over the light email body.
        ALL personal data is decoupled to config('appointments.brand.*'):
          · logo letter ... first letter of brand.name
          · brand line .... "> {brand.name}"
          · name .......... brand.owner_name
          · role .......... brand.owner_role
          · website ....... brand.website
          · email ......... brand.owner_email
          · linkedin ...... brand.linkedin (line hidden if empty)

    NOTE / NOTA:
        ES: El logo va en un <div> con width/height FIJOS (no en una celda de
            tabla): en Gmail móvil las celdas se estiran y el logo salía como una
            cápsula vertical. Dimensiones fijas + line-height lo mantienen cuadrado.
        EN: The "D" logo uses a <div> with FIXED width/height (not a table cell):
            in Gmail mobile, cells stretch to the row height and the logo turned
            into a vertical capsule. Fixed dimensions + line-height keep it square.
    ============================================================================
--}}
@php
    // ES: Sacamos la marca de config una sola vez (no repetir config() en el HTML).
    // EN: Pull branding from config once (avoid repeating config() in the HTML).
    $brandName  = config('appointments.brand.name');
    $ownerName  = config('appointments.brand.owner_name');
    $website    = config('appointments.brand.website');
    $ownerEmail = config('appointments.brand.owner_email');
    $linkedin   = config('appointments.brand.linkedin');

    // ES: Letra del logo = primer carácter del nombre de marca (en mayúscula). Fallback "?".
    // EN: Logo letter = first character of the brand name (uppercase). Fallback "?".
    $logoLetter = strtoupper(mb_substr((string) $brandName, 0, 1)) ?: '?';

    // ES: Web sin esquema, solo para mostrar (https://x.com → x.com).
    // EN: Website without scheme, just for display (https://x.com → x.com).
    $websiteLabel = preg_replace('#^https?://#', '', (string) $website);
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td style="background-color:#0f1117; background-image:linear-gradient(135deg,#12121a 0%,#0a0a0f 100%); border:1px solid rgba(147,51,234,0.4); border-radius:14px; padding:20px 22px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    {{-- ES: Letra del logo de marca (div de tamaño fijo → no se deforma).
                         EN: Brand logo letter (fixed-size div → never deforms). --}}
                    <td valign="top" width="56" style="width:56px; padding-right:16px;">
                        <div style="width:52px; height:52px; background-color:#9333ea; background-image:linear-gradient(135deg,#7c3aed 0%,#9333ea 100%); border-radius:13px; color:#ffffff; font-family:'Courier New',Courier,monospace; font-size:28px; font-weight:bold; text-align:center; line-height:52px; mso-line-height-rule:exactly;">{{ $logoLetter }}</div>
                    </td>
                    {{-- ES: Datos del dueño / marca. EN: Owner / brand data. --}}
                    <td valign="top" style="font-family:Arial,Helvetica,sans-serif;">
                        {{-- ES: Línea de marca estilo terminal. EN: Terminal-style brand line. --}}
                        <div style="font-family:'Courier New',Courier,monospace; color:#22ff88; font-size:12px; line-height:1.4;">&gt; {{ $brandName }}</div>
                        <div style="color:#ffffff; font-size:16px; font-weight:bold; line-height:1.3; padding-top:2px;">{{ $ownerName }}</div>
                        <div style="color:#a1a1aa; font-size:13px; line-height:1.4; padding-top:2px;">{{ __('emails.signature_role') }}</div>
                        <div style="height:2px; width:38px; background-color:#9333ea; background-image:linear-gradient(90deg,#9333ea,#22ff88); margin:9px 0; font-size:0; line-height:0;">&nbsp;</div>
                        <div style="font-size:13px; line-height:1.7;">
                            {{-- ES: Web + email + (opcional) LinkedIn. EN: Website + email + (optional) LinkedIn. --}}
                            <a href="{{ $website }}" style="color:#a855f7; text-decoration:none; font-weight:bold;">{{ $websiteLabel }}</a>
                            <span style="color:#555555;">&nbsp;·&nbsp;</span>
                            <a href="mailto:{{ $ownerEmail }}" style="color:#a855f7; text-decoration:none;">{{ $ownerEmail }}</a>
                            {{-- ES: Línea de LinkedIn solo si brand.linkedin tiene valor.
                                 EN: LinkedIn line only if brand.linkedin is set. --}}
                            @if(!empty($linkedin))
                                <span style="color:#555555;">&nbsp;·&nbsp;</span>
                                <a href="{{ $linkedin }}" style="color:#a855f7; text-decoration:none;">LinkedIn</a>
                            @endif
                        </div>
                        <div style="font-family:'Courier New',Courier,monospace; color:#22ff88; font-size:11px; line-height:1.4; padding-top:9px;">{{ __('emails.signature_available') }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
