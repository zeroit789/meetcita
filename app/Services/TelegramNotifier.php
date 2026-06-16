<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/*
|==============================================================================
| TelegramNotifier / Notificador de Telegram
|==============================================================================
| EN: Minimal Telegram Bot API client. Sends alerts to the owner (their chat_id)
|     and handles the button callbacks of the booking flow. Uses Laravel's HTTP
|     client against the Bot API. If no token/chat is configured, calls are
|     graceful no-ops (nothing breaks).
| ES: Cliente mínimo de la Bot API de Telegram. Envía avisos al dueño (su
|     chat_id) y maneja las respuestas a los botones (callbacks) del flujo de
|     citas. Usa el HTTP client de Laravel contra la Bot API. Si no hay
|     token/chat configurado, las llamadas no hacen nada (no rompe).
|
| INDEX / ÍNDICE
|   1. CONFIG .......... token/chat + is-configured / token/chat y configurado
|   2. SEND ............ send message with inline buttons / enviar mensaje
|   3. CALLBACK ........ answer button tap / responder al botón
|   4. EDIT ............ edit an already-sent message / editar mensaje
|==============================================================================
*/
class TelegramNotifier
{
    // ── 1. Config — token/chat y comprobación de configuración ──────────────

    protected ?string $token;
    protected ?string $chatId;

    public function __construct()
    {
        // EN: Read bot token + owner chat id from config. ES: Token + chat del dueño desde config.
        $this->token = config('services.telegram.token');
        $this->chatId = config('services.telegram.chat_id');
    }

    /**
     * EN: Is the bot configured? (token + chat).
     * ES: ¿Está el bot configurado? (token + chat).
     */
    public function configurado(): bool
    {
        return ! empty($this->token) && ! empty($this->chatId);
    }

    /**
     * EN: Builds the Bot API URL for a given method.
     * ES: Construye la URL de la Bot API para un método dado.
     */
    protected function apiUrl(string $method): string
    {
        return "https://api.telegram.org/bot{$this->token}/{$method}";
    }

    // ── 2. Send — enviar mensaje con botones inline ─────────────────────────

    /**
     * EN: Sends a message to the owner. $buttons = rows of inline buttons:
     *       [ [ ['text'=>'✅ Confirm','callback_data'=>'...'], ... ], ... ]
     * ES: Envía un mensaje al dueño. $buttons = filas de botones inline:
     *       [ [ ['text'=>'✅ Confirmar','callback_data'=>'...'], ... ], ... ]
     *
     * @return int|null  EN: message_id of the sent message (to edit later), or null.
     *                    ES: message_id del mensaje enviado (para editarlo luego), o null.
     */
    public function enviar(string $textoHtml, ?array $buttons = null): ?int
    {
        if (! $this->configurado()) {
            return null;
        }

        $payload = [
            'chat_id'    => $this->chatId,
            'text'       => $textoHtml,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($buttons) {
            $payload['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
        }

        try {
            $res = Http::asForm()->post($this->apiUrl('sendMessage'), $payload);

            return $res->json('result.message_id');
        } catch (\Throwable $e) {
            Log::error('Telegram enviar() falló: ' . $e->getMessage());

            return null;
        }
    }

    // ── 3. Callback — responder al toque de un botón ────────────────────────

    /**
     * EN: Answers a button tap (removes the loading "clock" on the client).
     * ES: Responde al toque de un botón (quita el "reloj" de carga en el cliente).
     */
    public function responderCallback(string $callbackQueryId, ?string $aviso = null): void
    {
        if (! $this->configurado()) {
            return;
        }

        try {
            Http::asForm()->post($this->apiUrl('answerCallbackQuery'), array_filter([
                'callback_query_id' => $callbackQueryId,
                'text'              => $aviso,
            ]));
        } catch (\Throwable $e) {
            Log::error('Telegram responderCallback() falló: ' . $e->getMessage());
        }
    }

    // ── 4. Edit — editar un mensaje ya enviado ──────────────────────────────

    /**
     * EN: Edits the text of an already-sent message (e.g. to reflect "confirmed"
     *     and remove the buttons after they're pressed).
     * ES: Edita el texto de un mensaje ya enviado (p. ej. para reflejar
     *     "confirmada" y quitar los botones tras pulsarlos).
     */
    public function editarMensaje(int $messageId, string $textoHtml): void
    {
        if (! $this->configurado()) {
            return;
        }

        try {
            Http::asForm()->post($this->apiUrl('editMessageText'), [
                'chat_id'    => $this->chatId,
                'message_id' => $messageId,
                'text'       => $textoHtml,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Telegram editarMensaje() falló: ' . $e->getMessage());
        }
    }
}
