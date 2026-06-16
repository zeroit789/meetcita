<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\BlockedDay;
use Carbon\Carbon;
use Livewire\Component;

/*
|==============================================================================
| PanelBlockedDays — manage blocked (off) days (Livewire)
| PanelBlockedDays — gestión de días bloqueados (vacaciones) (Livewire)
|==============================================================================
| EN: Lets the owner mark dates they're NOT operating. Those dates go to the
|     blocked_days table and AvailabilityService treats them as NOT bookable,
|     so they appear disabled in the public /citas calendar. Access is already
|     protected by the panel middleware on /panel.
| ES: Permite al dueño marcar fechas en las que NO estará operativo. Esas
|     fechas van a la tabla blocked_days y AvailabilityService las trata como NO
|     reservables, así que en el calendario público de /citas salen
|     deshabilitadas. El acceso ya está protegido por el middleware del panel.
|
| INDEX / ÍNDICE
|   1. STATE .............. form fields / campos del formulario
|   2. block() ........... add a blocked day / añadir día bloqueado
|   3. unblock() ......... remove a blocked day / quitar día bloqueado
|   4. render() .......... upcoming blocked days / días bloqueados próximos
|==============================================================================
*/
class PanelBlockedDays extends Component
{
    // ── 1. STATE EN / ES ─────────────────────────────────────────────────

    // EN: Date to block (date input). Must be today or future and not repeated.
    // ES: Fecha a bloquear (input date). Hoy o futura y no repetida.
    public string $newDate = '';

    // EN: Optional reason (informational only for the owner).
    // ES: Motivo opcional (solo informativo para el dueño).
    public string $newReason = '';

    // ── 2. block() EN / ES ───────────────────────────────────────────────

    /**
     * EN: Block a new day (add it to the table).
     * ES: Bloquea un día nuevo (lo añade a la tabla).
     */
    public function block(): void
    {
        // EN: Inline rules + localised messages (Livewire requires explicit
        //     rules here, else it throws MissingRulesException).
        // ES: Reglas inline + mensajes traducidos (Livewire exige reglas
        //     explícitas aquí, si no lanza MissingRulesException).
        $this->validate([
            'newDate'   => 'required|date|after_or_equal:today|unique:blocked_days,date',
            'newReason' => 'nullable|string|max:120',
        ], [
            'newDate.required'       => __('citas.blocked_err_required'),
            'newDate.date'           => __('citas.blocked_err_invalid'),
            'newDate.after_or_equal' => __('citas.blocked_err_past'),
            'newDate.unique'         => __('citas.blocked_err_duplicate'),
            'newReason.max'          => __('citas.blocked_err_reason_long'),
        ]);

        // EN: Before blocking, ensure the day has no active appointments. If it
        //     does, blocking would orphan those bookings: warn and DON'T block.
        // ES: Antes de bloquear, comprobamos que el día no tenga citas activas.
        //     Si las hay, bloquear dejaría esas citas huérfanas: avisamos y NO
        //     bloqueamos.
        $hasAppointments = Appointment::query()
            ->whereDate('date', $this->newDate)
            ->where('status', '!=', 'cancelada')
            ->exists();

        if ($hasAppointments) {
            $this->addError('newDate', __('citas.blocked_err_has_appointments'));

            return;
        }

        BlockedDay::create([
            'date'   => $this->newDate,
            'reason' => $this->newReason ?: null,
        ]);

        // EN: Clear the form for the next one. / ES: Limpiamos el formulario.
        $this->reset(['newDate', 'newReason']);
    }

    // ── 3. unblock() EN / ES ─────────────────────────────────────────────

    /**
     * EN: Unblock a day (delete it): it becomes bookable again.
     * ES: Desbloquea un día (lo elimina): vuelve a estar disponible para reservar.
     */
    public function unblock(int $id): void
    {
        BlockedDay::whereKey($id)->delete();
    }

    // ── 4. render() EN / ES ──────────────────────────────────────────────

    /**
     * EN: Render: blocked days from today onwards, ordered. Past blocked days
     *     are not shown (they add nothing).
     * ES: Render: días bloqueados de hoy en adelante, ordenados. Los días
     *     bloqueados ya pasados no se muestran (no aportan nada).
     */
    public function render()
    {
        return view('livewire.panel-blocked-days', [
            'dias' => BlockedDay::query()
                ->whereDate('date', '>=', Carbon::today())
                ->orderBy('date')
                ->get(),
        ]);
    }
}
