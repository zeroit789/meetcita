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
// ES: El captcha solo se descarga en las páginas que lo usan (paso 3 de la reserva).
// EN: The captcha is only downloaded on pages that use it (booking step 3).
function cargarCaptchaSiHaceFalta() {
    if (document.querySelector('altcha-widget')) {
        import('./altcha.js');
    }
}
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morphed', cargarCaptchaSiHaceFalta);
});
document.addEventListener('DOMContentLoaded', cargarCaptchaSiHaceFalta);

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

/*
| ES: FOCO ACCESIBLE en los componentes Livewire (WCAG 2.4.3 y 3.3.1):
|     · Al CAMBIAR de paso en la reserva (cambia data-paso-titulo), el foco va
|       al título del paso nuevo: Livewire quita el botón pulsado y, sin esto,
|       el foco caía al <body> y el lector de pantalla volvía al principio.
|     · Al ENVIAR la reserva con errores, el foco va al primer error: un campo
|       con aria-invalid o un aviso sin campo propio (data-error-envio).
|     · Si aparece un campo marcado con data-enfocar (p. ej. el motivo de
|       cancelación del panel), recibe el foco.
|     · Si el foco se pierde en el mismo paso (p. ej. la flecha de mes se
|       desactiva al llegar al límite), va a la otra flecha o al título.
|     No hace nada en la carga inicial: solo tras cada respuesta de Livewire.
| EN: ACCESSIBLE FOCUS in Livewire components (WCAG 2.4.3 and 3.3.1):
|     · On a booking STEP change (data-paso-titulo changes), focus goes to the
|       new step heading: Livewire removes the clicked button and, without
|       this, focus fell to <body> and screen readers restarted from the top.
|     · On SUBMITTING the booking with errors, focus goes to the first error: a
|       field with aria-invalid or a field-less notice (data-error-envio).
|     · When a field marked data-enfocar appears (e.g. the panel's cancellation
|       reason), it receives focus.
|     · If focus is lost within the same step (e.g. the month arrow becomes
|       disabled at the limit), it goes to the other arrow or the heading.
|     Does nothing on first load: only after each Livewire response.
*/
document.addEventListener('livewire:init', () => {
    const lw = window.Livewire;
    if (!lw || typeof lw.interceptMessage !== 'function') return;

    lw.interceptMessage(({ message, onFinish }) => {
        const raiz = message && message.component && message.component.el;
        if (!raiz) return;

        // ES: Estado ANTES de la respuesta. EN: State BEFORE the response.
        const marcaAntes = raiz.querySelector('[data-paso-titulo]');
        const pasoAntes = marcaAntes ? marcaAntes.dataset.pasoTitulo : null;
        const enfocablesAntes = new Set(raiz.querySelectorAll('[data-enfocar]'));
        const activoAntes = document.activeElement;
        const focoDentro = raiz.contains(activoAntes);
        const eraFlechaDeMes = focoDentro && activoAntes.closest('[data-cal-nav]') !== null;

        onFinish(() => {
            const acciones = message.actions ? Array.from(message.actions) : [];
            const esEnvio = acciones.some((a) => a && a.name === 'reserve');

            // ES: 1) Envío con errores → primer error. EN: 1) Submit with errors → first error.
            //    (también si el envío devuelve al paso 2 porque la hora se ocupó)
            //    (also if the submit goes back to step 2 because the time was taken)
            const error = esEnvio ? raiz.querySelector('[data-error-envio], [aria-invalid="true"]') : null;
            if (error) {
                error.focus();
                return;
            }

            // ES: 2) Cambio de paso → título del paso. EN: 2) Step change → step heading.
            const marca = raiz.querySelector('[data-paso-titulo]');
            if (marca && marca.dataset.pasoTitulo !== pasoAntes) {
                marca.focus();
                return;
            }

            // ES: 3) Campo nuevo marcado para enfocar. EN: 3) New field marked for focus.
            const nuevo = Array.from(raiz.querySelectorAll('[data-enfocar]')).find((el) => !enfocablesAntes.has(el));
            if (nuevo) {
                nuevo.focus();
                return;
            }

            // ES: 4) Foco perdido en el mismo paso → otra flecha de mes o título.
            // EN: 4) Focus lost within the same step → other month arrow or heading.
            const foco = document.activeElement;
            if (focoDentro && (foco === document.body || (foco && foco.matches(':disabled')))) {
                const otraFlecha = eraFlechaDeMes ? raiz.querySelector('[data-cal-nav] button:not(:disabled)') : null;
                // ES: En el panel no hay título de paso: el respaldo es el primer
                //     encabezado del componente (al cerrar el formulario de
                //     cancelar, confirmar o quitar un día, el botón desaparece).
                // EN: The panel has no step heading: the fallback is the
                //     component's first heading (closing the cancel form,
                //     confirming or removing a day makes the button disappear).
                let respaldo = marca || raiz.querySelector('h1, h2, h3');
                if (respaldo && !respaldo.hasAttribute('tabindex')) respaldo.setAttribute('tabindex', '-1');
                const destino = activoAntes.isConnected && !activoAntes.disabled ? activoAntes : (otraFlecha || respaldo);
                if (destino) destino.focus();
            }
        });
    });
});
