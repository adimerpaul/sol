<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 15px; margin: 0 0 6px 0; }
        .meta { font-size: 10px; color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 5px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>
<div class="meta">
    Generado: {{ $generatedAt }} | Por: {{ $generatedBy }} | Registros: {{ count($rows) }}
</div>

<table>
    <thead>
    <tr>
        <th style="width: 55px;">Mesa</th>
        <th>Recinto</th>
        <th style="width: 130px;">Municipio</th>
        <th style="width: 130px;">Provincia</th>
        <th style="width: 180px;">Delegado</th>
        <th style="width: 100px;">Usuario</th>
        <th style="width: 90px;">Estado</th>
        <th style="width: 85px;">Capacitacion</th>
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row['mesa_numero'] }}</td>
            <td>{{ $row['recinto_nombre'] ?? '-' }}</td>
            <td>{{ $row['municipio_nombre'] ?? '-' }}</td>
            <td>{{ $row['provincia_nombre'] ?? '-' }}</td>
            <td>{{ $row['delegado_nombre'] ?? 'SIN ASIGNAR' }}</td>
            <td>{{ $row['delegado_username'] ?? '-' }}</td>
            <td>{{ $row['estado'] ?? '-' }}</td>
            <td>{{ !empty($row['asistencia_capacitacion']) ? 'SI' : 'NO' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8">Sin registros</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
