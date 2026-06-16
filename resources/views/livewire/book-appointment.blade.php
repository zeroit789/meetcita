{{--
  ============================================================================
  BookAppointment view — public 4-step booking wizard
  Vista BookAppointment — wizard público de reserva en 4 pasos
  ============================================================================
  EN: Neutral dark + accent theme (resources/css/app.css). Reactive steps
      without page reloads (wire:click / wire:submit). The single root div is
      mandatory in Livewire. Durations come from config; modalities are shown
      only if enabled in config('appointments.modalities').
  ES: Tema oscuro neutro + acento (resources/css/app.css). Pasos reactivos sin
      recargar la página (wire:click / wire:submit). El div raíz único es
      obligatorio en Livewire. Las duraciones vienen de config; las modalidades
      se muestran solo si están activas en config('appointments.modalities').

  INDEX / ÍNDICE
    A. Breadcrumb ........ step indicator / indicador de pasos
    1. STEP 1 ............ pick day (calendar) / elegir día (calendario)
    2. STEP 2 ............ pick duration + time / duración + hora
    3. STEP 3 ............ details form / formulario de datos
    4. STEP 4 ............ confirmation / confirmación
  ============================================================================
--}}
<div class="w-full max-w-3xl mx-auto">

  @php
    // EN: Read the enabled modalities once (config contract). / ES: Leemos las
    //     modalidades activas una vez (contrato de config).
    $modOnline   = (bool) config('appointments.modalities.online', true);
    $modInPerson = (bool) config('appointments.modalities.in_person', true);
    // EN: Durations the client can pick (config). / ES: Duraciones elegibles (config).
    $durations   = config('appointments.schedule.durations', [30, 60]);
  @endphp

  {{-- ── A. Breadcrumb EN / ES — step indicator / indicador de pasos ──────── --}}
  <div class="flex items-center justify-center gap-2 mb-8 font-mono text-xs">
    <span class="px-2 py-1 rounded {{ $step >= 1 ? 'text-brand-glow' : 'text-faint' }}">{{ __('citas.steps_01') }}</span>
    <span class="text-faint">/</span>
    <span class="px-2 py-1 rounded {{ $step >= 2 ? 'text-brand-glow' : 'text-faint' }}">{{ __('citas.steps_02') }}</span>
    <span class="text-faint">/</span>
    <span class="px-2 py-1 rounded {{ $step >= 3 ? 'text-brand-glow' : 'text-faint' }}">{{ __('citas.steps_03') }}</span>
  </div>

  {{-- ══════════════════════════════════════════════════════════════════════
       1. STEP 1 EN / ES — PICK DAY / ELEGIR DÍA
       ══════════════════════════════════════════════════════════════════════ --}}
  @if($step === 1)
    {{-- EN: Smaller padding on mobile so calendar cells stay tappable.
         ES: Menos padding en móvil para que las celdas sigan siendo pulsables. --}}
    <div class="glass p-3 sm:p-8">
      <p class="font-mono text-sm text-term/70 mb-1">{{ __('citas.step1_cmd') }}</p>
      <h2 class="font-sans font-bold text-2xl text-ink mb-6">{{ __('citas.step1_title') }}</h2>

      {{-- ── MONTHLY CALENDAR / CALENDARIO MENSUAL ─────────────────────────── --}}
      <div class="rounded-xl border border-brand/15 bg-base/40 p-2 sm:p-5">

        {{-- Header: month navigation + month/year / Cabecera: navegación + mes/año --}}
        <div class="flex items-center justify-between mb-4">
          {{-- Previous month / Mes anterior --}}
          <button type="button" wire:click="prevMonth" @disabled(! $canPrev)
                  class="flex h-9 w-9 items-center justify-center rounded-lg border border-brand/20 text-brand-glow transition-all hover:border-brand hover:bg-brand/10 disabled:opacity-30 disabled:cursor-not-allowed"
                  aria-label="{{ __('citas.cal_prev_aria') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
          </button>

          {{-- Current month + year (capitalised) / Mes y año (capitalizado) --}}
          <span class="font-sans font-bold text-lg text-ink capitalize">{{ $monthLabel }}</span>

          {{-- Next month / Mes siguiente --}}
          <button type="button" wire:click="nextMonth" @disabled(! $canNext)
                  class="flex h-9 w-9 items-center justify-center rounded-lg border border-brand/20 text-brand-glow transition-all hover:border-brand hover:bg-brand/10 disabled:opacity-30 disabled:cursor-not-allowed"
                  aria-label="{{ __('citas.cal_next_aria') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>

        {{-- Weekday header (Mon -> Sun). Decorative for screen readers (each
             bookable day already carries its full date in the aria-label).
             Día de semana (L -> D). Decorativo para lectores de pantalla. --}}
        <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-2" aria-hidden="true">
          @foreach(explode(',', __('citas.cal_weekdays')) as $wd)
            <div class="text-center font-mono text-[0.7rem] uppercase tracking-wide text-faint py-1">{{ $wd }}</div>
          @endforeach
        </div>

        {{-- Day grid: one row per week. role="grid" exposes it as a calendar.
             Rejilla de días: una fila por semana. role="grid" para a11y. --}}
        <div class="space-y-1 sm:space-y-2" role="grid">
          @foreach($weeks as $week)
            <div class="grid grid-cols-7 gap-1 sm:gap-2" role="row">
              @foreach($week as $cell)
                {{-- Human-readable date for the aria-label, in the active locale.
                     Fecha legible para el aria-label, en el idioma activo. --}}
                @php
                  $apptLocale = app()->getLocale();
                  $readableDate = $apptLocale === 'en'
                      ? \Carbon\Carbon::parse($cell['date'])->locale('en')->isoFormat('dddd, MMMM D, YYYY')
                      : \Carbon\Carbon::parse($cell['date'])->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
                @endphp
                @if($cell['reservable'])
                  {{-- Bookable day: clickable button. aria-label = full date +
                       "available" (a bare number isn't enough for a11y).
                       Día reservable: botón clicable. aria-label = fecha + "disponible". --}}
                  <button type="button"
                          wire:click="selectDate('{{ $cell['date'] }}')"
                          role="gridcell"
                          aria-label="{{ $readableDate }}{{ $cell['isToday'] ? __('citas.cal_today_suffix') : '' }}{{ __('citas.cal_available') }}{{ $cell['isSelected'] ? __('citas.cal_selected') : '' }}"
                          @if($cell['isSelected']) aria-pressed="true" @endif
                          @class([
                            // EN: min-h keeps cells finger-friendly on mobile.
                            // ES: min-h mantiene las celdas usables con el dedo.
                            'aspect-square min-h-[40px] flex flex-col items-center justify-center rounded-lg border text-sm font-sans transition-all focus:outline-none',
                            // Selected day: accent fill / Día seleccionado: relleno acento
                            'border-brand bg-brand text-white font-bold' => $cell['isSelected'],
                            // Normal bookable day: faint border + hover / Día normal
                            'border-brand/25 bg-base/60 text-ink hover:border-brand hover:bg-brand/10 hover:-translate-y-0.5' => ! $cell['isSelected'],
                          ])>
                    <span>{{ $cell['day'] }}</span>
                    {{-- "Today" dot / Punto "hoy" --}}
                    @if($cell['isToday'])
                      <span class="mt-0.5 h-1 w-1 rounded-full bg-term"></span>
                    @endif
                  </button>
                @else
                  {{-- Non-bookable day (weekend, past, out of range): dimmed.
                       Color via inline rgba so the dimming is reliable.
                       Día NO reservable (finde, pasado, fuera de rango): atenuado. --}}
                  <div class="aspect-square min-h-[40px] flex items-center justify-center rounded-lg text-sm font-sans select-none"
                       role="gridcell"
                       aria-disabled="true"
                       aria-label="{{ $readableDate }}{{ __('citas.cal_unavailable') }}"
                       style="color: {{ $cell['isCurrent'] ? 'rgba(139,139,156,0.55)' : 'rgba(139,139,156,0.20)' }};">
                    {{ $cell['day'] }}
                  </div>
                @endif
              @endforeach
            </div>
          @endforeach
        </div>
      </div>

      {{-- Working-hours note / Nota de horario laboral --}}
      <div class="mt-6 flex items-center gap-3 rounded-lg border border-brand/15 bg-base/40 px-4 py-3">
        <svg class="h-5 w-5 flex-shrink-0 text-term" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <p class="font-sans text-base text-ink">
          <span class="font-semibold text-brand-glow">{{ __('citas.cal_hours_label') }}</span>
          {{ __('citas.cal_hours_value') }} <span class="text-muted">{{ __('citas.cal_hours_note') }}</span>
        </p>
      </div>
    </div>
  @endif

  {{-- ══════════════════════════════════════════════════════════════════════
       2. STEP 2 EN / ES — PICK DURATION + TIME / ELEGIR DURACIÓN + HORA
       ══════════════════════════════════════════════════════════════════════ --}}
  @if($step === 2)
    <div class="glass p-6 sm:p-8">
      <p class="font-mono text-sm text-term/70 mb-1">{{ __('citas.step2_cmd') }} {{ $selectedDate }}</p>
      <h2 class="font-sans font-bold text-2xl text-ink mb-2">{{ __('citas.step2_title') }}</h2>
      <p class="text-muted text-sm mb-5">
        {{ __('citas.step2_subtitle_pre') }}
        {{-- Date in the active locale (natural pattern per language) --}}
        <span class="text-brand-glow font-medium">{{ app()->getLocale() === 'en'
            ? \Carbon\Carbon::parse($selectedDate)->locale('en')->isoFormat('dddd, MMMM D')
            : \Carbon\Carbon::parse($selectedDate)->locale('es')->isoFormat('dddd D [de] MMMM') }}</span>
      </p>

      {{-- DURATION selector — one button per config duration. Changing it
           recalculates the free slots.
           Selector de DURACIÓN — un botón por duración de config. Cambiarlo
           recalcula los huecos. --}}
      <div class="mb-6">
        <p class="font-mono text-xs text-muted mb-2">{{ __('citas.step2_duration_label') }}</p>
        <div class="inline-flex rounded-lg border border-brand/20 bg-base/60 p-1 gap-1">
          @foreach($durations as $dur)
            {{-- EN: Label: dedicated keys for 30/60, generic for others.
                 ES: Etiqueta: claves dedicadas para 30/60, genérica para otras. --}}
            @php
              $durLabel = match((int) $dur) {
                30      => __('citas.btn_30min'),
                60      => __('citas.btn_1hour'),
                default => __('citas.btn_minutes', ['min' => $dur]),
              };
            @endphp
            <button type="button" wire:click="setDuration({{ (int) $dur }})"
                    class="rounded-md px-4 py-2 font-mono text-sm transition-all
                           {{ $duration === (int) $dur ? 'bg-brand text-white' : 'text-muted hover:text-brand-glow' }}">
              {{ $durLabel }}
            </button>
          @endforeach
        </div>
      </div>

      {{-- One-off error (slot taken between steps) / Error puntual (hueco ocupado) --}}
      @error('selectedTime')
        <p class="font-mono text-xs text-red-400 mb-4">// {{ $message }}</p>
      @enderror

      @if(count($freeSlots) > 0)
        {{-- Free-times grid (responsive columns) / Rejilla de horas libres --}}
        <div class="grid grid-cols-2 min-[360px]:grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
          @foreach($freeSlots as $slot)
            <button type="button"
                    wire:click="selectTime('{{ $slot }}')"
                    class="rounded-lg border border-brand/20 bg-base/60 px-2 py-3 font-mono text-sm text-ink transition-all hover:border-brand hover:bg-brand/10 hover:-translate-y-0.5 focus:outline-none focus:border-brand">
              {{ $slot }}
            </button>
          @endforeach
        </div>
      @else
        {{-- No slots that day / Sin huecos ese día --}}
        <div class="rounded-lg border border-brand/15 bg-base/60 p-6 text-center">
          <p class="font-mono text-sm text-muted">{{ __('citas.step2_no_slots') }}</p>
          <p class="text-faint text-sm mt-1">{{ __('citas.step2_no_slots_hint') }}</p>
        </div>
      @endif

      {{-- Back to step 1 / Volver al paso 1 --}}
      <button type="button" wire:click="back"
              class="mt-6 inline-flex items-center gap-2 font-mono text-sm text-muted hover:text-brand-glow transition-colors">
        {{ __('citas.step2_back') }}
      </button>
    </div>
  @endif

  {{-- ══════════════════════════════════════════════════════════════════════
       3. STEP 3 EN / ES — DETAILS FORM / FORMULARIO DE DATOS
       ══════════════════════════════════════════════════════════════════════ --}}
  @if($step === 3)
    <div class="glass p-6 sm:p-8">
      <p class="font-mono text-sm text-term/70 mb-1">{{ __('citas.step3_cmd') }}</p>
      <h2 class="font-sans font-bold text-2xl text-ink mb-2">{{ __('citas.step3_title') }}</h2>

      {{-- Selected-appointment summary (date + time + duration) / Resumen de la cita --}}
      <p class="text-muted text-sm mb-6">
        {{ __('citas.step3_summary_pre') }}
        <span class="text-brand-glow font-medium">{{ app()->getLocale() === 'en'
            ? \Carbon\Carbon::parse($selectedDate)->locale('en')->isoFormat('dddd, MMMM D')
            : \Carbon\Carbon::parse($selectedDate)->locale('es')->isoFormat('dddd D [de] MMMM') }}</span>
        {{ __('citas.step3_summary_at') }}
        <span class="text-brand-glow font-medium">{{ $selectedTime }}</span>
        ·
        <span class="text-brand-glow font-medium">{{ $duration === 60 ? __('citas.dur_1hour') : __('citas.dur_30min') }}</span>
      </p>

      {{-- wire:submit fires reserve() without reloading / sin recargar --}}
      <form wire:submit="reserve" class="space-y-4">

        {{-- Anti-spam honeypot: hidden to humans. If a bot fills it, max:0 fails.
             Honeypot anti-spam: oculto a humanos. Si un bot lo rellena, max:0 falla. --}}
        <div class="hidden" aria-hidden="true">
          <label>{{ __('citas.honeypot_label') }}</label>
          <input type="text" wire:model="website" tabindex="-1" autocomplete="off" />
        </div>

        {{-- ── MODALITY: online (video call) / in person ─────────────────────
             EN: Two toggle buttons. Shown only if enabled in config. If only
                 one modality is enabled, the toggle is hidden entirely (the
                 component default already matches). Online = term accent (Meet),
                 In person = brand accent.
             ES: Dos botones toggle. Se muestran solo si están activos en config.
                 Si solo hay una modalidad activa, el toggle se oculta entero (el
                 valor por defecto del componente ya encaja). Online = acento
                 term (Meet), Presencial = acento de marca. --}}
        @if($modOnline && $modInPerson)
          <div>
            <label class="block font-mono text-xs text-muted mb-1.5">{{ __('citas.field_modality') }}</label>
            <div class="grid grid-cols-2 gap-3">
              {{-- Online (with Meet) / Online (con Meet) --}}
              <button type="button" wire:click="setModality('online')"
                      @class([
                        'flex items-center gap-2 rounded-lg border px-3 py-3 font-sans text-sm transition-all focus:outline-none',
                        'border-term bg-term/10 text-term font-medium' => $modality === 'online',
                        'border-brand/20 bg-base/60 text-muted hover:border-term/60 hover:text-term' => $modality !== 'online',
                      ])>
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span class="text-left leading-tight">
                  {{ __('citas.modality_online') }}
                  <span class="block font-mono text-[0.72rem] opacity-90">{{ __('citas.modality_online_sub') }}</span>
                </span>
              </button>

              {{-- In person / Presencial --}}
              <button type="button" wire:click="setModality('presencial')"
                      @class([
                        'flex items-center gap-2 rounded-lg border px-3 py-3 font-sans text-sm transition-all focus:outline-none',
                        'border-brand bg-brand/10 text-brand-bright font-medium' => $modality === 'presencial',
                        'border-brand/20 bg-base/60 text-muted hover:border-brand hover:text-brand-glow' => $modality !== 'presencial',
                      ])>
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                <span class="text-left leading-tight">
                  {{ __('citas.modality_inperson') }}
                  <span class="block font-mono text-[0.72rem] opacity-90">{{ __('citas.modality_inperson_sub') }}</span>
                </span>
              </button>
            </div>
            @error('modality') <p class="font-mono text-xs text-red-400 mt-1">// {{ $message }}</p> @enderror
          </div>
        @endif

        {{-- Name / Nombre --}}
        <div>
          <label for="appt-name" class="block font-mono text-xs text-muted mb-1.5">{{ __('citas.field_name') }}</label>
          <input type="text" id="appt-name" wire:model="name" placeholder="{{ __('citas.ph_name') }}" required
                 class="w-full rounded-lg bg-base/60 border border-brand/20 px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-brand transition-colors" />
          @error('name') <p class="font-mono text-xs text-red-400 mt-1">// {{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div>
          <label for="appt-email" class="block font-mono text-xs text-muted mb-1.5">{{ __('citas.field_email') }}</label>
          <input type="email" id="appt-email" wire:model="email" placeholder="{{ __('citas.ph_email') }}" required
                 class="w-full rounded-lg bg-base/60 border border-brand/20 px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-brand transition-colors" />
          @error('email') <p class="font-mono text-xs text-red-400 mt-1">// {{ $message }}</p> @enderror
        </div>

        {{-- Phone (optional) / Teléfono (opcional) --}}
        <div>
          <label for="appt-phone" class="block font-mono text-xs text-muted mb-1.5">{{ __('citas.field_phone') }} <span class="text-faint">{{ __('citas.field_phone_optional') }}</span></label>
          <input type="tel" id="appt-phone" wire:model="phone" placeholder="{{ __('citas.ph_phone') }}"
                 class="w-full rounded-lg bg-base/60 border border-brand/20 px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-brand transition-colors" />
          @error('phone') <p class="font-mono text-xs text-red-400 mt-1">// {{ $message }}</p> @enderror
        </div>

        {{-- People attending / Personas que asistirán --}}
        <div>
          <label for="appt-attendees" class="block font-mono text-xs text-muted mb-1.5">{{ __('citas.field_attendees') }}</label>
          <input type="text" id="appt-attendees" wire:model="attendees" placeholder="{{ __('citas.ph_attendees') }}" required
                 class="w-full rounded-lg bg-base/60 border border-brand/20 px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-brand transition-colors" />
          @error('attendees') <p class="font-mono text-xs text-red-400 mt-1">// {{ $message }}</p> @enderror
        </div>

        {{-- Attendee emails (OPTIONAL) — only meaningful for ONLINE meetings
             (the invite carries the video-call link). Reactively shown only
             when modality === 'online'.
             Correos de asistentes (OPCIONAL) — solo tienen sentido en ONLINE
             (la invitación lleva el enlace de la videollamada). Se muestran de
             forma reactiva solo cuando modality === 'online'. --}}
        @if($modOnline && $modality === 'online')
          <div>
            <label for="appt-attendee-emails" class="block font-mono text-xs text-muted mb-1.5">{{ __('citas.field_attendee_emails') }} <span class="text-faint">{{ __('citas.field_attendee_emails_optional') }}</span></label>
            <input type="text" id="appt-attendee-emails" wire:model="attendeeEmails" placeholder="{{ __('citas.ph_attendee_emails') }}"
                   class="w-full rounded-lg bg-base/60 border border-brand/20 px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-brand transition-colors" />
            <p class="font-mono text-xs text-faint mt-1">{{ __('citas.help_attendee_emails') }}</p>
            @error('attendeeEmails') <p class="font-mono text-xs text-red-400 mt-1">// {{ $message }}</p> @enderror
          </div>
        @endif

        {{-- Message: what to discuss / Mensaje: qué tratar --}}
        <div>
          <label for="appt-message" class="block font-mono text-xs text-muted mb-1.5">{{ __('citas.field_message') }}</label>
          <textarea id="appt-message" wire:model="message" rows="4" placeholder="{{ __('citas.ph_message') }}" required
                    class="w-full rounded-lg bg-base/60 border border-brand/20 px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-brand transition-colors resize-none"></textarea>
          @error('message') <p class="font-mono text-xs text-red-400 mt-1">// {{ $message }}</p> @enderror
        </div>

        {{-- Actions: back + book / Acciones: atrás + reservar --}}
        <div class="flex items-center justify-between gap-4 pt-2">
          <button type="button" wire:click="back"
                  class="font-mono text-sm text-muted hover:text-brand-glow transition-colors">
            {{ __('citas.step3_back') }}
          </button>

          {{-- Book button: shows a loading state while processing.
               Botón reservar: muestra estado de carga mientras procesa. --}}
          <button type="submit"
                  class="btn-primary rounded-lg bg-brand px-6 py-3 text-white font-medium font-sans disabled:opacity-60"
                  wire:loading.attr="disabled" wire:target="reserve">
            <span wire:loading.remove wire:target="reserve">{{ __('citas.btn_book') }}</span>
            <span wire:loading wire:target="reserve">{{ __('citas.btn_booking') }}</span>
          </button>
        </div>
      </form>
    </div>
  @endif

  {{-- ══════════════════════════════════════════════════════════════════════
       4. STEP 4 EN / ES — CONFIRMATION / CONFIRMACIÓN
       ══════════════════════════════════════════════════════════════════════ --}}
  @if($step === 4)
    <div class="glass p-8 sm:p-10 text-center">
      {{-- Terminal-green check icon / Icono check verde terminal --}}
      <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border border-term/40 bg-term/10">
        <svg class="h-8 w-8 text-term" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>
      </div>

      <p class="font-mono text-sm text-term/70 mb-2">{{ __('citas.step4_cmd') }}</p>
      <h2 class="font-sans font-bold text-2xl sm:text-3xl text-brand-glow mb-3">{{ __('citas.step4_title') }}</h2>

      <p class="text-muted mb-2" style="line-height:1.7;">
        {{ __('citas.step4_summary_pre') }}
        <span class="text-ink font-medium">{{ $confirmedDate }}</span>
        {{ __('citas.step4_summary_at') }}
        <span class="text-ink font-medium">{{ $confirmedTime }}</span>
        ({{ $confirmedDuration === 60 ? __('citas.dur_1hour') : __('citas.dur_30min') }})
        {{ __('citas.step4_summary_post') }}
      </p>

      {{-- Booking reference: the client can quote it when contacting us.
           Identificador de la cita: el cliente puede citarlo al contactarnos. --}}
      @if($confirmedRef)
        <div class="inline-flex items-center gap-2 rounded-lg border border-brand/25 bg-base/60 px-4 py-2 mb-4">
          <span class="font-mono text-xs text-faint uppercase tracking-wide">{{ __('citas.step4_ref_label') }}</span>
          <span class="font-mono text-lg font-bold text-white">{{ $confirmedRef }}</span>
        </div>
        <p class="font-mono text-xs text-faint mb-6">{{ __('citas.step4_ref_hint') }}</p>
      @endif

      <p class="font-mono text-sm text-faint mb-8">{{ __('citas.step4_email_note') }}</p>

      {{-- Book another (restart the flow) / Pedir otra cita (reinicia el flujo) --}}
      <button type="button" wire:click="resetFlow"
              class="inline-flex items-center gap-2 rounded-lg border border-brand px-6 py-3 text-brand-glow font-medium font-sans hover:bg-brand/10 transition-colors">
        {{ __('citas.btn_book_another') }}
      </button>
    </div>
  @endif

</div>
