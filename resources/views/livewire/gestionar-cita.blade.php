{{--
  ============================================================================
  GestionarCita view — the client's "manage my booking"
  Vista GestionarCita — «gestionar mi cita» del cliente
  ============================================================================
  ES: Tema claro u oscuro con el acento de config (tokens de app.css). Tres
      vistas: 'ver' (datos de la cita + botones), 'reprogramar' (días y horas
      libres) y 'cancelar' (confirmación). Cada título lleva data-paso-titulo:
      al cambiar de vista, resources/js/app.js le lleva el foco. Todas las
      acciones son llamadas simples con literales (Livewire csp_safe). El div
      raíz único es obligatorio en Livewire.
  EN: Light or dark theme with the config accent (app.css tokens). Three
      views: 'ver' (booking details + buttons), 'reprogramar' (free days and
      times) and 'cancelar' (confirmation). Each heading carries
      data-paso-titulo: on a view change, resources/js/app.js moves focus to
      it. Every action is a simple call with literals (Livewire csp_safe). The
      single root div is mandatory in Livewire.

  INDEX / ÍNDICE
    A. NOTICE ........... result of the last action / aviso de la última acción
    1. VER .............. booking + deadline + buttons / cita + plazo + botones
    2. REPROGRAMAR ...... days + times + confirm / días + horas + confirmar
    3. CANCELAR ......... confirmation / confirmación
  ============================================================================
--}}
<div class="w-full max-w-3xl mx-auto">

  {{-- ── A. NOTICE / AVISO ─────────────────────────────────────────────────
       ES: Éxito = role="status" (se anuncia sin interrumpir); error = role="alert".
       EN: Success = role="status" (announced politely); error = role="alert". --}}
  @if($aviso)
    <div @if($avisoOk) role="status" @else role="alert" tabindex="-1" data-error-envio @endif
         @class([
           'mb-6 rounded-lg border px-4 py-3 text-sm',
           'border-success/40 bg-success/10 text-success' => $avisoOk,
           'border-danger/40 bg-danger/10 text-danger' => ! $avisoOk,
         ])>{{ __($aviso) }}</div>
  @endif

  {{-- ══════════════════════════════════════════════════════════════════════
       1. VER EN / ES — booking details / datos de la cita
       ══════════════════════════════════════════════════════════════════════ --}}
  @if($vista === 'ver')
    <div class="glass p-6 sm:p-8">
      <h2 id="gestionar-titulo" class="font-sans font-bold text-2xl text-ink mb-4" tabindex="-1" data-paso-titulo="ver">{{ __('citas.manage_title') }}</h2>

      {{-- ES: Datos de la cita como lista de definiciones. EN: Booking details as a description list. --}}
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 rounded-lg border border-[var(--card-border)] bg-base p-4" aria-label="{{ __('citas.manage_summary_aria') }}">
        <div>
          <dt class="text-xs uppercase tracking-wide text-faint">{{ __('citas.manage_label_ref') }}</dt>
          <dd class="font-mono font-bold text-brand-glow">{{ $cita->reference }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-faint">{{ __('citas.manage_label_status') }}</dt>
          <dd class="text-ink">{{ match($cita->status) { 'confirmada' => __('citas.status_confirmed'), 'cancelada' => __('citas.status_cancelled'), default => __('citas.status_pending') } }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-faint">{{ __('citas.manage_label_date') }}</dt>
          <dd class="text-ink first-letter:uppercase">{{ $fechaCita }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-faint">{{ __('citas.manage_label_time') }}</dt>
          <dd class="text-ink font-semibold">{{ $cita->time }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-faint">{{ __('citas.manage_label_duration') }}</dt>
          <dd class="text-ink">{{ $duracion }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-faint">{{ __('citas.manage_label_modality') }}</dt>
          <dd class="text-ink">{{ $cita->modality === 'presencial' ? __('citas.modality_inperson') : __('citas.modality_online') }}</dd>
        </div>
      </dl>

      @if($cita->status === 'cancelada')
        {{-- ES: Cancelada: nada que gestionar; enlace para pedir otra. EN: Cancelled: nothing to manage; link to book again. --}}
        <p class="mt-6 text-muted">{{ __('citas.manage_cancelled_state') }}</p>
        <a href="{{ route('citas') }}" class="mt-4 inline-flex rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">{{ __('citas.manage_book_new') }}</a>
      @elseif($puede)
        {{-- ES: Dentro del plazo: hasta cuándo y los dos botones. EN: Within the notice: until when and both buttons. --}}
        <p class="mt-6 text-sm text-muted">{{ __('citas.manage_deadline', ['date' => $limiteFecha, 'time' => $limiteHora]) }}</p>
        @unless($duracionOfrecida)
          <p class="mt-2 text-sm text-muted">{{ __('citas.manage_duration_unavailable') }}</p>
        @endunless
        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
          @if($duracionOfrecida)
            <button type="button" wire:click="abrirReprogramar"
                    class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">{{ __('citas.manage_btn_reschedule') }}</button>
          @endif
          <button type="button" wire:click="abrirCancelar"
                  class="rounded-lg border border-danger/50 px-5 py-2.5 text-sm font-semibold text-danger hover:bg-danger/10">{{ __('citas.manage_btn_cancel') }}</button>
        </div>
      @else
        {{-- ES: Fuera de plazo: se explica y se da el contacto. EN: Past the notice: explained, with the contact. --}}
        <p class="mt-6 text-ink">{{ __('citas.manage_too_late', ['hours' => $horas]) }}</p>
        <p class="mt-2 text-sm text-muted">{{ __('citas.manage_contact') }}</p>
        <ul class="mt-2 text-sm">
          <li><a href="mailto:{{ config('appointments.brand.owner_email') }}" class="text-brand-glow underline">{{ config('appointments.brand.owner_email') }}</a></li>
          @if(config('appointments.brand.phone'))
            <li class="mt-1"><a href="tel:{{ preg_replace('/[^\d+]/', '', (string) config('appointments.brand.phone')) }}" class="text-brand-glow underline">{{ config('appointments.brand.phone') }}</a></li>
          @endif
        </ul>
      @endif
    </div>
  @endif

  {{-- ══════════════════════════════════════════════════════════════════════
       2. REPROGRAMAR EN / ES — new day + time / día y hora nuevos
       ══════════════════════════════════════════════════════════════════════ --}}
  @if($vista === 'reprogramar')
    <div class="glass p-6 sm:p-8">
      <h2 class="font-sans font-bold text-2xl text-ink mb-2" tabindex="-1" data-paso-titulo="reprogramar">{{ __('citas.manage_reschedule_title') }}</h2>
      <p class="text-sm text-muted mb-6">{{ __('citas.manage_reschedule_note') }}</p>

      {{-- ES: Días ofrecidos: grupo con nombre; el elegido lleva aria-pressed.
           EN: Offered days: named group; the chosen one carries aria-pressed. --}}
      <p id="gestionar-dias" class="text-sm font-medium text-muted mb-2">{{ __('citas.manage_days_label') }}</p>
      <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 mb-6" role="group" aria-labelledby="gestionar-dias">
        @foreach($dias as $dia)
          <button type="button" wire:click="elegirDia('{{ $dia['value'] }}')"
                  aria-pressed="{{ $nuevaFecha === $dia['value'] ? 'true' : 'false' }}"
                  @class([
                    'min-h-[44px] rounded-lg border px-2 py-2 text-sm transition-colors',
                    'border-brand bg-brand text-white font-semibold' => $nuevaFecha === $dia['value'],
                    'border-[var(--card-border)] bg-base text-ink hover:border-brand hover:bg-brand/10' => $nuevaFecha !== $dia['value'],
                  ])>{{ $dia['label'] }}</button>
        @endforeach
      </div>

      @if($nuevaFecha !== '')
        {{-- ES: Horas libres del día para la duración de la cita. EN: Free times that day for the booking's duration. --}}
        <p id="gestionar-horas" class="text-sm font-medium text-muted mb-2">{{ __('citas.manage_times_label', ['duration' => $duracion]) }}</p>
        @if(count($huecos) > 0)
          <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-6" role="group" aria-labelledby="gestionar-horas">
            @foreach($huecos as $hueco)
              <button type="button" wire:click="elegirHora('{{ $hueco }}')"
                      aria-pressed="{{ $nuevaHora === $hueco ? 'true' : 'false' }}"
                      @class([
                        'min-h-[44px] rounded-lg border px-2 py-2 text-sm transition-colors',
                        'border-brand bg-brand text-white font-semibold' => $nuevaHora === $hueco,
                        'border-[var(--card-border)] bg-base text-ink hover:border-brand hover:bg-brand/10' => $nuevaHora !== $hueco,
                      ])>{{ $hueco }}</button>
            @endforeach
          </div>
        @else
          <p class="mb-6 rounded-lg border border-[var(--card-border)] bg-base p-4 text-sm text-muted">{{ __('citas.manage_no_slots') }}</p>
        @endif
      @endif

      @if($nuevaFecha !== '' && $nuevaHora !== '')
        {{-- ES: Resumen del cambio y botón de confirmar. EN: Change summary and confirm button. --}}
        <p class="mb-4 text-ink font-medium first-letter:uppercase">{{ __('citas.manage_new_summary', ['date' => $nuevaFechaTexto, 'time' => $nuevaHora]) }}</p>
      @endif

      <div class="flex flex-col gap-3 sm:flex-row">
        @if($nuevaFecha !== '' && $nuevaHora !== '')
          <button type="button" wire:click="confirmarReprogramacion"
                  class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">{{ __('citas.manage_reschedule_confirm') }}</button>
        @endif
        <button type="button" wire:click="volver"
                class="rounded-lg border border-[var(--card-border)] px-5 py-2.5 text-sm text-muted hover:text-brand-glow">{{ __('citas.manage_back') }}</button>
      </div>
    </div>
  @endif

  {{-- ══════════════════════════════════════════════════════════════════════
       3. CANCELAR EN / ES — confirmation / confirmación
       ══════════════════════════════════════════════════════════════════════ --}}
  @if($vista === 'cancelar')
    <div class="glass p-6 sm:p-8">
      <h2 class="font-sans font-bold text-2xl text-ink mb-2" tabindex="-1" data-paso-titulo="cancelar">{{ __('citas.manage_cancel_title') }}</h2>
      <p class="text-muted mb-2 first-letter:uppercase">{{ $fechaCita }} · {{ $cita->time }} ({{ $duracion }})</p>
      <p class="text-sm text-muted mb-6">{{ __('citas.manage_cancel_text') }}</p>
      <div class="flex flex-col gap-3 sm:flex-row">
        <button type="button" wire:click="cancelar"
                class="rounded-lg bg-danger px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">{{ __('citas.manage_cancel_confirm') }}</button>
        <button type="button" wire:click="volver"
                class="rounded-lg border border-[var(--card-border)] px-5 py-2.5 text-sm text-muted hover:text-brand-glow">{{ __('citas.manage_back') }}</button>
      </div>
    </div>
  @endif

</div>
