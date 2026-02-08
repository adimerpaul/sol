<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Partido;

class PartidoSeeder extends Seeder
{
    public function run(): void
    {
        $partidos = [
            [
                'id' => 1,
                'sigla' => 'FRI',
                'nombre' => 'Frente Revolucionario de Izquierda',
                'icono' => 'Frente Revolucionario de Izquierda.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#1A237E',
                'orden_municipal' => 1,
                'orden_departamental' => 10,
            ],
            [
                'id' => 2,
                'sigla' => 'LEAL',
                'nombre' => 'LEAL',
                'icono' => 'leal.png',
                'tipo' => 'PARTIDO',
                'color' => '#D32F2F',
                'orden_municipal' => 2,
                'orden_departamental' => 0,
            ],
            [
                'id' => 3,
                'sigla' => 'NGP',
                'nombre' => 'Nueva Generación Patriótica',
                'icono' => 'Nueva Generación Patriótica.png',
                'tipo' => 'PARTIDO',
                'color' => '#1976D2',
                'orden_municipal' => 3,
                'orden_departamental' => 7,
            ],
            [
                'id' => 4,
                'sigla' => 'AHORA',
                'nombre' => 'AHORA',
                'icono' => 'Alianza por Oruro.jpg',
                'tipo' => 'AGRUPACION',
                'color' => '#2E7D32',
                'orden_municipal' => 4,
                'orden_departamental' => 6,
            ],
            [
                    'id' => 5,
                'sigla' => 'UN',
                'nombre' => 'Unidad Nacional',
                'icono' => 'Unidad Nacional.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#0D47A1',
                'orden_municipal' => 5,
                'orden_departamental' => 3,
            ],
            [
                'id' => 6,
                'sigla' => 'A-UPP',
                'nombre' => 'Alianza Unidad Popular',
                'icono' => 'Alianza Unidad Popular.jpg',
                'tipo' => 'ALIANZA',
                'color' => '#FBC02D',
                'orden_municipal' => 6,
                'orden_departamental' => 13,
            ],
            [
                    'id' => 7,
                'sigla' => 'UCS',
                'nombre' => 'Unidad Cívica Solidaridad',
                'icono' => 'Unidad_civica_solidaridad_ucs.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#0288D1',
                'orden_municipal' => 7,
                'orden_departamental' => 14,
            ],
            [
                    'id' => 8,
                'sigla' => 'MCSFA',
                'nombre' => 'MCSFA',
                'icono' => 'MCSFA.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#00695C',
                'orden_municipal' => 8,
                'orden_departamental' => 0,
            ],
            [
                    'id' => 9,
                'sigla' => 'APB-SUMATE',
                'nombre' => 'Acción Para el Desarrollo – Súmate',
                'icono' => 'Acción Para el Desarrollo – Súmate.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#4A148C',
                'orden_municipal' => 9,
                'orden_departamental' => 11,
            ],
            [
                'id' => 10,
                'sigla' => 'MTS',
                'nombre' => 'Movimiento Tercer Sistema',
                'icono' => 'MTS_logo.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#2E7D32',
                'orden_municipal' => 10,
                'orden_departamental' => 1,
            ],
            [
                'id' => 11,
                'sigla' => 'PATRIA-ORURO',
                'nombre' => 'Patria Oruro',
                'icono' => 'Patria Oruro.jpg',
                'tipo' => 'AGRUPACION',
                'color' => '#D84315',
                'orden_municipal' => 11,
                'orden_departamental' => 8,
            ],
            [
                'id' => 12,
                'sigla' => 'LIBRE',
                'nombre' => 'Libre',
                'icono' => 'Libre.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#BDBDBD',
                'orden_municipal' => 12,
                'orden_departamental' => 9,
            ],
            [
                'id' => 13,
                'sigla' => 'P.P.',
                'nombre' => 'Poder Popular',
                'icono' => 'Poder Popular.png',
                'tipo' => 'PARTIDO',
                'color' => '#F9A825',
                'orden_municipal' => 13,
                'orden_departamental' => 5,
            ],
            [
                'id' => 14,
                'sigla' => 'SOMOS',
                'nombre' => 'Somos Pueblo',
                'icono' => 'Somos Pueblo.png',
                'tipo' => 'PARTIDO',
                'color' => '#C2185B',
                'orden_municipal' => 14,
                'orden_departamental' => 2,
            ],
            [
                'id' => 15,
                'sigla' => "JACH'A MARKA SOL. FS ORG",
                'nombre' => "JACH'A MARKA SOL. FS ORG",
                'icono' => 'jacha.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#EF6C00',
                'orden_municipal' => 15,
                'orden_departamental' => 4,
            ],
            [
                'id' => 16,
                'sigla' => 'PDC',
                'nombre' => 'Partido Demócrata Cristiano',
                'icono' => 'Partido Demócrata Cristiano.webp',
                'tipo' => 'PARTIDO',
                'color' => '#2E7D32',
                'orden_municipal' => 16,
                'orden_departamental' => 15,
            ],
//            [
//                'id' => 17,
//                'sigla' => 'ALIANZA',
//                'nombre' => 'ALIANZA',
//                'icono' => null,
//                'tipo' => 'ALIANZA',
//                'color' => null,
//                'orden_municipal' => 0,
//                'orden_departamental' => 12,
//            ],
        ];

        foreach ($partidos as $p) {
            Partido::updateOrCreate(
                ['sigla' => $p['sigla']],
                $p
            );
        }
    }
}
