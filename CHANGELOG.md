# Changelog · Registro de cambios

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y las versiones siguen [SemVer](https://semver.org/lang/es/).
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and versions follow [SemVer](https://semver.org/).

## [Sin publicar · Unreleased]

### Cambiado · Changed

- **ES:** CI: `shivammathur/setup-php` 2.40.0, `pnpm/action-setup` v6.1.1 y `actions/setup-node` v7.1.0, fijadas por SHA completo.
  **EN:** CI: `shivammathur/setup-php` 2.40.0, `pnpm/action-setup` v6.1.1 and `actions/setup-node` v7.1.0, pinned by full SHA.
- **ES:** ⚠️ `concurrently` 10.0.7 (dev), que ya no funciona en Node 20: el proyecto pide ahora **Node.js 22.12+** (`engines` de `package.json` y README). `composer dev` sigue igual.
  **EN:** ⚠️ `concurrently` 10.0.7 (dev), which no longer runs on Node 20: the project now requires **Node.js 22.12+** (`package.json` `engines` and README). `composer dev` is unchanged.
- **ES:** `phpunit/phpunit` 13.4.8 (dev). Los 240 tests pasan sin cambios ni avisos.
  **EN:** `phpunit/phpunit` 13.4.8 (dev). All 240 tests pass with no changes and no warnings.
- **ES:** `altcha` 3.7.0 (captcha del navegador). Su único cambio de comportamiento afecta a `serverVerificationFields`, que aquí no se usa. Probado en el navegador con la CSP con nonce: verifica sin violaciones.
  **EN:** `altcha` 3.7.0 (browser captcha). Its only behaviour change affects `serverVerificationFields`, which isn't used here. Tested in the browser under the nonce CSP: it verifies with no violations.
- **ES:** `laravel/pao` 1.1.6 (dev), con arreglos para PHPUnit 13.4.
  **EN:** `laravel/pao` 1.1.6 (dev), with fixes for PHPUnit 13.4.

### Arreglado · Fixed

- **ES:** `compose.yaml`: el servicio `scheduler` desactiva el healthcheck heredado de la imagen (no sirve HTTP y salía «unhealthy»). El README ya no marca Docker como «pendiente de probar»: probado con la v1.0.0.
  **EN:** `compose.yaml`: the `scheduler` service disables the healthcheck inherited from the image (it serves no HTTP and showed as unhealthy). The README no longer flags Docker as "not yet tested": tested with v1.0.0.

## [1.0.0] — 2026-11-19

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
- **ES:** Bloquear un día en el panel toma el mismo bloqueo por día que las reservas y comprueba y guarda dentro de él. Antes, una reserva hecha justo a la vez podía quedarse activa en un día bloqueado.
  **EN:** Blocking a day in the panel takes the same per-day lock as bookings and checks and stores inside it. Before, a booking made at that very moment could stay active on a blocked day.
- **ES:** Las llamadas a Google Calendar tienen tiempo límite (5 s para conectar, 10 s en total): algunas se hacen con el bloqueo del día tomado y una llamada colgada podía durar más que el bloqueo.
  **EN:** Google Calendar calls have a time limit (5 s to connect, 10 s in total): some run while holding the day lock and a hung call could outlast the lock.
- **ES:** El correo de «cita confirmada» no se envía si la cita cambió mientras esperaba en la cola (por ejemplo, el cliente la reprogramó): ya no anuncia como confirmada una hora pendiente.
  **EN:** The "booking confirmed" email isn't sent if the booking changed while it waited in the queue (e.g. the client rescheduled it): it no longer announces a pending time as confirmed.
- **ES:** Un enlace de gestionar caducado, revocado o mal copiado muestra una página propia, en el idioma de la cita, que explica por qué y da el contacto del negocio (antes, un 403 sin más).
  **EN:** An expired, revoked or miscopied manage link shows its own page, in the booking's language, explaining why and giving the business contact (before, a bare 403).
- **ES:** Quedan dos casos raros documentados en «Limitaciones» del README: la invitación de Google puede salir aunque el cliente cancele o cambie la cita justo mientras se crea el evento, y el correo de «cita confirmada» puede salir si la cita cambia en el instante exacto entre su última comprobación y el envío.
  **EN:** Two rare cases remain, documented under "Limitations" in the README: the Google invitation may go out even if the client cancels or changes the booking right while the event is being created, and the "booking confirmed" email may go out if the booking changes in the exact instant between its last check and the send.

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
- **ES:** El cliente cambia o cancela su cita él mismo con un enlace firmado de los correos («gestionar mi cita»), sin cuenta y hasta `APPOINTMENTS_SELF_SERVICE_HOURS_BEFORE` horas antes (24 por defecto). La página solo muestra; las acciones van por POST. Al cambiarla vuelve a pendiente y se avisa al dueño por correo y Telegram. Sin doble reserva: bloqueo de los dos días, re-comprobación sin contar la propia cita e índice único como red. `APPOINTMENTS_SELF_SERVICE=false` lo apaga. El enlace va **solo al titular** (los asistentes reciben sus correos aparte y sin él), **caduca a la hora de la cita** y **deja de valer** en cuanto la cita se cancela (por el cliente, en el panel o en Telegram) o se reprograma; con la cita nueva llega un enlace nuevo. Necesita las migraciones nuevas (`revision` y `manage_token`): ejecuta `php artisan migrate`.
  **EN:** Clients change or cancel their own booking with a signed link from the emails ("manage my booking"), without an account and up to `APPOINTMENTS_SELF_SERVICE_HOURS_BEFORE` hours before (24 by default). The page only shows; actions go through POST. A changed booking goes back to pending and the owner is told by email and Telegram. No double booking: lock on both days, re-check not counting the booking itself and the unique index as a safety net. `APPOINTMENTS_SELF_SERVICE=false` turns it off. The link goes **to the holder only** (attendees get their emails separately, without it), **expires at the booking time** and **stops working** as soon as the booking is cancelled (by the client, in the panel or in Telegram) or rescheduled; a new link comes with the new booking. Needs the new migrations (`revision` and `manage_token`): run `php artisan migrate`.
- **ES:** Recordatorio por correo la víspera de cada cita confirmada (`citas:recordar`, cada 15 minutos, nunca dos veces), en el idioma de la cita y con los enlaces de calendario y de gestionar. `APPOINTMENTS_REMINDERS` y `APPOINTMENTS_REMINDERS_FROM` (09:00). Necesita la migración nueva (`reminded_at`): ejecuta `php artisan migrate`. Si el envío falla se reintenta 15 minutos después, salvo que la cita haya cambiado entre medias; si el proceso se cae justo entre marcar y enviar, esa cita se queda sin recordatorio (ver «Limitaciones» en el README).
  **EN:** Email reminder the day before each confirmed booking (`citas:recordar`, every 15 minutes, never twice), in the booking's language and with the calendar and manage links. `APPOINTMENTS_REMINDERS` and `APPOINTMENTS_REMINDERS_FROM` (09:00). Needs the new migration (`reminded_at`): run `php artisan migrate`. If sending fails it is retried 15 minutes later, unless the booking changed meanwhile; if the process dies right between marking and sending, that booking gets no reminder (see "Limitations" in the README).
- **ES:** Horario partido y por día de la semana (`APPOINTMENTS_HOURS` o `schedule.hours`). Ninguna cita cruza el descanso y el texto del horario agrupa los días con el mismo horario. Sin él, todo sigue como antes.
  **EN:** Split and per-weekday hours (`APPOINTMENTS_HOURS` or `schedule.hours`). No booking crosses the break and the schedule text groups days with the same hours. Without it, everything works as before.
- **ES:** Panel con vistas «Hoy», «Próximas» (por defecto) y «Todas», alta manual de citas telefónicas (correo opcional; sin correo no se envía nada) y botón opcional de WhatsApp (`APPOINTMENTS_WHATSAPP_LINK`, `APPOINTMENTS_WHATSAPP_COUNTRY_CODE`; si el número no es válido, no sale el botón). Los botones de confirmar del panel y de Telegram llevan la revisión de la cita: si el cliente la cambió después, aunque sea en el mismo segundo, no confirman la hora nueva sin verla.
  **EN:** Panel with "Today", "Upcoming" (default) and "All" views, manual entry of phone bookings (optional email; without one nothing is sent) and an optional WhatsApp button (`APPOINTMENTS_WHATSAPP_LINK`, `APPOINTMENTS_WHATSAPP_COUNTRY_CODE`; if the number isn't valid, the button isn't shown). The panel and Telegram confirm buttons carry the booking's revision: if the client changed it afterwards, even within the same second, they don't confirm the new time unseen.
- **ES:** Eventos de Laravel para enganchar un CRM: `AppointmentRequested`, `AppointmentConfirmed`, `AppointmentCancelled` (con quién la canceló y el motivo) y `AppointmentRescheduled` (con la fecha y hora anteriores). Cada evento lleva en `$appointment` una foto fija de la cita sin datos de contacto (`AppointmentSnapshot`); el contacto se carga con `$appointment->current()`. Ver el README.
  **EN:** Laravel events to hook a CRM: `AppointmentRequested`, `AppointmentConfirmed`, `AppointmentCancelled` (with who cancelled it and the reason) and `AppointmentRescheduled` (with the previous date and time). Each event carries in `$appointment` a fixed snapshot of the booking without contact data (`AppointmentSnapshot`); the contact is loaded with `$appointment->current()`. See the README.
- **ES:** README: cómo configurar SPF, DKIM y DMARC para que los correos no acaben en spam.
  **EN:** README: how to set up SPF, DKIM and DMARC so emails don't end up in spam.
