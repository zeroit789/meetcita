{{--
  ============================================================================
  PANEL LOGIN — single shared password
  LOGIN DEL PANEL — una única contraseña compartida
  ============================================================================
  ES: Contraseña única (config('appointments.panel.password')) comprobada por
      el controlador backend. Tema claro u oscuro con el acento de config. Etiquetas vía __('citas.*').
  EN: Single password (config('appointments.panel.password')) checked by the
      backend controller. Light or dark theme with the config accent. Labels via __('citas.*').
  ============================================================================
--}}
@extends('layouts.panel')

@section('content')
  <section class="min-h-screen flex items-center justify-center px-5">
    <div class="w-full max-w-sm">

      {{-- Login card / Card de login --}}
      <div class="glass p-8">
        <h1 class="font-sans font-bold text-2xl text-ink mb-6">{{ __('citas.login_title') }}</h1>

        {{-- Wrong-password error / Error de contraseña incorrecta --}}
        @if($errors->any())
          <p class="text-xs text-danger mb-4">{{ $errors->first() }}</p>
        @endif

        {{-- ES: En la demo se enseña la contraseña. EN: The demo shows the password. --}}
        @if(config('appointments.demo.enabled'))
          <p class="text-sm mb-4">{{ __('citas.demo_panel_hint', ['password' => config('appointments.demo.panel_password', 'demo')]) }}</p>
        @endif

        <form method="POST" action="{{ route('panel.login.attempt') }}" class="space-y-4">
          @csrf

          {{-- Password field. Label tied by for/id (a11y). / Campo contraseña con label. --}}
          <div>
            <label for="panel-password" class="block text-xs text-muted mb-1.5">{{ __('citas.login_password') }}</label>
            {{-- ES: Foco accesible: borde de acento + anillo al enfocar.
                 EN: Accessible focus: accent border + ring on focus. --}}
            <input type="password" id="panel-password" name="password" autofocus autocomplete="current-password" required
                   class="w-full rounded-lg bg-base border border-line px-3 py-2.5 text-sm text-ink focus:border-brand focus:ring-2 focus:ring-brand-glow/60 focus:outline-none transition-colors" />
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
        <a href="{{ url('/') }}" class="text-sm text-muted hover:text-brand-glow transition-colors">{{ __('citas.login_back') }}</a>
      </div>

    </div>
  </section>
@endsection
