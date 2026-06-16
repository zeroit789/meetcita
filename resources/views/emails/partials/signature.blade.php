{{--
    ============================================================================
    SIGNATURE / FIRMA — Brand email signature ("Dark terminal" style)
    ============================================================================
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

    NOTE / NOTA:
        EN: The "D" logo uses a <div> with FIXED width/height (not a table cell):
            in Gmail mobile, cells stretch to the row height and the logo turned
            into a vertical capsule. Fixed dimensions + line-height keep it square.
        ES: El logo va en un <div> con width/height FIJOS (no en una celda de
            tabla): en Gmail móvil las celdas se estiran y el logo salía como una
            cápsula vertical. Dimensiones fijas + line-height lo mantienen cuadrado.
    ============================================================================
--}}
@php
    // EN: Pull branding from config once (avoid repeating config() in the HTML).
    // ES: Sacamos la marca de config una sola vez (no repetir config() en el HTML).
    $brandName  = config('appointments.brand.name');
    $ownerName  = config('appointments.brand.owner_name');
    $website    = config('appointments.brand.website');
    $ownerEmail = config('appointments.brand.owner_email');
    $linkedin   = config('appointments.brand.linkedin');

    // EN: Logo letter = first character of the brand name (uppercase). Fallback "?".
    // ES: Letra del logo = primer carácter del nombre de marca (en mayúscula). Fallback "?".
    $logoLetter = strtoupper(mb_substr((string) $brandName, 0, 1)) ?: '?';

    // EN: Website without scheme, just for display (https://x.com → x.com).
    // ES: Web sin esquema, solo para mostrar (https://x.com → x.com).
    $websiteLabel = preg_replace('#^https?://#', '', (string) $website);
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td style="background-color:#0f1117; background-image:linear-gradient(135deg,#12121a 0%,#0a0a0f 100%); border:1px solid rgba(147,51,234,0.4); border-radius:14px; padding:20px 22px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    {{-- EN: Brand logo letter (fixed-size div → never deforms).
                         ES: Letra del logo de marca (div de tamaño fijo → no se deforma). --}}
                    <td valign="top" width="56" style="width:56px; padding-right:16px;">
                        <div style="width:52px; height:52px; background-color:#9333ea; background-image:linear-gradient(135deg,#7c3aed 0%,#9333ea 100%); border-radius:13px; color:#ffffff; font-family:'Courier New',Courier,monospace; font-size:28px; font-weight:bold; text-align:center; line-height:52px; mso-line-height-rule:exactly;">{{ $logoLetter }}</div>
                    </td>
                    {{-- EN: Owner / brand data. ES: Datos del dueño / marca. --}}
                    <td valign="top" style="font-family:Arial,Helvetica,sans-serif;">
                        {{-- EN: Terminal-style brand line. ES: Línea de marca estilo terminal. --}}
                        <div style="font-family:'Courier New',Courier,monospace; color:#22ff88; font-size:12px; line-height:1.4;">&gt; {{ $brandName }}</div>
                        <div style="color:#ffffff; font-size:16px; font-weight:bold; line-height:1.3; padding-top:2px;">{{ $ownerName }}</div>
                        <div style="color:#a1a1aa; font-size:13px; line-height:1.4; padding-top:2px;">{{ __('emails.signature_role') }}</div>
                        <div style="height:2px; width:38px; background-color:#9333ea; background-image:linear-gradient(90deg,#9333ea,#22ff88); margin:9px 0; font-size:0; line-height:0;">&nbsp;</div>
                        <div style="font-size:13px; line-height:1.7;">
                            {{-- EN: Website + email + (optional) LinkedIn. ES: Web + email + (opcional) LinkedIn. --}}
                            <a href="{{ $website }}" style="color:#a855f7; text-decoration:none; font-weight:bold;">{{ $websiteLabel }}</a>
                            <span style="color:#555555;">&nbsp;·&nbsp;</span>
                            <a href="mailto:{{ $ownerEmail }}" style="color:#a855f7; text-decoration:none;">{{ $ownerEmail }}</a>
                            {{-- EN: LinkedIn line only if brand.linkedin is set.
                                 ES: Línea de LinkedIn solo si brand.linkedin tiene valor. --}}
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
