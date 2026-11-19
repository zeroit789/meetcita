<?php

/*
|==============================================================================
| Livewire — ajustes propios / own settings
|==============================================================================
| ES: Solo lo que cambia respecto a vendor/livewire/livewire/config/livewire.php.
|     csp_safe: Livewire y Alpine se cargan en su versión sin eval() ni
|     new Function(), así la CSP (config/csp.php) no necesita 'unsafe-eval'.
|     Las expresiones de wire:click de las vistas son llamadas simples a
|     métodos (selectDate('…'), setDuration(30)…), que este modo admite.
| EN: Only what differs from vendor/livewire/livewire/config/livewire.php.
|     csp_safe: Livewire and Alpine load their build without eval() or
|     new Function(), so the CSP (config/csp.php) needs no 'unsafe-eval'.
|     The views' wire:click expressions are simple method calls
|     (selectDate('…'), setDuration(30)…), which this mode supports.
|==============================================================================
*/

return [
    'csp_safe' => true,
];
