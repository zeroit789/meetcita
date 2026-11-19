<?php

namespace Tests\Feature;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentConfirmed;
use App\Events\AppointmentRequested;
use App\Livewire\BookAppointment;
use App\Livewire\PanelCitas;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ES: Eventos de Laravel para enganchar un CRM: se disparan en la reserva
 *     pública, el panel y Telegram, una sola vez por cambio real (si el UPDATE
 *     condicional no cambia nada, no hay evento). La autogestión del cliente y
 *     el alta manual tienen sus propios tests.
 * EN: Laravel events to hook a CRM: fired from the public booking, the panel
 *     and Telegram, once per real change (if the conditional UPDATE changes
 *     nothing, there is no event). Client self-service and manual entry have
 *     their own tests.
 */
class EventosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Correo falso y eventos de cita interceptados en cada test.
     * EN: Fake mail and intercepted booking events in every test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Event::fake([AppointmentRequested::class, AppointmentConfirmed::class, AppointmentCancelled::class]);
    }

    public function test_la_reserva_publica_dispara_appointment_requested(): void
    {
        Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->call('selectTime', '10:00')
            ->set('name', 'Ana García')
            ->set('email', 'ana@example.com')
            ->call('reserve')
            ->assertSet('step', 4);

        Event::assertDispatchedTimes(AppointmentRequested::class, 1);
        Event::assertDispatched(AppointmentRequested::class, fn ($e) => $e->source === 'web'
            && $e->appointment->email === 'ana@example.com');
    }

    public function test_confirmar_en_el_panel_dispara_una_sola_vez(): void
    {
        $cita = Appointment::factory()->create();
        $this->entrarAlPanel();

        Livewire::test(PanelCitas::class)
            ->call('confirmar', $cita->id)
            ->call('confirmar', $cita->id);

        Event::assertDispatchedTimes(AppointmentConfirmed::class, 1);
        Event::assertDispatched(AppointmentConfirmed::class, fn ($e) => $e->source === 'panel'
            && $e->appointment->is($cita) && $e->appointment->status === 'confirmada');
    }

    public function test_cancelar_en_el_panel_dispara_con_el_dueno_y_el_motivo(): void
    {
        $cita = Appointment::factory()->confirmada()->create();
        $this->entrarAlPanel();

        Livewire::test(PanelCitas::class)
            ->call('pedirCancelacion', $cita->id)
            ->set('motivoCancelacion', 'Me ha surgido un viaje')
            ->call('cancelar', $cita->id)
            ->call('cancelar', $cita->id);

        Event::assertDispatchedTimes(AppointmentCancelled::class, 1);
        Event::assertDispatched(AppointmentCancelled::class, fn ($e) => $e->cancelledBy === 'owner'
            && $e->reason === 'Me ha surgido un viaje');
    }

    /**
     * ES: Bot de Telegram configurado con la API simulada.
     * EN: Telegram bot configured with the API faked.
     */
    private function telegram(): void
    {
        config([
            'services.telegram.token' => 'token-de-test',
            'services.telegram.chat_id' => '1234',
            'services.telegram.webhook_secret' => 'secreto',
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
    }

    /**
     * ES: Envía un update al webhook de Telegram con el secreto correcto.
     * EN: Posts an update to the Telegram webhook with the right secret.
     */
    private function webhook(array $update): void
    {
        $this->postJson('/telegram/webhook', $update, ['X-Telegram-Bot-Api-Secret-Token' => 'secreto'])->assertOk();
    }

    public function test_confirmar_por_telegram_dispara_con_origen_telegram(): void
    {
        $this->telegram();
        $cita = Appointment::factory()->create();
        $boton = ['callback_query' => ['id' => 'cb1', 'data' => "ac:{$cita->id}", 'message' => ['message_id' => 9, 'chat' => ['id' => '1234']]]];

        $this->webhook($boton);
        $this->webhook($boton);

        Event::assertDispatchedTimes(AppointmentConfirmed::class, 1);
        Event::assertDispatched(AppointmentConfirmed::class, fn ($e) => $e->source === 'telegram');
    }

    public function test_rechazar_por_telegram_dispara_cancelada_por_telegram(): void
    {
        $this->telegram();
        $cita = Appointment::factory()->create();

        $this->webhook(['callback_query' => ['id' => 'cb1', 'data' => "ar:{$cita->id}", 'message' => ['message_id' => 9, 'chat' => ['id' => '1234']]]]);
        $this->webhook(['message' => ['chat' => ['id' => '1234'], 'text' => 'No llego a tiempo']]);

        Event::assertDispatchedTimes(AppointmentCancelled::class, 1);
        Event::assertDispatched(AppointmentCancelled::class, fn ($e) => $e->cancelledBy === 'telegram'
            && $e->reason === 'No llego a tiempo');
    }
}
