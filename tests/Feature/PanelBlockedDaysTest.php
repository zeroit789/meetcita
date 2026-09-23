<?php

namespace Tests\Feature;

use App\Livewire\PanelBlockedDays;
use App\Models\Appointment;
use App\Models\BlockedDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        Livewire::test(PanelBlockedDays::class)
            ->set('newDate', '2026-09-24')
            ->set('newReason', 'Festivo')
            ->call('block')
            ->assertHasNoErrors();

        $this->assertSame('2026-09-24', BlockedDay::sole()->date->toDateString());
    }

    public function test_no_se_bloquea_un_dia_pasado(): void
    {
        Livewire::test(PanelBlockedDays::class)
            ->set('newDate', '2026-09-18')
            ->call('block')
            ->assertHasErrors('newDate');

        $this->assertSame(0, BlockedDay::count());
    }

    public function test_no_se_bloquea_dos_veces_el_mismo_dia(): void
    {
        BlockedDay::create(['date' => '2026-09-24']);

        Livewire::test(PanelBlockedDays::class)
            ->set('newDate', '2026-09-24')
            ->call('block')
            ->assertHasErrors('newDate');

        $this->assertSame(1, BlockedDay::count());
    }

    public function test_no_se_bloquea_un_dia_con_citas_activas(): void
    {
        Appointment::factory()->create(['date' => '2026-09-24']);

        Livewire::test(PanelBlockedDays::class)
            ->set('newDate', '2026-09-24')
            ->call('block')
            ->assertHasErrors('newDate');

        $this->assertSame(0, BlockedDay::count());
    }

    public function test_desbloquear_borra_el_dia(): void
    {
        $dia = BlockedDay::create(['date' => '2026-09-24']);

        Livewire::test(PanelBlockedDays::class)->call('unblock', $dia->id);

        $this->assertSame(0, BlockedDay::count());
    }
}
