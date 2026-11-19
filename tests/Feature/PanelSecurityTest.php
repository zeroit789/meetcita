<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminPanelPassword;
use App\Models\Appointment;
use App\Models\BlockedDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ES: Seguridad del panel privado (/panel). Reproduce el fallo F1: una copia
 *     antigua del panel (la instantánea de Livewire) no debe servir para
 *     ejecutar acciones ni para leer datos de clientes cuando ya no hay sesión
 *     del panel válida: tras cerrar sesión, o tras cambiar la contraseña.
 * EN: Security of the private panel (/panel). Reproduces bug F1: an old copy of
 *     the panel (the Livewire snapshot) must not be usable to run actions or to
 *     read client data once there is no valid panel session: after logging out,
 *     or after the password has been changed.
 */
class PanelSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Crea una cita pendiente con un email reconocible.
     * EN: Creates a pending booking with a recognisable email.
     */
    private function cita(string $email, string $hora = '10:00'): Appointment
    {
        return Appointment::factory()->create([
            'email' => $email,
            'date' => '2026-09-24',
            'time' => $hora,
        ]);
    }

    /**
     * ES: Entra al panel de verdad (POST al login) y devuelve la instantánea
     *     del componente pedido, sacada del HTML del panel.
     * EN: Logs into the panel for real (POST to the login) and returns the
     *     snapshot of the requested component, taken from the panel HTML.
     */
    private function entrarYCapturar(string $componente): string
    {
        $this->post('/panel/login', ['password' => 'secreto-de-test'])->assertRedirect(route('panel.index'));
        $html = $this->get('/panel')->assertOk()->getContent();

        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $coincidencias);
        foreach ($coincidencias[1] as $crudo) {
            $snapshot = html_entity_decode($crudo, ENT_QUOTES);
            if ((json_decode($snapshot, true)['memo']['name'] ?? null) === $componente) {
                return $snapshot;
            }
        }

        $this->fail("No se encontró la instantánea de {$componente} en el panel");
    }

    /**
     * ES: Petición igual que la del JavaScript de Livewire a /livewire/update.
     * EN: Same request the Livewire JavaScript sends to /livewire/update.
     *
     * @param  array<int, mixed>  $params
     */
    private function accion(string $snapshot, string $metodo, array $params = []): TestResponse
    {
        // ES: Cada petición real arranca con Livewire limpio; aquí comparten
        //     proceso, así que se limpia a mano (como hace Livewire::test()).
        // EN: Each real request starts with a clean Livewire; here they share
        //     one process, so it is flushed by hand (as Livewire::test() does).
        app('livewire')->flushState();

        return $this->withHeaders(['X-Livewire' => '1'])->postJson(Livewire::getUpdateUri(), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => [],
                'calls' => [['path' => '', 'method' => $metodo, 'params' => $params]],
            ]],
        ]);
    }

    /**
     * ES: Comprueba que la respuesta es un rechazo (403 o redirección al login).
     * EN: Asserts the response is a rejection (403 or a redirect to the login).
     */
    private function assertRechazada(TestResponse $respuesta): void
    {
        // ES: Las peticiones de Livewire no siguen redirecciones: tiene que ser 403.
        // EN: Livewire requests don't follow redirects: it must be a 403.
        $this->assertSame(403, $respuesta->status(), 'Se esperaba 403; llegó '.$respuesta->status());
    }

    public function test_el_middleware_del_panel_es_persistente_en_livewire(): void
    {
        $this->assertContains(AdminPanelPassword::class, Livewire::getPersistentMiddleware());
    }

    public function test_la_instantanea_sirve_con_sesion_y_no_tras_cerrar_sesion(): void
    {
        $citaA = $this->cita('ana@example.com', '10:00');
        $citaB = $this->cita('secreto-b@example.com', '11:00');
        $snapshot = $this->entrarYCapturar('panel-citas');

        // ES: 1) Con sesión, la instantánea funciona (la prueba es válida).
        // EN: 1) With a session, the snapshot works (the test is valid).
        $conSesion = $this->accion($snapshot, 'confirmar', [$citaA->id])->assertOk();
        // ES: Ni la respuesta de la acción se guarda en caché. EN: Not even the action response gets cached.
        $this->assertStringContainsString('no-store', (string) $conSesion->headers->get('Cache-Control'));
        $this->assertSame('confirmada', $citaA->fresh()->status);

        // ES: 2) Tras cerrar sesión, la misma instantánea no confirma nada ni
        //     devuelve el listado con los datos de los clientes.
        // EN: 2) After logging out, the same snapshot confirms nothing and
        //     doesn't return the list with the clients' data.
        $this->post('/panel/logout')->assertRedirect(route('panel.login'));
        $respuesta = $this->accion($snapshot, 'confirmar', [$citaB->id]);

        $this->assertRechazada($respuesta);
        $this->assertSame('pendiente', $citaB->fresh()->status);
        $this->assertStringNotContainsString('secreto-b@example.com', $respuesta->getContent());
    }

    public function test_tras_cerrar_sesion_ni_siquiera_se_puede_repintar_el_listado(): void
    {
        $this->cita('secreto-b@example.com');
        $snapshot = $this->entrarYCapturar('panel-citas');
        $this->post('/panel/logout');

        // ES: Acciones que no cambian nada pero vuelven a pintar el listado.
        // EN: Actions that change nothing but render the list again.
        foreach (['$refresh', 'pedirCancelacion'] as $metodo) {
            $respuesta = $this->accion($snapshot, $metodo, $metodo === '$refresh' ? [] : [1]);
            $this->assertRechazada($respuesta);
            $this->assertStringNotContainsString('secreto-b@example.com', $respuesta->getContent());
        }
    }

    public function test_cambiar_la_contrasena_invalida_las_sesiones_abiertas(): void
    {
        $cita = $this->cita('secreto-b@example.com');
        $snapshot = $this->entrarYCapturar('panel-citas');

        // ES: El dueño cambia la contraseña (p. ej. tras irse un empleado).
        // EN: The owner changes the password (e.g. after an employee leaves).
        config(['appointments.panel.password' => 'contrasena-nueva']);

        $this->get('/panel')->assertRedirect(route('panel.login'));
        $respuesta = $this->accion($snapshot, 'confirmar', [$cita->id]);
        $this->assertRechazada($respuesta);
        $this->assertSame('pendiente', $cita->fresh()->status);
        $this->assertStringNotContainsString('secreto-b@example.com', $respuesta->getContent());
    }

    public function test_los_dias_bloqueados_tampoco_se_tocan_sin_sesion(): void
    {
        $dia = BlockedDay::create(['date' => '2026-09-25']);
        $snapshot = $this->entrarYCapturar('panel-blocked-days');
        $this->post('/panel/logout');

        $this->assertRechazada($this->accion($snapshot, 'unblock', [$dia->id]));
        $this->assertSame(1, BlockedDay::count());
    }

    public function test_sin_sesion_los_componentes_del_panel_dan_403(): void
    {
        $cita = $this->cita('secreto-b@example.com');

        Livewire::test('panel-citas')->assertForbidden();
        Livewire::test('panel-blocked-days')->assertForbidden();
        $this->assertSame('pendiente', $cita->fresh()->status);
    }

    public function test_el_panel_no_se_guarda_en_cache_del_navegador(): void
    {
        // ES: Una copia del panel en caché (ordenador compartido) es justo el
        //     punto de partida del ataque F1: el panel sale con no-store.
        // EN: A cached copy of the panel (shared computer) is exactly where
        //     the F1 attack starts: the panel is served with no-store.
        $this->post('/panel/login', ['password' => 'secreto-de-test']);

        $cabecera = (string) $this->get('/panel')->assertOk()->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cabecera);
    }

    public function test_cerrar_sesion_invalida_la_sesion_y_regenera_el_token(): void
    {
        $this->post('/panel/login', ['password' => 'secreto-de-test']);
        session(['otro_dato' => 'x']);
        $tokenAntes = session()->token();

        $this->post('/panel/logout')->assertRedirect(route('panel.login'));

        $this->assertNull(session('otro_dato'));
        $this->assertNotSame($tokenAntes, session()->token());
        $this->get('/panel')->assertRedirect(route('panel.login'));
    }
}
