{{--
  ============================================================================
  PUBLIC LAYOUT — /citas booking page wrapper
  LAYOUT PÚBLICO — envoltorio de la página de reserva /citas
  ============================================================================
  ES: Envoltorio mínimo, autónomo y profesional para la página pública de
      reserva. Sin marca personal de ningún dueño: el título sale de config.
      Tema oscuro neutro (ver resources/css/app.css). Incluye un pequeño
      selector de idioma ES/EN y carga los assets vía Vite.
  EN: Minimal, standalone, professional shell for the public booking page.
      No personal branding of any owner: the title comes from config. Dark
      neutral theme (see resources/css/app.css). Includes a tiny ES/EN
      language toggle and loads assets through Vite.

  INDEX / ÍNDICE
    1. HEAD ............. meta, title, favicon, fonts, vite / cabeza del doc
    2. TOPBAR .......... brand name + language toggle / marca + idioma
    3. CONTENT ......... @yield('content') / contenido de la página
    4. FOOTER .......... neutral credit + repo link / crédito neutro + repo
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

  {{-- ES: Título desde config (sin marca hardcodeada). Cada página puede
           sobreescribir la parte @yield('title').
       EN: Page title from config (no hardcoded brand). Sections may override
           the @yield('title') part per page. --}}
  <title>@yield('title', __('citas.wrapper_title')) · {{ config('appointments.brand.name') }}</title>

  {{-- ES: /citas es una herramienta, no contenido SEO → no indexar, follow.
       EN: /citas is a tool, not SEO content → don't index, but follow links. --}}
  <meta name="robots" content="noindex,follow">

  {{-- ES: Favicon simple y neutro: cuadrado redondeado con la inicial de la
           marca, pintado con el acento actual. No requiere fichero externo.
       EN: Simple, neutral favicon: a rounded square with the brand initial,
           painted with the current accent. No external file required. --}}
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%239333ea'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='34' font-weight='700' fill='white'%3E{{ strtoupper(mb_substr(config('appointments.brand.name'), 0, 1)) }}%3C/text%3E%3C/svg%3E">

  {{-- ES: Fuentes vía Bunny Fonts (sin tracking de Google, RGPD). Space Grotesk
           = sans de la UI, JetBrains Mono = toque terminal. Para cambiar la
           tipografía, edita este <link> y las vars --font-* en app.css.
       EN: Fonts via Bunny Fonts (GDPR-friendly, no Google tracking). Space
           Grotesk = UI sans, JetBrains Mono = terminal accent. To change the
           typeface, edit this <link> and the --font-* vars in app.css. --}}
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  <link rel="stylesheet" href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,700|space-grotesk:400,500,700">

  {{-- ES: CSS + JS compilados (ejecuta `npm run build` o `npm run dev`).
       EN: Compiled CSS + JS (run `npm run build` or `npm run dev`). --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- ES: Cabeza extra por página. EN: Per-page extra head tags (optional). --}}
  @stack('head')
</head>

<body class="font-sans antialiased text-ink selection:bg-brand/30 min-h-screen flex flex-col">

  {{-- ── 2. TOPBAR EN / ES ──────────────────────────────────────────────────
       ES: Nombre de marca (de config) a la izquierda, toggle ES/EN a la dcha.
       EN: Brand name (from config) on the left, ES/EN toggle on the right. --}}
  <header class="border-b border-brand/10">
    <div class="max-w-5xl mx-auto px-5 py-4 flex items-center justify-between gap-4">

      {{-- ES: La marca enlaza al inicio. EN: Brand name links to home. --}}
      <a href="{{ url('/') }}" class="font-mono text-sm font-bold text-ink hover:text-brand-glow transition-colors">
        <span class="text-term">$</span> {{ config('appointments.brand.name') }}
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
        <nav class="inline-flex rounded-lg border border-brand/20 bg-base/60 p-1 gap-1 font-mono text-xs" aria-label="{{ __('citas.lang_aria') }}">
          @foreach(config('appointments.locales', ['es']) as $loc)
            <a href="{{ url('/lang/' . $loc) }}"
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
       ES: Footer neutro: nombre de marca (config) + enlace a la web pública
           (config) + enlace al repo open-source. Sin datos personales.
       EN: Neutral footer: brand name (config) + link to the public website
           (config) + link to the open-source repo. No personal data. --}}
  <footer class="border-t border-brand/10 mt-10">
    <div class="max-w-5xl mx-auto px-5 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 font-mono text-xs text-faint">
      <span>© {{ date('Y') }} {{ config('appointments.brand.name') }}</span>
      <div class="flex items-center gap-4">
        {{-- ES: Web pública de config (oculta si es el valor por defecto).
             EN: Public website from config (hidden if it's the placeholder). --}}
        @if(config('appointments.brand.website') && config('appointments.brand.website') !== 'https://example.com')
          <a href="{{ config('appointments.brand.website') }}" target="_blank" rel="noopener"
             class="hover:text-brand-glow transition-colors">{{ __('citas.footer_website') }} ↗</a>
        @endif
        {{-- ES: Enlace al repo open-source. EN: Open-source repo link. --}}
        <a href="https://github.com/zeroit789/meetcita" target="_blank" rel="noopener"
           class="hover:text-brand-glow transition-colors">{{ __('citas.footer_repo') }} ↗</a>
      </div>
    </div>
  </footer>

</body>
</html>
