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
            border-bottom: 2px solid #0891b2;
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
            color: #0e7490;
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
        .account-section {
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .account-header {
            background-color: #e0f2fe;
            border: 1px solid #7dd3fc;
            padding: 5px 8px;
            font-size: 9.5px;
            font-weight: bold;
            color: #0369a1;
            margin-top: 10px;
        }
        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.ledger-table th, table.ledger-table td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
            vertical-align: top;
        }
        table.ledger-table th {
            background-color: #f8fafc;
            font-size: 8px;
            font-weight: bold;
            text-align: left;
            color: #334155;
        }
        table.ledger-table th.text-end, table.ledger-table td.text-end {
            text-align: right;
        }
        table.ledger-table th.text-center, table.ledger-table td.text-center {
            text-align: center;
        }
        .initial-row td {
            background-color: #f0fdf4;
            font-weight: bold;
            color: #166534;
        }
        .account-total td {
            background-color: #f1f5f9;
            font-weight: bold;
            border-top: 2px solid #94a3b8;
        }
        .text-debit { color: #065f46; }
        .text-credit { color: #991b1b; }
        .grand-summary {
            margin-top: 15px;
            border: 2px solid #0891b2;
            background: #f0fdfa;
            padding: 8px 12px;
        }
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
                        @if (!empty($filters['date_from'])) | Desde: {{ $filters['date_from'] }} @endif
                        @if (!empty($filters['date_to'])) | Hasta: {{ $filters['date_to'] }} @endif
                    </p>
                </td>
                <td class="meta-info">
                    <div>Generado el: {{ $generatedAt->format('d/m/Y H:i:s') }}</div>
                    <div>Cuentas con Movimiento: {{ count($accounts) }}</div>
                </td>
            </tr>
        </table>
    </div>

    @forelse ($accounts as $code => $acc)
        <div class="account-section">
            <div class="account-header">
                CUENTA: {{ $acc['account_code'] }} - {{ $acc['account_name'] }}
                <span style="font-size: 8px; font-weight: normal; margin-left: 10px;">(Naturaleza: {{ $acc['nature'] }})</span>
            </div>
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">Fecha</th>
                        <th style="width: 12%;">N° Asiento</th>
                        <th style="width: 32%;">Glosa / Concepto de la Operación</th>
                        <th style="width: 14%;">Tercero / Centro Costo</th>
                        <th style="width: 10%;">Doc. Ref.</th>
                        <th style="width: 8%;" class="text-end">Debe (S/.)</th>
                        <th style="width: 8%;" class="text-end">Haber (S/.)</th>
                        <th style="width: 8%;" class="text-end">Saldo (S/.)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="initial-row">
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td colspan="3"><strong>SALDO INICIAL ACUMULADO</strong></td>
                        <td class="text-end text-debit">{{ $acc['opening_debit'] > 0 ? number_format($acc['opening_debit'], 2) : '-' }}</td>
                        <td class="text-end text-credit">{{ $acc['opening_credit'] > 0 ? number_format($acc['opening_credit'], 2) : '-' }}</td>
                        <td class="text-end"><strong>{{ number_format($acc['opening_balance'], 2) }}</strong></td>
                    </tr>
                    @foreach ($acc['transactions'] as $tx)
                        <tr>
                            <td class="text-center">{{ $tx['entry_date'] }}</td>
                            <td>{{ $tx['entry_number'] }}</td>
                            <td>{{ $tx['concept'] }}</td>
                            <td>{{ $tx['third_party'] !== '-' ? $tx['third_party'] : ($tx['cost_center'] !== '-' ? $tx['cost_center'] : '-') }}</td>
                            <td>{{ $tx['document_reference'] !== '-' ? $tx['document_reference'] : '' }}</td>
                            <td class="text-end text-debit">{{ $tx['debit'] > 0 ? number_format($tx['debit'], 2) : '-' }}</td>
                            <td class="text-end text-credit">{{ $tx['credit'] > 0 ? number_format($tx['credit'], 2) : '-' }}</td>
                            <td class="text-end font-weight-bold">{{ number_format($tx['running_balance'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="account-total">
                        <td colspan="5" class="text-end"><strong>TOTALES PERÍODO Y SALDO FINAL:</strong></td>
                        <td class="text-end text-debit"><strong>{{ number_format($acc['period_debit'], 2) }}</strong></td>
                        <td class="text-end text-credit"><strong>{{ number_format($acc['period_credit'], 2) }}</strong></td>
                        <td class="text-end" style="font-size: 9px; background: #e2e8f0;"><strong>{{ number_format($acc['final_balance'], 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <div style="text-align: center; padding: 20px; color: #64748b;">
            No se encontraron movimientos contables en el Libro Mayor para los filtros seleccionados.
        </div>
    @endforelse

    @if (count($accounts) > 1)
        <table class="ledger-table grand-summary" style="margin-top: 15px;">
            <thead>
                <tr style="background: #0891b2; color: white;">
                    <th colspan="4" style="color: white; font-size: 9px;">RESUMEN GENERAL CONSOLIDADO DEL LIBRO MAYOR</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Saldo Inicial Total:</strong> S/. {{ number_format($summary['opening_balance'] ?? 0, 2) }}</td>
                    <td><strong>Total Debe Período:</strong> S/. {{ number_format($summary['period_debit'] ?? 0, 2) }}</td>
                    <td><strong>Total Haber Período:</strong> S/. {{ number_format($summary['period_credit'] ?? 0, 2) }}</td>
                    <td><strong>Saldo Final Total:</strong> S/. {{ number_format($summary['final_balance'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
