<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/*
|==============================================================================
| AppointmentConfirmed — cita confirmada / booking confirmed
|==============================================================================
| ES: Una cita pasa a confirmada: desde el panel (source = "panel", también
|     en el alta manual) o con el botón de Telegram (source = "telegram").
|     Pensado para enganchar un CRM u otra herramienta con un listener
|     (ver README, «Eventos»). Se dispara una sola vez por cambio real.
| EN: A booking becomes confirmed: from the panel (source = "panel", also on
|     a manual entry) or with the Telegram button (source = "telegram").
|     Meant to hook a CRM or another tool with a listener (see README,
|     "Events"). Fired once per real change.
|==============================================================================
*/
class AppointmentConfirmed
{
    use Dispatchable, SerializesModels;

    // ES: Desde dónde se confirmó. EN: Where it was confirmed from.
    public const SOURCE_PANEL = 'panel';

    public const SOURCE_TELEGRAM = 'telegram';

    /**
     * ES: $appointment = la cita ya confirmada; $source = "panel" o "telegram".
     * EN: $appointment = the now confirmed booking; $source = "panel" or "telegram".
     */
    public function __construct(
        public Appointment $appointment,
        public string $source = self::SOURCE_PANEL,
    ) {}
}
