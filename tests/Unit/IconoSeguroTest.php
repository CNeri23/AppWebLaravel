<?php

namespace Tests\Unit;

use App\Support\IconoSeguro;
use PHPUnit\Framework\TestCase;

class IconoSeguroTest extends TestCase
{
    public function test_permite_iconos_validos_de_font_awesome(): void
    {
        $this->assertSame(
            '<i class="fa-solid fa-users"></i>',
            IconoSeguro::sanitizar('<i class="fa-solid fa-users"></i>')
        );

        $this->assertSame(
            '<i class="fa-regular fa-trash-can"></i>',
            IconoSeguro::sanitizar('<i class="fa-regular fa-trash-can"></i>')
        );

        $this->assertSame(
            '<i class="fa-brands fa-github"></i>',
            IconoSeguro::sanitizar('<i class="fa-brands fa-github"></i>')
        );
    }

    public function test_rechaza_html_y_atributos_no_permitidos(): void
    {
        $entradasMaliciosas = [
            '<script>alert(1)</script>',
            '<i class="fa-solid fa-users" onclick="alert(1)"></i>',
            '<img src=x onerror=alert(1)>',
            '<i class="fa-solid fa-users"></i><script>alert(1)</script>',
            '<svg onload="alert(1)"></svg>',
        ];

        foreach ($entradasMaliciosas as $entrada) {
            $this->assertNull(IconoSeguro::sanitizar($entrada), $entrada);
        }
    }

    public function test_devuelve_null_para_valores_vacios_o_nulos(): void
    {
        $this->assertNull(IconoSeguro::sanitizar(null));
        $this->assertNull(IconoSeguro::sanitizar(''));
        $this->assertNull(IconoSeguro::sanitizar('   '));
    }
}
