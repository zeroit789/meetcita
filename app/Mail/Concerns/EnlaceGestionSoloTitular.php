<?php

namespace App\Mail\Concerns;

/*
|==============================================================================
| EnlaceGestionSoloTitular — el enlace de gestionar, solo para el titular
| EnlaceGestionSoloTitular — the manage link, for the holder only
|==============================================================================
| ES: Lo usan los correos al cliente que llevan el enlace de «gestionar mi
|     cita». El enlace sale solo si el correo es para el titular y nadie más:
|       - no en la copia de los asistentes ($paraAsistentes);
|       - no si el correo lleva destinatarios en Cc o Bcc. Ningún envío
|         actual lo hace, pero un correo encolado con el código anterior
|         (asistentes en Cc) y procesado ya con este, sí: sin esta regla
|         recibirían un enlace válido.
| EN: Used by the client emails that carry the "manage my booking" link. The
|     link is included only if the email is for the holder and nobody else:
|       - not on the attendees' copy ($paraAsistentes);
|       - not if the email has Cc or Bcc recipients. No current send does
|         that, but an email queued with the previous code (attendees in Cc)
|         and processed with this one does: without this rule they would
|         get a valid link.
|==============================================================================
*/
trait EnlaceGestionSoloTitular
{
    /**
     * ES: Enlace firmado de gestionar o null (ver reglas arriba).
     * EN: Signed manage link or null (see the rules above).
     */
    protected function enlaceGestionSoloTitular(): ?string
    {
        if ($this->paraAsistentes || $this->cc !== [] || $this->bcc !== [] || count($this->to) > 1) {
            return null;
        }

        return $this->appointment->urlGestionar();
    }
}
