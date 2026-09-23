<?php

namespace App\Console\Commands;

use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

/*
|==============================================================================
| TelegramTest / Mensaje de prueba por Telegram
|==============================================================================
| ES: Envía un mensaje de prueba al chat del dueño para verificar que el bot
|     funciona. Uso: php artisan telegram:test
| EN: Sends a test message to the owner's chat to verify the bot works.
|     Usage: php artisan telegram:test
|==============================================================================
*/
class TelegramTest extends Command
{
    // ES: Firma del comando; el argumento "mensaje" es opcional y trae un texto por defecto.
    // EN: Command signature; the "mensaje" argument is optional and has a default text.
    protected $signature = 'telegram:test {mensaje=✅ Prueba del bot de citas — funciona correctamente.}';

    protected $description = 'Send a test message via Telegram to the configured chat / Envía un mensaje de prueba por Telegram al chat configurado';

    /**
     * ES: Envía el mensaje de prueba al chat del dueño y muestra el message_id.
     * EN: Sends the test message to the owner's chat and prints the message_id.
     */
    public function handle(TelegramNotifier $tg): int
    {
        // ES: Salida limpia si el bot no está configurado.
        // EN: Bail out gracefully if the bot isn't configured.
        if (! $tg->configurado()) {
            $this->error('Telegram no configurado (faltan TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID en .env)');

            return self::FAILURE;
        }

        // ES: enviar() devuelve el message_id, o null si Telegram falla.
        // EN: enviar() returns the message_id, or null if Telegram fails.
        $id = $tg->enviar('🔔 '.$this->argument('mensaje'));

        if ($id) {
            $this->info("Mensaje enviado (message_id={$id}).");

            return self::SUCCESS;
        }

        $this->error('No se pudo enviar el mensaje. Revisa el token/chat_id.');

        return self::FAILURE;
    }
}
