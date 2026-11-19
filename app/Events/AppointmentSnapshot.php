<?php

namespace App\Events;

use App\Models\Appointment;

/*
|==============================================================================
| AppointmentSnapshot — foto fija de una cita para los eventos
| AppointmentSnapshot — fixed picture of a booking for the events
|==============================================================================
| ES: Lo que llevan los eventos de cita (App\Events\*) en $appointment: los
|     datos de la cita EN EL MOMENTO del cambio, de solo lectura y sin datos
|     de contacto (nombre, correo, teléfono, asistentes, enlace de Meet).
|     Así un listener en cola nunca mezcla la fecha anterior del evento con
|     un estado posterior de la cita, y los datos personales no viajan por
|     la cola ni acaban en logs de terceros.
|     Si el listener necesita el contacto (por ejemplo, para un CRM), carga
|     la cita con current(): es el estado ACTUAL, que puede ser posterior al
|     del evento (o null si ya se borró).
| EN: What the booking events (App\Events\*) carry in $appointment: the
|     booking's data AT THE MOMENT of the change, read-only and without
|     contact data (name, email, phone, attendees, Meet link). So a queued
|     listener never mixes the event's previous date with a later state of
|     the booking, and personal data doesn't travel through the queue nor
|     end up in third-party logs.
|     If the listener needs the contact (e.g. for a CRM), it loads the
|     booking with current(): that is the CURRENT state, which may be later
|     than the event's (or null if it was already deleted).
|==============================================================================
*/
final class AppointmentSnapshot
{
    /**
     * ES: Campos de la foto (todos escalares e inmutables).
     * EN: Snapshot fields (all scalar and immutable).
     */
    public function __construct(
        public readonly int $id,            // ES: id de la cita · EN: booking id
        public readonly string $reference,  // ES: código público · EN: public code ("APT-K7P-3QX")
        public readonly string $date,       // ES: día "YYYY-MM-DD" · EN: day "YYYY-MM-DD"
        public readonly string $time,       // ES: hora "HH:MM" · EN: time "HH:MM"
        public readonly int $duration,      // ES: minutos · EN: minutes
        public readonly string $status,     // ES: pendiente|confirmada|cancelada · EN: status
        public readonly string $modality,   // ES: online|presencial · EN: online|in person
        public readonly string $locale,     // ES: idioma de la cita · EN: booking language
        public readonly int $revision,      // ES: revisión en ese momento · EN: revision at that moment
    ) {}

    /**
     * ES: Hace la foto de la cita tal y como está ahora.
     * EN: Takes the picture of the booking as it is now.
     */
    public static function from(Appointment $cita): self
    {
        return new self(
            id: (int) $cita->id,
            reference: (string) $cita->reference,
            date: $cita->date->toDateString(),
            time: (string) $cita->time,
            duration: (int) $cita->duration,
            status: (string) $cita->status,
            modality: (string) $cita->modality,
            locale: $cita->idiomaCita(),
            revision: (int) $cita->revision,
        );
    }

    /**
     * ES: La cita ACTUAL desde la base de datos (con el contacto), o null si
     *     ya no existe. Puede haber cambiado después del evento.
     * EN: The CURRENT booking from the database (with the contact), or null
     *     if it no longer exists. It may have changed after the event.
     */
    public function current(): ?Appointment
    {
        return Appointment::find($this->id);
    }
}
