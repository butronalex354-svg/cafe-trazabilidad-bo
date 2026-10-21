<x-mail::message>
# {{ $titulo }}

{{ $mensaje }}

Ingresa al sistema para ver el detalle completo y, si corresponde, marcarla como resuelta.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
