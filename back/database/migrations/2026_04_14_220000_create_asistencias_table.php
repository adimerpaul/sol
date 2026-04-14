<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesa_id')->constrained('mesas')->cascadeOnDelete();
            $table->unique('mesa_id');
            $table->foreignId('delegado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('aviso_antes')->default(false);
            $table->timestamp('aviso_antes_at')->nullable();
            $table->foreignId('aviso_antes_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('aviso_manana')->default(false);
            $table->timestamp('aviso_manana_at')->nullable();
            $table->foreignId('aviso_manana_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('aviso_mediodia')->default(false);
            $table->timestamp('aviso_mediodia_at')->nullable();
            $table->foreignId('aviso_mediodia_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('aviso_tarde')->default(false);
            $table->timestamp('aviso_tarde_at')->nullable();
            $table->foreignId('aviso_tarde_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hora_apertura_mesa', 5)->nullable();
            $table->decimal('presente_latitud', 10, 7)->nullable();
            $table->decimal('presente_longitud', 10, 7)->nullable();
            $table->timestamp('presente_at')->nullable();
            $table->timestamps();
        });

        $rows = DB::table('resultados_mesa')
            ->whereNull('deleted_at')
            ->get();

        foreach ($rows as $row) {
            $mesa = DB::table('mesas')->where('id', $row->mesa_id)->first(['delegado_id']);

            DB::table('asistencias')->updateOrInsert(
                ['mesa_id' => $row->mesa_id],
                [
                    'delegado_id' => $mesa->delegado_id ?? null,
                    'aviso_antes' => (bool) ($row->aviso_antes ?? false),
                    'aviso_antes_at' => !empty($row->aviso_antes) ? ($row->updated_at ?? $row->created_at) : null,
                    'aviso_antes_by' => !empty($row->aviso_antes) ? $row->registrado_por : null,
                    'aviso_manana' => (bool) ($row->aviso_manana ?? false),
                    'aviso_manana_at' => !empty($row->aviso_manana) ? ($row->updated_at ?? $row->created_at) : null,
                    'aviso_manana_by' => !empty($row->aviso_manana) ? $row->registrado_por : null,
                    'aviso_mediodia' => (bool) ($row->aviso_mediodia ?? false),
                    'aviso_mediodia_at' => !empty($row->aviso_mediodia) ? ($row->updated_at ?? $row->created_at) : null,
                    'aviso_mediodia_by' => !empty($row->aviso_mediodia) ? $row->registrado_por : null,
                    'aviso_tarde' => (bool) ($row->aviso_tarde ?? false),
                    'aviso_tarde_at' => !empty($row->aviso_tarde) ? ($row->updated_at ?? $row->created_at) : null,
                    'aviso_tarde_by' => !empty($row->aviso_tarde) ? $row->registrado_por : null,
                    'hora_apertura_mesa' => $row->hora_apertura_mesa ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
