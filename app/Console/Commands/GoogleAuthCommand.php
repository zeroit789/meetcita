<?php

namespace App\Console\Commands;

use Google\Client;
use Google\Service\Calendar;
use Illuminate\Console\Command;

/*
|==============================================================================
| GoogleAuthCommand / Comando de autorización OAuth de Google
|==============================================================================
| ES: Comando de autorización OAuth de Google. Flujo en DOS pasos (solo se hace
|     una vez para obtener el refresh_token):
|
|       1) php artisan google:auth
|          -> Imprime una URL. Ábrela, inicia sesión con la cuenta de Google y
|             autoriza el acceso al calendario. Google te devolverá un "code".
|
|       2) php artisan google:auth --code=EL_CODIGO_QUE_TE_DA_GOOGLE
|          -> Cambia ese código por los tokens e imprime el refresh_token. Cópialo
|             en el .env como GOOGLE_REFRESH_TOKEN.
|
|     Una vez en el .env, GoogleCalendarService ya puede crear/borrar eventos solo.
| EN: Google OAuth authorization command. Two-step flow (done once to obtain the
|     refresh_token):
|
|       1) php artisan google:auth
|          -> Prints a URL. Open it, sign in with the Google account and authorize
|             calendar access. Google returns a "code" (authorization code).
|
|       2) php artisan google:auth --code=THE_CODE_GOOGLE_GAVE_YOU
|          -> Exchanges that code for the tokens and prints the refresh_token.
|             Copy that refresh_token into .env as GOOGLE_REFRESH_TOKEN.
|
|     Once in .env, GoogleCalendarService can create/delete events on its own.
|==============================================================================
*/
class GoogleAuthCommand extends Command
{
    // ES: Firma del comando; --code solo se usa en el paso 2.
    // EN: Command signature; --code is only used in step 2.
    protected $signature = 'google:auth {--code= : Authorization code returned by Google (step 2) / Código de autorización devuelto por Google (paso 2)}';

    protected $description = 'Authorize Google Calendar access and get the refresh_token for .env / Autoriza el acceso a Google Calendar y obtiene el refresh_token para el .env';

    /**
     * ES: Ejecuta el flujo OAuth: sin --code imprime la URL de autorización;
     *     con --code canjea el código y muestra el refresh_token.
     * EN: Runs the OAuth flow: without --code it prints the authorization URL;
     *     with --code it exchanges the code and prints the refresh_token.
     */
    public function handle(): int
    {
        // ES: Comprobamos que existan al menos client_id y client_secret en config.
        // EN: Check at least client_id and client_secret exist in config.
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            $this->error('Faltan GOOGLE_CLIENT_ID y/o GOOGLE_CLIENT_SECRET en el .env');

            return self::FAILURE;
        }

        // ES: Construimos el cliente de Google con la config del .env.
        // EN: Build the Google client with the .env config.
        $client = new Client;
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri(config('services.google.redirect_uri', 'http://localhost'));
        // ES: accessType offline + prompt consent -> garantiza que Google devuelva
        //     un refresh_token (sin esto, a veces solo da access_token).
        // EN: offline access type + consent prompt -> guarantees Google returns a
        //     refresh_token (without this it sometimes only gives an access_token).
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        // ES: Solo pedimos permiso para gestionar eventos del calendario.
        // EN: We only request permission to manage calendar events.
        $client->addScope(Calendar::CALENDAR_EVENTS);

        $code = $this->option('code');

        // ES: PASO 2: ya tenemos el código -> lo cambiamos por los tokens.
        // EN: STEP 2: we have the code -> exchange it for the tokens.
        if ($code) {
            // ES: fetchAccessTokenWithAuthCode devuelve un array con access_token,
            //     refresh_token, expires_in, etc. (o un array con 'error' si falla).
            // EN: fetchAccessTokenWithAuthCode returns an array with access_token,
            //     refresh_token, expires_in, etc. (or an array with 'error' on failure).
            $token = $client->fetchAccessTokenWithAuthCode($code);

            // ES: Código caducado, ya usado o de otro cliente OAuth.
            // EN: Code expired, already used, or issued for another OAuth client.
            if (isset($token['error'])) {
                $this->error('Error al canjear el código: '.($token['error_description'] ?? $token['error']));

                return self::FAILURE;
            }

            // ES: Google solo da refresh_token la primera vez que se concede el acceso.
            // EN: Google only returns a refresh_token the first time access is granted.
            if (empty($token['refresh_token'])) {
                $this->warn('Google no devolvió refresh_token. Revoca el acceso de la app en');
                $this->warn('https://myaccount.google.com/permissions y vuelve a ejecutar el paso 1.');

                return self::FAILURE;
            }

            // ES: Mostramos el refresh_token para pegarlo en el .env.
            // EN: Print the refresh_token to paste into .env.
            $this->info('¡Listo! Copia esta línea en tu .env:');
            $this->newLine();
            $this->line('GOOGLE_REFRESH_TOKEN='.$token['refresh_token']);
            $this->newLine();

            return self::SUCCESS;
        }

        // ES: PASO 1: sin código -> generamos e imprimimos la URL de autorización.
        // EN: STEP 1: no code -> generate and print the authorization URL.
        $authUrl = $client->createAuthUrl();

        $this->info('1) Abre esta URL en el navegador y autoriza con la cuenta de Google:');
        $this->newLine();
        $this->line($authUrl);
        $this->newLine();
        $this->info('2) Copia el "code" que te da Google y ejecuta:');
        $this->line('   php artisan google:auth --code=EL_CODIGO');

        return self::SUCCESS;
    }
}
