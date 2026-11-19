<?php

namespace Tests\Feature;

use App\Mail\AppointmentChangedToOwner;
use App\Mail\AppointmentConfirmationToClient;
use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentRejected;
use App\Mail\AppointmentReminder;
use App\Mail\AppointmentRequestedToOwner;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ES: Los cuatro emails de la cita se renderizan enteros (con la fecha, la
 *     referencia y la marca) en español y en inglés.
 * EN: The four booking emails render in full (with the date, the reference
 *     and the brand) in Spanish and English.
 */
class EmailsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Los tres mailables que recibe el cliente (van en su idioma).
     * EN: The three mailables the client gets (sent in their language).
     *
     * @return array<string, array{0: class-string}>
     */
    public static function mailables(): array
    {
        return [
            'cliente / client' => [AppointmentConfirmationToClient::class],
            'confirmada / confirmed' => [AppointmentConfirmed::class],
            'rechazada / rejected' => [AppointmentRejected::class],
            'recordatorio / reminder' => [AppointmentReminder::class],
        ];
    }

    #[DataProvider('mailables')]
    public function test_el_email_se_renderiza_completo_en_los_dos_idiomas(string $clase): void
    {
        config(['appointments.brand.name' => 'Marca de Prueba']);
        $cita = Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:00']);

        foreach (['es' => 'martes', 'en' => 'Tuesday'] as $idioma => $diaSemana) {
            $mail = (new $clase($cita))->locale($idioma);
            $html = $mail->render();

            // ES: La referencia va también en el asunto (en el idioma del cliente).
            // EN: The reference also goes in the subject (in the client's language).
            app()->setLocale($idioma);
            $this->assertStringContainsString($cita->reference, $mail->envelope()->subject);

            $this->assertStringContainsString('<!DOCTYPE html>', $html);
            $this->assertStringContainsString($cita->reference, $html);
            $this->assertStringContainsString('Marca de Prueba', $html);
            $this->assertStringContainsStringIgnoringCase($diaSemana, $html);
        }
    }

    public function test_el_email_al_dueno_se_renderiza_en_el_idioma_por_defecto(): void
    {
        config(['appointments.brand.name' => 'Marca de Prueba']);
        $cita = Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:00', 'name' => 'Ana García']);

        // ES: Aunque la cita sea en inglés, el dueño lo recibe en default_locale (es).
        // EN: Even for an English booking, the owner gets it in default_locale (es).
        $html = (new AppointmentRequestedToOwner($cita))->locale('en')->render();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('Marca de Prueba', $html);
        $this->assertStringContainsString('Ana García', $html);
        $this->assertStringContainsString('martes 22 de septiembre', $html);
        $this->assertStringContainsString($cita->reference, $html);
    }

    public function test_los_avisos_al_dueno_de_cambios_del_cliente_se_renderizan(): void
    {
        $cita = Appointment::factory()->create(['date' => '2026-09-24', 'time' => '12:00', 'name' => 'Ana García']);

        $cancelada = (new AppointmentChangedToOwner($cita, 'cancelada'))->render();
        $this->assertStringContainsString(__('emails.owner_change_cancelled_heading', [], 'es'), $cancelada);
        $this->assertStringContainsString('Ana García', $cancelada);

        $mail = new AppointmentChangedToOwner($cita, 'reprogramada', '2026-09-22', '10:00');
        $html = $mail->render();
        $this->assertStringContainsStringIgnoringCase('martes 22 de septiembre', $html);
        $this->assertStringContainsStringIgnoringCase('jueves 24 de septiembre', $html);
        $this->assertStringContainsString($cita->reference, $mail->envelope()->subject);
    }

    public function test_el_cambio_recibido_del_cliente_sale_en_su_idioma(): void
    {
        $cita = Appointment::factory()->create(['date' => '2026-09-24', 'time' => '12:00', 'locale' => 'en']);

        $html = (new AppointmentConfirmationToClient($cita, reprogramada: true))->locale('en')->render();

        $this->assertStringContainsString(__('emails.client_rescheduled_heading', [], 'en'), $html);
        $this->assertStringContainsString('/gestionar?expires=', $html);
    }

    public function test_un_correo_en_cola_de_antes_de_actualizar_no_falla_sin_reprogramada(): void
    {
        // ES: Al deserializar un trabajo viejo no se llama al constructor.
        // EN: Unserializing an old job doesn't call the constructor.
        $mail = (new \ReflectionClass(AppointmentConfirmationToClient::class))->newInstanceWithoutConstructor();

        $this->assertFalse($mail->reprogramada);
    }

    public function test_un_correo_de_confirmada_no_sale_si_la_cita_cambio_mientras_esperaba_en_la_cola(): void
    {
        // ES: Se encola al confirmar (revisión 1); antes de que el worker lo
        //     envíe, el cliente la reprograma (revisión 2, pendiente). El
        //     worker recarga la cita: el correo ya no es verdad y no sale.
        // EN: Queued on confirm (revision 1); before the worker sends it, the
        //     client reschedules (revision 2, pending). The worker reloads the
        //     booking: the email is no longer true and isn't sent.
        $cita = Appointment::factory()->confirmada()->create(['revision' => 1, 'email' => 'cliente@example.com']);
        $correo = new AppointmentConfirmed($cita);
        $cita->forceFill(['status' => 'pendiente', 'revision' => 2]);

        Mail::to('cliente@example.com')->sendNow($correo);
        $this->assertCount(0, app('mail.manager')->mailer()->getSymfonyTransport()->messages());

        // ES: Sin cambios, sí sale. EN: Unchanged, it is sent.
        $cita->forceFill(['status' => 'confirmada', 'revision' => 1]);
        Mail::to('cliente@example.com')->sendNow($correo);
        $this->assertCount(1, app('mail.manager')->mailer()->getSymfonyTransport()->messages());
    }
}
