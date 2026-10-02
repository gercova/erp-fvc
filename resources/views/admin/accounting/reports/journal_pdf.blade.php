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
            font-size: 9px;
            color: #111827;
        }
        .header {
            margin-bottom: 12px;
            border-bottom: 2px solid #2563eb;
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
            color: #1e3a8a;
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
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #f3f4f6;
            font-size: 8.5px;
            font-weight: bold;
            text-align: left;
            color: #1f2937;
        }
        table.data-table th.text-end, table.data-table td.text-end {
            text-align: right;
        }
        table.data-table th.text-center, table.data-table td.text-center {
            text-align: center;
        }
        tr.entry-header {
            background-color: #f8fafc;
            font-weight: bold;
        }
        .text-debit {
            color: #065f46;
        }
        .text-credit {
            color: #991b1b;
        }
        .totals-row td {
            background-color: #e5e7eb;
            font-weight: bold;
            border-top: 2px solid #374151;
            font-size: 9px;
        }
        .badge {
            display: inline-block;
            padding: 2px 4px;
            font-size: 7.5px;
            border-radius: 3px;
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
                        Período: {{ $filters['period_id'] ? 'ID #' . $filters['period_id'] : 'Todos' }}
                        @if (!empty($filters['date_from'])) | Desde: {{ $filters['date_from'] }} @endif
                        @if (!empty($filters['date_to'])) | Hasta: {{ $filters['date_to'] }} @endif
                        @if (!empty($filters['voucher_type'])) | Tipo: {{ $filters['voucher_type'] }} @endif
                    </p>
                </td>
                <td class="meta-info">
                    <div>Generado el: {{ $generatedAt->format('d/m/Y H:i:s') }}</div>
                    <div>Estado: <span class="badge {{ $isBalanced ? 'badge-success' : 'badge-danger' }}">{{ $isBalanced ? 'CUADRADO' : 'DESCUADRADO' }}</span></div>
                    <div>Total Asientos: {{ count($entries) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">N°</th>
                <th style="width: 8%;">Fecha</th>
                <th style="width: 12%;">N° Asiento</th>
                <th style="width: 8%;">Cuenta</th>
                <th style="width: 22%;">Denominación de la Cuenta</th>
                <th style="width: 26%;">Glosa / Concepto de la Operación</th>
                <th style="width: 10%;" class="text-end">Debe (S/.)</th>
                <th style="width: 10%;" class="text-end">Haber (S/.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr class="entry-header">
                    <td class="text-center">{{ $entry['seq_number'] }}</td>
                    <td class="text-center">{{ $entry['entry_date'] }}</td>
                    <td colspan="4">
                        <strong>{{ $entry['entry_number'] }}</strong> - {{ $entry['concept'] }}
                        @if($entry['period_code'] !== '-') <small>({{ $entry['period_code'] }})</small> @endif
                    </td>
                    <td class="text-end"><strong>{{ number_format($entry['total_debit'], 2) }}</strong></td>
                    <td class="text-end"><strong>{{ number_format($entry['total_credit'], 2) }}</strong></td>
                </tr>
                @foreach ($entry['lines'] as $line)
                    <tr>
                        <td></td>
                        <td></td>
                        <td style="font-size: 8px; color: #4b5563;">{{ $line['document_reference'] !== '-' ? $line['document_reference'] : '' }}</td>
                        <td><strong>{{ $line['account_code'] }}</strong></td>
                        <td>{{ $line['account_name'] }}</td>
                        <td>{{ $line['glosa'] }}</td>
                        <td class="text-end text-debit">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '-' }}</td>
                        <td class="text-end text-credit">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '-' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px;">No se encontraron asientos contables registrados para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="6" class="text-end"><strong>TOTALES GENERALES (S/.):</strong></td>
                <td class="text-end text-debit"><strong>{{ number_format($grandDebit, 2) }}</strong></td>
                <td class="text-end text-credit"><strong>{{ number_format($grandCredit, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
