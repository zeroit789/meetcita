{{--
  ============================================================================
  PANEL INDEX — appointments dashboard (password protected)
  PANEL INDEX — panel de citas (protegido por contraseña)
  ============================================================================
  ES: Renderiza el gestor de días bloqueados + el alta manual + la tabla de citas. NOTA: el
      componente de mensajes de contacto NO se incluye a propósito (fuera del
      alcance del módulo de citas standalone). Etiquetas vía __('citas.*').
  EN: Renders the blocked-days manager + the manual entry + the appointments table. NOTE: the
      contact-messages component is intentionally NOT included (out of scope
      for the standalone appointments module). Labels via __('citas.*').
  ============================================================================
--}}
@extends('layouts.panel')

@section('content')
  <section class="max-w-7xl mx-auto px-5 py-10 min-h-screen">

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
      <div>
        <h1 class="font-sans font-bold text-3xl text-ink">{{ __('citas.panel_heading') }}</h1>
        <p class="text-muted text-sm mt-1">{{ __('citas.panel_subtitle') }}</p>
      </div>

      {{-- Header controls: theme toggle + log out / Controles: toggle de tema + logout --}}
      <div class="flex items-center gap-3">

        {{-- ES: Toggle de tema sol/luna (partial compartido). EN: Sun/moon theme toggle (shared partial). --}}
        @include('partials.theme-toggle')

        {{-- Log out of the panel / Cerrar sesión del panel --}}
        <form method="POST" action="{{ route('panel.logout') }}">
          @csrf
          <button type="submit"
                  class="inline-flex items-center gap-2 rounded-lg border border-brand/30 px-4 py-2 text-sm text-muted hover:text-brand-glow hover:border-brand transition-colors">
            {{ __('citas.panel_logout') }} <span aria-hidden="true">→</span>
          </button>
        </form>

      </div>
    </div>

    {{-- Off-days (holidays) manager / Gestión de días no disponibles (vacaciones) --}}
    <div class="mb-8">
      <livewire:panel-blocked-days />
    </div>

    {{-- ES: Alta manual de una cita telefónica. EN: Manual entry of a phone booking. --}}
    <div class="mb-8">
      <livewire:panel-nueva-cita />
    </div>

    {{-- Appointments table / Tabla de citas --}}
    <livewire:panel-citas />

  </section>
@endsection
