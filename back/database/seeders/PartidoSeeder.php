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
                'icono' => null,
                'tipo' => 'PARTIDO',
                'color' => '#2E7D32',
                'orden' => 1,
            ],
            [
                'sigla' => 'SOMOS',
                'nombre' => 'Somos Pueblo',
                'icono' => null,
                'tipo' => 'PARTIDO',
                'color' => '#C2185B',
                'orden' => 2,
            ],
            [
                'sigla' => 'UN',
                'nombre' => 'Unidad Nacional',
                'icono' => null,
                'tipo' => 'PARTIDO',
                'color' => '#0D47A1',
                'orden' => 3,
            ],
            [
                'sigla' => 'JACHA',
                'nombre' => 'Jach’a Jakasawi',
                'icono' => null,
                'tipo' => 'INDIGENA',
                'color' => '#EF6C00',
                'orden' => 4,
            ],
            [
                'sigla' => 'PP',
                'nombre' => 'Poder Popular',
                'icono' => null,
                'tipo' => 'PARTIDO',
                'color' => '#F9A825',
                'orden' => 5,
            ],
            [
                'sigla' => 'AORA',
                'nombre' => 'Alianza por Oruro',
                'icono' => null,
                'tipo' => 'AGRUPACION',
                'color' => '#2E7D32',
                'orden' => 6,
            ],
            [
                'sigla' => 'NGP',
                'nombre' => 'Nueva Generación Patriótica',
                'icono' => null,
                'tipo' => 'PARTIDO',
                'color' => '#1976D2',
                'orden' => 7,
            ],
            [
                'sigla' => 'PATRIA',
                'nombre' => 'Patria Oruro',
                'icono' => null,
                'tipo' => 'AGRUPACION',
                'color' => '#D84315',
                'orden' => 8,
            ],
            [
                'sigla' => 'LIBRE',
                'nombre' => 'Libre',
                'icono' => null,
                'tipo' => 'PARTIDO',
                'color' => '#BDBDBD',
                'orden' => 9,
            ],
            [
                'sigla' => 'FRI',
                'nombre' => 'Frente Revolucionario de Izquierda',
                'icono' => null,
                'tipo' => 'PARTIDO',
                'color' => '#1A237E',
                'orden' => 10,
            ],
            [
                'sigla' => 'APD-SUMATE',
                'nombre' => 'Acción Para el Desarrollo – Súmate',
                'icono' => null,
                'tipo' => 'PARTIDO',
                'color' => '#4A148C',
                'orden' => 11,
            ],
            [
                'sigla' => 'A-UPP',
                'nombre' => 'Alianza Unidad Popular',
                'icono' => null,
                'tipo' => 'ALIANZA',
                'color' => '#FBC02D',
                'orden' => 12,
            ],
            [
                'sigla' => 'UCS',
                'nombre' => 'Unidad Cívica Solidaridad',
                'icono' => null,
                'tipo' => 'PARTIDO',
                'color' => '#0288D1',
                'orden' => 13,
            ],
            [
                'sigla' => 'PDC',
                'nombre' => 'Partido Demócrata Cristiano',
                'icono' => null,
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
