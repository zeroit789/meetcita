<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;

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
|     $appointment es una foto fija sin datos de contacto (AppointmentSnapshot).
| EN: A booking becomes cancelled. $cancelledBy says who did it: the client
|     with their link ("client"), the owner in the panel ("owner") or the
|     owner rejecting it on Telegram ("telegram"). $reason = the reason the
|     owner wrote (null if none).
|     Meant to hook a CRM or another tool with a listener (see README,
|     "Events"). Fired once per real change.
|     $appointment is a fixed snapshot without contact data (AppointmentSnapshot).
|==============================================================================
*/
class AppointmentCancelled
{
    use Dispatchable;

    // ES: Quién canceló. EN: Who cancelled.
    public const BY_CLIENT = 'client';

    public const BY_OWNER = 'owner';

    public const BY_TELEGRAM = 'telegram';

    // ES: Foto fija de la cita en el momento del evento, sin datos de
    //     contacto (ver AppointmentSnapshot; el contacto, con ->current()).
    // EN: Fixed picture of the booking at the moment of the event, without
    //     contact data (see AppointmentSnapshot; the contact, via ->current()).
    public readonly AppointmentSnapshot $appointment;

    /**
     * ES: $appointment = la cita ya cancelada; $cancelledBy = "client", "owner"
     *     o "telegram"; $reason = motivo opcional.
     * EN: $appointment = the now cancelled booking; $cancelledBy = "client",
     *     "owner" or "telegram"; $reason = optional reason.
     */
    public function __construct(
        Appointment $appointment,
        public readonly string $cancelledBy,
        public readonly ?string $reason = null,
    ) {
        $this->appointment = AppointmentSnapshot::from($appointment);
    }
}
