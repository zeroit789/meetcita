<?php

namespace Tests\Feature;

use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentRejected;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * ES: Tests del webhook del bot de Telegram: secreto, solo el chat del dueño,
 *     confirmar con un botón y rechazar con motivo escrito.
 * EN: Tests for the Telegram bot webhook: secret, owner chat only, confirm
 *     with a button and reject with a typed reason.
 */
class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Bot configurado y API de Telegram simulada (sin red).
     * EN: Bot configured and Telegram API faked (no network).
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.token' => 'token-de-test',
            'services.telegram.chat_id' => '1234',
            'services.telegram.webhook_secret' => 'secreto',
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
        Mail::fake();
    }

    /**
     * ES: Envía un update al webhook con el secreto correcto.
     * EN: Posts an update to the webhook with the right secret.
     */
    private function webhook(array $update, string $secreto = 'secreto'): TestResponse
    {
        return $this->postJson('/telegram/webhook', $update, ['X-Telegram-Bot-Api-Secret-Token' => $secreto]);
    }

    /**
     * ES: Update de pulsación de botón ("ac:ID" o "ar:ID") desde un chat.
     * EN: Button-tap update ("ac:ID" or "ar:ID") from a chat.
     */
    private function boton(string $data, string $chat = '1234'): array
    {
        return ['callback_query' => [
            'id' => 'cb1',
            'data' => $data,
            'message' => ['message_id' => 99, 'chat' => ['id' => $chat]],
        ]];
    }

    /**
     * ES: Update de mensaje de texto desde el chat del dueño.
     * EN: Text-message update from the owner's chat.
     */
    private function texto(string $texto): array
    {
        return ['message' => ['chat' => ['id' => '1234'], 'text' => $texto]];
    }

    public function test_sin_el_secreto_correcto_responde_403(): void
    {
        $cita = Appointment::factory()->create();

        $this->webhook($this->boton("ac:{$cita->id}"), 'otro')->assertForbidden();

        $this->assertSame('pendiente', $cita->fresh()->status);
    }

    public function test_confirmar_desde_telegram_confirma_y_avisa_al_cliente(): void
    {
        $cita = Appointment::factory()->create(['email' => 'cliente@example.com']);

        $this->webhook($this->boton("ac:{$cita->id}"))->assertOk();

        $this->assertSame('confirmada', $cita->fresh()->status);
        Mail::assertQueued(AppointmentConfirmed::class, fn ($mail) => $mail->hasTo('cliente@example.com'));
    }

    public function test_otro_chat_no_puede_operar(): void
    {
        $cita = Appointment::factory()->create();

        $this->webhook($this->boton("ac:{$cita->id}", '9999'))->assertOk();

        $this->assertSame('pendiente', $cita->fresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_rechazar_pide_motivo_y_el_siguiente_mensaje_cancela_con_ese_motivo(): void
    {
        $cita = Appointment::factory()->create(['email' => 'cliente@example.com']);

        $this->webhook($this->boton("ar:{$cita->id}"))->assertOk();
        $this->assertSame('pendiente', $cita->fresh()->status);

        $this->webhook($this->texto('Estoy de viaje esa semana'))->assertOk();

        $this->assertSame('cancelada', $cita->fresh()->status);
        Mail::assertQueued(AppointmentRejected::class, fn (AppointmentRejected $mail) => $mail->hasTo('cliente@example.com')
            && $mail->motivo === 'Estoy de viaje esa semana');
    }

    public function test_no_se_actua_sobre_citas_ya_resueltas(): void
    {
        $cita = Appointment::factory()->cancelada()->create();

        $this->webhook($this->boton("ac:{$cita->id}"))->assertOk();

        $this->assertSame('cancelada', $cita->fresh()->status);
        Mail::assertNothingQueued();
    }
}
