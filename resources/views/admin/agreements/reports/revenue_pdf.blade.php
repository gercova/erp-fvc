<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ingresos de Convenios</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm 12mm 15mm 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.3;
            color: #333333;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1a365d;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 13pt;
            font-weight: bold;
            color: #1a365d;
            text-transform: uppercase;
        }
        .header-sub {
            font-size: 8pt;
            color: #666;
        }
        .report-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            color: #1a365d;
            margin: 8px 0 3px 0;
            text-transform: uppercase;
        }
        .report-subtitle {
            text-align: center;
            font-size: 9pt;
            color: #555;
            margin-bottom: 12px;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .kpi-card {
            border: 1px solid #cbd5e0;
            background-color: #f8fafc;
            padding: 8px;
            text-align: center;
            width: 25%;
        }
        .kpi-val {
            font-size: 12pt;
            font-weight: bold;
            color: #1a365d;
            margin-top: 4px;
        }
        .kpi-lbl {
            font-size: 7.5pt;
            color: #718096;
            text-transform: uppercase;
            font-weight: bold;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8pt;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e0;
            padding: 4px 6px;
            text-align: left;
        }
        table.data-table th {
            background-color: #edf2f7;
            font-weight: bold;
            color: #1a365d;
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .total-row {
            background-color: #edf2f7;
            font-weight: bold;
            color: #1a365d;
        }
        .badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-success { background-color: #c6f6d5; color: #22543d; }
        .badge-info { background-color: #bee3f8; color: #2a4365; }
        .badge-warning { background-color: #feebc8; color: #744210; }
        .footer {
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            font-size: 7.5pt;
            color: #718096;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="header-title">{{ $business->nombre_comercial ?? 'FUNDACIÓN VALLE DEL COLORADO' }}</div>
                <div class="header-sub">RUC: {{ $business->ruc ?? '20600000000' }} | ERP INSTITUCIONAL FVC</div>
            </td>
            <td style="width: 30%; text-align: right;">
                <div class="header-sub">Fecha Emisión: {{ $generated_at->format('d/m/Y H:i') }}</div>
                <div class="header-sub">Módulo: Seguimiento Financiero de Convenios</div>
            </td>
        </tr>
    </table>

    <div class="report-title">REPORTE INTEGRAL DE INGRESOS POR CONVENIO</div>
    <div class="report-subtitle">Programado vs. Facturado vs. Cobrado vs. Libro Mayor (Track B)</div>

    <table class="kpi-table">
        <tr>
            <td class="kpi-card">
                <div class="kpi-lbl">Ingreso Programado</div>
                <div class="kpi-val">S/ {{ number_format($report['grand_total_scheduled'], 2) }}</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-lbl">Ingreso Facturado</div>
                <div class="kpi-val" style="color: #2b6cb0;">S/ {{ number_format($report['grand_total_invoiced'], 2) }}</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-lbl">Ingreso Cobrado</div>
                <div class="kpi-val" style="color: #276749;">S/ {{ number_format($report['grand_total_collected'], 2) }}</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-lbl">Saldo Pendiente</div>
                <div class="kpi-val" style="color: #c53030;">S/ {{ number_format($report['grand_total_pending'], 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Código</th>
                <th style="width: 20%;">Convenio / Título</th>
                <th style="width: 18%;">Contraparte</th>
                <th style="width: 14%;">Centro Costos / Actividad</th>
                <th style="width: 8%;">Monto Tot.</th>
                <th style="width: 8%;">Programado</th>
                <th style="width: 8%;">Facturado</th>
                <th style="width: 8%;">Cobrado</th>
                <th style="width: 8%;">Saldo</th>
                <th style="width: 6%;">% Cumpl.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td class="font-bold text-center">{{ $row['agreement_code'] }}</td>
                    <td>{{ $row['agreement_title'] }}</td>
                    <td>
                        {{ $row['counterparty'] }}<br>
                        <small style="color: #718096;">Doc: {{ $row['counterparty_doc'] }}</small>
                    </td>
                    <td>{{ $row['productive_activity'] }}</td>
                    <td class="text-right">{{ number_format($row['total_agreement'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['scheduled'], 2) }}</td>
                    <td class="text-right" style="color: #2b6cb0;">{{ number_format($row['invoiced'], 2) }}</td>
                    <td class="text-right" style="color: #276749; font-weight: bold;">{{ number_format($row['collected'], 2) }}</td>
                    <td class="text-right" style="color: #c53030;">{{ number_format($row['pending'], 2) }}</td>
                    <td class="text-center">
                        <span class="badge {{ $row['compliance_pct'] >= 100 ? 'badge-success' : ($row['compliance_pct'] > 0 ? 'badge-info' : 'badge-warning') }}">
                            {{ $row['compliance_pct'] }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 15px; color: #a0aec0;">
                        No se encontraron registros de ingresos para los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right font-bold">TOTALES GENERALES (PEN):</td>
                <td class="text-right">-</td>
                <td class="text-right">S/ {{ number_format($report['grand_total_scheduled'], 2) }}</td>
                <td class="text-right" style="color: #2b6cb0;">S/ {{ number_format($report['grand_total_invoiced'], 2) }}</td>
                <td class="text-right" style="color: #276749;">S/ {{ number_format($report['grand_total_collected'], 2) }}</td>
                <td class="text-right" style="color: #c53030;">S/ {{ number_format($report['grand_total_pending'], 2) }}</td>
                <td class="text-center">-</td>
            </tr>
            @if(isset($report['gl_revenue_credits']))
            <tr style="background-color: #f7fafc; font-size: 7.5pt; color: #4a5568;">
                <td colspan="4" class="text-right"><strong>Conciliación Libro Mayor (Cta 70 - Ventas / Ingresos):</strong></td>
                <td colspan="6">
                    Créditos Asientos Contables Registrados (Track B): <strong>S/ {{ number_format($report['gl_revenue_credits'], 2) }}</strong>
                    <span style="color: #2b6cb0; margin-left: 8px;">(Integración estricta sin duplicidad de asientos)</span>
                </td>
            </tr>
            @endif
        </tfoot>
    </table>

    <table class="footer" style="width: 100%;">
        <tr>
            <td style="width: 50%;">ERP-FVC - Sistema de Gestión de Convenios y Servicios Institucionales</td>
            <td style="width: 50%; text-align: right;">Documento generado automáticamente con fines de control interno.</td>
        </tr>
    </table>
</body>
</html>
