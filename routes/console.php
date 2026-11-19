<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|==============================================================================
| CONSOLE ROUTES / RUTAS DE CONSOLA
|==============================================================================
| ES: Tareas programadas del módulo de citas. La principal es el worker de la
|     cola que envía los emails de las citas (los Mailables implementan
|     ShouldQueue, así que reservar / confirmar / rechazar solo deja el email en
|     la cola sin esperar al SMTP; este worker lo envía después).
| EN: Scheduled tasks for the appointments module. The main scheduled job is the
|     queue worker that sends the booking emails (the Mailables implement
|     ShouldQueue, so reserving / confirming / rejecting just leaves the email in
|     the queue without waiting for SMTP; this worker sends it afterwards).
|==============================================================================
*/

// ES: Comando de ejemplo del esqueleto de Laravel (php artisan inspire).
// EN: Laravel skeleton example command (php artisan inspire).
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ES: Procesa la cola de emails (y cualquier job futuro) una vez por minuto.
//       --stop-when-empty -> sale en cuanto vacía la cola (no queda colgado)
//       --max-time=55     -> nunca pasa de 55s, deja hueco antes del siguiente minuto
//       --tries=3         -> reintenta hasta 3 veces si un envío falla
//     withoutOverlapping evita que dos workers se solapen si uno tarda.
// EN: Process the email queue (and any future job) once per minute.
//       --stop-when-empty -> exits as soon as the queue is empty (not stuck open)
//       --max-time=55     -> never runs past 55s, leaving room before the next minute
//       --tries=3         -> retries up to 3 times if a send fails
//     withoutOverlapping prevents two workers running at once if one is slow.
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// ES: Retención de datos (RGPD): cada noche borra las citas que pasaron hace
//     más de APPOINTMENTS_RETENTION_MONTHS meses (12 por defecto; 0 = nunca) y
//     los registros técnicos caducados. Ver app/Console/Commands/PurgeOldData.php.
//     A las 03:30 para no coincidir con el tráfico del día.
// EN: Data retention (GDPR): every night deletes the bookings that took place
//     more than APPOINTMENTS_RETENTION_MONTHS months ago (12 by default; 0 =
//     never) and expired technical records. See PurgeOldData.php.
//     At 03:30 so it doesn't clash with daytime traffic.
// ES: Demo pública: cada hora se vacía y se vuelven a poner datos de ejemplo.
//     Solo corre con MEETCITA_DEMO=true (la orden, además, se niega fuera).
// EN: Public demo: every hour it is emptied and sample data is put back.
//     Only runs with MEETCITA_DEMO=true (the command refuses otherwise too).
Schedule::command('citas:demo')
    ->hourly()
    ->when(fn () => (bool) config('appointments.demo.enabled'))
    ->withoutOverlapping();

Schedule::command('citas:purgar')
    ->dailyAt('03:30')
    ->withoutOverlapping();
