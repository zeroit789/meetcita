{{--
  ============================================================================
  PUBLIC LAYOUT — /citas booking page wrapper
  LAYOUT PÚBLICO — envoltorio de la página de reserva /citas
  ============================================================================
  ES: Envoltorio mínimo, autónomo y profesional para la página pública de
      reserva. El nombre del negocio y su color de acento salen de config.
      Tema claro por defecto con oscuro opcional (ver resources/css/app.css).
      Incluye un selector de idioma ES/EN y carga los assets vía Vite.
  EN: Minimal, standalone, professional shell for the public booking page.
      The business name and its accent colour come from config. Light theme
      by default with an optional dark one (see resources/css/app.css).
      Includes an ES/EN language toggle and loads assets through Vite.

  INDEX / ÍNDICE
    1. HEAD ............. meta, title, vite, accent + favicon / cabeza del doc
    2. TOPBAR .......... business name + theme + language / negocio + tema + idioma
    3. CONTENT ......... @yield('content') / contenido de la página
    4. FOOTER .......... business name + website / negocio + web
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

  {{-- ES: Título desde config (sin marca hardcodeada). Cada página puede
           sobreescribir la parte @yield('title').
       EN: Page title from config (no hardcoded brand). Sections may override
           the @yield('title') part per page. --}}
  <title>@yield('title', __('citas.wrapper_title')) · {{ config('appointments.brand.name') }}</title>

  {{-- ES: /citas es una herramienta, no contenido SEO → no indexar, follow.
       EN: /citas is a tool, not SEO content → don't index, but follow links. --}}
  <meta name="robots" content="noindex,follow">


  {{-- ES: CSS + JS compilados (ejecuta `pnpm run build` o `pnpm run dev`). Sin
           fuentes externas: se usan las del sistema (ver app.css).
       EN: Compiled CSS + JS (run `pnpm run build` or `pnpm run dev`). No
           external fonts: system ones are used (see app.css). --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- ES: Color de acento de config (validado) + favicon. Va tras @vite para
           pisar los valores de reserva del CSS.
       EN: Accent colour from config (validated) + favicon. Goes after @vite to
           override the CSS fallbacks. --}}
  @include('partials.theme-vars')

  {{-- ES: Cabeza extra por página. EN: Per-page extra head tags (optional). --}}
  @stack('head')
</head>

<body class="font-sans antialiased text-ink selection:bg-brand/30 min-h-screen flex flex-col">
  {{-- Public demo notice / Aviso de demo pública --}}
  @if(config('appointments.demo.enabled'))
    <div role="note" class="bg-amber-100 text-amber-950 text-center text-sm px-4 py-2">{{ __('citas.demo_banner') }}</div>
  @endif

  {{-- ── 2. TOPBAR EN / ES ──────────────────────────────────────────────────
       ES: Nombre del negocio (de config) a la izquierda; tema e idioma a la dcha.
       EN: Business name (from config) on the left; theme and language on the right. --}}
  <header class="border-b border-[var(--card-border)] bg-surface">
    <div class="max-w-5xl mx-auto px-5 py-4 flex items-center justify-between gap-4">

      {{-- ES: El nombre del negocio enlaza al inicio. EN: The business name links to home. --}}
      <a href="{{ url('/') }}" class="text-lg font-semibold text-ink hover:text-brand-glow transition-colors">
        {{ config('appointments.brand.name') }}
      </a>

      {{-- ── Right controls: theme toggle + language toggle ─────────────────────
           ES: Toggle de tema sol/luna y las pastillas de idioma ES/EN, juntos.
           EN: Sun/moon theme toggle and the ES/EN language pills, side by side. --}}
      <div class="flex items-center gap-3">

        {{-- ── Theme toggle (sun / moon) EN / ES ──────────────────────────────
             ES: Botón accesible. El JS (resources/js/app.js) muestra el sol en
                 oscuro y la luna en claro, cambia data-theme en <html> y guarda
                 la elección en localStorage. Funciona con Livewire por
                 delegación de eventos.
             EN: Accessible button. JS (resources/js/app.js) shows the sun on
                 dark and the moon on light, flips data-theme on <html> and
                 stores the choice in localStorage. Works through Livewire via
                 event delegation. --}}
        @include('partials.theme-toggle')

        {{-- ── Language toggle ES / EN ──────────────────────────────────────────
             ES: Una pastilla por idioma de config('appointments.locales'). Cada
                 una apunta a /lang/{locale} (ruta que provee el agente backend).
                 El idioma activo se resalta con el acento.
             EN: One pill per locale from config('appointments.locales'). Each
                 points to /lang/{locale} (route provided by the backend agent).
                 The active locale is highlighted with the accent. --}}
        <nav class="inline-flex rounded-lg border border-[var(--card-border)] bg-base p-1 gap-1 text-xs font-medium" aria-label="{{ __('citas.lang_aria') }}">
          @foreach(config('appointments.locales', ['es']) as $loc)
            <a href="{{ url('/lang/' . $loc) }}" hreflang="{{ $loc }}" lang="{{ $loc }}"
               @if(app()->getLocale() === $loc) aria-current="true" @endif
               @class([
                 'rounded-md px-3 py-1.5 uppercase tracking-wide transition-all',
                 // ES: idioma activo = relleno acento / EN: active locale = accent fill
                 'bg-brand text-white' => app()->getLocale() === $loc,
                 // ES: inactivo = atenuado / EN: inactive = muted, accent on hover
                 'text-muted hover:text-brand-glow' => app()->getLocale() !== $loc,
               ])>{{ $loc }}</a>
          @endforeach
        </nav>

      </div>

    </div>
  </header>

  {{-- ── 3. CONTENT EN / ES ─────────────────────────────────────────────────
       ES: Cuerpo de la página. flex-1 empuja el footer abajo en páginas cortas.
       EN: Page body. flex-1 pushes the footer to the bottom on short pages. --}}
  <main class="flex-1">
    @yield('content')
  </main>

  {{-- ── 4. FOOTER EN / ES ──────────────────────────────────────────────────
       ES: Footer neutro de cara al cliente: nombre del negocio (config) y
           enlace a su web (config). Sin enlaces técnicos ni datos personales.
       EN: Neutral, client-facing footer: business name (config) and a link to
           its website (config). No technical links or personal data. --}}
  <footer class="border-t border-[var(--card-border)] mt-10">
    <div class="max-w-5xl mx-auto px-5 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-faint">
      <span>© {{ date('Y') }} {{ config('appointments.brand.name') }}</span>
      <div class="flex items-center gap-4">
        {{-- ES: Web pública de config (oculta si es el valor por defecto).
             EN: Public website from config (hidden if it's the placeholder). --}}
        {{-- ES: Solo enlaces http(s). EN: http(s) links only. --}}
        @if(config('appointments.brand.website') && config('appointments.brand.website') !== 'https://example.com' && preg_match('#^https?://#i', (string) config('appointments.brand.website')))
          <a href="{{ config('appointments.brand.website') }}" target="_blank" rel="noopener"
             class="underline hover:text-brand-glow transition-colors">{{ __('citas.footer_website') }}</a>
        @endif
      </div>
    </div>
  </footer>

</body>
</html>
