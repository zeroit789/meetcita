{{--
  ============================================================================
  PANEL LAYOUT — private admin panel wrapper (/panel)
  LAYOUT DEL PANEL — envoltorio del panel privado de administración (/panel)
  ============================================================================
  ES: Envoltorio autónomo del panel privado. Mismo tema que la parte pública
      (claro por defecto, oscuro opcional, acento de config); assets vía Vite.
      Nunca se indexa.
  EN: Standalone shell for the private panel. Same theme as the public side
      (light by default, optional dark, accent from config); assets via Vite.
      Never indexed.

  INDEX / ÍNDICE
    1. HEAD ............. meta, title, vite, accent + favicon / cabeza del doc
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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-default-theme="{{ config('appointments.theme', 'light') }}">

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
  <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
      try {
        // ES: 1) Gana la elección guardada. / EN: 1) Saved visitor choice wins.
        var saved = localStorage.getItem('appointments-theme');
        if (saved === 'light' || saved === 'dark') {
          document.documentElement.dataset.theme = saved;
          return;
        }
        // ES: 2) Si no, el default de config. / EN: 2) Fall back to the config default.
        var def = document.documentElement.dataset.defaultTheme || 'light';
        if (def === 'auto') {
          // ES: 'auto' → seguir el SO. / EN: 'auto' → follow the OS preference.
          def = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        }
        document.documentElement.dataset.theme = (def === 'dark') ? 'dark' : 'light';
      } catch (e) {
        // ES: Si algo falla → claro por defecto. / EN: Anything fails → light default.
        document.documentElement.dataset.theme = 'light';
      }
    })();
  </script>

  {{-- ES: El panel es privado → no indexar ni seguir.
       EN: The panel is private → never index/follow. --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- ES: Título de config + "panel". EN: Title from config + the word "panel". --}}
  <title>{{ __('citas.panel_title') }} · {{ config('appointments.brand.name') }}</title>


  {{-- ES: Assets compilados vía Vite (fuentes del sistema, sin descargas).
       EN: Compiled assets via Vite (system fonts, no downloads). --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- ES: Color de acento de config (validado) + favicon, tras @vite.
       EN: Accent colour from config (validated) + favicon, after @vite. --}}
  @include('partials.theme-vars')

  {{-- ES: Cabeza extra por página. EN: Per-page extra head tags (optional). --}}
  @stack('head')
</head>

{{-- ── 2. CONTENT EN / ES ───────────────────────────────────────────────────
     ES: Livewire 4 inyecta sus assets automáticamente; @yield del contenido.
     EN: Livewire 4 injects its assets automatically; @yield for the content. --}}
<body class="font-sans antialiased text-ink selection:bg-brand/30">
  {{-- Public demo notice / Aviso de demo pública --}}
  @if(config('appointments.demo.enabled'))
    <div role="note" class="bg-amber-100 text-amber-950 text-center text-sm px-4 py-2">{{ __('citas.demo_banner') }}</div>
  @endif
  @yield('content')
</body>
</html>
