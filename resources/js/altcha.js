/*
|==============================================================================
| ALTCHA.JS — captcha propio / own captcha
|==============================================================================
| ES: Carga el widget ALTCHA (versión "external": sin estilos en línea, apta
|     para la CSP), su trabajador PBKDF2 y los textos en español. Al
|     verificarse, copia la solución a la propiedad `altcha` del componente
|     Livewire que lo contiene; tras cada envío, el servidor pide reiniciarlo.
| EN: Loads the ALTCHA widget ("external" build: no inline styles, CSP
|     friendly), its PBKDF2 worker and the Spanish texts. Once verified, it
|     copies the solution into the `altcha` property of the enclosing Livewire
|     component; after each submit the server asks to reset it.
|==============================================================================
*/
import 'altcha/external';
import 'altcha/altcha.css';
import 'altcha/i18n/es-es';
import Pbkdf2Worker from 'altcha/workers/pbkdf2?worker';

// ES: Trabajador que resuelve el reto sin bloquear la página.
// EN: Worker that solves the challenge without blocking the page.
window.$altcha.algorithms.set('PBKDF2/SHA-256', () => new Pbkdf2Worker());

// ES: Solución verificada -> al componente Livewire más cercano.
// EN: Verified solution -> to the closest Livewire component.
document.addEventListener('verified', (evento) => {
    const widget = evento.target;
    if (widget?.tagName !== 'ALTCHA-WIDGET') {
        return;
    }
    const raiz = widget.closest('[wire\\:id]');
    const componente = raiz && window.Livewire?.find(raiz.getAttribute('wire:id'));
    if (componente) {
        componente.altcha = evento.detail.payload;
    }
}, true);

// ES: El servidor pide reiniciar el widget tras usar (o rechazar) la solución.
// EN: The server asks to reset the widget after using (or rejecting) the solution.
window.addEventListener('altcha-reset', () => {
    document.querySelectorAll('altcha-widget').forEach((widget) => widget.reset?.());
});
