<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11px;
            margin: 18px;
        }
        .header {
            border: 1px solid #dbe3ef;
            background: #f8fbff;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
        }
        .meta {
            font-size: 10px;
            color: #475569;
            margin: 0;
        }
        .chip {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 8px;
            font-size: 10px;
            border-radius: 12px;
            background: #e2e8f0;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        thead th {
            background: #1d4ed8;
            color: #fff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .3px;
            border: 1px solid #1e40af;
            padding: 7px 5px;
        }
        tbody td {
            border: 1px solid #dbe3ef;
            padding: 6px 5px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        tbody tr:nth-child(even) {
            background: #f8fafc;
        }
    </style>
</head>
<body>
<div class="header">
    <p class="title">Reporte de Usuarios - {{ $title }}</p>
    <p class="meta">Generado el: {{ $generatedAt }} | Generado por: {{ $generatedBy }}</p>
    <span class="chip">Total registros: {{ $users->count() }}</span>
</div>

<table>
    <thead>
    <tr>
        <th style="width: 140px;">Codigo de ingreso</th>
        <th style="width: 220px;">Nombres completos</th>
        <th style="width: 90px;">CI</th>
        <th style="width: 86px;">Nacimiento</th>
        <th style="width: 88px;">Celular</th>
        <th style="width: 105px;">Rol</th>
        <th style="width: 84px;">Asistencia</th>
        <th style="width: 120px;">Bloque</th>
        <th style="width: 120px;">Recinto</th>
    </tr>
    </thead>
    <tbody>
    @forelse($users as $u)
        <tr>
            <td>{{ $u['username'] ?: '-' }}</td>
            <td>
                {{ trim(($u['nombres'] ?? '') . ' ' . ($u['apellido_paterno'] ?? '') . ' ' . ($u['apellido_materno'] ?? '')) ?: ($u['name'] ?: '-') }}
            </td>
            <td>{{ $u['ci'] ?: '-' }}</td>
            <td>{{ $u['fecha_nacimiento'] ?: '-' }}</td>
            <td>{{ $u['celular'] ?: '-' }}</td>
            <td>{{ $u['role'] ?: '-' }}</td>
            <td>
                {{ !empty($u['asistencia']) ? 'Si' : 'No' }}
                @if(!empty($u['asistencia']) && !empty($u['asistencia_at']))
                    <br><span style="font-size: 9px; color: #475569;">{{ $u['asistencia_at'] }}</span>
                @endif
            </td>
            <td>{{ $u['bloque'] ?: '-' }}</td>
            <td>{{ $u['recinto_nombre'] ?: '-' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="9" style="text-align:center;">No hay datos para este filtro</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
