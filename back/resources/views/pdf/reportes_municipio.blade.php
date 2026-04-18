<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte por Municipio</title>
    <style>
        @page {
            margin: 12px 16px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #222;
            font-size: 9px;
            line-height: 1.15;
        }

        .title {
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2px;
        }

        .subtitle {
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .meta {
            width: 100%;
            margin-bottom: 6px;
            font-size: 8px;
        }

        .meta td {
            padding: 1px 0;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
        }

        .report th,
        .report td {
            border: 1px solid #777;
            padding: 3px 4px;
            vertical-align: middle;
        }

        .report th {
            background: #dcefd9;
            font-weight: 700;
            text-align: center;
            font-size: 8px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .totals-label {
            font-weight: 700;
            text-transform: uppercase;
        }

        .footer {
            margin-top: 6px;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="title">REPORTE POR MUNICIPIO</div>
    <div class="subtitle">
        {{ mb_strtoupper($provincia['nombre'], 'UTF-8') }} - MUNICIPIO DE {{ mb_strtoupper($municipio['nombre'], 'UTF-8') }}
    </div>

    <table class="meta">
        <tr>
            <td><strong>Departamento:</strong> {{ $departamento['nombre'] }}</td>
            <td class="text-right"><strong>Generado:</strong> {{ $generatedAt }}</td>
        </tr>
        <tr>
            <td><strong>Provincia:</strong> {{ $provincia['nombre'] }}</td>
            <td class="text-right"><strong>Usuario:</strong> {{ $generatedBy }}</td>
        </tr>
    </table>

    <table class="report">
        <thead>
        <tr>
            <th style="width: 44px;">N°</th>
            <th style="width: 95px;">Municipio</th>
            <th style="width: 115px;">Localidad</th>
            <th>Nombre de Recinto</th>
            <th style="width: 58px;"># Mesas</th>
            <th style="width: 92px;">Inscritos o habilitados</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td class="text-center">{{ $row['nro'] }}</td>
                <td>{{ $row['municipio'] }}</td>
                <td>{{ $row['localidad'] }}</td>
                <td>{{ $row['recinto_nombre'] }}</td>
                <td class="text-center">{{ $row['total_mesas'] }}</td>
                <td class="text-right">{{ number_format($row['total_habilitados'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">Sin datos para el municipio seleccionado.</td>
            </tr>
        @endforelse
        <tr>
            <td colspan="3" class="totals-label text-center">Totales</td>
            <td class="totals-label">{{ $totals['recintos'] }} recintos</td>
            <td class="totals-label text-center">{{ $totals['mesas'] }}</td>
            <td class="totals-label text-right">{{ number_format($totals['habilitados'], 0, ',', '.') }}</td>
        </tr>
        </tbody>
    </table>

    <div class="footer">
        Reporte consolidado por recinto para el municipio seleccionado.
    </div>
</body>
</html>
