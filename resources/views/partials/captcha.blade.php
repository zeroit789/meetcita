{{--
  ES: Widget del captcha propio ALTCHA. wire:ignore para que Livewire no lo
      repinte; resources/js/altcha.js copia la solución al componente. El reto
      sale de GET /altcha/challenge (este mismo servidor).
  EN: Own ALTCHA captcha widget. wire:ignore so Livewire doesn't re-render it;
      resources/js/altcha.js copies the solution into the component. The
      challenge comes from GET /altcha/challenge (this very server).
--}}
<div wire:ignore>
  <altcha-widget challenge="{{ route('altcha.challenge') }}" auto="onfocus" configuration='{"hideLogo":true}'
                 language="{{ app()->getLocale() === 'es' ? 'es-es' : 'en' }}"></altcha-widget>
</div>
