{{--
  ============================================================================
  PanelBlockedDays view — manage off days (holidays)
  Vista PanelBlockedDays — gestión de días no disponibles (vacaciones)
  ============================================================================
  ES: Formulario para añadir una fecha no operativa + lista de las marcadas con
      botón para quitarlas. Tema claro u oscuro con el acento de config. Todas las etiquetas vía
      __('citas.*'). El div raíz único es obligatorio en Livewire.
  EN: Form to add a non-operating date + list of the marked ones with a remove
      button. Light or dark theme with the config accent. All labels via __('citas.*'). The
      single root div is mandatory in Livewire.
  ============================================================================
--}}
<div class="glass p-6">

  <h2 class="font-sans font-bold text-xl text-ink mb-1">{{ __('citas.blocked_title') }}</h2>
  <p class="text-muted text-sm mb-6">{{ __('citas.blocked_intro') }}</p>

  {{-- ── Form to add a blocked day / Formulario para añadir un día bloqueado ── --}}
  <form wire:submit="block" class="flex flex-col sm:flex-row sm:items-end gap-3 mb-6">
    {{-- Date / Fecha --}}
    <div class="flex-1">
      <label class="block text-xs text-muted mb-1.5">{{ __('citas.blocked_field_date') }}</label>
      <input type="date" wire:model="newDate"
             class="w-full rounded-lg bg-base border border-line px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-brand transition-colors" />
      @error('newDate') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Reason (optional) / Motivo (opcional) --}}
    <div class="flex-1">
      <label class="block text-xs text-muted mb-1.5">{{ __('citas.blocked_field_reason') }} <span class="text-faint">{{ __('citas.blocked_field_reason_optional') }}</span></label>
      <input type="text" wire:model="newReason" placeholder="{{ __('citas.blocked_ph_reason') }}"
             class="w-full rounded-lg bg-base border border-line px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-brand transition-colors" />
      @error('newReason') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Add button / Botón añadir --}}
    <button type="submit"
            class="btn-primary rounded-lg bg-brand px-5 py-2.5 text-white font-medium font-sans whitespace-nowrap">
      + {{ __('citas.blocked_add') }}
    </button>
  </form>

  {{-- ── List of blocked days / Lista de días bloqueados ──────────────────── --}}
  @if($dias->isEmpty())
    <p class="text-sm text-faint">{{ __('citas.blocked_empty') }}</p>
  @else
    <div class="space-y-2">
      @foreach($dias as $dia)
        <div class="flex items-center justify-between gap-4 rounded-lg border border-[var(--card-border)] bg-base px-4 py-3">
          <div class="min-w-0">
            {{-- Date in a readable format (capitalised) / Fecha legible (capitalizada) --}}
            <p class="font-sans text-ink capitalize">{{ $dia->date->locale(app()->getLocale())->isoFormat('dddd D [de] MMMM YYYY') }}</p>
            {{-- Reason, if any / Motivo, si lo hay --}}
            @if($dia->reason)
              <p class="text-xs text-muted mt-0.5">{{ $dia->reason }}</p>
            @endif
          </div>
          {{-- Remove (with confirmation to avoid accidental deletes).
               Quitar (con confirmación para no borrar por accidente). --}}
          <button type="button"
                  wire:click="unblock({{ $dia->id }})"
                  wire:confirm="{{ __('citas.blocked_confirm_remove') }}"
                  class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-danger/40 px-3 py-1.5 text-xs text-danger hover:bg-danger/10 transition-colors">
            {{ __('citas.blocked_remove') }} ✕
          </button>
        </div>
      @endforeach
    </div>
  @endif

</div>
