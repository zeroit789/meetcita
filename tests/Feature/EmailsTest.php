<?php

namespace Tests\Feature;

use App\Mail\AppointmentConfirmationToClient;
use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentRejected;
use App\Mail\AppointmentRequestedToOwner;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ];
    }

    #[DataProvider('mailables')]
    public function test_el_email_se_renderiza_completo_en_los_dos_idiomas(string $clase): void
    {
        config(['appointments.brand.name' => 'Marca de Prueba']);
        $cita = Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:00']);

        foreach (['es' => 'martes', 'en' => 'Tuesday'] as $idioma => $diaSemana) {
            $html = (new $clase($cita))->locale($idioma)->render();

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
    }
}
