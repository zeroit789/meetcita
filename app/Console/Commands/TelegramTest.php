<?php

namespace App\Console\Commands;

use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

/*
|==============================================================================
| TelegramTest / Mensaje de prueba por Telegram
|==============================================================================
| EN: Sends a test message to the owner's chat to verify the bot works.
|     Usage: php artisan telegram:test
| ES: Envía un mensaje de prueba al chat del dueño para verificar que el bot
|     funciona. Uso: php artisan telegram:test
|==============================================================================
*/
class TelegramTest extends Command
{
    protected $signature = 'telegram:test {mensaje=✅ Prueba del bot de citas — funciona correctamente.}';

    protected $description = 'Send a test message via Telegram to the configured chat / Envía un mensaje de prueba por Telegram al chat configurado';

    public function handle(TelegramNotifier $tg): int
    {
        // EN: Bail out gracefully if the bot isn't configured.
        // ES: Salida limpia si el bot no está configurado.
        if (! $tg->configurado()) {
            $this->error('Telegram no configurado (faltan TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID en .env)');

            return self::FAILURE;
        }

        $id = $tg->enviar('🔔 ' . $this->argument('mensaje'));

        if ($id) {
            $this->info("Mensaje enviado (message_id={$id}).");

            return self::SUCCESS;
        }

        $this->error('No se pudo enviar el mensaje. Revisa el token/chat_id.');

        return self::FAILURE;
    }
}
