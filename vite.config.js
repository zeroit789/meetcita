// ES: Configuración de Vite: compila resources/css/app.css (Tailwind 4) y
//     resources/js/app.js y los publica en public/build para @vite.
// EN: Vite config: builds resources/css/app.css (Tailwind 4) and
//     resources/js/app.js and outputs them to public/build for @vite.
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        // ES: Plugin de Laravel: puntos de entrada y recarga al cambiar vistas.
        // EN: Laravel plugin: entry points and reload when views change.
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        // ES: Tailwind CSS 4 como plugin de Vite (sin tailwind.config.js).
        // EN: Tailwind CSS 4 as a Vite plugin (no tailwind.config.js).
        tailwindcss(),
    ],
    // ES: En desarrollo, ignora las vistas compiladas de Blade para no recargar en bucle.
    // EN: In dev, ignore compiled Blade views to avoid reload loops.
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
