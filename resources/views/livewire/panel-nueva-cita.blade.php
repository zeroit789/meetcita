{{--
  ============================================================================
  PanelNuevaCita view — manual booking entry (phone)
  Vista PanelNuevaCita — alta manual de una cita (teléfono)
  ============================================================================
  ES: Botón «Nueva cita» que abre el formulario (aria-expanded). Etiquetas
      ligadas por for/id, errores con aria-invalid + aria-describedby y
      autocomplete="off" (son datos de otra persona, no del dueño). Día y
      duración con wire:model.live para recalcular las horas libres. El div
      raíz único es obligatorio en Livewire.
  EN: "New booking" button that opens the form (aria-expanded). Labels tied
      by for/id, errors with aria-invalid + aria-describedby and
      autocomplete="off" (it is someone else's data, not the owner's). Day and
      duration use wire:model.live to recompute the free times. The single
      root div is mandatory in Livewire.
  ============================================================================
--}}
<div class="w-full">
  <div class="glass p-4 sm:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h2 class="font-sans font-bold text-xl text-ink">{{ __('citas.new_title') }}</h2>
      @if($abierto)
        <button type="button" wire:click="cerrar" aria-expanded="true" aria-controls="nueva-cita-form"
                class="rounded-lg border border-[var(--card-border)] px-4 py-2 text-sm text-muted hover:text-brand-glow">{{ __('citas.new_close') }}</button>
      @else
        <button type="button" wire:click="abrir" aria-expanded="false" aria-controls="nueva-cita-form"
                class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90">{{ __('citas.new_open') }}</button>
      @endif
    </div>

    {{-- ES: Aviso de cita guardada (se anuncia sin interrumpir). EN: Saved-booking notice (announced politely). --}}
    @if($guardada)
      <p role="status" class="mt-4 rounded-lg border border-success/40 bg-success/10 px-4 py-2 text-sm text-success">{{ __('citas.new_saved', ['reference' => $guardada]) }}</p>
    @endif

    @if($abierto)
      <form id="nueva-cita-form" wire:submit="guardar" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2" novalidate>
        <p class="sm:col-span-2 text-sm text-muted">{{ __('citas.new_intro') }}</p>

        {{-- ES: Nombre (obligatorio). EN: Name (required). --}}
        <div>
          <label for="nc-nombre" class="block text-sm font-medium text-muted">{{ __('citas.new_field_name') }}</label>
          <input id="nc-nombre" type="text" wire:model="nombre" maxlength="80" required autocomplete="off" data-enfocar
                 @error('nombre') aria-invalid="true" aria-describedby="nc-nombre-error" @enderror
                 class="mt-1 w-full rounded-md border border-line bg-base px-3 py-2 text-sm text-ink focus:border-brand">
          @error('nombre') <p id="nc-nombre-error" class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        {{-- ES: Email (opcional). EN: Email (optional). --}}
        <div>
          <label for="nc-email" class="block text-sm font-medium text-muted">{{ __('citas.new_field_email') }}</label>
          <input id="nc-email" type="email" wire:model="email" maxlength="120" autocomplete="off"
                 @error('email') aria-invalid="true" aria-describedby="nc-email-error" @enderror
                 class="mt-1 w-full rounded-md border border-line bg-base px-3 py-2 text-sm text-ink focus:border-brand">
          @error('email') <p id="nc-email-error" class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        {{-- ES: Teléfono (opcional). EN: Phone (optional). --}}
        <div>
          <label for="nc-telefono" class="block text-sm font-medium text-muted">{{ __('citas.new_field_phone') }}</label>
          <input id="nc-telefono" type="tel" wire:model="telefono" maxlength="30" autocomplete="off"
                 @error('telefono') aria-invalid="true" aria-describedby="nc-telefono-error" @enderror
                 class="mt-1 w-full rounded-md border border-line bg-base px-3 py-2 text-sm text-ink focus:border-brand">
          @error('telefono') <p id="nc-telefono-error" class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        {{-- ES: Idioma de los correos. EN: Email language. --}}
        <div>
          <label for="nc-idioma" class="block text-sm font-medium text-muted">{{ __('citas.new_field_locale') }}</label>
          <select id="nc-idioma" wire:model="idioma"
                  class="mt-1 w-full rounded-md border border-line bg-base px-3 py-2 text-sm text-ink focus:border-brand">
            @foreach(config('appointments.locales', ['es']) as $loc)
              <option value="{{ $loc }}">{{ strtoupper($loc) }}</option>
            @endforeach
          </select>
        </div>

        {{-- ES: Día (de los ofrecidos). EN: Day (from the offered ones). --}}
        <div>
          <label for="nc-fecha" class="block text-sm font-medium text-muted">{{ __('citas.new_field_date') }}</label>
          <select id="nc-fecha" wire:model.live="fecha" required
                  @error('fecha') aria-invalid="true" aria-describedby="nc-fecha-error" @enderror
                  class="mt-1 w-full rounded-md border border-line bg-base px-3 py-2 text-sm text-ink focus:border-brand">
            <option value="">{{ __('citas.new_choose_day') }}</option>
            @foreach($dias as $dia)
              <option value="{{ $dia['value'] }}">{{ $dia['label'] }}</option>
            @endforeach
          </select>
          @error('fecha') <p id="nc-fecha-error" class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        {{-- ES: Duración (de la config). EN: Duration (from the config). --}}
        <div>
          <label for="nc-duracion" class="block text-sm font-medium text-muted">{{ __('citas.new_field_duration') }}</label>
          <select id="nc-duracion" wire:model.live="duracion"
                  class="mt-1 w-full rounded-md border border-line bg-base px-3 py-2 text-sm text-ink focus:border-brand">
            @foreach($duraciones as $dur)
              <option value="{{ $dur }}">{{ \App\Support\Duracion::etiqueta((int) $dur) }}</option>
            @endforeach
          </select>
        </div>

        {{-- ES: Hora libre para ese día y duración. EN: Free time for that day and duration. --}}
        <div>
          <label for="nc-hora" class="block text-sm font-medium text-muted">{{ __('citas.new_field_time') }}</label>
          <select id="nc-hora" wire:model="hora" required
                  @error('hora') aria-invalid="true" aria-describedby="nc-hora-error" @enderror
                  class="mt-1 w-full rounded-md border border-line bg-base px-3 py-2 text-sm text-ink focus:border-brand">
            <option value="">{{ __('citas.new_choose_time') }}</option>
            @foreach($huecos as $hueco)
              <option value="{{ $hueco }}">{{ $hueco }}</option>
            @endforeach
          </select>
          @if($fecha !== '' && count($huecos) === 0)
            <p class="mt-1 text-sm text-muted">{{ __('citas.new_no_slots') }}</p>
          @endif
          @error('hora') <p id="nc-hora-error" class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- ES: Modalidad (solo las activas). EN: Modality (enabled ones only). --}}
        <div>
          <label for="nc-modalidad" class="block text-sm font-medium text-muted">{{ __('citas.new_field_modality') }}</label>
          <select id="nc-modalidad" wire:model="modalidad"
                  class="mt-1 w-full rounded-md border border-line bg-base px-3 py-2 text-sm text-ink focus:border-brand">
            @foreach($modalidades as $mod)
              <option value="{{ $mod }}">{{ $mod === 'presencial' ? __('citas.modality_inperson') : __('citas.modality_online') }}</option>
            @endforeach
          </select>
        </div>

        {{-- ES: Nota (opcional). EN: Note (optional). --}}
        <div class="sm:col-span-2">
          <label for="nc-mensaje" class="block text-sm font-medium text-muted">{{ __('citas.new_field_message') }}</label>
          <textarea id="nc-mensaje" wire:model="mensaje" rows="2" maxlength="1000"
                    @error('mensaje') aria-invalid="true" aria-describedby="nc-mensaje-error" @enderror
                    class="mt-1 w-full rounded-md border border-line bg-base px-3 py-2 text-sm text-ink focus:border-brand"></textarea>
          @error('mensaje') <p id="nc-mensaje-error" class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
          <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">{{ __('citas.new_submit') }}</button>
        </div>
      </form>
    @endif
  </div>
</div>
