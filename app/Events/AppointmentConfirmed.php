<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;

/*
|==============================================================================
| AppointmentConfirmed — cita confirmada / booking confirmed
|==============================================================================
| ES: Una cita pasa a confirmada: desde el panel (source = "panel", también
|     en el alta manual) o con el botón de Telegram (source = "telegram").
|     Pensado para enganchar un CRM u otra herramienta con un listener
|     (ver README, «Eventos»). Se dispara una sola vez por cambio real.
|     $appointment es una foto fija sin datos de contacto (AppointmentSnapshot).
| EN: A booking becomes confirmed: from the panel (source = "panel", also on
|     a manual entry) or with the Telegram button (source = "telegram").
|     Meant to hook a CRM or another tool with a listener (see README,
|     "Events"). Fired once per real change.
|     $appointment is a fixed snapshot without contact data (AppointmentSnapshot).
|==============================================================================
*/
class AppointmentConfirmed
{
    use Dispatchable;

    // ES: Desde dónde se confirmó. EN: Where it was confirmed from.
    public const SOURCE_PANEL = 'panel';

    public const SOURCE_TELEGRAM = 'telegram';

    // ES: Foto fija de la cita en el momento del evento, sin datos de
    //     contacto (ver AppointmentSnapshot; el contacto, con ->current()).
    // EN: Fixed picture of the booking at the moment of the event, without
    //     contact data (see AppointmentSnapshot; the contact, via ->current()).
    public readonly AppointmentSnapshot $appointment;

    /**
     * ES: $appointment = la cita ya confirmada; $source = "panel" o "telegram".
     * EN: $appointment = the now confirmed booking; $source = "panel" or "telegram".
     */
    public function __construct(
        Appointment $appointment,
        public readonly string $source = self::SOURCE_PANEL,
    ) {
        $this->appointment = AppointmentSnapshot::from($appointment);
    }
}
