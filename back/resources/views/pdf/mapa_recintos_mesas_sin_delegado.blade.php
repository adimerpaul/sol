<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { margin: 0 0 8px; font-size: 18px; }
        .meta { margin-bottom: 14px; color: #4b5563; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #e5e7eb; text-align: left; font-size: 10px; }
        td { font-size: 10px; }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>
<div class="meta">Generado: {{ $generatedAt }} | Usuario: {{ $generatedBy }}</div>

<table>
    <thead>
    <tr>
        <th>Provincia</th>
        <th>Municipio</th>
        <th>Localidad</th>
        <th>Recinto</th>
        <th>Mesa</th>
        <th>Estado</th>
        <th>Jefe(s) de recinto</th>
        <th>Celular(es)</th>
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row['provincia'] ?: '-' }}</td>
            <td>{{ $row['municipio'] ?: '-' }}</td>
            <td>{{ $row['localidad'] ?: '-' }}</td>
            <td>{{ $row['recinto'] }}</td>
            <td>{{ $row['mesa_numero'] }}</td>
            <td>{{ $row['estado'] }}</td>
            <td>{{ $row['jefes'] }}</td>
            <td>{{ $row['celulares'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8">No hay mesas sin delegado para los filtros actuales.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
