<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 15px; margin: 0 0 6px 0; }
        .meta { font-size: 10px; color: #555; margin-bottom: 10px; }
        .section { margin-bottom: 10px; }
        .section-title { font-weight: bold; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 5px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>
<div class="meta">
    Generado: {{ $generatedAt }} | Por: {{ $generatedBy }}<br>
    Recinto: {{ $payload['recinto']['nombre'] }}
</div>

<div class="section">
    <div class="section-title">Supervisores</div>
    <table>
        <thead><tr><th>Nombre</th><th>Usuario</th><th>Celular</th></tr></thead>
        <tbody>
        @forelse($payload['supervisores'] as $item)
            <tr><td>{{ $item['name'] }}</td><td>{{ $item['username'] ?: '-' }}</td><td>{{ $item['celular'] }}</td></tr>
        @empty
            <tr><td colspan="3">Sin supervisores</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">Jefes de recinto</div>
    <table>
        <thead><tr><th>Nombre</th><th>Usuario</th><th>Celular</th></tr></thead>
        <tbody>
        @forelse($payload['jefes'] as $item)
            <tr><td>{{ $item['name'] }}</td><td>{{ $item['username'] ?: '-' }}</td><td>{{ $item['celular'] }}</td></tr>
        @empty
            <tr><td colspan="3">Sin jefes</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">Delegados de mesa</div>
    <table>
        <thead><tr><th>Mesa</th><th>Nombre</th><th>Usuario</th><th>Celular</th></tr></thead>
        <tbody>
        @forelse($payload['delegados'] as $item)
            <tr><td>{{ $item['mesa_numero'] }}</td><td>{{ $item['name'] }}</td><td>{{ $item['username'] ?: '-' }}</td><td>{{ $item['celular'] }}</td></tr>
        @empty
            <tr><td colspan="4">Sin delegados</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
