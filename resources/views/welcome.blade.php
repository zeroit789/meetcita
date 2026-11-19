{{--
  ============================================================================
  WELCOME — minimal landing for "/"
  WELCOME — landing mínima para "/"
  ============================================================================
  ES: Página de inicio mínima y de cara al cliente: invita a pedir cita y
      enlaza a /citas. El enlace al código solo sale en modo demo. Bilingüe vía
      __('citas.*'). Usa el layout público (tema claro u oscuro + acento).
  EN: A tiny, client-facing home page: invites to book and links to /citas.
      The source-code link only shows in demo mode. Bilingual via
      __('citas.*'). Uses the public layout (light or dark theme + accent).
  ============================================================================
--}}
@extends('layouts.public')

@section('title', __('citas.home_title'))

@section('content')
  <section class="max-w-3xl mx-auto px-5 py-20 sm:py-28 min-h-[70vh] flex flex-col justify-center">


    {{-- Business name / Nombre del negocio --}}
    <p class="text-sm font-semibold uppercase tracking-wide text-brand-glow mb-3">{{ config('appointments.brand.name') }}</p>

    {{-- Main heading / Título principal --}}
    <h1 class="font-sans font-bold text-4xl sm:text-5xl text-ink mb-5 leading-tight">
      {{ __('citas.home_title') }}
    </h1>

    {{-- Lead paragraph / Párrafo de entrada --}}
    <p class="text-muted text-lg mb-10 max-w-2xl" style="line-height:1.7;">
      {{ __('citas.home_lead') }}
    </p>

    {{-- Call to action: book (+ repo only in demo) / Llamada a la acción: reservar (+ repo solo en demo) --}}
    <div class="flex flex-col sm:flex-row gap-4">
      {{-- Primary: go to the booking wizard / Primario: ir al wizard de reserva --}}
      <a href="{{ url('/citas') }}"
         class="btn-primary inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-7 py-3.5 text-white font-medium font-sans">
        {{ __('citas.home_cta_book') }} →
      </a>
      {{-- ES: Secundario, solo en la demo pública: el repo open-source.
           EN: Secondary, only in the public demo: the open-source repo. --}}
      @if(config('appointments.demo.enabled'))
        <a href="https://github.com/zeroit789/meetcita" target="_blank" rel="noopener"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-brand px-7 py-3.5 text-brand-glow font-medium font-sans hover:bg-brand/10 transition-colors">
          {{ __('citas.home_cta_repo') }}
        </a>
      @endif
    </div>

    {{-- How it works, in three short points / Cómo funciona, en tres puntos --}}
    <ul class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-muted">
      <li class="rounded-lg border border-[var(--card-border)] bg-surface px-4 py-3">{{ __('citas.home_feat_1') }}</li>
      <li class="rounded-lg border border-[var(--card-border)] bg-surface px-4 py-3">{{ __('citas.home_feat_2') }}</li>
      <li class="rounded-lg border border-[var(--card-border)] bg-surface px-4 py-3">{{ __('citas.home_feat_3') }}</li>
    </ul>

  </section>
@endsection
