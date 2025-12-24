<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compra de Almacén #{{ $compra->id }}</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: DejaVu Sans, sans-serif;
        }
        body {
            font-size: 11px;
            margin: 15px;
            color: #333;
        }
        .header {
            padding: 10px 15px;
            border-radius: 6px;
            background: linear-gradient(90deg, #1e88e5, #42a5f5);
            color: #fff;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 18px;
            font-weight: bold;
        }
        .subtitle {
            font-size: 12px;
            opacity: 0.9;
        }
        .info-box {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-box td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #1565c0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .table th {
            background: #eeeeee;
            color: #333;
            padding: 6px;
            border-bottom: 1px solid #bdbdbd;
            text-align: left;
        }
        .table td {
            padding: 5px 6px;
            border-bottom: 1px solid #eeeeee;
        }
        .table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .right {
            text-align: right;
        }
        .tag {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            color: #fff;
        }
        .tag-activo {
            background-color: #43a047;
        }
        .tag-anulado {
            background-color: #e53935;
        }
        .total-box {
            margin-top: 10px;
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #1b5e20;
        }
        .footer {
            margin-top: 25px;
            font-size: 9px;
            color: #777;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-title">Compra de Almacén #{{ $compra->id }}</div>
    <div class="subtitle">Registro de insumos generales de almacén</div>
</div>

<table class="info-box">
    <tr>
        <td width="50%">
            <div><span class="info-label">Fecha: </span>{{ $compra->fecha }}</div>
            <div>
                <span class="info-label">Proveedor: </span>
                {{ $compra->proveedor ?: '—' }}
            </div>
        </td>
        <td width="50%">
            <div>
                <span class="info-label">Estado: </span>
                @if($compra->estado === 'ANULADO')
                    <span class="tag tag-anulado">ANULADO</span>
                @else
                    <span class="tag tag-activo">ACTIVO</span>
                @endif
            </div>
            <div>
                <span class="info-label">Nota: </span>
                {{ $compra->nota ?: '—' }}
            </div>
        </td>
    </tr>
</table>

<table class="table">
    <thead>
    <tr>
        <th>#</th>
        <th>Insumo almacén</th>
        <th>Unidad</th>
        <th class="right">Cantidad</th>
        <th class="right">Costo (Bs)</th>
        <th class="right">Subtotal (Bs)</th>
    </tr>
    </thead>
    <tbody>
    @foreach($compra->detalles as $i => $det)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $det->almacen?->nombre }}</td>
            <td>{{ $det->almacen?->unidad }}</td>
            <td class="right">{{ number_format($det->cantidad, 2, ',', '.') }}</td>
            <td class="right">{{ number_format($det->costo, 2, ',', '.') }}</td>
            <td class="right">{{ number_format($det->subtotal, 2, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="total-box">
    Total: {{ number_format($compra->total, 2, ',', '.') }} Bs
</div>

<div class="footer">
    Sistema de gestión de almacén · Generado el {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
