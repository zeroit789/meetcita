{{--
  ============================================================================
  THEME VARS — business accent colour from config, as CSS variables
  VARIABLES DEL TEMA — color de acento del negocio desde config, como variables CSS
  ============================================================================
  ES: Lo incluyen los dos layouts DESPUÉS de @vite, así estas variables pisan
      los valores de reserva de resources/css/app.css. Cada color se valida
      contra /^#[0-9a-fA-F]{6}$/D (la D impide que cuele un salto de línea
      final): si no encaja, se usa el de por defecto, de modo que desde el .env
      no se puede inyectar CSS ni HTML. Lleva el nonce de la CSP aunque
      style-src permita estilos en línea.
  EN: Both layouts include it AFTER @vite, so these variables override the
      fallback values in resources/css/app.css. Every colour is validated
      against /^#[0-9a-fA-F]{6}$/D (the D stops a trailing newline slipping
      through): when it doesn't match, the default is used, so no CSS or HTML
      can be injected from the .env. It carries the CSP nonce even though
      style-src allows inline styles.
  ============================================================================
--}}
@php
    // ES: Valida un hex #rrggbb; si no vale, devuelve $porDefecto.
    // EN: Validates a #rrggbb hex; returns $porDefecto when invalid.
    $hexValido = static fn ($valor, $porDefecto) => is_string($valor) && preg_match('/^#[0-9a-fA-F]{6}$/D', $valor) ? $valor : $porDefecto;

    // ES: Contraste WCAG de un hex contra el blanco (texto blanco sobre el acento).
    // EN: WCAG contrast of a hex against white (white text on the accent).
    $contrasteBlanco = static function (string $hex): float {
        $canal = static function (string $c): float {
            $v = hexdec($c) / 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        };
        $l = 0.2126 * $canal(substr($hex, 1, 2)) + 0.7152 * $canal(substr($hex, 3, 2)) + 0.0722 * $canal(substr($hex, 5, 2));

        return 1.05 / ($l + 0.05);
    };
    // ES: Acento principal (azul sobrio por defecto). Si con texto blanco no llega
    //     a 4,5:1 (AA), se usa el de por defecto: un acento claro dejaría los
    //     botones ilegibles.
    // EN: Main accent (sober blue by default). If white text on it doesn't reach
    //     4.5:1 (AA), the default is used: a light accent would make buttons unreadable.
    $acento = $hexValido(config('appointments.accent'), '#1d4ed8');
    if ($contrasteBlanco($acento) < 4.5) {
        $acento = '#1d4ed8';
    }

    // ES: Tono claro opcional; null = lo deriva el CSS. EN: Optional light shade; null = derived in CSS.
    $acentoClaro = $hexValido(config('appointments.accent_glow'), null);
@endphp
{{-- ES: Favicon neutro: cuadrado redondeado del color de acento con la inicial
         de la marca. El # del hex validado va codificado como %23.
     EN: Neutral favicon: rounded square in the accent colour with the brand
         initial. The validated hex's # is encoded as %23. --}}
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%23{{ substr($acento, 1) }}'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='34' font-weight='700' fill='white'%3E{{ rawurlencode(htmlspecialchars(mb_strtoupper(mb_substr((string) config('appointments.brand.name'), 0, 1)), ENT_XML1)) }}%3C/text%3E%3C/svg%3E">
<style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
  :root {
    --accent: {{ $acento }};
@if($acentoClaro)
    --accent-glow: {{ $acentoClaro }};
@endif
  }
</style>
