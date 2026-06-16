{{--
  ============================================================================
  PANEL INDEX — appointments dashboard (password protected)
  PANEL INDEX — panel de citas (protegido por contraseña)
  ============================================================================
  EN: Renders the blocked-days manager + the appointments table. NOTE: the
      contact-messages component is intentionally NOT included (out of scope
      for the standalone appointments module). Labels via __('citas.*').
  ES: Renderiza el gestor de días bloqueados + la tabla de citas. NOTA: el
      componente de mensajes de contacto NO se incluye a propósito (fuera del
      alcance del módulo de citas standalone). Etiquetas vía __('citas.*').
  ============================================================================
--}}
@extends('layouts.panel')

@section('content')
  <section class="max-w-7xl mx-auto px-5 py-10 min-h-screen">

    {{-- Terminal-style header / Cabecera estilo terminal --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
      <div>
        <p class="font-mono text-sm text-term/70 mb-1">$ ./panel.sh --appointments</p>
        <h1 class="font-sans font-bold text-3xl text-ink">{{ __('citas.panel_heading') }}</h1>
        <p class="text-muted text-sm mt-1">{{ __('citas.panel_subtitle') }}</p>
      </div>

      {{-- Header controls: theme toggle + log out / Controles: toggle de tema + logout --}}
      <div class="flex items-center gap-3">

        {{-- EN: Sun/moon theme toggle (shared partial). ES: Toggle de tema sol/luna (partial compartido). --}}
        @include('partials.theme-toggle')

        {{-- Log out of the panel / Cerrar sesión del panel --}}
        <form method="POST" action="{{ route('panel.logout') }}">
          @csrf
          <button type="submit"
                  class="inline-flex items-center gap-2 rounded-lg border border-brand/30 px-4 py-2 font-mono text-sm text-muted hover:text-brand-glow hover:border-brand transition-colors">
            {{ __('citas.panel_logout') }} →
          </button>
        </form>

      </div>
    </div>

    {{-- Off-days (holidays) manager / Gestión de días no disponibles (vacaciones) --}}
    <div class="mb-8">
      <livewire:panel-blocked-days />
    </div>

    {{-- Appointments table / Tabla de citas --}}
    <livewire:panel-citas />

  </section>
@endsection
