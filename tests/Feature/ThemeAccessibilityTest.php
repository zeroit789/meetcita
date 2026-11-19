<?php

/*
|==============================================================================
| ThemeAccessibilityTest — neutral theme + WCAG basics in the views
| ThemeAccessibilityTest — tema neutro + básicos de WCAG en las vistas
|==============================================================================
| ES: Comprueba lo que axe no mide: el color de acento sale de la config y se
|     valida (no se puede inyectar CSS), no quedan textos de consola ni el
|     enlace al código en el pie, y los campos llevan autocomplete, aria-invalid
|     ligado al error, aria-pressed en duración/modalidad y landmark <main>.
| EN: Checks what axe doesn't measure: the accent colour comes from config and
|     is validated (no CSS injection), no console-like texts or footer source
|     link remain, and fields carry autocomplete, aria-invalid tied to the
|     error, aria-pressed on duration/modality and a <main> landmark.
|==============================================================================
*/

namespace Tests\Feature;

use App\Livewire\BookAppointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ThemeAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    // ── 1. ACCENT / ACENTO ────────────────────────────────────────────────

    /**
     * ES: Un hex válido de la config llega tal cual a la variable --accent.
     * EN: A valid hex from config reaches the --accent variable as is.
     */
    public function test_el_acento_valido_de_la_config_se_inyecta(): void
    {
        config(['appointments.accent' => '#0f766e', 'appointments.accent_glow' => '#99f6e4']);

        $this->get('/citas')
            ->assertOk()
            ->assertSee('--accent: #0f766e;', false)
            ->assertSee('--accent-glow: #99f6e4;', false)
            ->assertSee("fill='%230f766e'", false);
    }

    /**
     * ES: Valores no válidos o maliciosos caen al azul por defecto y nunca
     *     llegan al HTML (ni en la página pública ni en el panel).
     * EN: Invalid or malicious values fall back to the default blue and never
     *     reach the HTML (neither the public page nor the panel).
     */
    #[DataProvider('acentosNoValidos')]
    public function test_un_acento_no_valido_usa_el_de_por_defecto(string $valor): void
    {
        config(['appointments.accent' => $valor, 'appointments.accent_glow' => $valor]);

        foreach (['/citas', '/panel/login'] as $ruta) {
            $html = $this->get($ruta)->assertOk()->getContent();
            $this->assertStringContainsString('--accent: #1d4ed8;', $html);
            $this->assertStringNotContainsString('--accent-glow:', $html);
            $this->assertStringNotContainsString('</style><script>', $html);
            $this->assertStringNotContainsString('red;}body', $html);
        }
    }

    /**
     * ES: Casos: inyección de CSS/HTML, salto de línea final, formato corto,
     *     sin almohadilla y nombre de color.
     * EN: Cases: CSS/HTML injection, trailing newline, short form, no hash and
     *     colour name.
     */
    public static function acentosNoValidos(): array
    {
        return [
            'inyección CSS' => ['#123456;}body{background:red;}'],
            'inyección HTML' => ['</style><script>alert(1)</script>'],
            'salto de línea final' => ["#123456\n"],
            'formato corto' => ['#fff'],
            'sin almohadilla' => ['1d4ed8'],
            'nombre de color' => ['red'],
        ];
    }

    /**
     * ES: Sin APPOINTMENTS_THEME el tema de partida es el claro (se mira el
     *     valor por defecto del fichero: el .env local puede fijar otro), y el
     *     layout lo pasa al script anti-parpadeo.
     * EN: Without APPOINTMENTS_THEME the starting theme is light (the file's
     *     default is checked: the local .env may set another), and the layout
     *     hands it to the anti-FOUC script.
     */
    public function test_el_tema_por_defecto_es_claro(): void
    {
        $this->assertStringContainsString(
            "env('APPOINTMENTS_THEME', 'light')",
            file_get_contents(config_path('appointments.php'))
        );

        config(['appointments.theme' => 'light']);
        $this->get('/citas')->assertSee('data-default-theme="light"', false);
    }

    // ── 2. CLIENT-FACING TEXTS / TEXTOS DE CARA AL CLIENTE ────────────────

    /**
     * ES: Sin textos de consola, sin enlace al código en el pie y con el
     *     nombre del negocio en la cabecera.
     * EN: No console texts, no source link in the footer and the business name
     *     in the header.
     */
    public function test_la_pagina_de_reserva_no_tiene_jerga_de_programador(): void
    {
        config(['appointments.brand.name' => 'Asesoría Pérez SL']);

        $html = $this->get('/citas')->assertOk()->getContent();

        $this->assertStringNotContainsString('$ ./', $html);
        $this->assertStringNotContainsString('github.com', $html);
        $this->assertStringNotContainsString('fonts.bunny.net', $html);
        $this->assertMatchesRegularExpression('/<header[^>]*>.*Asesoría Pérez SL.*<\/header>/s', $html);
    }

    // ── 3. BOOKING FORM A11Y / A11Y DEL FORMULARIO DE RESERVA ─────────────

    /**
     * ES: Paso a un día y hora válidos (fecha fija de TestCase).
     * EN: Moves to a valid day and time (fixed date from TestCase).
     */
    private function wizardEnPaso3()
    {
        return Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->call('selectTime', '10:00');
    }

    /**
     * ES: Nombre, email y teléfono llevan autocomplete; la modalidad dice cuál
     *     está elegida con aria-pressed.
     * EN: Name, email and phone carry autocomplete; modality states the chosen
     *     one with aria-pressed.
     */
    public function test_los_campos_tienen_autocomplete_y_la_modalidad_aria_pressed(): void
    {
        $this->wizardEnPaso3()
            ->assertSeeHtml('autocomplete="name"')
            ->assertSeeHtml('autocomplete="email"')
            ->assertSeeHtml('autocomplete="tel"')
            ->assertSeeHtml('role="group" aria-labelledby="modalidad-etiqueta"')
            ->assertSeeHtml('aria-pressed="true"')
            ->assertSeeHtml('aria-pressed="false"');
    }

    /**
     * ES: Al enviar vacío, cada campo con error queda marcado y ligado a su
     *     mensaje, que se anuncia con role="alert".
     * EN: Submitting empty marks each failing field and ties it to its message,
     *     announced with role="alert".
     */
    public function test_los_errores_quedan_ligados_a_su_campo(): void
    {
        config(['services.altcha.enabled' => false]);

        $this->wizardEnPaso3()
            ->call('reserve')
            ->assertHasErrors(['name', 'email'])
            ->assertSeeHtml('aria-invalid="true" aria-describedby="appt-name-error"')
            ->assertSeeHtml('<p id="appt-name-error" class="text-sm text-danger mt-1" role="alert">')
            ->assertSeeHtml('aria-invalid="true" aria-describedby="appt-email-error"');
    }

    /**
     * ES: La duración elegida se marca con aria-pressed y el paso actual con
     *     aria-current="step"; el título del paso es enfocable para el JS.
     * EN: The chosen duration is marked with aria-pressed and the current step
     *     with aria-current="step"; the step heading is focusable for the JS.
     */
    public function test_duracion_y_pasos_exponen_su_estado(): void
    {
        Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->assertSeeHtml('aria-labelledby="duracion-etiqueta"')
            ->assertSeeHtml('aria-pressed="true"')
            ->assertSeeHtml('aria-current="step"')
            ->assertSeeHtml('tabindex="-1" data-paso-titulo="2"');
    }

    // ── 4. PANEL / PANEL ───────────────────────────────────────────────────

    /**
     * ES: El login y el panel tienen landmark <main>; los campos de días
     *     bloqueados tienen etiqueta ligada por for/id.
     * EN: Login and panel have a <main> landmark; the blocked-day fields have a
     *     label tied by for/id.
     */
    public function test_el_panel_tiene_main_y_campos_etiquetados(): void
    {
        $this->get('/panel/login')->assertOk()->assertSee('<main id="contenido">', false);

        $this->post('/panel/login', ['password' => 'secreto-de-test']);

        $this->get('/panel')
            ->assertOk()
            ->assertSee('<main id="contenido">', false)
            ->assertSee('for="blocked-date"', false)
            ->assertSee('id="blocked-date"', false)
            ->assertSee('for="blocked-reason"', false)
            ->assertSee('id="blocked-reason"', false);
    }

    /**
     * ES: Contraseña errónea: el campo queda marcado y ligado al aviso.
     * EN: Wrong password: the field is marked and tied to the notice.
     */
    public function test_el_error_del_login_queda_ligado_al_campo(): void
    {
        $this->from('/panel/login')->post('/panel/login', ['password' => 'mala']);

        $this->get('/panel/login')
            ->assertSee('aria-invalid="true" aria-describedby="panel-password-error"', false)
            ->assertSee('id="panel-password-error"', false);
    }
}
