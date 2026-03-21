<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller
{
    private int $MUNICIPIO_ID = 191;

    // ─── 1. Delegados de mesa asignados ────────────────────────────────────────
    public function delegadosAsignados()
    {
        $rows = DB::select("
            SELECT
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                r.nombre AS recinto,
                m.numero_mesa AS numero_mesa,
                u.nombres AS nombres,
                u.apellido_paterno AS apellido_paterno,
                u.apellido_materno AS apellido_materno,
                u.ci AS ci,
                u.fecha_nacimiento AS fecha_nacimiento,
                u.celular AS celular,
                u.bloque AS bloque,
                cb.name AS registrado_por,
                u.created_at AS registrado_en_fecha
            FROM mesas m
            INNER JOIN recintos r ON m.recinto_id = r.id
            INNER JOIN users u ON m.delegado_id = u.id
            LEFT JOIN users cb ON u.created_by = cb.id
            WHERE u.deleted_at IS NULL
              AND r.municipio_id = ?
            ORDER BY r.nombre ASC, u.apellido_paterno ASC
        ", [$this->MUNICIPIO_ID]);

        return response()->json($rows);
    }

    // ─── 2. Jefes de recinto asignados ─────────────────────────────────────────
    public function jefesAsignados()
    {
        $rows = DB::select("
            SELECT
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                r.nombre AS recinto,
                u.nombres AS nombres,
                u.apellido_paterno AS apellido_paterno,
                u.apellido_materno AS apellido_materno,
                u.ci AS ci,
                u.fecha_nacimiento AS fecha_nacimiento,
                u.celular AS celular,
                u.bloque AS bloque,
                cb.name AS registrado_por,
                u.created_at AS registrado_en_fecha,
                CASE
                    WHEN rj.super_jefe = 1 THEN 'SUPER JEFE'
                    ELSE 'JEFE'
                END AS tipo_jefe
            FROM recinto_jefe rj
            INNER JOIN recintos r ON rj.recinto_id = r.id
            INNER JOIN users u ON rj.jefe_id = u.id
            LEFT JOIN users cb ON u.created_by = cb.id
            WHERE u.deleted_at IS NULL
              AND r.municipio_id = ?
            ORDER BY r.nombre ASC, u.apellido_paterno ASC
        ", [$this->MUNICIPIO_ID]);

        return response()->json($rows);
    }

    // ─── 3. Delegados de mesa libres ────────────────────────────────────────────
    public function delegadosLibres()
    {
        $rows = DB::select("
            SELECT
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                r.nombre AS recinto,
                u.nombres AS nombres,
                u.apellido_paterno AS apellido_paterno,
                u.apellido_materno AS apellido_materno,
                u.ci AS ci,
                u.fecha_nacimiento AS fecha_nacimiento,
                u.celular AS celular,
                u.bloque AS bloque,
                cb.name AS registrado_por,
                u.created_at AS registrado_en_fecha,
                'DELEGADO LIBRE' AS estado
            FROM users u
            LEFT JOIN recintos r ON u.recinto_id = r.id
            LEFT JOIN users cb ON u.created_by = cb.id
            LEFT JOIN mesas m ON u.id = m.delegado_id AND m.deleted_at IS NULL
            WHERE u.role = 'Delegado de mesa'
              AND u.deleted_at IS NULL
              AND m.id IS NULL
              AND r.municipio_id = ?
            ORDER BY r.nombre ASC, u.apellido_paterno ASC
        ", [$this->MUNICIPIO_ID]);

        return response()->json($rows);
    }

    // ─── 4. Jefes de recinto libres ─────────────────────────────────────────────
    public function jefesLibres()
    {
        $rows = DB::select("
            SELECT
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                r.nombre AS recinto,
                u.nombres AS nombres,
                u.apellido_paterno AS apellido_paterno,
                u.apellido_materno AS apellido_materno,
                u.ci AS ci,
                u.fecha_nacimiento AS fecha_nacimiento,
                u.celular AS celular,
                u.bloque AS bloque,
                cb.name AS registrado_por,
                u.created_at AS registrado_en_fecha,
                'JEFE LIBRE' AS estado
            FROM users u
            LEFT JOIN recintos r ON u.recinto_id = r.id
            LEFT JOIN users cb ON u.created_by = cb.id
            LEFT JOIN recinto_jefe rj ON u.id = rj.jefe_id AND rj.deleted_at IS NULL
            WHERE u.role = 'Jefe de recinto'
              AND u.deleted_at IS NULL
              AND rj.id IS NULL
              AND r.municipio_id = ?
            ORDER BY r.nombre ASC, u.apellido_paterno ASC
        ", [$this->MUNICIPIO_ID]);

        return response()->json($rows);
    }

    // ─── 5. Recintos sin jefe asignado ──────────────────────────────────────────
    public function recintosSinJefe()
    {
        $rows = DB::select("
            SELECT
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                r.id AS id_recinto,
                r.nombre AS recinto
            FROM recintos r
            LEFT JOIN recinto_jefe rj ON r.id = rj.recinto_id AND rj.deleted_at IS NULL
            WHERE rj.id IS NULL
              AND r.municipio_id = ?
            ORDER BY r.nombre ASC
        ", [$this->MUNICIPIO_ID]);

        return response()->json($rows);
    }

    public function mesasLibres()
    {
        $rows = DB::select("
            SELECT
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                CONCAT(r.id, '-', m.numero_mesa) AS mesa_key,
                r.nombre AS recinto,
                m.numero_mesa AS numero_mesa,
                'MESA LIBRE' AS estado
            FROM mesas m
            INNER JOIN recintos r ON m.recinto_id = r.id
            LEFT JOIN users u ON m.delegado_id = u.id
            WHERE m.delegado_id IS NULL
              AND r.municipio_id = ?
            ORDER BY r.nombre ASC, m.numero_mesa ASC
        ", [$this->MUNICIPIO_ID]);

        return response()->json($rows);
    }

    // ─── Exports ──────────────────────────────────────────────────────────────
    public function exportDelegadosAsignados()
    {
        $rows = json_decode(json_encode($this->delegadosAsignados()->getData()), true);
        return $this->streamCsv($rows, 'delegados_asignados.csv', [
            'Nro Recinto','Recinto','Número de Mesa','Nombres','Apellido Paterno','Apellido Materno',
            'CI','Fecha Nacimiento','Celular','Bloque','Registrado Por','Registrado En Fecha'
        ], [
            'nro_recinto','recinto','numero_mesa','nombres','apellido_paterno','apellido_materno',
            'ci','fecha_nacimiento','celular','bloque','registrado_por','registrado_en_fecha'
        ]);
    }

    public function exportJefesAsignados()
    {
        $rows = json_decode(json_encode($this->jefesAsignados()->getData()), true);
        return $this->streamCsv($rows, 'jefes_asignados.csv', [
            'Nro Recinto','Recinto','Nombres','Apellido Paterno','Apellido Materno',
            'CI','Fecha Nacimiento','Celular','Bloque','Registrado Por','Registrado En Fecha','Tipo Jefe'
        ], [
            'nro_recinto','recinto','nombres','apellido_paterno','apellido_materno',
            'ci','fecha_nacimiento','celular','bloque','registrado_por','registrado_en_fecha','tipo_jefe'
        ]);
    }

    public function exportDelegadosLibres()
    {
        $rows = json_decode(json_encode($this->delegadosLibres()->getData()), true);
        return $this->streamCsv($rows, 'delegados_libres.csv', [
            'Nro Recinto','Recinto','Nombres','Apellido Paterno','Apellido Materno',
            'CI','Fecha Nacimiento','Celular','Bloque','Registrado Por','Registrado En Fecha','Estado'
        ], [
            'nro_recinto','recinto','nombres','apellido_paterno','apellido_materno',
            'ci','fecha_nacimiento','celular','bloque','registrado_por','registrado_en_fecha','estado'
        ]);
    }

    public function exportJefesLibres()
    {
        $rows = json_decode(json_encode($this->jefesLibres()->getData()), true);
        return $this->streamCsv($rows, 'jefes_libres.csv', [
            'Nro Recinto','Recinto','Nombres','Apellido Paterno','Apellido Materno',
            'CI','Fecha Nacimiento','Celular','Bloque','Registrado Por','Registrado En Fecha','Estado'
        ], [
            'nro_recinto','recinto','nombres','apellido_paterno','apellido_materno',
            'ci','fecha_nacimiento','celular','bloque','registrado_por','registrado_en_fecha','estado'
        ]);
    }

    public function exportRecintosSinJefe()
    {
        $rows = json_decode(json_encode($this->recintosSinJefe()->getData()), true);
        return $this->streamCsv($rows, 'recintos_sin_jefe.csv', [
            'Nro Recinto','ID Recinto','Recinto'
        ], [
            'nro_recinto','id_recinto','recinto'
        ]);
    }

    // ─── Helper ───────────────────────────────────────────────────────────────
    public function exportMesasLibres()
    {
        $rows = json_decode(json_encode($this->mesasLibres()->getData()), true);
        return $this->streamCsv($rows, 'mesas_libres.csv', [
            'Nro Recinto','Recinto','Número de Mesa','Estado'
        ], [
            'nro_recinto','recinto','numero_mesa','estado'
        ]);
    }

    private function streamCsv(array $rows, string $filename, array $headers, array $keys)
    {
        $callback = function () use ($rows, $headers, $keys) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // BOM UTF-8
            fputcsv($handle, $headers, ';');
            foreach ($rows as $row) {
                $line = [];
                foreach ($keys as $k) {
                    $line[] = $row[$k] ?? '';
                }
                fputcsv($handle, $line, ';');
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
