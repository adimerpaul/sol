<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 6px 0; }
        .meta { font-size: 10px; color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; vertical-align: top; }
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
        <th style="width: 70px;">Mesa</th>
        <th>Recinto</th>
        <th>Delegado</th>
        <th style="width: 120px;">Usuario</th>
        <th style="width: 110px;">Celular</th>
        <th style="width: 110px;">Estado</th>
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row['mesa_numero'] }}</td>
            <td>{{ $row['recinto_nombre'] ?? '-' }}</td>
            <td>{{ $row['delegado_nombre'] ?? '-' }}</td>
            <td>{{ $row['delegado_username'] ?? '-' }}</td>
            <td>{{ $row['delegado_celular'] ?? '-' }}</td>
            <td>{{ $row['estado'] ?? '-' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6">Sin registros</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
