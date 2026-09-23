/*
|==============================================================================
| APP.JS — Frontend JavaScript entry point / Punto de entrada de JS del front
|==============================================================================
| ES: Livewire 4 arranca su propio runtime solo (vía @vite), así que todo el
|     wizard de reserva funciona sin JS propio. La ÚNICA lógica propia aquí es
|     el TOGGLE DE TEMA claro/oscuro (botón sol/luna) que se conecta abajo.
| EN: Livewire 4 boots its own runtime automatically (via @vite), so the whole
|     booking wizard works without custom JS. The ONLY custom logic here is the
|     light/dark THEME TOGGLE (sun/moon button) wired up below.
|
| NOTE / NOTA:
|   ES: El tema del PRIMER pintado lo fija un script inline en el <head> de cada
|       layout (anti-parpadeo). Este archivo solo gestiona el toggle en runtime
|       y mantiene el icono del botón sincronizado, también tras navegaciones de
|       Livewire.
|   EN: The FIRST paint theme is set by a tiny inline script in the <head> of
|       each layout (anti-FOUC). This file only handles the runtime toggle and
|       keeps the button icon in sync, including after Livewire navigations.
|==============================================================================
*/

// ES: Clave de localStorage compartida con el script anti-parpadeo de los layouts.
// EN: localStorage key shared with the anti-FOUC script in the layouts.
const THEME_KEY = 'appointments-theme';

/**
 * ES: Devuelve el tema aplicado ahora mismo en <html> ('dark' o 'light').
 * EN: Returns the theme currently applied to <html> ('dark' or 'light').
 */
function currentTheme() {
    return document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
}

/**
 * ES: Aplica un tema: fija <html data-theme>, guarda la elección y refresca el
 *     icono + estado aria-pressed de cada botón toggle.
 * EN: Applies a theme: sets <html data-theme>, persists the choice and refreshes
 *     every toggle button's icon + aria-pressed state.
 *
 * @param {('dark'|'light')} theme
 */
function applyTheme(theme) {
    // ES: 1) Gobierna el CSS (todos los bg-base/text-ink/... siguen este atributo).
    // EN: 1) Drive the CSS (all bg-base/text-ink/... follow this attribute).
    document.documentElement.dataset.theme = theme;

    // ES: 2) Recuerda la elección del visitante para próximas visitas / navegaciones.
    // EN: 2) Remember the visitor's choice for next visits / navigations.
    try {
        localStorage.setItem(THEME_KEY, theme);
    } catch (e) {
        // ES: Modo privado / storage bloqueado → ignorar, el tema se aplica igual.
        // EN: Private mode / storage blocked → ignore, theme still applies live.
    }

    // ES: 3) Sincroniza cada toggle de la página (puede haber más de uno).
    // EN: 3) Sync every toggle on the page (there may be more than one).
    syncToggleIcons(theme);
}

/**
 * ES: Muestra el icono correcto (sol en oscuro = "pasar a claro", luna en claro
 *     = "pasar a oscuro") y actualiza aria-pressed para tecnología de apoyo.
 * EN: Shows the right icon (sun on dark = "switch to light", moon on light =
 *     "switch to dark") and updates aria-pressed for assistive tech.
 *
 * @param {('dark'|'light')} theme
 */
function syncToggleIcons(theme) {
    const isDark = theme === 'dark';
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        // ES: aria-pressed = true cuando el tema CLARO está activo.
        // EN: aria-pressed = true when the LIGHT theme is active.
        btn.setAttribute('aria-pressed', String(!isDark));

        // ES: Icono sol visible en oscuro; icono luna visible en claro.
        // EN: Sun icon visible on dark; moon icon visible on light.
        const sun = btn.querySelector('[data-icon-sun]');
        const moon = btn.querySelector('[data-icon-moon]');
        if (sun) sun.classList.toggle('hidden', !isDark);
        if (moon) moon.classList.toggle('hidden', isDark);
    });
}

/**
 * ES: Alterna entre oscuro y claro y aplica el resultado.
 * EN: Toggles between dark and light and applies the result.
 */
function toggleTheme() {
    applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
}

// ES: Delegación de eventos en el documento → funciona también para botones que
//     Livewire añada/reemplace (no hay que re-enlazar listeners tras navegar).
// EN: Event delegation on the document → works for buttons added/replaced by
//     Livewire too (no need to re-bind listeners after a navigation).
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-theme-toggle]');
    if (toggle) {
        event.preventDefault();
        toggleTheme();
    }
});

// ES: Al cargar, alinea los iconos con lo que el script anti-parpadeo dejó puesto.
// EN: On first load, align the icons with whatever the anti-FOUC script set.
syncToggleIcons(currentTheme());

// ES: Tras una navegación SPA de Livewire el <head>/iconos pueden re-renderizarse,
//     así que re-sincronizamos los iconos (Livewire conserva el data-theme del <html>).
// EN: After a Livewire SPA navigation the <head>/icons may be re-rendered, so
//     re-sync the icons (the data-theme on <html> is preserved by Livewire).
document.addEventListener('livewire:navigated', () => {
    syncToggleIcons(currentTheme());
});
