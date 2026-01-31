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
                'sigla' => 'MTS',
                'nombre' => 'Movimiento Tercer Sistema',
                'icono' => 'MTS_logo.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#2E7D32',
                'orden' => 1,
            ],
            [
                'sigla' => 'SOMOS',
                'nombre' => 'Somos Pueblo',
                'icono' => 'Somos Pueblo.png',
                'tipo' => 'PARTIDO',
                'color' => '#C2185B',
                'orden' => 2,
            ],
            [
                'sigla' => 'UN',
                'nombre' => 'Unidad Nacional',
                'icono' => 'Unidad Nacional.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#0D47A1',
                'orden' => 3,
            ],
            [
                'sigla' => 'JACHA',
                'nombre' => 'Jach’a Jakasawi',
                'icono' => 'jacha.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#EF6C00',
                'orden' => 4,
            ],
            [
                'sigla' => 'PP',
                'nombre' => 'Poder Popular',
                'icono' => 'Poder Popular.png',
                'tipo' => 'PARTIDO',
                'color' => '#F9A825',
                'orden' => 5,
            ],
            [
                'sigla' => 'AORA',
                'nombre' => 'Alianza por Oruro',
                'icono' => 'Alianza por Oruro.jpg',
                'tipo' => 'AGRUPACION',
                'color' => '#2E7D32',
                'orden' => 6,
            ],
            [
                'sigla' => 'NGP',
                'nombre' => 'Nueva Generación Patriótica',
                'icono' => 'Nueva Generación Patriótica.png',
                'tipo' => 'PARTIDO',
                'color' => '#1976D2',
                'orden' => 7,
            ],
            [
                'sigla' => 'PATRIA',
                'nombre' => 'Patria Oruro',
                'icono' => 'Patria Oruro.jpg',
                'tipo' => 'AGRUPACION',
                'color' => '#D84315',
                'orden' => 8,
            ],
            [
                'sigla' => 'LIBRE',
                'nombre' => 'Libre',
                'icono' => 'Libre.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#BDBDBD',
                'orden' => 9,
            ],
            [
                'sigla' => 'FRI',
                'nombre' => 'Frente Revolucionario de Izquierda',
                'icono' => 'Frente Revolucionario de Izquierda.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#1A237E',
                'orden' => 10,
            ],
            [
                'sigla' => 'APD-SUMATE',
                'nombre' => 'Acción Para el Desarrollo – Súmate',
                'icono' => 'Acción Para el Desarrollo – Súmate.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#4A148C',
                'orden' => 11,
            ],
            [
                'sigla' => 'A-UPP',
                'nombre' => 'Alianza Unidad Popular',
                'icono' => 'Alianza Unidad Popular.jpg',
                'tipo' => 'ALIANZA',
                'color' => '#FBC02D',
                'orden' => 12,
            ],
            [
                'sigla' => 'UCS',
                'nombre' => 'Unidad Cívica Solidaridad',
                'icono' => 'Unidad_civica_solidaridad_ucs.jpg',
                'tipo' => 'PARTIDO',
                'color' => '#0288D1',
                'orden' => 13,
            ],
            [
                'sigla' => 'PDC',
                'nombre' => 'Partido Demócrata Cristiano',
                'icono' => 'Partido Demócrata Cristiano.webp',
                'tipo' => 'PARTIDO',
                'color' => '#2E7D32',
                'orden' => 14,
            ],
        ];

        foreach ($partidos as $p) {
            Partido::updateOrCreate(
                ['sigla' => $p['sigla']],
                $p
            );
        }
    }
}
