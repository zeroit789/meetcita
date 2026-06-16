{{--
  ============================================================================
  PanelCitas view — appointments table for the owner
  Vista PanelCitas — tabla de citas para el dueño
  ============================================================================
  EN: Neutral dark + accent theme. All labels go through __('citas.*') so the
      panel is bilingual too. The single root div is mandatory in Livewire.
  ES: Tema oscuro neutro + acento. Todas las etiquetas pasan por __('citas.*')
      para que el panel también sea bilingüe. El div raíz único es obligatorio
      en Livewire.
  ============================================================================
--}}
<div class="w-full">

  {{-- Appointments table inside a glass card / Tabla de citas en una card glass --}}
  <div class="glass p-4 sm:p-6 overflow-x-auto">

    @if($citas->isEmpty())
      {{-- No appointments yet / Sin citas todavía --}}
      <div class="rounded-lg border border-brand/15 bg-base/60 p-8 text-center">
        <p class="font-mono text-sm text-muted">// {{ __('citas.panel_empty') }}</p>
      </div>
    @else
      <table class="w-full text-left border-collapse">
        {{-- Table header / Cabecera de la tabla --}}
        <thead>
          <tr class="font-mono text-[0.7rem] uppercase tracking-wide text-faint border-b border-brand/20">
            <th class="py-3 px-2">{{ __('citas.col_ref') }}</th>
            <th class="py-3 px-2">{{ __('citas.col_date') }}</th>
            <th class="py-3 px-2">{{ __('citas.col_time') }}</th>
            <th class="py-3 px-2">{{ __('citas.col_duration') }}</th>
            <th class="py-3 px-2">{{ __('citas.col_modality') }}</th>
            <th class="py-3 px-2">{{ __('citas.col_name') }}</th>
            <th class="py-3 px-2">{{ __('citas.col_email') }}</th>
            <th class="py-3 px-2">{{ __('citas.col_phone') }}</th>
            <th class="py-3 px-2">{{ __('citas.col_people') }}</th>
            <th class="py-3 px-2">{{ __('citas.col_message') }}</th>
            <th class="py-3 px-2">{{ __('citas.col_status') }}</th>
            <th class="py-3 px-2 text-right">{{ __('citas.col_actions') }}</th>
          </tr>
        </thead>

        <tbody class="text-sm text-ink">
          @foreach($citas as $cita)
            <tr class="border-b border-brand/10 hover:bg-brand/5 transition-colors align-top">
              {{-- Booking reference / Nº de cita (identificador público) --}}
              <td class="py-3 px-2 font-mono text-xs whitespace-nowrap font-bold text-brand-glow">{{ $cita->reference ?: '—' }}</td>
              {{-- Date / Fecha --}}
              <td class="py-3 px-2 font-mono text-xs whitespace-nowrap">
                {{ $cita->date->locale(app()->getLocale())->isoFormat('ddd D MMM YYYY') }}
              </td>
              {{-- Time / Hora --}}
              <td class="py-3 px-2 font-mono text-xs whitespace-nowrap text-brand-glow">{{ $cita->time }}</td>
              {{-- Duration / Duración --}}
              <td class="py-3 px-2 font-mono text-xs whitespace-nowrap">
                {{ $cita->duration == 60 ? __('citas.dur_1hour') : $cita->duration . ' ' . __('citas.dur_min_unit') }}
              </td>
              {{-- Modality: online (with Meet link if it exists) or in person.
                   Modalidad: online (con enlace de Meet si existe) o presencial. --}}
              <td class="py-3 px-2 font-mono text-xs whitespace-nowrap">
                @if($cita->modality === 'presencial')
                  {{-- In person: accent badge / Presencial: badge de acento --}}
                  <span class="inline-block rounded-full border border-brand/40 bg-brand/10 px-2 py-0.5 text-brand-glow">{{ __('citas.modality_inperson') }}</span>
                @else
                  {{-- Online: term badge + Meet link if generated. Only render
                       the link if the URL starts with https:// (avoids unsafe
                       schemes like javascript:/data: if the data were tampered).
                       Online: badge term + enlace Meet si se generó. Solo lo
                       renderizamos si la URL empieza por https:// (evita esquemas
                       peligrosos si el dato viniera manipulado). --}}
                  <span class="inline-block rounded-full border border-term/40 bg-term/10 px-2 py-0.5 text-term">{{ __('citas.modality_online') }}</span>
                  @if($cita->google_meet_url && \Illuminate\Support\Str::startsWith($cita->google_meet_url, 'https://'))
                    <a href="{{ $cita->google_meet_url }}" target="_blank" rel="noopener"
                       class="block mt-1 text-term underline decoration-dotted hover:text-brand-glow">{{ __('citas.meet_link') }} ↗</a>
                  @endif
                @endif
              </td>
              {{-- Name / Nombre --}}
              <td class="py-3 px-2 whitespace-nowrap">{{ $cita->name }}</td>
              {{-- Email --}}
              <td class="py-3 px-2 text-muted text-xs whitespace-nowrap">{{ $cita->email }}</td>
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
                  // EN: Badge colour + localised label by status.
                  // ES: Color del badge + etiqueta traducida según el estado.
                  $style = match($cita->status) {
                    'confirmada' => 'border-term/40 bg-term/10 text-term',
                    'cancelada'  => 'border-red-500/40 bg-red-500/10 text-red-400',
                    default      => 'border-brand/40 bg-brand/10 text-brand-glow',
                  };
                  $statusLabel = match($cita->status) {
                    'confirmada' => __('citas.status_confirmed'),
                    'cancelada'  => __('citas.status_cancelled'),
                    default      => __('citas.status_pending'),
                  };
                @endphp
                <span class="inline-block rounded-full border px-2.5 py-0.5 font-mono text-[0.65rem] uppercase {{ $style }}">
                  {{ $statusLabel }}
                </span>
              </td>
              {{-- Actions: confirm / cancel / Acciones: confirmar / cancelar --}}
              <td class="py-3 px-2 text-right whitespace-nowrap">
                <div class="inline-flex gap-1">
                  {{-- Confirm (hidden if already confirmed) / Confirmar (oculto si ya está) --}}
                  @if($cita->status !== 'confirmada')
                    <button type="button" wire:click="confirmar({{ $cita->id }})"
                            class="rounded-md border border-term/40 px-2 py-1 font-mono text-[0.65rem] text-term hover:bg-term/10 transition-colors">
                      ✓ {{ __('citas.action_confirm') }}
                    </button>
                  @endif
                  {{-- Cancel (hidden if already cancelled) / Cancelar (oculto si ya está) --}}
                  @if($cita->status !== 'cancelada')
                    <button type="button" wire:click="cancelar({{ $cita->id }})"
                            class="rounded-md border border-red-500/40 px-2 py-1 font-mono text-[0.65rem] text-red-400 hover:bg-red-500/10 transition-colors">
                      ✕ {{ __('citas.action_cancel') }}
                    </button>
                  @endif
                </div>
              </td>
            </tr>
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
