<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/*
|==============================================================================
| PanelController / Controlador del panel privado de citas (/panel)
|==============================================================================
| ES: Login muy simple: una única contraseña compartida (la del dueño) guardada
|     en config('appointments.panel.password'). No usa la tabla users. Al
|     acertar, se marca un flag en la sesión; el middleware 'appointments.panel'
|     protege /panel.
| EN: Very simple login: a single shared password (the owner's) stored in
|     config('appointments.panel.password'). It does NOT use the users table.
|     On success a session flag is set; the 'appointments.panel' middleware
|     protects /panel.
|
| INDEX / ÍNDICE
|   1. SHOW LOGIN ..... show login form / mostrar formulario de login
|   2. LOGIN .......... process login attempt / procesar el intento de login
|   3. INDEX .......... show the panel / mostrar el panel
|   4. LOGOUT ......... end the panel session / cerrar la sesión del panel
|==============================================================================
*/
class PanelController extends Controller
{
    // ── 1. Show login — mostrar el formulario de login ──────────────────────

    /**
     * ES: Muestra el formulario de login del panel. Si ya está autenticado, va al panel.
     * EN: Shows the panel login form. If already authenticated, goes to the panel.
     */
    public function showLogin(Request $request)
    {
        if ($request->session()->get('panel_autenticado', false)) {
            return redirect()->route('panel.index');
        }

        return view('panel.login');
    }

    // ── 2. Login — procesar el intento de login ─────────────────────────────

    /**
     * ES: Procesa el intento de login: compara la contraseña enviada con la de config.
     * EN: Processes the login attempt: compares the submitted password with config.
     */
    public function login(Request $request)
    {
        // ES: La contraseña es obligatoria. EN: The password is required.
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Escribe la contraseña.',
        ]);

        // ES: Comparación segura contra timing attacks con la contraseña de config.
        // EN: Timing-attack-safe comparison against the config password.
        $correcta = config('appointments.panel.password');

        if (! hash_equals((string) $correcta, (string) $request->input('password'))) {
            // ES: Contraseña incorrecta: volvemos al login con error.
            // EN: Wrong password: back to login with an error.
            return back()->withErrors(['password' => 'Contraseña incorrecta.']);
        }

        // ES: Acierto: marcamos la sesión como autenticada y regeneramos el id.
        // EN: Success: mark the session as authenticated and regenerate the id.
        $request->session()->regenerate();
        $request->session()->put('panel_autenticado', true);

        return redirect()->route('panel.index');
    }

    // ── 3. Index — mostrar el panel ─────────────────────────────────────────

    /**
     * ES: Muestra el panel con la tabla de citas (componente Livewire). Protegido
     *     por el middleware 'appointments.panel'.
     * EN: Shows the panel with the bookings table (Livewire component). Protected
     *     by the 'appointments.panel' middleware.
     */
    public function index()
    {
        return view('panel.index');
    }

    // ── 4. Logout — cerrar la sesión del panel ──────────────────────────────

    /**
     * ES: Cierra la sesión del panel.
     * EN: Ends the panel session.
     */
    public function logout(Request $request)
    {
        $request->session()->forget('panel_autenticado');

        return redirect()->route('panel.login');
    }
}
