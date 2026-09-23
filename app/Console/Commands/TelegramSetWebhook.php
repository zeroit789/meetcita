<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/*
|==============================================================================
| TelegramSetWebhook / Registrar el webhook del bot de Telegram
|==============================================================================
| ES: Registra (o reconfigura) el webhook del bot de Telegram apuntando a la app.
|     Uso: php artisan telegram:set-webhook [--url=https://example.com/telegram/webhook]
|     Si se omite --url, por defecto usa APP_URL/telegram/webhook.
| EN: Registers (or reconfigures) the Telegram bot webhook pointing to the app.
|     Usage: php artisan telegram:set-webhook [--url=https://example.com/telegram/webhook]
|     If --url is omitted it defaults to APP_URL/telegram/webhook.
|==============================================================================
*/
class TelegramSetWebhook extends Command
{
    // ES: Firma del comando; --url permite sobreescribir la URL del webhook.
    // EN: Command signature; --url lets you override the webhook URL.
    protected $signature = 'telegram:set-webhook {--url= : Webhook URL (defaults to APP_URL/telegram/webhook) / URL del webhook (por defecto APP_URL/telegram/webhook)}';

    protected $description = 'Register the Telegram bot webhook in the Bot API / Registra el webhook del bot de Telegram en la Bot API';

    /**
     * ES: Llama a setWebhook de la Bot API con la URL y el secreto del .env.
     *     Devuelve SUCCESS si Telegram lo acepta y FAILURE en cualquier otro caso.
     * EN: Calls the Bot API setWebhook with the URL and the secret from .env.
     *     Returns SUCCESS if Telegram accepts it and FAILURE otherwise.
     */
    public function handle(): int
    {
        // ES: Token del bot y secreto que Telegram reenviará en cada petición.
        // EN: Bot token and the secret Telegram will send back on every request.
        $token = config('services.telegram.token');
        $secret = config('services.telegram.webhook_secret');

        // ES: Sin token no se puede llamar a la Bot API.
        // EN: Without a token the Bot API can't be called.
        if (! $token) {
            $this->error('Falta TELEGRAM_BOT_TOKEN en el .env');

            return self::FAILURE;
        }

        // ES: URL por defecto: APP_URL + /telegram/webhook. EN: Default URL: the app URL + /telegram/webhook.
        $url = $this->option('url') ?: rtrim(config('app.url'), '/').'/telegram/webhook';

        // ES: Registramos el webhook con el secret token para validar la cabecera.
        // EN: Register the webhook with the secret token for header validation.
        $res = Http::asForm()->post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => json_encode(['message', 'callback_query']),
        ]);

        // ES: La Bot API responde {"ok": true} si el registro ha ido bien.
        // EN: The Bot API answers {"ok": true} when registration succeeds.
        if ($res->json('ok')) {
            $this->info("Webhook configurado: {$url}");

            return self::SUCCESS;
        }

        $this->error('Error: '.$res->json('description', 'desconocido'));

        return self::FAILURE;
    }
}
