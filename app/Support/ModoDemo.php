<?php

namespace App\Support;

/*
|==============================================================================
| ModoDemo — ajustes de la demo pública / public demo settings
|==============================================================================
| ES: Con MEETCITA_DEMO=true, al arrancar la app se fuerza que nada salga de
|     verdad: el correo va al log, Telegram y Google se apagan y el panel usa
|     una contraseña conocida (la que enseña la página de login). Lo llama
|     AppServiceProvider::boot(). Sin demo no toca nada.
| EN: With MEETCITA_DEMO=true, on boot the app makes sure nothing is really
|     sent: mail goes to the log, Telegram and Google are turned off and the
|     panel uses a known password (shown on the login page). Called from
|     AppServiceProvider::boot(). Without demo it touches nothing.
|==============================================================================
*/
class ModoDemo
{
    /**
     * ES: ¿Está activado el modo demo? EN: Is demo mode on?
     */
    public static function activo(): bool
    {
        return (bool) config('appointments.demo.enabled', false);
    }

    /**
     * ES: Aplica los ajustes de la demo sobre la config ya cargada.
     * EN: Applies the demo settings on top of the loaded config.
     */
    public static function aplicar(): void
    {
        if (! self::activo()) {
            return;
        }

        config([
            // ES: Ningún correo real: se escriben en storage/logs.
            // EN: No real email: they are written to storage/logs.
            'mail.default' => 'log',
            // ES: Sin Telegram ni Google (datos de visitantes fuera de terceros).
            // EN: No Telegram nor Google (visitors' data kept away from third parties).
            'services.telegram.token' => null,
            'services.telegram.chat_id' => null,
            'services.telegram.webhook_secret' => null,
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.refresh_token' => null,
            // ES: Contraseña conocida para que cualquiera pruebe el panel.
            // EN: Known password so anyone can try the panel.
            'appointments.panel.password' => (string) config('appointments.demo.panel_password', 'demo'),
        ]);
    }
}
