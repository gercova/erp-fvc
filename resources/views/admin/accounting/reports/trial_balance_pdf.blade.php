<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 12mm 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            color: #111827;
        }
        .header {
            margin-bottom: 12px;
            border-bottom: 2px solid #059669;
            padding-bottom: 6px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .header td {
            border: none;
            padding: 0;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            color: #065f46;
            margin: 0 0 2px 0;
        }
        .subtitle {
            font-size: 9px;
            color: #4b5563;
            margin: 0;
        }
        .meta-info {
            text-align: right;
            font-size: 8px;
            color: #6b7280;
        }
        table.tb-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.tb-table th, table.tb-table td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            vertical-align: top;
        }
        table.tb-table th {
            background-color: #f3f4f6;
            font-size: 8px;
            font-weight: bold;
            text-align: left;
            color: #1f2937;
        }
        table.tb-table th.header-group {
            text-align: center;
            background-color: #e5e7eb;
            font-size: 8.5px;
        }
        table.tb-table th.text-end, table.tb-table td.text-end {
            text-align: right;
        }
        table.tb-table th.text-center, table.tb-table td.text-center {
            text-align: center;
        }
        .text-debit { color: #065f46; }
        .text-credit { color: #991b1b; }
        .totals-row td {
            background-color: #e5e7eb;
            font-weight: bold;
            border-top: 2px solid #111827;
            font-size: 9px;
        }
        .verification-box {
            margin-top: 12px;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #10b981;
            background: #ecfdf5;
            font-size: 8.5px;
        }
        .verification-box.unbalanced {
            border-color: #ef4444;
            background: #fef2f2;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1 class="title">{{ $title }}</h1>
                    <p class="subtitle">{{ $business?->nombre_comercial ?? 'ERP-FVC' }} | RUC: {{ $business?->ruc ?? '-' }}</p>
                    <p class="subtitle">
                        Período: {{ !empty($filters['period_id']) ? 'ID #' . $filters['period_id'] : 'Todos' }}
                        | Nivel: {{ !empty($filters['digits']) ? $filters['digits'] . ' Dígitos' : 'Cuentas de Movimiento' }}
                        @if (!empty($filters['date_from'])) | Desde: {{ $filters['date_from'] }} @endif
                        @if (!empty($filters['date_to'])) | Hasta: {{ $filters['date_to'] }} @endif
                    </p>
                </td>
                <td class="meta-info">
                    <div>Generado el: {{ $generatedAt->format('d/m/Y H:i:s') }}</div>
                    <div>Estado: <span class="badge {{ $verification['is_balanced'] ? 'badge-success' : 'badge-danger' }}">{{ $verification['status'] }}</span></div>
                    <div>Cuentas Listadas: {{ count($accounts) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="tb-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 10%;" class="text-center">Código</th>
                <th rowspan="2" style="width: 42%;">Denominación de la Cuenta Contable</th>
                <th colspan="2" class="header-group" style="width: 24%;">SUMAS DEL MAYOR</th>
                <th colspan="2" class="header-group" style="width: 24%;">SALDOS</th>
            </tr>
            <tr>
                <th class="text-end" style="width: 12%;">Debe (S/.)</th>
                <th class="text-end" style="width: 12%;">Haber (S/.)</th>
                <th class="text-end" style="width: 12%;">Deudor (S/.)</th>
                <th class="text-end" style="width: 12%;">Acreedor (S/.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($accounts as $acc)
                <tr>
                    <td class="text-center font-monospace"><strong>{{ $acc['code'] }}</strong></td>
                    <td>{{ $acc['name'] }}</td>
                    <td class="text-end text-debit">{{ $acc['total_debit'] > 0 ? number_format($acc['total_debit'], 2) : '-' }}</td>
                    <td class="text-end text-credit">{{ $acc['total_credit'] > 0 ? number_format($acc['total_credit'], 2) : '-' }}</td>
                    <td class="text-end text-debit">{{ $acc['saldo_deudor'] > 0 ? number_format($acc['saldo_deudor'], 2) : '-' }}</td>
                    <td class="text-end text-credit">{{ $acc['saldo_acreedor'] > 0 ? number_format($acc['saldo_acreedor'], 2) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-3">No hay movimientos contables registrados para el período seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="2" class="text-end"><strong>TOTALES GENERALES (S/.):</strong></td>
                <td class="text-end text-debit"><strong>{{ number_format($totals['total_debit'] ?? 0, 2) }}</strong></td>
                <td class="text-end text-credit"><strong>{{ number_format($totals['total_credit'] ?? 0, 2) }}</strong></td>
                <td class="text-end text-debit"><strong>{{ number_format($totals['saldo_deudor'] ?? 0, 2) }}</strong></td>
                <td class="text-end text-credit"><strong>{{ number_format($totals['saldo_acreedor'] ?? 0, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="verification-box {{ $verification['is_balanced'] ? '' : 'unbalanced' }}">
        <strong>VERIFICACIÓN DE PARTIDA DOBLE:</strong>
        @if ($verification['is_balanced'])
            El balance se encuentra perfectamente <strong>CUADRADO</strong>. Suma Debe = Suma Haber (S/. {{ number_format($totals['total_debit'] ?? 0, 2) }}), y Saldo Deudor = Saldo Acreedor (S/. {{ number_format($totals['saldo_deudor'] ?? 0, 2) }}).
        @else
            <strong style="color: #991b1b;">ALERTA DE DESCUADRE:</strong>
            Diferencia en Sumas del Mayor: S/. {{ number_format($verification['debit_credit_diff'] ?? 0, 2) }} | Diferencia en Saldos: S/. {{ number_format($verification['balances_diff'] ?? 0, 2) }}.
        @endif
    </div>
</body>
</html>
