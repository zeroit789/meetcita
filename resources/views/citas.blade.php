{{--
  ============================================================================
  CITAS — public booking page
  CITAS — página pública de reserva
  ============================================================================
  ES: Renderiza el wizard <livewire:book-appointment /> dentro del layout
      público, con cabecera con toque terminal. Envoltorio server-side; Livewire
      aporta la reactividad de los pasos sin recargar. Título desde config.
  EN: Renders the <livewire:book-appointment /> wizard inside the public
      layout, with a terminal-flavoured header. Server-rendered shell; Livewire
      adds the step reactivity without reloads. Title from config.
  ============================================================================
--}}
@extends('layouts.public')

{{-- ES: Título propio. EN: Page-specific title fed into the layout's <title>. --}}
@section('title', __('citas.wrapper_title'))

@section('content')
  <section class="max-w-5xl mx-auto px-5 py-14 min-h-screen">

    {{-- ES: Volver al inicio ARRIBA (no hay que bajar para volver atrás).
         EN: Back-to-home link at the TOP (no need to scroll to go back). --}}
    <a href="{{ url('/') }}"
       class="inline-flex items-center gap-2 font-mono text-sm text-muted hover:text-brand-glow transition-colors mb-8">{{ __('citas.wrapper_back_home') }}</a>

    {{-- ES: Cabecera estilo terminal, coherente con el resto de la UI.
         EN: Terminal-style header, coherent with the rest of the UI. --}}
    <p class="font-mono text-sm text-term/70 mb-3">$ ./book.sh --new</p>
    <h1 class="font-sans font-bold text-3xl sm:text-[2rem] text-ink mb-2">{{ __('citas.wrapper_title') }}</h1>
    <p class="text-muted mb-10 max-w-2xl" style="line-height:1.7;">{{ __('citas.wrapper_intro') }}</p>

    {{-- ES: Componente Livewire de reserva (4 pasos: día / hora / datos / confirmación).
         EN: Booking Livewire component (4 steps: day / time / details / done). --}}
    <livewire:book-appointment />

  </section>
@endsection
