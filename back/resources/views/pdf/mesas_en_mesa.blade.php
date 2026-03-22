<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: {{ !empty($isNoEnMesa) ? '8.5px' : '10px' }}; color: #222; }
        h1 { font-size: 15px; margin: 0 0 6px 0; }
        .meta { font-size: 10px; color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: {{ !empty($isNoEnMesa) ? '2px 3px' : '4px 5px' }}; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .compact { white-space: nowrap; }
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
        <th @if(!empty($isNoEnMesa)) style="width: 260px;" @endif>Recinto</th>
        @if(empty($isNoEnMesa))
            <th style="width: 120px;">Municipio</th>
            <th style="width: 120px;">Provincia</th>
        @endif
        <th style="width: {{ !empty($isNoEnMesa) ? '220px' : '170px' }};">Delegado</th>
        <th style="width: 90px;">Usuario</th>
        <th style="width: 90px;">Celular</th>
        @if(empty($isNoEnMesa))
            <th style="width: 120px;">Presencia</th>
        @endif
        <th style="width: 80px;">Estado</th>
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td class="compact">{{ $row['mesa_numero'] }}</td>
            <td>{{ $row['recinto_nombre'] ?? '-' }}</td>
            @if(empty($isNoEnMesa))
                <td>{{ $row['municipio_nombre'] ?? '-' }}</td>
                <td>{{ $row['provincia_nombre'] ?? '-' }}</td>
            @endif
            <td>{{ $row['delegado_nombre'] ?? 'SIN ASIGNAR' }}</td>
            <td class="compact">{{ $row['delegado_username'] ?? '-' }}</td>
            <td class="compact">{{ $row['delegado_celular'] ?? '-' }}</td>
            @if(empty($isNoEnMesa))
                <td class="compact">{{ $row['presencia_at'] ?? 'Registrada' }}</td>
            @endif
            <td class="compact">{{ $row['estado'] ?? '-' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="{{ !empty($isNoEnMesa) ? '6' : '9' }}">Sin registros</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
