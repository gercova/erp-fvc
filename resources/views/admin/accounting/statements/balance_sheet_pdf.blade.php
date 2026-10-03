<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Estado de Situación Financiera' }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 12mm 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
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
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 8.5px;
            color: #4b5563;
            margin: 0;
        }
        .meta-info {
            text-align: right;
            font-size: 7.5px;
            color: #6b7280;
        }
        table.statement-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.statement-table th, table.statement-table td {
            border: 1px solid #d1d5db;
            padding: 3px 5px;
            vertical-align: middle;
        }
        table.statement-table th {
            background-color: #f3f4f6;
            font-size: 7.5px;
            font-weight: bold;
            text-align: left;
            color: #1f2937;
        }
        table.statement-table th.text-end, table.statement-table td.text-end {
            text-align: right;
        }
        table.statement-table th.text-center, table.statement-table td.text-center {
            text-align: center;
        }
        .row-header {
            background-color: #e5e7eb;
            font-weight: bold;
            color: #111827;
            font-size: 8px;
        }
        .row-subtotal {
            background-color: #f9fafb;
            font-weight: bold;
            border-top: 1px solid #9ca3af;
        }
        .row-total {
            background-color: #dbeafe;
            font-weight: bold;
            font-size: 8.5px;
            border-top: 2px solid #1e40af;
            border-bottom: 2px double #1e40af;
        }
        .verification-box {
            margin-top: 10px;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #10b981;
            background: #ecfdf5;
            font-size: 8px;
        }
        .verification-box.unbalanced {
            border-color: #ef4444;
            background: #fef2f2;
            color: #991b1b;
        }
        .signatures {
            margin-top: 35px;
            width: 100%;
        }
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .signature-line {
            border-top: 1px solid #6b7280;
            margin-top: 30px;
            padding-top: 4px;
            font-size: 7.5px;
            color: #374151;
            font-weight: bold;
        }
        .signature-title {
            font-size: 7px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width: 65%;">
                    <div class="title">{{ $title }}</div>
                    <div class="subtitle">{{ $business?->nombre_comercial ?? 'ERP-FVC' }} &bull; RUC: {{ $business?->ruc ?? '-' }}</div>
                    <div class="subtitle">
                        Período: <strong>{{ $report['period']['code'] ?? 'AL ' . ($report['period']['to'] ?? date('Y-m-d')) }}</strong>
                        @if($report['has_comparison'])
                            &nbsp;|&nbsp; Comparativo con: <strong>{{ $report['comparison']['period']['code'] ?? $report['comparison']['period']['to'] ?? 'Período Ant.' }}</strong>
                        @endif
                    </div>
                </td>
                <td class="meta-info" style="width: 35%;">
                    <div><strong>Fecha Emisión:</strong> {{ $generatedAt->format('d/m/Y H:i') }}</div>
                    <div><strong>Moneda:</strong> Nuevos Soles (PEN)</div>
                    <div><strong>Normativa:</strong> PCGE / NIIF</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="statement-table">
        <thead>
            <tr>
                <th style="width: 12%;" class="text-center">Código</th>
                <th style="width: {{ $report['has_comparison'] ? '48%' : '68%' }};">Denominación del Rubro</th>
                <th style="width: {{ $report['has_comparison'] ? '13%' : '20%' }};" class="text-end">
                    Actual (PEN)<br><span style="font-size: 6.5px; font-weight: normal;">{{ $report['period']['code'] ?? '' }}</span>
                </th>
                @if($report['has_comparison'])
                    <th style="width: 13%;" class="text-end">
                        Anterior (PEN)<br><span style="font-size: 6.5px; font-weight: normal;">{{ $report['comparison']['period']['code'] ?? '' }}</span>
                    </th>
                    <th style="width: 7%;" class="text-end">Var. S/</th>
                    <th style="width: 7%;" class="text-end">Var. %</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($report['lines'] as $line)
                @php
                    $isHeader = $line['line_type'] === 'HEADER';
                    $isSubtotal = $line['line_type'] === 'SUBTOTAL';
                    $isTotal = $line['line_type'] === 'TOTAL';
                    $rowClass = $isHeader ? 'row-header' : ($isTotal ? 'row-total' : ($isSubtotal ? 'row-subtotal' : ''));
                    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $line['indent_level'] ?? 0);
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="text-center" style="font-size: 7px; color: #4b5563;">
                        {{ $line['line_code'] }}
                    </td>
                    <td style="{{ ($line['is_bold'] ?? false) ? 'font-weight: bold;' : '' }} {{ ($line['is_italic'] ?? false) ? 'font-style: italic;' : '' }}">
                        {!! $indent !!}{{ $line['line_name'] }}
                    </td>
                    <td class="text-end" style="{{ ($line['is_bold'] ?? false) ? 'font-weight: bold;' : '' }}">
                        @if(!$isHeader)
                            {{ number_format($line['current_amount'], 2) }}
                        @endif
                    </td>
                    @if($report['has_comparison'])
                        <td class="text-end" style="{{ $line['is_bold'] ? 'font-weight: bold;' : '' }}">
                            @if(!$isHeader && $line['comparison_amount'] !== null)
                                {{ number_format($line['comparison_amount'], 2) }}
                            @endif
                        </td>
                        <td class="text-end" style="color: {{ ($line['variance_abs'] ?? 0) < 0 ? '#b91c1c' : '#047857' }};">
                            @if(!$isHeader && $line['variance_abs'] !== null)
                                {{ number_format($line['variance_abs'], 2) }}
                            @endif
                        </td>
                        <td class="text-end" style="color: {{ ($line['variance_pct'] ?? 0) < 0 ? '#b91c1c' : '#047857' }};">
                            @if(!$isHeader && $line['variance_pct'] !== null)
                                {{ number_format($line['variance_pct'], 1) }}%
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Accounting Equation Invariant Box -->
    <div class="verification-box {{ ($report['totals']['is_balanced'] ?? true) ? '' : 'unbalanced' }}">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; padding: 0;">
                    <strong>ECUACIÓN CONTABLE FUNDAMENTAL:</strong>
                    ACTIVO (S/ {{ number_format($report['totals']['total_assets'] ?? 0, 2) }}) =
                    PASIVO (S/ {{ number_format($report['totals']['total_liabilities'] ?? 0, 2) }}) +
                    PATRIMONIO (S/ {{ number_format($report['totals']['total_equity'] ?? 0, 2) }})
                    &bull; Total P+PN: S/ {{ number_format($report['totals']['total_liabilities_and_equity'] ?? 0, 2) }}
                </td>
                <td style="border: none; padding: 0; text-align: right;">
                    @if($report['totals']['is_balanced'] ?? true)
                        <strong style="color: #059669;">&check; BALANCE CUADRADO (Diferencia: S/ 0.00)</strong>
                    @else
                        <strong style="color: #dc2626;">&cross; ALERTA: DESCUADRE S/ {{ number_format($report['totals']['imbalance'] ?? 0, 2) }}</strong>
                    @endif
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border: none; padding-top: 3px; font-size: 7.5px; color: #4b5563;">
                    Resultado del Ejercicio incorporado en el Patrimonio: <strong>S/ {{ number_format($report['net_result'] ?? 0, 2) }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <table>
            <tr>
                <td>
                    <div class="signature-line">CONTADOR GENERAL</div>
                    <div class="signature-title">Matrícula CPC / Colegio de Contadores</div>
                </td>
                <td>
                    <div class="signature-line">GERENCIA DE ADMINISTRACIÓN</div>
                    <div class="signature-title">Administración y Finanzas</div>
                </td>
                <td>
                    <div class="signature-line">GERENCIA GENERAL</div>
                    <div class="signature-title">Representante Legal</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
