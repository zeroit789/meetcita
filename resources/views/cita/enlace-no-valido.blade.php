{{--
  ============================================================================
  ENLACE NO VÁLIDO — "manage my booking" link no longer valid
  ENLACE NO VÁLIDO — el enlace de «gestionar mi cita» ya no vale
  ============================================================================
  ES: La sirve GestionarCitaController (403) cuando el enlace de gestionar
      ha caducado, se ha revocado (la cita se cambió o se canceló) o está
      mal copiado. Explica por qué puede pasar, invita a buscar el correo más
      reciente y da el contacto del negocio. No enseña ningún dato de la cita.
      Layout público (tema y selector de idioma); el idioma lo fija el
      controlador (el de la cita cuando la firma es auténtica).
  EN: Served by GestionarCitaController (403) when the manage link has
      expired, was revoked (the booking was changed or cancelled) or is
      miscopied. It explains why that can happen, invites the client to look
      for the latest email and gives the business contact. It shows no
      booking data. Public layout (theme and language switcher); the
      language is set by the controller (the booking's when the signature
      is authentic).
  ============================================================================
--}}
@extends('layouts.public')

{{-- ES: Título propio. EN: Page-specific title. --}}
@section('title', __('citas.manage_invalid_title'))

@section('content')
  <section class="max-w-2xl mx-auto px-5 py-14">
    <h1 class="font-sans font-bold text-3xl sm:text-[2rem] text-ink mb-4">{{ __('citas.manage_invalid_title') }}</h1>

    {{-- ES: Por qué puede pasar y qué hacer. EN: Why it can happen and what to do. --}}
    <p class="text-ink" style="line-height:1.7;">{{ __('citas.manage_invalid_text') }}</p>
    <p class="mt-4 text-muted" style="line-height:1.7;">{{ __('citas.manage_invalid_hint') }}</p>

    {{-- ES: Contacto del negocio (correo y, si lo hay, teléfono).
         EN: Business contact (email and, if any, phone). --}}
    <ul class="mt-3 text-sm">
      <li><a href="mailto:{{ config('appointments.brand.owner_email') }}" class="text-brand-glow underline">{{ config('appointments.brand.owner_email') }}</a></li>
      @if(config('appointments.brand.phone'))
        <li class="mt-1"><a href="tel:{{ preg_replace('/[^\d+]/', '', (string) config('appointments.brand.phone')) }}" class="text-brand-glow underline">{{ config('appointments.brand.phone') }}</a></li>
      @endif
    </ul>

    {{-- ES: Salida: pedir una cita nueva. EN: Way out: book a new appointment. --}}
    <a href="{{ route('citas') }}" class="mt-8 inline-flex rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">{{ __('citas.manage_book_new') }}</a>
  </section>
@endsection
