<?php

namespace App\Support;

class IconoSeguro
{
    /**
     * Permite únicamente un elemento <i> con clases de Font Awesome.
     * Cualquier HTML adicional o atributo inesperado se descarta.
     */
    public static function sanitizar(?string $icono): ?string
    {
        if ($icono === null || trim($icono) === '') {
            return null;
        }

        $patron = '/\A<i\s+class="\s*(fa-(?:solid|regular|brands)(?:\s+fa-[a-z0-9]+(?:-[a-z0-9]+)*)+)\s*"\s*>\s*<\/i>\z/D';

        if (!preg_match($patron, trim($icono), $coincidencias)) {
            return null;
        }

        return '<i class="' . $coincidencias[1] . '"></i>';
    }
}
