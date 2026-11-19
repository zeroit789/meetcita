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
- **ES:** Cada ruta limitada tiene su propio contador por IP. Antes compartían uno: abrir unas cuantas veces el `.ics` o pedir retos del captcha agotaba los 5 intentos del login del panel.
  **EN:** Each rate-limited route has its own per-IP counter. They used to share one: opening the `.ics` a few times or fetching captcha challenges used up the panel login's 5 attempts.

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
- **ES:** ⚠️ Tema nuevo, claro y neutro, pensado para negocios, y **claro por defecto** (`APPOINTMENTS_THEME=light`; antes era oscuro). Si quieres seguir con el oscuro, pon `APPOINTMENTS_THEME=dark`.
  **EN:** ⚠️ New neutral light theme designed for businesses, and **light by default** (`APPOINTMENTS_THEME=light`; it used to be dark). To keep the dark one, set `APPOINTMENTS_THEME=dark`.
- **ES:** El código de cita es más fácil de leer y dictar: `APT-K7P-3QX` (dos grupos de tres, sin caracteres que se confunden como 0/O o 1/I). Los códigos antiguos siguen valiendo.
  **EN:** The booking code is easier to read and dictate: `APT-K7P-3QX` (two groups of three, without look-alike characters such as 0/O or 1/I). Old codes keep working.

### Añadido · Added

- **ES:** Captcha propio ALTCHA en la reserva (sin cookies ni terceros; `ALTCHA_ENABLED`) y cabeceras de seguridad con CSP por nonce. Las fuentes ya no se cargan de Bunny Fonts.
  **EN:** Own ALTCHA captcha on booking (no cookies or third parties; `ALTCHA_ENABLED`) and security headers with a nonce-based CSP. Fonts are no longer loaded from Bunny Fonts.
- **ES:** Primera capa de protección de datos junto al botón de reservar y nota en los correos (`APPOINTMENTS_PRIVACY_*`).
  **EN:** First-layer data-protection notice next to the booking button and a note in the emails (`APPOINTMENTS_PRIVACY_*`).
- **ES:** Tope de citas activas por correo (`APPOINTMENTS_MAX_ACTIVE_PER_EMAIL`, 2 por defecto).
  **EN:** Cap of active bookings per email (`APPOINTMENTS_MAX_ACTIVE_PER_EMAIL`, 2 by default).
- **ES:** Color de acento configurable con `APPOINTMENTS_ACCENT` (entre comillas: `"#1d4ed8"`) y `APPOINTMENTS_ACCENT_GLOW`, sin recompilar. Si el color no da un contraste de 4,5:1 con texto blanco, se usa el de por defecto.
  **EN:** Configurable accent colour with `APPOINTMENTS_ACCENT` (quoted: `"#1d4ed8"`) and `APPOINTMENTS_ACCENT_GLOW`, no rebuild needed. If the colour doesn't give a 4.5:1 contrast with white text, the default is used.
- **ES:** Accesibilidad WCAG 2.1 AA en la reserva y el panel: contraste, foco visible, etiquetas y errores anunciados. Revisado con axe en los dos temas: sin violaciones serias.
  **EN:** WCAG 2.1 AA accessibility on the booking page and the panel: contrast, visible focus, labels and announced errors. Checked with axe in both themes: no serious violations.
- **ES:** `Dockerfile` (FrankenPHP, sin root, puerto 8080, SQLite en el volumen `/data`) y `compose.yaml` con la web y el scheduler. ⚠️ Aún sin construir ni probar en CI.
  **EN:** `Dockerfile` (FrankenPHP, no root, port 8080, SQLite on the `/data` volume) and `compose.yaml` with the web and the scheduler. ⚠️ Not yet built or tested in CI.
- **ES:** Modo demo pública con `MEETCITA_DEMO=true`: correo al log, sin Telegram ni Google, aviso en las páginas, contraseña de panel conocida (`MEETCITA_DEMO_PANEL_PASSWORD`) y reinicio con datos de ejemplo cada hora (`citas:demo`). Nunca en una instalación real.
  **EN:** Public demo mode with `MEETCITA_DEMO=true`: mail to the log, no Telegram or Google, a notice on the pages, a known panel password (`MEETCITA_DEMO_PANEL_PASSWORD`) and an hourly reset with sample data (`citas:demo`). Never on a real installation.
