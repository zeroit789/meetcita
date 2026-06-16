{{--
  ============================================================================
  PANEL LOGIN — single shared password
  LOGIN DEL PANEL — una única contraseña compartida
  ============================================================================
  EN: Single password (config('appointments.panel.password')) checked by the
      backend controller. Neutral dark + accent theme. Labels via __('citas.*').
  ES: Contraseña única (config('appointments.panel.password')) comprobada por
      el controlador backend. Tema oscuro neutro + acento. Etiquetas vía __('citas.*').
  ============================================================================
--}}
@extends('layouts.panel')

@section('content')
  <section class="min-h-screen flex items-center justify-center px-5">
    <div class="w-full max-w-sm">

      {{-- Login card / Card de login --}}
      <div class="glass p-8">
        <p class="font-mono text-sm text-term/70 mb-1">$ ./panel.sh --login</p>
        <h1 class="font-sans font-bold text-2xl text-ink mb-6">{{ __('citas.login_title') }}</h1>

        {{-- Wrong-password error / Error de contraseña incorrecta --}}
        @if($errors->any())
          <p class="font-mono text-xs text-red-400 mb-4">// {{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('panel.login.attempt') }}" class="space-y-4">
          @csrf

          {{-- Password field. Label tied by for/id (a11y). / Campo contraseña con label. --}}
          <div>
            <label for="panel-password" class="block font-mono text-xs text-muted mb-1.5">{{ __('citas.login_password') }}</label>
            {{-- EN: Accessible focus: accent border + ring on focus.
                 ES: Foco accesible: borde de acento + anillo al enfocar. --}}
            <input type="password" id="panel-password" name="password" autofocus autocomplete="current-password" required
                   class="w-full rounded-lg bg-base/60 border border-brand/20 px-3 py-2.5 text-sm text-ink focus:border-brand focus:ring-2 focus:ring-brand-glow/60 focus:outline-none transition-colors" />
          </div>

          {{-- Submit / Botón entrar --}}
          <button type="submit"
                  class="btn-primary w-full rounded-lg bg-brand px-6 py-3 text-white font-medium font-sans">
            {{ __('citas.login_submit') }}
          </button>
        </form>
      </div>

      {{-- Back to the public site / Volver al sitio público --}}
      <div class="text-center mt-6">
        <a href="{{ url('/') }}" class="font-mono text-sm text-muted hover:text-brand-glow transition-colors">{{ __('citas.login_back') }}</a>
      </div>

    </div>
  </section>
@endsection
