# Changelog · Registro de cambios

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y las versiones siguen [SemVer](https://semver.org/lang/es/).
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and versions follow [SemVer](https://semver.org/).

## [Sin publicar · Unreleased] — 1.0.0

### Seguridad · Security

- **ES:** El panel exige una sesión válida en cada petición de Livewire. Antes, una copia antigua de la página del panel (capturada con la sesión abierta) seguía sirviendo tras cerrar sesión para confirmar o cancelar citas y para leer nombres, correos y teléfonos de los clientes. Cerrar sesión invalida la sesión entera y cambiar la contraseña cierra todas las sesiones abiertas.
  **EN:** The panel requires a valid session on every Livewire request. Before, an old copy of the panel page (captured while logged in) still worked after logging out to confirm or cancel bookings and to read clients' names, emails and phone numbers. Logging out invalidates the whole session and changing the password closes every open session.
- **ES:** La duración, el día y la hora de una reserva solo los fija el servidor. Antes se podía reservar una duración no ofrecida (p. ej. 240 min) que solo bloqueaba media hora, o una fecha mal escrita que saltaba el bloqueo por día.
  **EN:** A booking's duration, day and time are set by the server only. Before, a non-offered duration (e.g. 240 min) could be booked while blocking only half an hour, or a malformed date could skip the per-day lock.
- **ES:** Laravel 13.43.0 (CVE-2026-128083) y Symfony 8.1.11 (avisos del 04-11-2026). `composer audit` limpio.
  **EN:** Laravel 13.43.0 (CVE-2026-128083) and Symfony 8.1.11 (advisories of 2026-11-04). Clean `composer audit`.
- **ES:** ⚠️ El enlace del `.ics` de los correos va firmado: la referencia corta sola ya no basta para descargar el asunto y el enlace de la videollamada. Los enlaces viejos ya enviados siguen valiendo para citas creadas antes de `APPOINTMENTS_ICS_UNSIGNED_UNTIL` (pon el día en que actualizas).
  **EN:** ⚠️ The emailed `.ics` link is signed: the short reference alone no longer downloads the topic and the video call link. Old links already sent keep working for bookings created before `APPOINTMENTS_ICS_UNSIGNED_UNTIL` (use the day you upgrade).
- **ES:** El secreto del webhook de Telegram se compara en tiempo constante. Los logs ya no guardan el SQL con datos del cliente.
  **EN:** The Telegram webhook secret is compared in constant time. Logs no longer store the SQL with client data.
- **ES:** Nuevo `SECURITY.md` con cómo informar de vulnerabilidades.
  **EN:** New `SECURITY.md` explaining how to report vulnerabilities.

### Cambiado · Changed

- **ES:** ⚠️ **Borrado de datos antiguos ACTIVADO por defecto.** La nueva orden `citas:purgar` se ejecuta cada noche a las 03:30 y borra las citas que pasaron hace más de 12 meses sin cambios desde entonces. Si actualizas y quieres conservarlo todo, pon `APPOINTMENTS_RETENTION_MONTHS=0` **antes** de que corra el scheduler. Prueba primero con `php artisan citas:purgar --simular`.
  **EN:** ⚠️ **Old-data purge ENABLED by default.** The new `citas:purgar` command runs every night at 03:30 and deletes bookings that took place more than 12 months ago and were not changed since. If you upgrade and want to keep everything, set `APPOINTMENTS_RETENTION_MONTHS=0` **before** the scheduler runs. Try `php artisan citas:purgar --simular` first.
- **ES:** ⚠️ Los proxies de confianza salen de `TRUSTED_PROXIES` en el `.env` (antes estaba fijo `10.0.1.0/24`). Si estabas detrás de Coolify, pon `TRUSTED_PROXIES=10.0.1.0/24`.
  **EN:** ⚠️ Trusted proxies come from `TRUSTED_PROXIES` in `.env` (it used to be a fixed `10.0.1.0/24`). If you were behind Coolify, set `TRUSTED_PROXIES=10.0.1.0/24`.
- **ES:** Las duraciones de la config se respetan de verdad (90, 120…): la cita no pisa la siguiente ni pasa del cierre. El texto del horario de la página sale de la config.
  **EN:** Configured durations are really honoured (90, 120…): the booking neither overlaps the next one nor runs past closing time. The page's schedule text comes from the config.
- **ES:** «Personas que asistirán» y el mensaje pasan a ser opcionales.
  **EN:** "Who will attend" and the message are now optional.
- **ES:** El `.ics` de una cita pendiente sale como `TENTATIVE`. Los textos del calendario van en el idioma de la cita.
  **EN:** A pending booking's `.ics` is `TENTATIVE`. Calendar texts use the booking's language.
- **ES:** El aviso de Telegram solo lleva referencia, nombre, día y hora.
  **EN:** The Telegram notice only carries reference, name, day and time.

### Añadido · Added

- **ES:** Primera capa de protección de datos junto al botón de reservar y nota en los correos (`APPOINTMENTS_PRIVACY_*`).
  **EN:** First-layer data-protection notice next to the booking button and a note in the emails (`APPOINTMENTS_PRIVACY_*`).
- **ES:** Tope de citas activas por correo (`APPOINTMENTS_MAX_ACTIVE_PER_EMAIL`, 2 por defecto).
  **EN:** Cap of active bookings per email (`APPOINTMENTS_MAX_ACTIVE_PER_EMAIL`, 2 by default).
