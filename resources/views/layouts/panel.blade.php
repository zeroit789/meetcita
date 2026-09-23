{{--
  ============================================================================
  PANEL LAYOUT — private admin panel wrapper (/panel)
  LAYOUT DEL PANEL — envoltorio del panel privado de administración (/panel)
  ============================================================================
  ES: Envoltorio autónomo del panel privado. Mismo tema oscuro neutro que la
      parte pública pero los assets vienen de Vite (SIN Tailwind CDN). Nunca
      se indexa.
  EN: Standalone shell for the private panel. Same neutral dark theme as the
      public side but assets come from Vite (NO Tailwind CDN). Never indexed.

  INDEX / ÍNDICE
    1. HEAD ............. meta, title, favicon, fonts, vite / cabeza del doc
    2. CONTENT ......... @yield('content') / contenido del panel
  ============================================================================
--}}
<!DOCTYPE html>
{{-- ES: data-default-theme lleva el valor por defecto de config('appointments.theme')
         ('dark' | 'light' | 'auto') para que el script anti-parpadeo de abajo lo
         lea cuando el visitante aún no ha elegido tema.
     EN: data-default-theme carries the config('appointments.theme') default
         ('dark' | 'light' | 'auto') so the inline anti-FOUC script below can
         read it when the visitor has not chosen a theme yet. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-default-theme="{{ config('appointments.theme', 'dark') }}">

{{-- ── 1. HEAD EN / ES ──────────────────────────────────────────────────── --}}
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- ── ANTI-FOUC THEME SCRIPT EN / ES ───────────────────────────────────
       ES: Se ejecuta ANTES de @vite/CSS para pintar el tema correcto sin
           parpadeo. Prioridad: (1) la elección guardada del visitante en
           localStorage; si no, (2) el default de config — si es 'auto', sigue
           el esquema de color del sistema operativo.
       EN: Runs BEFORE @vite/CSS so the right theme is painted with no flash.
           Priority: (1) the visitor's saved choice in localStorage; else
           (2) the config default — if 'auto', follow the OS color scheme. --}}
  <script>
    (function () {
      try {
        // ES: 1) Gana la elección guardada. / EN: 1) Saved visitor choice wins.
        var saved = localStorage.getItem('appointments-theme');
        if (saved === 'light' || saved === 'dark') {
          document.documentElement.dataset.theme = saved;
          return;
        }
        // ES: 2) Si no, el default de config. / EN: 2) Fall back to the config default.
        var def = document.documentElement.dataset.defaultTheme || 'dark';
        if (def === 'auto') {
          // ES: 'auto' → seguir el SO. / EN: 'auto' → follow the OS preference.
          def = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        }
        document.documentElement.dataset.theme = (def === 'light') ? 'light' : 'dark';
      } catch (e) {
        // ES: Si algo falla → oscuro seguro. / EN: Anything fails → safe dark default.
        document.documentElement.dataset.theme = 'dark';
      }
    })();
  </script>

  {{-- ES: El panel es privado → no indexar ni seguir.
       EN: The panel is private → never index/follow. --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- ES: Título de config + "panel". EN: Title from config + the word "panel". --}}
  <title>{{ __('citas.panel_title') }} · {{ config('appointments.brand.name') }}</title>

  {{-- ES: Mismo favicon neutro que el layout público (inicial + acento).
       EN: Same neutral favicon as the public layout (brand initial + accent). --}}
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%239333ea'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='34' font-weight='700' fill='white'%3E{{ strtoupper(mb_substr(config('appointments.brand.name'), 0, 1)) }}%3C/text%3E%3C/svg%3E">

  {{-- ES: Fuentes vía Bunny Fonts (RGPD) + assets compilados vía Vite.
       EN: Fonts via Bunny Fonts (GDPR-friendly) + compiled assets via Vite. --}}
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  <link rel="stylesheet" href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,700|space-grotesk:400,500,700">
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- ES: Cabeza extra por página. EN: Per-page extra head tags (optional). --}}
  @stack('head')
</head>

{{-- ── 2. CONTENT EN / ES ───────────────────────────────────────────────────
     ES: Livewire 4 inyecta sus assets automáticamente; @yield del contenido.
     EN: Livewire 4 injects its assets automatically; @yield for the content. --}}
<body class="font-sans antialiased text-ink selection:bg-brand/30">
  @yield('content')
</body>
</html>
