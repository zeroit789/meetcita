<?php

namespace Tests\Feature;

use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * ES: Recordatorio por correo la víspera (orden citas:recordar): solo citas
 *     CONFIRMADAS de MAÑANA en la zona del negocio, una sola vez (cada cita se
 *     reclama con un UPDATE condicional antes de enviar), en el idioma de la
 *     cita, con el enlace de gestionar y el .ics firmados.
 * EN: Day-before email reminder (citas:recordar command): only CONFIRMED
 *     bookings for TOMORROW in the business zone, only once (each booking is
 *     claimed with a conditional UPDATE before sending), in the booking's
 *     language, with the signed manage and .ics links.
 */
class RecordatorioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Correo falso, recordatorios activos y reloj a las 10:00 del lunes 21-09.
     * EN: Fake mail, reminders on and clock at 10:00 on Monday 21-09.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['appointments.reminders.enabled' => true, 'appointments.reminders.from' => '09:00']);
        Carbon::setTestNow(Carbon::parse('2026-09-21 10:00', 'Europe/Madrid'));
    }

    public function test_solo_recuerda_las_confirmadas_de_manana_y_las_marca(): void
    {
        $manana = Appointment::factory()->confirmada()->create(['date' => '2026-09-22', 'time' => '10:00', 'email' => 'si@example.com']);
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '11:00', 'email' => 'pendiente@example.com']);
        Appointment::factory()->cancelada()->create(['date' => '2026-09-22', 'time' => '12:00', 'email' => 'cancelada@example.com']);
        Appointment::factory()->confirmada()->create(['date' => '2026-09-21', 'time' => '12:00', 'email' => 'hoy@example.com']);
        Appointment::factory()->confirmada()->create(['date' => '2026-09-23', 'time' => '12:00', 'email' => 'pasado@example.com']);

        $this->artisan('citas:recordar')->assertSuccessful();

        Mail::assertSentCount(1);
        Mail::assertSent(AppointmentReminder::class, fn ($m) => $m->hasTo('si@example.com'));
        $this->assertNotNull($manana->fresh()->reminded_at);
    }

    public function test_no_reenvia_si_ya_se_envio_o_si_otro_proceso_la_reclamo(): void
    {
        Appointment::factory()->confirmada()->create(['date' => '2026-09-22', 'time' => '10:00']);
        $reclamada = Appointment::factory()->confirmada()->create(['date' => '2026-09-22', 'time' => '11:00']);
        $reclamada->reminded_at = now();
        $reclamada->save();

        $this->artisan('citas:recordar');
        $this->artisan('citas:recordar');

        Mail::assertSentCount(1);
    }

    public function test_un_envio_fallido_de_la_hora_vieja_no_suelta_la_marca_de_la_hora_nueva(): void
    {
        $cita = Appointment::factory()->confirmada()->create(['date' => '2026-09-22', 'time' => '10:00']);

        // ES: Mientras se envía el de las 10:00, el cliente pasa la cita a las
        //     12:00, el dueño la confirma y otro proceso ya reclama SU
        //     recordatorio. Después el envío viejo falla.
        // EN: While the 10:00 one is being sent, the client moves the booking
        //     to 12:00, the owner confirms it and another process already
        //     claims ITS reminder. Then the old send fails.
        Mail::shouldReceive('to')->andReturnUsing(function () use ($cita) {
            Appointment::whereKey($cita->id)->update(['time' => '12:00', 'revision' => 2, 'reminded_at' => now()]);

            throw new \RuntimeException('SMTP caído');
        });

        $this->artisan('citas:recordar')->assertSuccessful();

        // ES: La marca es del recordatorio de las 12:00: no se suelta.
        // EN: The mark belongs to the 12:00 reminder: it isn't released.
        $this->assertNotNull($cita->fresh()->reminded_at);
    }

    public function test_un_envio_fallido_se_suelta_para_reintentar(): void
    {
        $cita = Appointment::factory()->confirmada()->create(['date' => '2026-09-22', 'time' => '10:00']);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));

        $this->artisan('citas:recordar')->assertSuccessful();

        $this->assertNull($cita->fresh()->reminded_at);
    }

    public function test_va_en_el_idioma_de_la_cita_con_los_enlaces_firmados(): void
    {
        $cita = Appointment::factory()->confirmada()->create(['date' => '2026-09-22', 'time' => '10:00', 'locale' => 'en']);

        $this->artisan('citas:recordar');

        Mail::assertSent(AppointmentReminder::class, function (AppointmentReminder $m) {
            return $m->locale === 'en';
        });

        $html = (new AppointmentReminder($cita))->locale('en')->render();
        $this->assertStringContainsString(__('emails.reminder_heading', [], 'en'), $html);
        $this->assertStringContainsString('Tuesday', $html);
        $this->assertStringContainsString('/gestionar?signature=', $html);
        $this->assertStringContainsString('/calendario.ics?signature=', $html);

        $es = (new AppointmentReminder($cita))->locale('es')->render();
        $this->assertStringContainsString(__('emails.reminder_heading', [], 'es'), $es);
    }

    public function test_manana_es_manana_en_la_zona_del_negocio(): void
    {
        // ES: Martes 22-09 a las 00:30 en Madrid = lunes 21-09 a las 22:30 UTC.
        //     Mañana en Madrid es el 23 (en UTC sería el 22).
        // EN: Tuesday 22-09 at 00:30 in Madrid = Monday 21-09 at 22:30 UTC.
        //     Tomorrow in Madrid is the 23rd (in UTC it would be the 22nd).
        config(['appointments.reminders.from' => '00:00']);
        Carbon::setTestNow(Carbon::parse('2026-09-22 00:30', 'Europe/Madrid'));
        Appointment::factory()->confirmada()->create(['date' => '2026-09-22', 'time' => '10:00', 'email' => 'hoy@example.com']);
        Appointment::factory()->confirmada()->create(['date' => '2026-09-23', 'time' => '10:00', 'email' => 'manana@example.com']);

        $this->artisan('citas:recordar');

        Mail::assertSentCount(1);
        Mail::assertSent(AppointmentReminder::class, fn ($m) => $m->hasTo('manana@example.com'));
    }

    public function test_no_envia_antes_de_la_hora_minima_ni_desactivado(): void
    {
        Appointment::factory()->confirmada()->create(['date' => '2026-09-22', 'time' => '10:00']);

        Carbon::setTestNow(Carbon::parse('2026-09-21 08:45', 'Europe/Madrid'));
        $this->artisan('citas:recordar')->assertSuccessful();
        Mail::assertNothingSent();

        Carbon::setTestNow(Carbon::parse('2026-09-21 10:00', 'Europe/Madrid'));
        config(['appointments.reminders.enabled' => false]);
        $this->artisan('citas:recordar')->assertSuccessful();
        Mail::assertNothingSent();
    }

    public function test_sin_email_no_se_envia_nada(): void
    {
        Appointment::factory()->confirmada()->create(['date' => '2026-09-22', 'time' => '10:00', 'email' => '']);

        $this->artisan('citas:recordar')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_esta_programada_cada_quince_minutos_sin_solaparse(): void
    {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'citas:recordar'));

        $this->assertNotNull($evento);
        $this->assertSame('*/15 * * * *', $evento->expression);
        $this->assertTrue($evento->withoutOverlapping);
    }
}
