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
        .ok { background: #dcfce7; }
        .warn { background: #fee2e2; }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>
<div class="meta">Generado: {{ $generatedAt }} | Por: {{ $generatedBy }} | Registros: {{ count($rows) }}</div>

<table>
    <thead>
    <tr>
        <th>Recinto</th>
        <th>Mesa</th>
        <th>Jefe de recinto</th>
        <th>Celular jefe</th>
        <th>Delegado de mesa</th>
        <th>Celular delegado</th>
        <th>Hora apertura</th>
        <th>Registrado por</th>
        <th>Estado</th>
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr class="{{ !empty($row['completo']) ? 'ok' : 'warn' }}">
            <td>{{ $row['recinto_nombre'] ?? '-' }}</td>
            <td>{{ $row['mesa_numero'] ?? '-' }}</td>
            <td>{{ $row['jefe_nombre'] ?? '-' }}</td>
            <td>{{ $row['jefe_celular'] ?? '-' }}</td>
            <td>{{ $row['delegado_nombre'] ?? '-' }}</td>
            <td>{{ $row['delegado_celular'] ?? '-' }}</td>
            <td>{{ $row['hora_apertura_mesa'] ?? '-' }}</td>
            <td>{{ $row['registrado_por'] ?? '-' }}</td>
            <td>{{ !empty($row['completo']) ? 'Completo' : 'Incompleto' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="9">Sin registros</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
