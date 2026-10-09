<?php

/*
|--------------------------------------------------------------------------
| Identidad de marca: Conexa Capital Central
|--------------------------------------------------------------------------
|
| Fuente única de los datos de marca para el panel de Filament, el PDF y
| las vistas. Los mismos colores están en tailwind.config.js (clases
| `marca-*`) y como variables CSS en resources/css/app.css: si cambian
| aquí, hay que cambiarlos allí.
|
*/

return [

    'nombre' => 'Conexa Capital Central',

    /*
    | Archivos de public/images. "claro" es el logo con texto blanco, para fondos
    | oscuros (azul o negro); "oscuro" es el logo con texto azul, para fondos claros.
    */
    'logos' => [
        'claro' => 'Sobre fondo azul o negro.png',
        'oscuro' => 'Logo sobre fondo blanco.png',
    ],

    'fuente' => 'Montserrat',

    'colores' => [
        'azul' => '#0E1A34',            // base institucional
        'dorado' => '#D7A743',          // acento
        'gris' => '#8C8C8E',            // texto secundario
        'complementario' => '#4A6FA5',  // apoyo
        'profundo' => '#1F2F4A',        // apoyo
        'gris_claro' => '#B3B3B3',      // apoyo
    ],

    /*
    | Paletas para Filament. Filament solo toma el matiz de un hex suelto, así que
    | se definen los tonos a mano: el azul institucional queda exacto en el 600
    | (el tono de los botones en modo claro) y el dorado oficial en el 500.
    */
    'paletas' => [
        'azul' => [
            50 => '#F2F5FA',
            100 => '#E4EAF3',
            200 => '#C9D5E8',
            300 => '#A6BADA',
            400 => '#7F9BC8',
            500 => '#4A6FA5',
            600 => '#0E1A34',
            700 => '#0B152B',
            800 => '#091122',
            900 => '#070D1A',
            950 => '#040810',
        ],
        'dorado' => [
            50 => '#FCF8EE',
            100 => '#F8EFD6',
            200 => '#F1DEAD',
            300 => '#E8CA80',
            400 => '#DFB65A',
            500 => '#D7A743',
            600 => '#B98A2E',
            700 => '#966D25',
            800 => '#7A5822',
            900 => '#654A20',
            950 => '#3A2810',
        ],
    ],

];
