<?php

namespace Tests\Feature;

use App\Livewire\BookAppointment;
use App\Mail\AppointmentConfirmationToClient;
use App\Mail\AppointmentRequestedToOwner;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ES: Tests del wizard público de reserva (/citas): elegir día, duración y
 *     hora, validar el formulario, reservar y evitar la doble reserva.
 * EN: Tests for the public booking wizard (/citas): pick day, duration and
 *     time, validate the form, book and prevent double booking.
 */
class BookAppointmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Wizard ya en el paso 3 (martes 22-09 a las 10:00) con un formulario válido.
     * EN: Wizard already on step 3 (Tuesday 2026-09-22 at 10:00) with a valid form.
     */
    private function wizardEnPaso3(int $duracion = 30): Testable
    {
        return Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->call('setDuration', $duracion)
            ->call('selectTime', '10:00')
            ->set('name', 'Ana García')
            ->set('email', 'ana@example.com')
            ->set('attendees', 'Ana y Luis')
            ->set('message', 'Revisar el presupuesto');
    }

    public function test_la_pagina_de_citas_carga(): void
    {
        $this->get('/citas')->assertOk()->assertSeeLivewire(BookAppointment::class);
    }

    public function test_la_home_carga(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_elegir_un_dia_reservable_pasa_al_paso_2(): void
    {
        Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->assertSet('selectedDate', '2026-09-22')
            ->assertSet('step', 2)
            ->assertViewHas('freeSlots', fn (array $slots) => $slots[0] === '09:30' && count($slots) === 17);
    }

    public function test_un_dia_no_reservable_se_ignora(): void
    {
        Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-26')
            ->assertSet('selectedDate', '')
            ->assertSet('step', 1);
    }

    public function test_cambiar_a_una_hora_ofrece_solo_inicios_validos(): void
    {
        Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->call('setDuration', 60)
            ->assertSet('duration', 60)
            ->assertViewHas('freeSlots', fn (array $slots) => ! in_array('17:30', $slots, true) && count($slots) === 16);
    }

    public function test_una_duracion_no_soportada_se_ignora(): void
    {
        Livewire::test(BookAppointment::class)
            ->call('setDuration', 45)
            ->assertSet('duration', 30);
    }

    public function test_elegir_una_hora_ocupada_no_avanza(): void
    {
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:00']);

        Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->call('selectTime', '10:00')
            ->assertSet('selectedTime', '')
            ->assertSet('step', 2);
    }

    public function test_reservar_crea_la_cita_pendiente_y_avisa_por_email(): void
    {
        Mail::fake();

        $this->wizardEnPaso3(60)
            ->call('reserve')
            ->assertHasNoErrors()
            ->assertSet('step', 4);

        $cita = Appointment::sole();
        $this->assertSame('pendiente', $cita->status);
        $this->assertSame('2026-09-22', $cita->date->toDateString());
        $this->assertSame('10:00', $cita->time);
        $this->assertSame(60, $cita->duration);
        $this->assertSame('es', $cita->locale);
        $this->assertStringStartsWith('APT-', $cita->reference);

        Mail::assertQueued(AppointmentRequestedToOwner::class, fn ($mail) => $mail->hasTo('owner@example.com'));
        Mail::assertQueued(AppointmentConfirmationToClient::class, fn ($mail) => $mail->hasTo('ana@example.com'));
    }

    /**
     * ES: El aviso al dueño lleva la referencia de la cita en el asunto y en el cuerpo.
     * EN: The owner alert carries the booking reference in the subject and the body.
     */
    public function test_el_aviso_al_dueno_lleva_la_referencia_en_asunto_y_cuerpo(): void
    {
        Mail::fake();

        $this->wizardEnPaso3()->call('reserve')->assertHasNoErrors();

        $referencia = Appointment::sole()->reference;

        Mail::assertQueued(AppointmentRequestedToOwner::class, function (AppointmentRequestedToOwner $mail) use ($referencia) {
            return str_contains($mail->envelope()->subject, $referencia)
                && str_contains($mail->render(), $referencia);
        });
    }

    public function test_no_hay_doble_reserva_si_el_hueco_se_ocupa_antes_de_confirmar(): void
    {
        Mail::fake();

        $wizard = $this->wizardEnPaso3();

        // ES: Otra persona reserva el mismo hueco mientras rellenamos el formulario.
        // EN: Someone else books the same slot while we fill in the form.
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:00']);

        $wizard->call('reserve')
            ->assertSet('step', 2)
            ->assertSet('selectedTime', '')
            ->assertHasErrors('selectedTime');

        $this->assertSame(1, Appointment::count());
        Mail::assertNothingQueued();
    }

    public function test_una_hora_que_solapa_con_otra_cita_no_se_reserva(): void
    {
        Mail::fake();

        $wizard = $this->wizardEnPaso3(60);
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:30']);

        $wizard->call('reserve')->assertSet('step', 2);

        $this->assertSame(1, Appointment::count());
    }

    public function test_el_formulario_exige_los_campos_obligatorios(): void
    {
        Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->call('selectTime', '10:00')
            ->call('reserve')
            ->assertHasErrors(['name' => 'required', 'email' => 'required', 'attendees' => 'required', 'message' => 'required']);

        $this->assertSame(0, Appointment::count());
    }

    public function test_el_email_debe_ser_valido(): void
    {
        $this->wizardEnPaso3()
            ->set('email', 'no-es-un-email')
            ->call('reserve')
            ->assertHasErrors(['email' => 'email']);
    }

    public function test_el_honeypot_bloquea_bots(): void
    {
        $this->wizardEnPaso3()
            ->set('website', 'http://spam.example')
            ->call('reserve')
            ->assertHasErrors('website');

        $this->assertSame(0, Appointment::count());
    }

    public function test_los_correos_extra_invalidos_se_rechazan(): void
    {
        $this->wizardEnPaso3()
            ->set('attendeeEmails', 'bien@example.com, mal-correo')
            ->call('reserve')
            ->assertHasErrors('attendeeEmails');

        $this->assertSame(0, Appointment::count());
    }

    public function test_no_se_admiten_mas_asistentes_extra_que_el_maximo(): void
    {
        config(['appointments.schedule.max_attendees' => 2]);

        $this->wizardEnPaso3()
            ->set('attendeeEmails', 'a@example.com, b@example.com, c@example.com')
            ->call('reserve')
            ->assertHasErrors('attendeeEmails');

        $this->assertSame(0, Appointment::count());
    }

    public function test_la_modalidad_solo_admite_online_o_presencial(): void
    {
        Livewire::test(BookAppointment::class)
            ->call('setModality', 'presencial')
            ->assertSet('modality', 'presencial')
            ->call('setModality', 'marte')
            ->assertSet('modality', 'presencial');
    }
}
