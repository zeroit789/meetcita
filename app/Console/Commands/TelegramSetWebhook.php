<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/*
|==============================================================================
| TelegramSetWebhook / Registrar el webhook del bot de Telegram
|==============================================================================
| EN: Registers (or reconfigures) the Telegram bot webhook pointing to the app.
|     Usage: php artisan telegram:set-webhook [--url=https://example.com/telegram/webhook]
|     If --url is omitted it defaults to APP_URL/telegram/webhook.
| ES: Registra (o reconfigura) el webhook del bot de Telegram apuntando a la app.
|     Uso: php artisan telegram:set-webhook [--url=https://example.com/telegram/webhook]
|     Si se omite --url, por defecto usa APP_URL/telegram/webhook.
|==============================================================================
*/
class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {--url= : Webhook URL (defaults to APP_URL/telegram/webhook) / URL del webhook (por defecto APP_URL/telegram/webhook)}';

    protected $description = 'Register the Telegram bot webhook in the Bot API / Registra el webhook del bot de Telegram en la Bot API';

    public function handle(): int
    {
        $token = config('services.telegram.token');
        $secret = config('services.telegram.webhook_secret');

        if (! $token) {
            $this->error('Falta TELEGRAM_BOT_TOKEN en el .env');

            return self::FAILURE;
        }

        // EN: Default URL: the app URL + /telegram/webhook. ES: URL por defecto: APP_URL + /telegram/webhook.
        $url = $this->option('url') ?: rtrim(config('app.url'), '/') . '/telegram/webhook';

        // EN: Register the webhook with the secret token for header validation.
        // ES: Registramos el webhook con el secret token para validar la cabecera.
        $res = Http::asForm()->post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url'             => $url,
            'secret_token'    => $secret,
            'allowed_updates' => json_encode(['message', 'callback_query']),
        ]);

        if ($res->json('ok')) {
            $this->info("Webhook configurado: {$url}");

            return self::SUCCESS;
        }

        $this->error('Error: ' . $res->json('description', 'desconocido'));

        return self::FAILURE;
    }
}
