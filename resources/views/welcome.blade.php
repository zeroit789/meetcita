{{--
  ============================================================================
  WELCOME — minimal landing for "/"
  WELCOME — landing mínima para "/"
  ============================================================================
  EN: A tiny, clean home page that explains this is a demo of the open-source
      appointments system and links to /citas and to the repo. Bilingual via
      __('citas.*'). Uses the public layout (neutral dark + accent theme).
  ES: Página de inicio mínima y limpia que explica que esto es una demo del
      sistema de reservas open-source y enlaza a /citas y al repo. Bilingüe vía
      __('citas.*'). Usa el layout público (tema oscuro neutro + acento).
  ============================================================================
--}}
@extends('layouts.public')

@section('title', __('citas.home_title'))

@section('content')
  <section class="max-w-3xl mx-auto px-5 py-20 sm:py-28 min-h-[70vh] flex flex-col justify-center">

    {{-- Terminal-style intro line / Línea de intro estilo terminal --}}
    <p class="font-mono text-sm text-term/70 mb-4">$ ./appointments --demo</p>

    {{-- Main heading / Título principal --}}
    <h1 class="font-sans font-bold text-4xl sm:text-5xl text-ink mb-5 leading-tight">
      {{ __('citas.home_title') }}
    </h1>

    {{-- Lead paragraph / Párrafo de entrada --}}
    <p class="text-muted text-lg mb-10 max-w-2xl" style="line-height:1.7;">
      {{ __('citas.home_lead') }}
    </p>

    {{-- Call to action: book + repo / Llamada a la acción: reservar + repo --}}
    <div class="flex flex-col sm:flex-row gap-4">
      {{-- Primary: go to the booking wizard / Primario: ir al wizard de reserva --}}
      <a href="{{ url('/citas') }}"
         class="btn-primary inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-7 py-3.5 text-white font-medium font-sans">
        {{ __('citas.home_cta_book') }} →
      </a>
      {{-- Secondary: the open-source repo / Secundario: el repo open-source --}}
      <a href="https://github.com/zeroit789/meetcita" target="_blank" rel="noopener"
         class="inline-flex items-center justify-center gap-2 rounded-lg border border-brand/30 px-7 py-3.5 text-brand-glow font-medium font-sans hover:bg-brand/10 transition-colors">
        {{ __('citas.home_cta_repo') }} ↗
      </a>
    </div>

    {{-- Tiny feature hints / Pequeñas pistas de funcionalidad --}}
    <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-4 font-mono text-xs text-faint">
      <div class="rounded-lg border border-brand/10 bg-base/40 px-4 py-3">// {{ __('citas.home_feat_1') }}</div>
      <div class="rounded-lg border border-brand/10 bg-base/40 px-4 py-3">// {{ __('citas.home_feat_2') }}</div>
      <div class="rounded-lg border border-brand/10 bg-base/40 px-4 py-3">// {{ __('citas.home_feat_3') }}</div>
    </div>

  </section>
@endsection