- **ES:** README reescrito en español e inglés, con una parte para el negocio y otra para el desarrollador, y capturas nuevas.
  **EN:** README rewritten in Spanish and English, with one part for the business and one for the developer, and new screenshots.
- **ES:** El cliente cambia o cancela su cita él mismo con un enlace firmado de los correos («gestionar mi cita»), sin cuenta y hasta `APPOINTMENTS_SELF_SERVICE_HOURS_BEFORE` horas antes (24 por defecto). La página solo muestra; las acciones van por POST. Al cambiarla vuelve a pendiente y se avisa al dueño por correo y Telegram. Sin doble reserva: bloqueo de los dos días, re-comprobación sin contar la propia cita e índice único como red. `APPOINTMENTS_SELF_SERVICE=false` lo apaga.
  **EN:** Clients change or cancel their own booking with a signed link from the emails ("manage my booking"), without an account and up to `APPOINTMENTS_SELF_SERVICE_HOURS_BEFORE` hours before (24 by default). The page only shows; actions go through POST. A changed booking goes back to pending and the owner is told by email and Telegram. No double booking: lock on both days, re-check not counting the booking itself and the unique index as a safety net. `APPOINTMENTS_SELF_SERVICE=false` turns it off.
- **ES:** Recordatorio por correo la víspera de cada cita confirmada (`citas:recordar`, cada 15 minutos, nunca dos veces), en el idioma de la cita y con los enlaces de calendario y de gestionar. `APPOINTMENTS_REMINDERS` y `APPOINTMENTS_REMINDERS_FROM` (09:00). Necesita la migración nueva (`reminded_at`): ejecuta `php artisan migrate`.
  **EN:** Email reminder the day before each confirmed booking (`citas:recordar`, every 15 minutes, never twice), in the booking's language and with the calendar and manage links. `APPOINTMENTS_REMINDERS` and `APPOINTMENTS_REMINDERS_FROM` (09:00). Needs the new migration (`reminded_at`): run `php artisan migrate`.
- **ES:** Horario partido y por día de la semana (`APPOINTMENTS_HOURS` o `schedule.hours`). Ninguna cita cruza el descanso y el texto del horario agrupa los días con el mismo horario. Sin él, todo sigue como antes.
  **EN:** Split and per-weekday hours (`APPOINTMENTS_HOURS` or `schedule.hours`). No booking crosses the break and the schedule text groups days with the same hours. Without it, everything works as before.
- **ES:** Panel con vistas «Hoy», «Próximas» (por defecto) y «Todas», alta manual de citas telefónicas (correo opcional; sin correo no se envía nada) y botón opcional de WhatsApp (`APPOINTMENTS_WHATSAPP_LINK`, `APPOINTMENTS_WHATSAPP_COUNTRY_CODE`).
  **EN:** Panel with "Today", "Upcoming" (default) and "All" views, manual entry of phone bookings (optional email; without one nothing is sent) and an optional WhatsApp button (`APPOINTMENTS_WHATSAPP_LINK`, `APPOINTMENTS_WHATSAPP_COUNTRY_CODE`).
- **ES:** Eventos de Laravel para enganchar un CRM: `AppointmentRequested`, `AppointmentConfirmed`, `AppointmentCancelled` (con quién la canceló y el motivo) y `AppointmentRescheduled` (con la fecha y hora anteriores). Ver el README.
  **EN:** Laravel events to hook a CRM: `AppointmentRequested`, `AppointmentConfirmed`, `AppointmentCancelled` (with who cancelled it and the reason) and `AppointmentRescheduled` (with the previous date and time). See the README.
- **ES:** README: cómo configurar SPF, DKIM y DMARC para que los correos no acaben en spam.
  **EN:** README: how to set up SPF, DKIM and DMARC so emails don't end up in spam.
