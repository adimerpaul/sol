<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { margin: 0 0 8px; font-size: 18px; }
        .meta { margin-bottom: 14px; color: #4b5563; font-size: 10px; }
        .block { margin-bottom: 12px; }
        .sheet { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .sheet td { border: 1px solid #d1d5db; padding: 5px 6px; vertical-align: top; font-size: 10px; }
        .left-label { width: 22%; color: #374151; background: #f9fafb; }
        .left-value { width: 28%; font-weight: bold; color: #1f2937; }
        .right-value { width: 50%; }
        .subtle { color: #6b7280; font-weight: normal; }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>
<div class="meta">Generado: {{ $generatedAt }} | Usuario: {{ $generatedBy }}</div>

@forelse($groups as $group)
    <div class="block">
        <table class="sheet">
            <tbody>
            <tr>
                <td class="left-label">Jefe de recinto</td>
                <td class="left-value">
                    {{ $group['jefe_nombre'] }}
                    @if($group['super_jefe'])
                        <span class="subtle">| Super jefe</span>
                    @endif
                </td>
                <td class="right-value">
                    @php $firstLines = true; @endphp
                    @foreach($group['recintos'] as $recinto)
                        @foreach($recinto['mesas'] as $mesa)
                            @if(!$firstLines)<br>@endif
                            {{ $recinto['recinto'] }} | Mesa {{ $mesa['mesa_numero'] }} | {{ $mesa['delegado_nombre'] }}{{ $mesa['delegado_celular'] ? ' | ' . $mesa['delegado_celular'] : '' }}
                            @php $firstLines = false; @endphp
                        @endforeach
                    @endforeach
                    @if($firstLines)
                        Sin mesas registradas
                    @endif
                </td>
            </tr>
            <tr>
                <td class="left-label">Usuario</td>
                <td class="left-value">{{ $group['jefe_username'] ?: '-' }}</td>
                <td class="right-value subtle">Recinto | Mesa | Delegado | Celular</td>
            </tr>
            <tr>
                <td class="left-label">Celular</td>
                <td class="left-value">{{ $group['jefe_celular'] }}</td>
                <td class="right-value"></td>
            </tr>
            </tbody>
        </table>
    </div>
@empty
    <p>No hay jefes asignados para los filtros actuales.</p>
@endforelse
</body>
</html>
