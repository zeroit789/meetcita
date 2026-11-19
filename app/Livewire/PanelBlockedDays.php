<?php

namespace App\Livewire;

use App\Http\Middleware\AdminPanelPassword;
use App\Models\Appointment;
use App\Models\BlockedDay;
use App\Services\GestionCitas;
use Carbon\Carbon;
use Livewire\Component;

/*
|==============================================================================
| PanelBlockedDays — manage blocked (off) days (Livewire)
| PanelBlockedDays — gestión de días bloqueados (vacaciones) (Livewire)
|==============================================================================
| ES: Permite al dueño marcar fechas en las que NO estará operativo. Esas
|     fechas van a la tabla blocked_days y AvailabilityService las trata como NO
|     reservables, así que en el calendario público de /citas salen
|     deshabilitadas. El acceso ya está protegido por el middleware del panel.
| EN: Lets the owner mark dates they're NOT operating. Those dates go to the
|     blocked_days table and AvailabilityService treats them as NOT bookable,
|     so they appear disabled in the public /citas calendar. Access is already
|     protected by the panel middleware on /panel.
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

    // ES: Fecha a bloquear (input date). Hoy o futura y no repetida.
    // EN: Date to block (date input). Must be today or future and not repeated.
    public string $newDate = '';

    // ES: Motivo opcional (solo informativo para el dueño).
    // EN: Optional reason (informational only for the owner).
    public string $newReason = '';

    // ── 2. block() EN / ES ───────────────────────────────────────────────

    /**
     * ES: Guarda del panel en CADA petición del componente (montaje, acciones,
     *     paginación y repintados): sin una sesión válida del panel, 403. Va en
     *     boot() y no en cada acción porque repintar también enseña los datos.
     *     Es la defensa en profundidad del middleware persistente.
     * EN: Panel guard on EVERY request of the component (mount, actions,
     *     pagination and re-renders): without a valid panel session, 403. It
     *     lives in boot() rather than in each action because re-rendering also
     *     shows the data. Defence in depth on top of the persistent middleware.
     */
    public function boot(): void
    {
        abort_unless(AdminPanelPassword::sesionValida(), 403);
    }

    /**
     * ES: Bloquea un día nuevo (lo añade a la tabla).
     * EN: Block a new day (add it to the table).
     */
    public function block(): void
    {
        // ES: Reglas inline + mensajes traducidos (Livewire exige reglas
        //     explícitas aquí, si no lanza MissingRulesException).
        // EN: Inline rules + localised messages (Livewire requires explicit
        //     rules here, else it throws MissingRulesException).
        $this->validate([
            // ES: date_format exacto: "2026-9-24" daría otra clave de bloqueo
            //     ('reserva-dia:FECHA') que la que usan las reservas.
            // EN: Exact date_format: "2026-9-24" would give a different lock
            //     key ('reserva-dia:DATE') than the one the bookings use.
            'newDate' => 'required|date_format:Y-m-d|after_or_equal:today',
            'newReason' => 'nullable|string|max:120',
        ], [
            'newDate.required' => __('citas.blocked_err_required'),
            'newDate.date_format' => __('citas.blocked_err_invalid'),
            'newDate.after_or_equal' => __('citas.blocked_err_past'),
            'newReason.max' => __('citas.blocked_err_reason_long'),
        ]);

        // ES: Comprobar y guardar DENTRO del bloqueo 'reserva-dia:FECHA', el
        //     mismo que toman la reserva pública, la reprogramación y el alta
        //     manual. Sin él, una reserva podía colarse entre «no hay citas» y
        //     el guardado y dejar una cita activa en un día bloqueado. Con él,
        //     o la reserva termina antes (y aquí se ve su cita) o empieza
        //     después (y su re-comprobación ve el día bloqueado).
        // EN: Check and store INSIDE the 'reserva-dia:DATE' lock, the same one
        //     the public booking, the reschedule and the manual entry take.
        //     Without it, a booking could slip in between "no bookings" and
        //     the save and leave an active booking on a blocked day. With it,
        //     the booking either finishes first (and its booking is seen here)
        //     or starts after (and its re-check sees the day blocked).
        $fecha = $this->newDate;
        $error = app(GestionCitas::class)->conBloqueosDeDias([$fecha], function () use ($fecha) {
            // ES: ¿Ya está bloqueado? La regla unique comparaba "2026-09-24" con el
            //     valor guardado "2026-09-24 00:00:00", no lo detectaba y el índice
            //     único de la tabla acababa en un 500. whereDate compara solo la fecha.
            // EN: Already blocked? The unique rule compared "2026-09-24" with the
            //     stored "2026-09-24 00:00:00", missed it and the table's unique
            //     index ended in a 500. whereDate compares the date only.
            if (BlockedDay::whereDate('date', $fecha)->exists()) {
                return 'citas.blocked_err_duplicate';
            }

            // ES: Antes de bloquear, comprobamos que el día no tenga citas activas.
            //     Si las hay, bloquear dejaría esas citas huérfanas: avisamos y NO
            //     bloqueamos.
            // EN: Before blocking, ensure the day has no active appointments. If it
            //     does, blocking would orphan those bookings: warn and DON'T block.
            $hasAppointments = Appointment::query()
                ->whereDate('date', $fecha)
                ->where('status', '!=', 'cancelada')
                ->exists();

            if ($hasAppointments) {
                return 'citas.blocked_err_has_appointments';
            }

            // ES: Día libre de citas: lo guardamos como bloqueado.
            // EN: Day has no bookings: store it as blocked.
            BlockedDay::create([
                'date' => $fecha,
                'reason' => $this->newReason ?: null,
            ]);

            return null;
        });

        // ES: Otra operación tenía el día bloqueado demasiado tiempo: reintentar.
        // EN: Another operation held the day for too long: try again.
        if ($error === GestionCitas::REINTENTAR) {
            $error = 'citas.blocked_err_retry';
        }
        if ($error !== null) {
            $this->addError('newDate', __($error));

            return;
        }

        // ES: Limpiamos el formulario. / EN: Clear the form for the next one.
        $this->reset(['newDate', 'newReason']);
    }

    // ── 3. unblock() EN / ES ─────────────────────────────────────────────

    /**
     * ES: Desbloquea un día (lo elimina): vuelve a estar disponible para reservar.
     * EN: Unblock a day (delete it): it becomes bookable again.
     */
    public function unblock(int $id): void
    {
        BlockedDay::whereKey($id)->delete();
    }

    // ── 4. render() EN / ES ──────────────────────────────────────────────

    /**
     * ES: Render: días bloqueados de hoy en adelante, ordenados. Los días
     *     bloqueados ya pasados no se muestran (no aportan nada).
     * EN: Render: blocked days from today onwards, ordered. Past blocked days
     *     are not shown (they add nothing).
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
