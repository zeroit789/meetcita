<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\Carbon;

/*
|==============================================================================
| AppointmentIcsController / Controlador del .ics de una cita
|==============================================================================
| EN: Serves an .ics (iCalendar) file for a booking.
|       - Lets the client download their booking as a standard calendar event
|         (.ics), compatible with Outlook, Apple Calendar, etc.
|       - Linked from the confirmation email. The route is public but requires
|         the exact booking 'reference' (APT-XXXXX), which only the client knows.
|     Generates a valid VCALENDAR/VEVENT per RFC 5545:
|       - Dates in UTC ('Ymd\THis\Z'); computed by the model.
|       - Text escaped (commas, semicolons, line breaks) as the RFC requires.
|     PRODID and UID are derived from the brand name and the website host
|     (config('appointments.brand.*')) so nothing is hardcoded.
| ES: Sirve un fichero .ics (iCalendar) para una cita.
|       - Permite al cliente descargar su cita como evento de calendario estándar
|         (.ics), compatible con Outlook, Apple Calendar, etc.
|       - Se enlaza desde el email de confirmación. La ruta es pública pero
|         requiere la 'reference' exacta (APT-XXXXX), que solo conoce el cliente.
|     Genera un VCALENDAR/VEVENT válido según RFC 5545:
|       - Fechas en UTC ('Ymd\THis\Z'); las calcula el modelo.
|       - Texto escapado (comas, puntos y coma, saltos) como exige el RFC.
|     PRODID y UID se derivan de la marca y del host de la web
|     (config('appointments.brand.*')) para no hardcodear nada.
|
| INDEX / ÍNDICE
|   1. SHOW ......... build + return the .ics download / construir y devolver
|   2. HELPERS ...... brand host + RFC 5545 escaping / host y escape RFC 5545
|==============================================================================
*/
class AppointmentIcsController extends Controller
{
    // ── 1. Show — construir y devolver el .ics ──────────────────────────────

    /**
     * EN: Returns the .ics of the booking identified by its 'reference'.
     *     404 if the reference matches no booking.
     * ES: Devuelve el .ics de la cita identificada por su 'reference'.
     *     404 si la referencia no corresponde a ninguna cita.
     */
    public function show(string $reference)
    {
        // EN: Look the booking up by its public reference. 404 if missing.
        // ES: Buscamos la cita por su referencia pública. 404 si no existe.
        $cita = Appointment::where('reference', $reference)->firstOrFail();

        // EN: Event dates in UTC (computed by the model). iCalendar format: 20260615T133000Z
        // ES: Fechas del evento en UTC (las calcula el modelo). Formato iCalendar: 20260615T133000Z
        $fmt = 'Ymd\THis\Z';
        $dtStart = $cita->inicioUtc()->format($fmt);
        $dtEnd = $cita->finUtc()->format($fmt);
        $dtStamp = Carbon::now('UTC')->format($fmt); // EN: .ics creation stamp · ES: sello de creación

        // EN: Event texts (reuse the model helpers). ES: Textos del evento (helpers del modelo).
        $resumen = $cita->tituloCalendario();
        $descripcion = $cita->descripcionCalendario();
        $ubicacion = $cita->ubicacionCalendario();

        // EN: Website host (e.g. "example.com") for the PRODID and UID domain.
        // ES: Host de la web (p.ej. "example.com") para el PRODID y el dominio del UID.
        $host = $this->hostMarca();

        // EN: Brand name, sanitized for the PRODID token (no slashes/spaces issues).
        // ES: Marca, saneada para el token del PRODID (sin problemas de barras/espacios).
        $marca = (string) config('appointments.brand.name');

        // EN: Stable, globally unique UID: reference + host (RFC 5545 wants an
        //     address-like unique identifier).
        // ES: UID estable y globalmente único: referencia + host (RFC 5545 pide un
        //     identificador único tipo dirección).
        $uid = $this->escapar($cita->reference) . '@' . $host;

        // EN: Map the booking status to the VEVENT STATUS.
        // ES: Mapeamos el estado de la cita al STATUS del VEVENT.
        $status = $cita->status === 'cancelada' ? 'CANCELLED' : 'CONFIRMED';

        // EN: Build the VCALENDAR (CRLF-separated lines as the RFC requires).
        // ES: Montamos el VCALENDAR (líneas separadas por CRLF como pide el RFC).
        // EN: PRODID derived from the brand name (decoupled). ES: PRODID derivado de la marca (desacoplado).
        $lineas = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//' . $this->escapar($marca) . '//Appointments//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $dtStamp,
            'DTSTART:' . $dtStart,
            'DTEND:' . $dtEnd,
            'SUMMARY:' . $this->escapar($resumen),
            'DESCRIPTION:' . $this->escapar($descripcion),
            'LOCATION:' . $this->escapar($ubicacion),
            'STATUS:' . $status,
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        // EN: RFC 5545 requires CRLF line terminators. ES: El RFC 5545 exige terminadores CRLF.
        $ics = implode("\r\n", $lineas) . "\r\n";

        // EN: Respond as a calendar download. ES: Respuesta como descarga de calendario.
        return response($ics, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="cita-' . $reference . '.ics"');
    }

    // ── 2. Helpers — host de la marca + escape RFC 5545 ─────────────────────

    /**
     * EN: Returns the website host from config('appointments.brand.website')
     *     (e.g. "example.com"). Falls back to the app's request host if missing.
     * ES: Devuelve el host de la web de config('appointments.brand.website')
     *     (p.ej. "example.com"). Cae al host de la petición si falta.
     */
    protected function hostMarca(): string
    {
        $host = parse_url((string) config('appointments.brand.website'), PHP_URL_HOST);

        return $host ?: (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
    }

    /**
     * EN: Escapes a text for an iCalendar property value (RFC 5545):
     *       - Backslashes, commas and semicolons are prefixed with '\'.
     *       - Line breaks become the literal sequence '\n'.
     * ES: Escapa un texto para un valor de propiedad iCalendar (RFC 5545):
     *       - Las contrabarras, comas y puntos y coma se prefijan con '\'.
     *       - Los saltos de línea se convierten en la secuencia literal '\n'.
     */
    protected function escapar(string $texto): string
    {
        // EN: Order matters: backslash first so we don't re-escape it later.
        // ES: Orden importante: primero la contrabarra para no re-escaparla luego.
        $texto = str_replace('\\', '\\\\', $texto);
        $texto = str_replace([',', ';'], ['\\,', '\\;'], $texto);
        // EN: Normalize real line breaks to the escaped \n sequence.
        // ES: Normalizamos saltos de línea reales a la secuencia escapada \n.
        $texto = str_replace(["\r\n", "\r", "\n"], '\\n', $texto);

        return $texto;
    }
}
