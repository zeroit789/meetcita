<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/*
|==============================================================================
| AppointmentCancelled — cita cancelada / booking cancelled
|==============================================================================
| ES: Una cita pasa a cancelada. $cancelledBy dice quién la canceló: el
|     cliente con su enlace ("client"), el dueño en el panel ("owner") o el
|     dueño al rechazarla por Telegram ("telegram"). $reason = motivo escrito
|     por el dueño (null si no hay).
|     Pensado para enganchar un CRM u otra herramienta con un listener
|     (ver README, «Eventos»). Se dispara una sola vez por cambio real.
| EN: A booking becomes cancelled. $cancelledBy says who did it: the client
|     with their link ("client"), the owner in the panel ("owner") or the
|     owner rejecting it on Telegram ("telegram"). $reason = the reason the
|     owner wrote (null if none).
|     Meant to hook a CRM or another tool with a listener (see README,
|     "Events"). Fired once per real change.
|==============================================================================
*/
class AppointmentCancelled
{
    use Dispatchable, SerializesModels;

    // ES: Quién canceló. EN: Who cancelled.
    public const BY_CLIENT = 'client';

    public const BY_OWNER = 'owner';

    public const BY_TELEGRAM = 'telegram';

    /**
     * ES: $appointment = la cita ya cancelada; $cancelledBy = "client", "owner"
     *     o "telegram"; $reason = motivo opcional.
     * EN: $appointment = the now cancelled booking; $cancelledBy = "client",
     *     "owner" or "telegram"; $reason = optional reason.
     */
    public function __construct(
        public Appointment $appointment,
        public string $cancelledBy,
        public ?string $reason = null,
    ) {}
}
