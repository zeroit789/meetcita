<?php

namespace Tests\Feature;

use App\Livewire\BookAppointment;
use App\Livewire\PanelCitas;
use App\Mail\AppointmentConfirmationToClient;
use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Services\GestionCitas;
use App\Services\TelegramNotifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ES: El enlace de «gestionar mi cita» (cancelar o cambiar) es solo del
 *     titular. Los asistentes extra siguen recibiendo cada correo de la cita,
 *     pero en un envío aparte y SIN ese enlace: antes iban en Cc del mismo
 *     correo y cualquiera de ellos podía cancelar o cambiar la cita.
 * EN: The "manage my booking" link (cancel or change) belongs to the holder
 *     only. Extra attendees still get every booking email, but in a separate
 *     send and WITHOUT that link: they used to be in Cc of the same email
 *     and any of them could cancel or change the booking.
 */
class EnlaceSoloTitularTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Correo falso para todos los casos.
     * EN: Fake mail for every case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    /**
     * ES: Comprueba un tipo de correo: el del titular lleva el enlace y no
     *     tiene asistentes en copia; el de los asistentes va aparte y sin enlace.
     * EN: Checks one kind of email: the holder's carries the link and has no
     *     attendees in copy; the attendees' one goes separately, without a link.
     *
     * @param  class-string<Mailable>  $clase
     */
    private function comprobarReparto(string $clase, bool $enviado = false): void
    {
        $assert = $enviado ? 'assertSent' : 'assertQueued';

        Mail::$assert($clase, fn (Mailable $m) => $m->hasTo('cliente@example.com')
            && ! $m->hasCc('extra@example.com')
            && str_contains($m->render(), '/gestionar?'));

        Mail::$assert($clase, fn (Mailable $m) => $m->hasTo('extra@example.com')
            && ! $m->hasTo('cliente@example.com')
            && ! str_contains($m->render(), '/gestionar?'));
    }

    public function test_la_reserva_publica(): void
    {
        Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->call('setDuration', 30)
            ->call('selectTime', '10:00')
            ->set('name', 'Ana García')
            ->set('email', 'cliente@example.com')
            ->set('attendeeEmails', 'extra@example.com')
            ->call('reserve')
            ->assertHasNoErrors();

        $this->comprobarReparto(AppointmentConfirmationToClient::class);
    }

    public function test_la_confirmacion_desde_el_panel(): void
    {
        $cita = $this->cita();
        $this->entrarAlPanel();

        Livewire::test(PanelCitas::class)->call('confirmar', $cita->id, '0')->assertHasNoErrors();

        $this->comprobarReparto(AppointmentConfirmed::class);
    }

    public function test_la_confirmacion_desde_telegram(): void
    {
        config([
            'services.telegram.token' => 'token-de-test',
            'services.telegram.chat_id' => '1234',
            'services.telegram.webhook_secret' => 'secreto',
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
        $cita = $this->cita();

        $this->postJson('/telegram/webhook', ['callback_query' => [
            'id' => 'cb1',
            'data' => "ac:{$cita->id}:".TelegramNotifier::version($cita->fresh()),
            'message' => ['message_id' => 99, 'chat' => ['id' => '1234']],
        ]], ['X-Telegram-Bot-Api-Secret-Token' => 'secreto'])->assertOk();

        $this->comprobarReparto(AppointmentConfirmed::class);
    }

    public function test_la_reprogramacion(): void
    {
        $cita = $this->cita('confirmada');

        $this->assertSame(GestionCitas::OK, app(GestionCitas::class)->reprogramar($cita, '2026-09-24', '12:00'));

        $this->comprobarReparto(AppointmentConfirmationToClient::class);
    }

    public function test_el_recordatorio(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 10:00', 'Europe/Madrid'));
        $this->cita('confirmada');

        $this->artisan('citas:recordar')->assertSuccessful();

        $this->comprobarReparto(AppointmentReminder::class, enviado: true);
    }

    /**
     * ES: Cita del miércoles 23-09 a las 10:00 con un asistente extra.
     * EN: Booking on Wednesday 23-09 at 10:00 with one extra attendee.
     */
    private function cita(string $estado = 'pendiente'): Appointment
    {
        return Appointment::factory()->state(['status' => $estado])->create([
            'date' => '2026-09-23', 'time' => '10:00', 'email' => 'cliente@example.com',
            'attendee_emails' => 'extra@example.com',
        ]);
    }
}
