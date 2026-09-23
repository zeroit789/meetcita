<?php

namespace Tests\Feature;

use App\Livewire\PanelCitas;
use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentRejected;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ES: Tests del panel privado (/panel): acceso con contraseña, confirmar y
 *     cancelar citas (con email al cliente y motivo opcional).
 * EN: Tests for the private panel (/panel): password access, confirming and
 *     cancelling bookings (with an email to the client and an optional reason).
 */
class PanelCitasTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_sesion_el_panel_redirige_al_login(): void
    {
        $this->get('/panel')->assertRedirect(route('panel.login'));
    }

    public function test_con_la_contrasena_correcta_se_entra_al_panel(): void
    {
        $this->post('/panel/login', ['password' => 'secreto-de-test'])
            ->assertRedirect(route('panel.index'));

        $this->get('/panel')->assertOk()->assertSeeLivewire(PanelCitas::class);
    }

    public function test_con_una_contrasena_incorrecta_no_se_entra(): void
    {
        $this->post('/panel/login', ['password' => 'mala']);

        $this->get('/panel')->assertRedirect(route('panel.login'));
    }

    public function test_confirmar_una_cita_pendiente_la_confirma_y_avisa_al_cliente(): void
    {
        Mail::fake();
        $cita = Appointment::factory()->create(['email' => 'cliente@example.com']);

        Livewire::test(PanelCitas::class)
            ->call('confirmar', $cita->id)
            ->assertHasNoErrors();

        $this->assertSame('confirmada', $cita->fresh()->status);
        Mail::assertQueued(AppointmentConfirmed::class, fn ($mail) => $mail->hasTo('cliente@example.com'));
    }

    public function test_no_se_puede_confirmar_una_cita_cancelada(): void
    {
        Mail::fake();
        $cita = Appointment::factory()->cancelada()->create();

        Livewire::test(PanelCitas::class)
            ->call('confirmar', $cita->id)
            ->assertHasErrors('accion')
            ->assertSee(__('citas.panel_err_only_pending'));

        $this->assertSame('cancelada', $cita->fresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_reconfirmar_una_cancelada_cuyo_hueco_ya_esta_ocupado_no_da_500(): void
    {
        Mail::fake();
        // ES: La cita cancelada liberó las 10:00 y otra persona las reservó después.
        // EN: The cancelled booking freed 10:00 and someone else booked it later.
        $cancelada = Appointment::factory()->cancelada()->create(['time' => '10:00']);
        Appointment::factory()->create(['time' => '10:00']);

        Livewire::test(PanelCitas::class)
            ->call('confirmar', $cancelada->id)
            ->assertHasErrors('accion');

        $this->assertSame('cancelada', $cancelada->fresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_confirmar_dos_veces_no_reenvia_el_email(): void
    {
        Mail::fake();
        $cita = Appointment::factory()->confirmada()->create();

        Livewire::test(PanelCitas::class)
            ->call('confirmar', $cita->id)
            ->assertHasErrors('accion');

        Mail::assertNothingQueued();
    }

    public function test_cancelar_con_motivo_envia_el_email_de_cancelacion_con_ese_motivo(): void
    {
        Mail::fake();
        $cita = Appointment::factory()->confirmada()->create([
            'email' => 'cliente@example.com',
            'attendee_emails' => 'extra@example.com',
            'locale' => 'en',
        ]);

        Livewire::test(PanelCitas::class)
            ->call('pedirCancelacion', $cita->id)
            ->assertSet('cancelandoId', $cita->id)
            ->assertSee(__('citas.cancel_reason_label'))
            ->set('motivoCancelacion', 'Me ha surgido un viaje')
            ->call('cancelar', $cita->id)
            ->assertSet('cancelandoId', null)
            ->assertSet('motivoCancelacion', '');

        $this->assertSame('cancelada', $cita->fresh()->status);
        Mail::assertQueued(AppointmentRejected::class, function (AppointmentRejected $mail) {
            return $mail->hasTo('cliente@example.com')
                && $mail->hasCc('extra@example.com')
                && $mail->motivo === 'Me ha surgido un viaje'
                && $mail->locale === 'en';
        });
    }

    public function test_cancelar_sin_motivo_tambien_avisa_al_cliente(): void
    {
        Mail::fake();
        $cita = Appointment::factory()->create(['email' => 'cliente@example.com']);

        Livewire::test(PanelCitas::class)->call('cancelar', $cita->id);

        $this->assertSame('cancelada', $cita->fresh()->status);
        Mail::assertQueued(AppointmentRejected::class, fn (AppointmentRejected $mail) => $mail->motivo === '');
    }

    public function test_el_email_sin_motivo_no_pinta_el_bloque_del_motivo(): void
    {
        $cita = Appointment::factory()->create();

        $html = (new AppointmentRejected($cita))->render();

        $this->assertStringNotContainsString('border-left:4px solid #7c3aed', $html);
        $this->assertStringContainsString('border-left:4px solid #7c3aed', (new AppointmentRejected($cita, 'Un motivo'))->render());
    }

    public function test_el_motivo_no_puede_pasar_de_1000_caracteres(): void
    {
        Mail::fake();
        $cita = Appointment::factory()->create();

        Livewire::test(PanelCitas::class)
            ->set('motivoCancelacion', str_repeat('x', 1001))
            ->call('cancelar', $cita->id)
            ->assertHasErrors('motivoCancelacion');

        $this->assertSame('pendiente', $cita->fresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_cancelar_una_cita_ya_cancelada_no_reenvia_nada(): void
    {
        Mail::fake();
        $cita = Appointment::factory()->cancelada()->create();

        Livewire::test(PanelCitas::class)->call('cancelar', $cita->id);

        Mail::assertNothingQueued();
    }
}
