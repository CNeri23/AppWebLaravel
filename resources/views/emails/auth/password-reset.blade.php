@component('mail::message')
# Restablecimiento de contraseña

¡Hola!

Recibimos una solicitud para restablecer la contraseña de tu cuenta.

@component('mail::button', ['url' => $url])
Restablecer contraseña
@endcomponent

Este enlace caducará en {{ $minutos }} minutos.

Si no solicitaste restablecer tu contraseña, no necesitas realizar ninguna otra acción.

Si tienes problemas para usar el botón, copia y pega el siguiente enlace en tu navegador:

{{ $url }}

Saludos,  
{{ config('app.name', 'IronPulse') }}
@endcomponent
