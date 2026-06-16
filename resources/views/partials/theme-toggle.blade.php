{{--
  ============================================================================
  THEME TOGGLE — sun/moon button (shared by public + panel layouts)
  TOGGLE DE TEMA — botón sol/luna (compartido por los layouts público + panel)
  ============================================================================
  EN: Accessible, dependency-free toggle. The button carries data-theme-toggle
      so resources/js/app.js can wire it via event delegation (survives Livewire
      navigations). It holds BOTH icons; JS shows the sun on dark (= "switch to
      light") and the moon on light (= "switch to dark") and keeps aria-pressed
      in sync. The anti-FOUC script in the <head> sets the initial state.
  ES: Toggle accesible y sin dependencias. El botón lleva data-theme-toggle para
      que resources/js/app.js lo conecte por delegación de eventos (sobrevive a
      las navegaciones de Livewire). Contiene AMBOS iconos; el JS muestra el sol
      en oscuro (= "pasar a claro") y la luna en claro (= "pasar a oscuro") y
      mantiene aria-pressed sincronizado. El script anti-parpadeo del <head> fija
      el estado inicial.
  ============================================================================
--}}
<button
  type="button"
  data-theme-toggle
  aria-label="{{ __('citas.theme_toggle') }}"
  title="{{ __('citas.theme_toggle') }}"
  class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-brand/20 bg-base/60 text-muted hover:text-brand-glow hover:border-brand/40 transition-all cursor-pointer">

  {{-- EN: SUN icon — shown while the DARK theme is active (click → go light).
       ES: Icono SOL — visible mientras el tema OSCURO está activo (clic → claro). --}}
  <svg data-icon-sun xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
       stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
       class="w-4.5 h-4.5" aria-hidden="true">
    <circle cx="12" cy="12" r="4"/>
    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
  </svg>

  {{-- EN: MOON icon — shown while the LIGHT theme is active (click → go dark).
           Hidden by default; JS reveals it on light. The 'hidden' class avoids a
           flash of both icons before JS runs (dark is the safe default).
       ES: Icono LUNA — visible mientras el tema CLARO está activo (clic → oscuro).
           Oculto por defecto; el JS lo muestra en claro. La clase 'hidden' evita
           ver ambos iconos antes de que corra el JS (oscuro es el default seguro). --}}
  <svg data-icon-moon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
       stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
       class="w-4.5 h-4.5 hidden" aria-hidden="true">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
  </svg>
</button>
