<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BlockedDay;
use App\Support\ModoDemo;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ES: Modo demo (MEETCITA_DEMO=true) para una demo pública: nada sale de
 *     verdad (correo al log, sin Telegram ni Google), avisos de demo en las
 *     páginas, contraseña de panel conocida, datos de ejemplo y purga cada
 *     hora. La orden de reinicio se niega a borrar fuera del modo demo.
 * EN: Demo mode (MEETCITA_DEMO=true) for a public demo: nothing is really
 *     sent (mail to the log, no Telegram nor Google), demo notices on the
 *     pages, a known panel password, sample data and an hourly purge. The
 *     reset command refuses to delete anything outside demo mode.
 */
class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Activa la demo como lo haría el .env y aplica sus ajustes.
     * EN: Turns the demo on as the .env would and applies its settings.
     */
    private function activarDemo(): void
    {
        config([
            'appointments.demo.enabled' => true,
            'mail.default' => 'smtp',
            'services.telegram.token' => 'token-real',
            'services.telegram.chat_id' => '123',
            'services.google.refresh_token' => 'refresh-real',
        ]);
        ModoDemo::aplicar();
    }

    public function test_en_demo_no_sale_nada_de_verdad(): void
    {
        $this->activarDemo();

        $this->assertSame('log', config('mail.default'));
        $this->assertNull(config('services.telegram.token'));
        $this->assertNull(config('services.google.refresh_token'));
        $this->assertSame('demo', config('appointments.panel.password'));
    }

    public function test_sin_demo_no_se_toca_nada(): void
    {
        config(['mail.default' => 'smtp', 'services.telegram.token' => 'token-real']);
        ModoDemo::aplicar();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('token-real', config('services.telegram.token'));
    }

    public function test_las_paginas_avisan_de_que_es_una_demo(): void
    {
        $this->activarDemo();

        $this->get('/citas')->assertSee(__('citas.demo_banner'));
        $this->get('/panel/login')->assertSee(__('citas.demo_panel_hint', ['password' => 'demo']));
    }

    public function test_el_reinicio_de_la_demo_deja_datos_de_ejemplo(): void
    {
        $this->activarDemo();
        Appointment::factory()->create(['email' => 'real@visitante.com']);

        $this->artisan('citas:demo')->assertSuccessful();

        $this->assertSame(0, Appointment::where('email', 'real@visitante.com')->count());
        $this->assertGreaterThanOrEqual(3, Appointment::count());
        $this->assertTrue(Appointment::pluck('email')->every(fn ($e) => str_ends_with($e, '@example.com')));
        $this->assertSame(1, BlockedDay::count());
    }

    public function test_fuera_de_la_demo_el_reinicio_no_borra_nada(): void
    {
        $cita = Appointment::factory()->create();

        $this->artisan('citas:demo')->assertFailed();

        $this->assertModelExists($cita);
    }

    public function test_el_reinicio_esta_programado_cada_hora_y_solo_corre_en_demo(): void
    {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'citas:demo'));

        $this->assertNotNull($evento);
        $this->assertSame('0 * * * *', $evento->expression);
        $this->assertFalse($evento->filtersPass($this->app));

        config(['appointments.demo.enabled' => true]);
        $this->assertTrue($evento->filtersPass($this->app));
    }
}
