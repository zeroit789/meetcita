<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/*
|==============================================================================
| PanelController / Controlador del panel privado de citas (/panel)
|==============================================================================
| EN: Very simple login: a single shared password (the owner's) stored in
|     config('appointments.panel.password'). It does NOT use the users table.
|     On success a session flag is set; the 'appointments.panel' middleware
|     protects /panel.
| ES: Login muy simple: una única contraseña compartida (la del dueño) guardada
|     en config('appointments.panel.password'). No usa la tabla users. Al
|     acertar, se marca un flag en la sesión; el middleware 'appointments.panel'
|     protege /panel.
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
     * EN: Shows the panel login form. If already authenticated, goes to the panel.
     * ES: Muestra el formulario de login del panel. Si ya está autenticado, va al panel.
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
     * EN: Processes the login attempt: compares the submitted password with config.
     * ES: Procesa el intento de login: compara la contraseña enviada con la de config.
     */
    public function login(Request $request)
    {
        // EN: The password is required. ES: La contraseña es obligatoria.
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Escribe la contraseña.',
        ]);

        // EN: Timing-attack-safe comparison against the config password.
        // ES: Comparación segura contra timing attacks con la contraseña de config.
        $correcta = config('appointments.panel.password');

        if (! hash_equals((string) $correcta, (string) $request->input('password'))) {
            // EN: Wrong password: back to login with an error.
            // ES: Contraseña incorrecta: volvemos al login con error.
            return back()->withErrors(['password' => 'Contraseña incorrecta.']);
        }

        // EN: Success: mark the session as authenticated and regenerate the id.
        // ES: Acierto: marcamos la sesión como autenticada y regeneramos el id.
        $request->session()->regenerate();
        $request->session()->put('panel_autenticado', true);

        return redirect()->route('panel.index');
    }

    // ── 3. Index — mostrar el panel ─────────────────────────────────────────

    /**
     * EN: Shows the panel with the bookings table (Livewire component). Protected
     *     by the 'appointments.panel' middleware.
     * ES: Muestra el panel con la tabla de citas (componente Livewire). Protegido
     *     por el middleware 'appointments.panel'.
     */
    public function index()
    {
        return view('panel.index');
    }

    // ── 4. Logout — cerrar la sesión del panel ──────────────────────────────

    /**
     * EN: Ends the panel session.
     * ES: Cierra la sesión del panel.
     */
    public function logout(Request $request)
    {
        $request->session()->forget('panel_autenticado');

        return redirect()->route('panel.login');
    }
}
