{{--
  ============================================================================
  GESTIONAR — client's "manage my booking" page (signed link)
  GESTIONAR — página «gestionar mi cita» del cliente (enlace firmado)
  ============================================================================
  ES: Se abre desde el enlace firmado de los correos al cliente. El GET solo
      muestra la cita: cancelar y reprogramar los hace el componente Livewire
      <livewire:gestionar-cita> con acciones POST. Layout público (tema y
      selector de idioma). El controlador añade no-store y noindex.
  EN: Opened from the signed link in the client emails. The GET only shows
      the booking: cancelling and rescheduling are done by the
      <livewire:gestionar-cita> Livewire component with POST actions. Public
      layout (theme and language switcher). The controller adds no-store and
      noindex.
  ============================================================================
--}}
@extends('layouts.public')

{{-- ES: Título propio. EN: Page-specific title. --}}
@section('title', __('citas.manage_title'))

@section('content')
  <section class="max-w-5xl mx-auto px-5 py-14">
    <h1 class="font-sans font-bold text-3xl sm:text-[2rem] text-ink mb-2">{{ __('citas.manage_title') }}</h1>
    <p class="text-muted mb-10 max-w-2xl" style="line-height:1.7;">{{ __('citas.manage_intro') }}</p>

    {{-- ES: El id lo fija el servidor tras validar la firma (#[Locked] en el componente).
         EN: The id is set by the server after validating the signature (#[Locked] in the component). --}}
    <livewire:gestionar-cita :cita-id="$cita->id" />
  </section>
@endsection
