{{--
  ============================================================================
  PanelCitas view — appointments table for the owner
  Vista PanelCitas — tabla de citas para el dueño
  ============================================================================
  ES: Tema claro u oscuro con el acento de config. Todas las etiquetas pasan por __('citas.*')
      para que el panel también sea bilingüe. Filtro «Hoy / Próximas / Todas»
      (aria-pressed) y botón opcional de WhatsApp por cita. El div raíz único
      es obligatorio en Livewire.
  EN: Light or dark theme with the config accent. All labels go through __('citas.*') so the
      panel is bilingual too. "Today / Upcoming / All" filter (aria-pressed)
      and an optional WhatsApp button per booking. The single root div is
      mandatory in Livewire.
  ============================================================================
--}}
<div class="w-full">

  {{-- Appointments table inside a glass card / Tabla de citas en una card glass --}}
  <div class="glass p-4 sm:p-6 overflow-x-auto">

    {{-- ES: Título de la sección (nombra también la tabla). EN: Section heading (also names the table). --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
      <h2 id="citas-titulo" class="font-sans font-bold text-xl text-ink">
        {{ __('citas.panel_list_title') }}
        {{-- ES: En «Hoy», la fecha de hoy junto al título. EN: In "Today", today's date next to the heading. --}}
        @if($vista === 'hoy')
          <span class="font-normal text-muted text-base">· {{ __('citas.panel_today_heading', ['date' => $hoyTexto]) }}</span>
        @endif
      </h2>

      {{-- ES: Filtro de vistas: grupo con nombre; el activo lleva aria-pressed.
           EN: View filter: named group; the active one carries aria-pressed. --}}
      <div class="inline-flex rounded-lg border border-line bg-base p-1 gap-1" role="group" aria-label="{{ __('citas.panel_view_aria') }}">
        @foreach(['hoy' => __('citas.panel_view_today'), 'proximas' => __('citas.panel_view_upcoming'), 'todas' => __('citas.panel_view_all')] as $clave => $etiqueta)
          <button type="button" wire:click="verVista('{{ $clave }}')"
                  aria-pressed="{{ $vista === $clave ? 'true' : 'false' }}"
                  class="rounded-md px-3 py-1.5 text-sm transition-colors {{ $vista === $clave ? 'bg-brand text-white' : 'text-muted hover:text-brand-glow' }}">{{ $etiqueta }}</button>
        @endforeach
      </div>
    </div>

    {{-- ES: Error controlado de una acción (p. ej. confirmar una cita que no está pendiente).
         EN: Handled action error (e.g. confirming a booking that is not pending). --}}
    @error('accion')
      <div role="alert" class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-2 text-sm text-danger">{{ $message }}</div>
    @enderror

    @if($citas->isEmpty())
      {{-- No appointments yet / Sin citas todavía --}}
      <div class="rounded-lg border border-[var(--card-border)] bg-base p-8 text-center">
        <p class="text-sm text-muted">{{ match($vista) { 'hoy' => __('citas.panel_empty_today'), 'proximas' => __('citas.panel_empty_upcoming'), default => __('citas.panel_empty') } }}</p>
      </div>
    @else
      {{-- ES: La tabla toma su nombre del título de la sección (lectores de pantalla).
           EN: The table takes its name from the section heading (screen readers). --}}
      <table class="w-full text-left border-collapse" aria-labelledby="citas-titulo">
        {{-- Table header / Cabecera de la tabla --}}
        <thead>
          <tr class="text-xs uppercase tracking-wide text-faint border-b border-[var(--card-border)]">
            <th scope="col" class="py-3 px-2">{{ __('citas.col_ref') }}</th>
            <th scope="col" class="py-3 px-2">{{ __('citas.col_date') }}</th>
            <th scope="col" class="py-3 px-2">{{ __('citas.col_time') }}</th>
            <th scope="col" class="py-3 px-2">{{ __('citas.col_duration') }}</th>
            <th scope="col" class="py-3 px-2">{{ __('citas.col_modality') }}</th>
            <th scope="col" class="py-3 px-2">{{ __('citas.col_name') }}</th>
            <th scope="col" class="py-3 px-2">{{ __('citas.col_email') }}</th>
            <th scope="col" class="py-3 px-2">{{ __('citas.col_phone') }}</th>
            <th scope="col" class="py-3 px-2">{{ __('citas.col_people') }}</th>
            <th scope="col" class="py-3 px-2">{{ __('citas.col_message') }}</th>
            <th scope="col" class="py-3 px-2">{{ __('citas.col_status') }}</th>
            <th scope="col" class="py-3 px-2 text-right">{{ __('citas.col_actions') }}</th>
          </tr>
        </thead>

        <tbody class="text-sm text-ink">
          @foreach($citas as $cita)
            <tr class="border-b border-[var(--card-border)] hover:bg-brand/5 transition-colors align-top">
              {{-- Booking reference / Nº de cita (identificador público) --}}
              <td class="py-3 px-2 font-mono text-xs whitespace-nowrap font-bold text-brand-glow">{{ $cita->reference ?: '—' }}</td>
              {{-- Date / Fecha --}}
              <td class="py-3 px-2 text-xs whitespace-nowrap">
                {{ $cita->date->locale(app()->getLocale())->isoFormat('ddd D MMM YYYY') }}
              </td>
              {{-- Time / Hora --}}
              <td class="py-3 px-2 text-xs whitespace-nowrap text-brand-glow">{{ $cita->time }}</td>
              {{-- Duration / Duración --}}
              <td class="py-3 px-2 text-xs whitespace-nowrap">
                {{ $cita->duration == 60 ? __('citas.dur_1hour') : $cita->duration . ' ' . __('citas.dur_min_unit') }}
              </td>
              {{-- Modality: online (with Meet link if it exists) or in person.
                   Modalidad: online (con enlace de Meet si existe) o presencial. --}}
              <td class="py-3 px-2 text-xs whitespace-nowrap">
                @if($cita->modality === 'presencial')
                  {{-- In person: accent badge / Presencial: badge de acento --}}
                  <span class="inline-block rounded-full border border-brand/40 bg-brand/10 px-2 py-0.5 text-brand-glow">{{ __('citas.modality_inperson') }}</span>
                @else
                  {{-- Online: success badge + Meet link if generated. Only render
                       the link if the URL starts with https:// (avoids unsafe
                       schemes like javascript:/data: if the data were tampered).
                       Online: badge verde + enlace Meet si se generó. Solo lo
                       renderizamos si la URL empieza por https:// (evita esquemas
                       peligrosos si el dato viniera manipulado). --}}
                  <span class="inline-block rounded-full border border-success/40 bg-success/10 px-2 py-0.5 text-success">{{ __('citas.modality_online') }}</span>
                  @if($cita->google_meet_url && \Illuminate\Support\Str::startsWith($cita->google_meet_url, 'https://'))
                    <a href="{{ $cita->google_meet_url }}" target="_blank" rel="noopener"
                       class="block mt-1 text-success underline decoration-dotted hover:text-brand-glow">{{ __('citas.meet_link') }} ↗</a>
                  @endif
                @endif
              </td>
              {{-- Name / Nombre --}}
              <td class="py-3 px-2 whitespace-nowrap">{{ $cita->name }}</td>
              {{-- Email --}}
              <td class="py-3 px-2 text-muted text-xs whitespace-nowrap">{{ $cita->email !== '' ? $cita->email : '—' }}</td>
              {{-- Phone / Teléfono --}}
              <td class="py-3 px-2 text-muted text-xs whitespace-nowrap">{{ $cita->phone ?: '—' }}</td>
              {{-- Attendees / Personas asistentes --}}
              <td class="py-3 px-2 text-muted text-xs max-w-[10rem]">{{ $cita->attendees ?: '—' }}</td>
              {{-- Message (trimmed so it doesn't break the table) / Mensaje (recortado) --}}
              <td class="py-3 px-2 text-muted text-xs max-w-[16rem]">
                <span title="{{ $cita->message }}">{{ \Illuminate\Support\Str::limit($cita->message, 80) }}</span>
              </td>
              {{-- Status: colour badge per value / Estado: badge con color por valor --}}
              <td class="py-3 px-2 whitespace-nowrap">
                @php
                  // ES: Color del badge + etiqueta traducida según el estado.
                  // EN: Badge colour + localised label by status.
                  $style = match($cita->status) {
                    'confirmada' => 'border-success/40 bg-success/10 text-success',
                    'cancelada'  => 'border-danger/40 bg-danger/10 text-danger',
                    default      => 'border-brand/40 bg-brand/10 text-brand-glow',
                  };
                  $statusLabel = match($cita->status) {
                    'confirmada' => __('citas.status_confirmed'),
                    'cancelada'  => __('citas.status_cancelled'),
                    default      => __('citas.status_pending'),
                  };
                @endphp
                <span class="inline-block rounded-full border px-2.5 py-0.5 text-xs uppercase {{ $style }}">
                  {{ $statusLabel }}
                </span>
              </td>
              {{-- Actions: confirm / cancel / Acciones: confirmar / cancelar --}}
              <td class="py-3 px-2 text-right whitespace-nowrap">
                <div class="inline-flex gap-1">
                  {{-- ES: WhatsApp (opcional, si hay teléfono): pestaña nueva y rel="noopener".
                       EN: WhatsApp (optional, if there is a phone): new tab and rel="noopener". --}}
                  @if($wa = $cita->urlWhatsapp())
                    <a href="{{ $wa }}" target="_blank" rel="noopener"
                       aria-label="{{ __('citas.whatsapp_aria', ['name' => $cita->name]) }}"
                       class="rounded-md border border-success/40 px-2 py-1 text-xs text-success hover:bg-success/10 transition-colors">{{ __('citas.action_whatsapp') }}</a>
                  @endif
                  {{-- ES: Confirmar (solo si está pendiente) / EN: Confirm (only if pending) --}}
                  @if($cita->status === 'pendiente')
                    <button type="button" wire:click="confirmar({{ $cita->id }}, '{{ $cita->revision }}')"
                            class="rounded-md border border-success/40 px-2 py-1 text-xs text-success hover:bg-success/10 transition-colors">
                      <span aria-hidden="true">✓</span> {{ __('citas.action_confirm') }}
                    </button>
                  @endif
                  {{-- ES: Cancelar (oculto si ya está): abre el formulario del motivo.
                       EN: Cancel (hidden if already cancelled): opens the reason form. --}}
                  @if($cita->status !== 'cancelada')
                    <button type="button" wire:click="pedirCancelacion({{ $cita->id }})"
                            class="rounded-md border border-danger/40 px-2 py-1 text-xs text-danger hover:bg-danger/10 transition-colors">
                      <span aria-hidden="true">✕</span> {{ __('citas.action_cancel') }}
                    </button>
                  @endif
                </div>
              </td>
            </tr>
            {{-- ES: Formulario de cancelación con motivo opcional (se envía por email al cliente).
                 EN: Cancellation form with an optional reason (emailed to the client). --}}
            @if($cancelandoId === $cita->id)
              <tr class="border-b border-[var(--card-border)] bg-danger/5">
                <td colspan="12" class="py-3 px-2">
                  <form wire:submit="cancelar({{ $cita->id }})" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    {{-- ES: Etiqueta ligada al campo por for/id; el JS lleva el foco al
                             campo al abrirse (data-enfocar).
                         EN: Label tied to the field by for/id; JS moves focus to the
                             field when it opens (data-enfocar). --}}
                    <div class="flex-1">
                      <label for="cancel-reason-{{ $cita->id }}" class="block text-sm font-medium text-muted">{{ __('citas.cancel_reason_label') }}</label>
                      <textarea id="cancel-reason-{{ $cita->id }}" data-enfocar wire:model="motivoCancelacion" rows="2" maxlength="1000"
                                @error('motivoCancelacion') aria-invalid="true" aria-describedby="cancel-reason-error" @enderror
                                placeholder="{{ __('citas.cancel_reason_placeholder') }}"
                                class="mt-1 w-full rounded-md border border-line bg-base px-2 py-1 text-sm text-ink focus:border-brand"></textarea>
                      @error('motivoCancelacion') <p id="cancel-reason-error" class="text-sm text-danger mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="inline-flex gap-1">
                      <button type="submit"
                              class="rounded-md border border-danger/40 px-2 py-1 text-xs text-danger hover:bg-danger/10 transition-colors">
                        <span aria-hidden="true">✕</span> {{ __('citas.cancel_submit') }}
                      </button>
                      <button type="button" wire:click="cerrarCancelacion"
                              class="rounded-md border border-brand/30 px-2 py-1 text-xs text-muted hover:bg-brand/10 transition-colors">
                        {{ __('citas.cancel_back') }}
                      </button>
                    </div>
                  </form>
                </td>
              </tr>
            @endif
          @endforeach
        </tbody>
      </table>
    @endif
  </div>

  {{-- Pagination links: only if there's more than one page. Livewire
       intercepts clicks without reloading the page.
       Enlaces de paginación: solo si hay más de una página. Livewire intercepta
       los clicks sin recargar la página. --}}
  @if($citas->hasPages())
    <div class="mt-4">
      {{ $citas->links() }}
    </div>
  @endif

</div>
