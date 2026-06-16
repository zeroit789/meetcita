<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|==============================================================================
| CONSOLE ROUTES / RUTAS DE CONSOLA
|==============================================================================
| EN: Scheduled tasks for the appointments module. The only scheduled job is the
|     queue worker that sends the booking emails (the Mailables implement
|     ShouldQueue, so reserving / confirming / rejecting just leaves the email in
|     the queue without waiting for SMTP; this worker sends it afterwards).
| ES: Tareas programadas del módulo de citas. La única tarea es el worker de la
|     cola que envía los emails de las citas (los Mailables implementan
|     ShouldQueue, así que reservar / confirmar / rechazar solo deja el email en
|     la cola sin esperar al SMTP; este worker lo envía después).
|==============================================================================
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// EN: Process the email queue (and any future job) once per minute.
//       --stop-when-empty -> exits as soon as the queue is empty (not stuck open)
//       --max-time=55     -> never runs past 55s, leaving room before the next minute
//       --tries=3         -> retries up to 3 times if a send fails
//     withoutOverlapping prevents two workers running at once if one is slow.
// ES: Procesa la cola de emails (y cualquier job futuro) una vez por minuto.
//       --stop-when-empty -> sale en cuanto vacía la cola (no queda colgado)
//       --max-time=55     -> nunca pasa de 55s, deja hueco antes del siguiente minuto
//       --tries=3         -> reintenta hasta 3 veces si un envío falla
//     withoutOverlapping evita que dos workers se solapen si uno tarda.
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
