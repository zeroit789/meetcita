<?php

namespace Tests\Feature;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use App\Livewire\BookAppointment;
use App\Models\Appointment;
use App\Services\CaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ES: Captcha propio ALTCHA: sin solución válida no se reserva; con una
 *     resuelta se reserva una vez y la misma solución no sirve dos veces.
 * EN: Own ALTCHA captcha: without a valid solution there is no booking; with
 *     a solved one it books once and the same solution can't be reused.
 */
class CaptchaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Activa el captcha con un trabajo pequeño para que el test sea rápido.
     * EN: Enables the captcha with little work so the test is fast.
     */
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.altcha.enabled' => true,
            'services.altcha.cost' => 1,
            'services.altcha.counter_min' => 1,
            'services.altcha.counter_max' => 3,
            'services.altcha.expires' => 600,
        ]);
        Mail::fake();
    }

    /**
     * ES: Pide un reto a la ruta y lo resuelve como lo haría el navegador.
     * EN: Requests a challenge from the route and solves it like the browser.
     */
    private function solucion(): string
    {
        $reto = Challenge::fromArray($this->getJson('/altcha/challenge')->assertOk()->json());
        $sol = app(CaptchaService::class)->altcha()->solveChallenge(new SolveChallengeOptions(challenge: $reto, algorithm: new Pbkdf2));

        return (new Payload($reto, $sol))->toBase64();
    }

    /**
     * ES: Reserva con lo mínimo y la solución dada. EN: Books with the minimum and the given solution.
     */
    private function reservar(string $altcha, string $hora = '10:00')
    {
        return Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->call('selectTime', $hora)
            ->set('name', 'Ana García')
            ->set('email', 'ana'.str_replace(':', '', $hora).'@example.com')
            ->set('altcha', $altcha)
            ->call('reserve');
    }

    public function test_sin_captcha_no_se_reserva(): void
    {
        $this->reservar('')->assertHasErrors('altcha');
        $this->reservar('{"falso":true}')->assertHasErrors('altcha');

        $this->assertSame(0, Appointment::count());
    }

    public function test_con_el_captcha_resuelto_se_reserva_y_no_se_puede_reutilizar(): void
    {
        $solucion = $this->solucion();

        $this->reservar($solucion)->assertHasNoErrors()->assertSet('step', 4);
        $this->reservar($solucion, '11:00')->assertHasErrors('altcha');

        $this->assertSame(1, Appointment::count());
    }

    public function test_el_reto_no_se_guarda_en_cache(): void
    {
        $this->get('/altcha/challenge')->assertHeader('Cache-Control', 'no-store, private');
    }
}
