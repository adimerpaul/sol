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
        td { border: 1px solid #d1d5db; padding: 5px 6px; vertical-align: top; }
        .left { width: 38%; color: #0f172a; }
        .right { width: 62%; }
        .header { background: #f3f4f6; font-weight: bold; }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>
<div class="meta">
    Generado: {{ $generatedAt }} | Por: {{ $generatedBy }}<br>
    Supervisor: {{ $supervisor['name'] }} | Usuario: {{ $supervisor['username'] ?: '-' }} | Celular: {{ $supervisor['celular'] }}
</div>

<table>
    <tbody>
    @forelse($rows as $row)
        <tr class="header">
            <td class="left">Recinto</td>
            <td class="right">{{ $row['recinto_nombre'] ?: 'Sin recinto' }}</td>
        </tr>
        <tr>
            <td class="left">Jefes asignados</td>
            <td class="right">
                @forelse($row['jefes'] as $jefe)
                    <strong>{{ $jefe['name'] }}</strong>
                    @if(!empty($jefe['username']))
                        | {{ $jefe['username'] }}
                    @endif
                    | {{ $jefe['celular'] }}<br>
                @empty
                    Sin jefes asignados
                @endforelse
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="2">Sin recintos asignados para este supervisor.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
