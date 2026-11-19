<?php

namespace Tests\Feature;

use App\Livewire\PanelBlockedDays;
use App\Models\Appointment;
use App\Models\BlockedDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ES: Tests de los días bloqueados del panel (vacaciones, festivos).
 * EN: Tests for the panel's blocked days (holidays, days off).
 */
class PanelBlockedDaysTest extends TestCase
{
    use RefreshDatabase;

    public function test_bloquear_un_dia_lo_guarda(): void
    {
        $this->entrarAlPanel();
        Livewire::test(PanelBlockedDays::class)
            ->set('newDate', '2026-09-24')
            ->set('newReason', 'Festivo')
            ->call('block')
            ->assertHasNoErrors();

        $this->assertSame('2026-09-24', BlockedDay::sole()->date->toDateString());
    }

    public function test_no_se_bloquea_un_dia_pasado(): void
    {
        $this->entrarAlPanel();
        Livewire::test(PanelBlockedDays::class)
            ->set('newDate', '2026-09-18')
            ->call('block')
            ->assertHasErrors('newDate');

        $this->assertSame(0, BlockedDay::count());
    }

    public function test_no_se_bloquea_dos_veces_el_mismo_dia(): void
    {
        BlockedDay::create(['date' => '2026-09-24']);

        $this->entrarAlPanel();

        Livewire::test(PanelBlockedDays::class)
            ->set('newDate', '2026-09-24')
            ->call('block')
            ->assertHasErrors('newDate');

        $this->assertSame(1, BlockedDay::count());
    }

    public function test_no_se_bloquea_un_dia_con_citas_activas(): void
    {
        Appointment::factory()->create(['date' => '2026-09-24']);

        $this->entrarAlPanel();

        Livewire::test(PanelBlockedDays::class)
            ->set('newDate', '2026-09-24')
            ->call('block')
            ->assertHasErrors('newDate');

        $this->assertSame(0, BlockedDay::count());
    }

    public function test_desbloquear_borra_el_dia(): void
    {
        $dia = BlockedDay::create(['date' => '2026-09-24']);

        $this->entrarAlPanel();

        Livewire::test(PanelBlockedDays::class)->call('unblock', $dia->id);

        $this->assertSame(0, BlockedDay::count());
    }

    public function test_bloquear_espera_al_bloqueo_del_dia_que_usan_las_reservas(): void
    {
        // ES: Una reserva o reprogramación de ese día está en curso (tiene el bloqueo).
        // EN: A booking or reschedule for that day is in progress (it holds the lock).
        config(['appointments.booking.lock_wait_seconds' => 0]);
        Cache::lock('reserva-dia:2026-09-24', 10)->get();

        $this->entrarAlPanel();

        Livewire::test(PanelBlockedDays::class)
            ->set('newDate', '2026-09-24')
            ->call('block')
            ->assertHasErrors('newDate');

        $this->assertSame(0, BlockedDay::count());
    }

    public function test_una_fecha_mal_escrita_no_se_bloquea(): void
    {
        // ES: "2026-9-24" daría otra clave de bloqueo que la de las reservas.
        // EN: "2026-9-24" would give a different lock key than the bookings'.
        $this->entrarAlPanel();

        Livewire::test(PanelBlockedDays::class)
            ->set('newDate', '2026-9-24')
            ->call('block')
            ->assertHasErrors('newDate');

        $this->assertSame(0, BlockedDay::count());
    }
}
