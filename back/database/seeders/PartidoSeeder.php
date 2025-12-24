<?php
// database/seeders/PartidoSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Partido;

class PartidoSeeder extends Seeder
{
    public function run(): void
    {
        $partidos = [
            ['sigla'=>'BST','nombre'=>'Bolivia Somos Todos','tipo'=>'PARTIDO'],
            ['sigla'=>'C-A','nombre'=>'Ciudadanía Activa','tipo'=>'AGRUPACION'],
            ['sigla'=>'FPV','nombre'=>'Frente Para la Victoria','tipo'=>'PARTIDO'],
            ['sigla'=>'INCA-FS','nombre'=>'INCA Frente Social','tipo'=>'PARTIDO'],
            ['sigla'=>'L.E.A.L','nombre'=>'LEAL','tipo'=>'AGRUPACION'],
            ['sigla'=>'MAS-IPSP','nombre'=>'Movimiento al Socialismo','tipo'=>'PARTIDO','alcalde'=>'Adhemar Wilcarani Morales'],
            ['sigla'=>'MRP','nombre'=>'Movimiento de Renovación Popular','tipo'=>'PARTIDO'],
            ['sigla'=>'MTS','nombre'=>'Movimiento Tercer Sistema','tipo'=>'PARTIDO'],
            ['sigla'=>'PAN-BOL','nombre'=>'PAN-BOL','tipo'=>'PARTIDO'],
            ['sigla'=>'PDC','nombre'=>'Partido Demócrata Cristiano','tipo'=>'PARTIDO'],
            ['sigla'=>'PP','nombre'=>'Poder Popular','tipo'=>'PARTIDO'],
            ['sigla'=>'UCS','nombre'=>'Unidad Cívica Solidaridad','tipo'=>'PARTIDO'],
            ['sigla'=>'UNICO','nombre'=>'Único','tipo'=>'AGRUPACION'],
            ['sigla'=>'UN SOL PARA ORURO','nombre'=>'Un Sol Para Oruro','tipo'=>'AGRUPACION'],
        ];

        foreach ($partidos as $p) {
            Partido::firstOrCreate(['sigla' => $p['sigla']], $p);
        }
    }
}
