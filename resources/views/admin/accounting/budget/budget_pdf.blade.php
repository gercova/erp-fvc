<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ejecución Presupuestal - {{ $budget['budget_code'] }}</title>
    <style>
        @page {
            margin: 15mm 12mm 15mm 12mm;
            size: A4 landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #2c3e50;
            line-height: 1.25;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1a365d;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .institution-name {
            font-size: 11pt;
            font-weight: bold;
            color: #1a365d;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            color: #2b6cb0;
            margin-top: 3px;
        }
        .meta-info {
            font-size: 8pt;
            color: #718096;
            margin-top: 3px;
        }
        .summary-box {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 6px 8px;
            border: 1px solid #cbd5e0;
            background-color: #f7fafc;
            text-align: center;
        }
        .summary-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            color: #718096;
            font-weight: bold;
            display: block;
        }
        .summary-value {
            font-size: 10.5pt;
            font-weight: bold;
            color: #1a202c;
            margin-top: 2px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 16px;
        }
        table.data-table th {
            background-color: #2b6cb0;
            color: #ffffff;
            font-weight: bold;
            padding: 5px 4px;
            border: 1px solid #2b6cb0;
            text-align: center;
        }
        table.data-table td {
            padding: 4px 4px;
            border: 1px solid #e2e8f0;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 6.5pt;
            font-weight: bold;
            border-radius: 3px;
            text-align: center;
        }
        .badge-green { background-color: #c6f6d5; color: #22543d; }
        .badge-yellow { background-color: #fefcbf; color: #744210; }
        .badge-red { background-color: #fed7d7; color: #742a2a; }

        .signature-section {
            margin-top: 25px;
            width: 100%;
            border-collapse: collapse;
        }
        .signature-box {
            text-align: center;
            width: 33.33%;
            padding: 0 15px;
        }
        .signature-line {
            border-top: 1px solid #4a5568;
            margin-top: 40px;
            padding-top: 4px;
            font-size: 8pt;
            font-weight: bold;
        }
        .signature-role {
            font-size: 7pt;
            color: #718096;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="institution-name">{{ $business->nombre ?? 'INSTITUTO DE EDUCACIÓN SUPERIOR TECNOLÓGICO PÚBLICO "FRANCISCO VIGO CABALLERO"' }}</div>
        <div class="report-title">ESTADO DE EJECUCIÓN PRESUPUESTAL - AÑO FISCAL {{ $budget['fiscal_year'] }}</div>
        <div class="meta-info">
            Presupuesto: <strong>{{ $budget['budget_code'] }}</strong> - {{ $budget['budget_name'] }} | Estado: <strong>{{ $budget['status'] }}</strong> | Emisión: {{ $generatedAt->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <!-- Summary Box -->
    <table class="summary-box">
        <tr>
            <td>
                <span class="summary-label">Presupuesto Inicial (PIA)</span>
                <span class="summary-value">S/ {{ number_format($budget['total_allocated'], 2) }}</span>
            </td>
            <td>
                <span class="summary-label">Modificaciones (+/-)</span>
                <span class="summary-value">S/ {{ number_format($budget['total_modified'], 2) }}</span>
            </td>
            <td style="background-color: #ebf8ff;">
                <span class="summary-label" style="color: #2b6cb0;">Presupuesto Vigente (PIM)</span>
                <span class="summary-value" style="color: #2b6cb0;">S/ {{ number_format($budget['total_current'], 2) }}</span>
            </td>
            <td>
                <span class="summary-label">Comprometido</span>
                <span class="summary-value" style="color: #d69e2e;">S/ {{ number_format($budget['total_committed'], 2) }}</span>
            </td>
            <td style="background-color: #fff5f5;">
                <span class="summary-label" style="color: #e53e3e;">Devengado (Contabilidad)</span>
                <span class="summary-value" style="color: #e53e3e;">S/ {{ number_format($budget['total_accrued'], 2) }}</span>
            </td>
            <td>
                <span class="summary-label" style="color: #38a169;">Girado / Pagado</span>
                <span class="summary-value" style="color: #38a169;">S/ {{ number_format($budget['total_paid'], 2) }}</span>
            </td>
            <td>
                <span class="summary-label">Saldo Disponible</span>
                <span class="summary-value">S/ {{ number_format($budget['total_available'], 2) }}</span>
            </td>
            <td style="background-color: #f0fff4;">
                <span class="summary-label">% Ejecución</span>
                <span class="summary-value">{{ $budget['overall_percentage'] }}%</span>
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35px;">Mes</th>
                <th style="width: 55px;">Partida</th>
                <th>Rubro / Clasificador</th>
                <th>Centro de Costo</th>
                <th style="width: 50px;">Fuente</th>
                <th class="text-end" style="width: 65px;">Pres. Inicial</th>
                <th class="text-end" style="width: 55px;">Modif.</th>
                <th class="text-end" style="width: 70px;">PIM</th>
                <th class="text-end" style="width: 65px;">Comprom.</th>
                <th class="text-end" style="width: 65px;">Devengado</th>
                <th class="text-end" style="width: 65px;">Girado</th>
                <th class="text-end" style="width: 65px;">Disponible</th>
                <th class="text-center" style="width: 45px;">% Ejec.</th>
                <th class="text-center" style="width: 50px;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($budget['lines'] as $line)
                @php
                    $light = $line['traffic_light'];
                    $bClass = match($light) {
                        'RED' => 'badge-red',
                        'YELLOW' => 'badge-yellow',
                        default => 'badge-green',
                    };
                @endphp
                <tr>
                    <td class="text-center">M{{ $line['period_month'] }}</td>
                    <td class="fw-bold">{{ $line['account_code'] ?? '-' }}</td>
                    <td>{{ $line['category_name'] ?? '' }}</td>
                    <td>{{ $line['cost_center_code'] ?? 'General' }}</td>
                    <td class="text-center">{{ $line['fund_source_id'] ? 'FTE-'.$line['fund_source_id'] : 'Institucional' }}</td>
                    <td class="text-end">S/ {{ number_format($line['allocated_amount'], 2) }}</td>
                    <td class="text-end">{{ $line['modified_amount'] >= 0 ? '+' : '' }}{{ number_format($line['modified_amount'], 2) }}</td>
                    <td class="text-end fw-bold">S/ {{ number_format($line['current_amount'], 2) }}</td>
                    <td class="text-end">S/ {{ number_format($line['committed_amount'], 2) }}</td>
                    <td class="text-end fw-bold" style="color: #c53030;">S/ {{ number_format($line['accrued_amount'], 2) }}</td>
                    <td class="text-end" style="color: #276749;">S/ {{ number_format($line['paid_amount'], 2) }}</td>
                    <td class="text-end fw-bold">S/ {{ number_format($line['available_amount'], 2) }}</td>
                    <td class="text-center fw-bold">{{ $line['execution_percentage'] }}%</td>
                    <td class="text-center">
                        <span class="badge {{ $bClass }}">{{ $light }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #edf2f7; font-weight: bold;">
                <td colspan="5" class="text-end text-uppercase">TOTAL GENERAL</td>
                <td class="text-end">S/ {{ number_format($budget['total_allocated'], 2) }}</td>
                <td class="text-end">S/ {{ number_format($budget['total_modified'], 2) }}</td>
                <td class="text-end">S/ {{ number_format($budget['total_current'], 2) }}</td>
                <td class="text-end">S/ {{ number_format($budget['total_committed'], 2) }}</td>
                <td class="text-end" style="color: #c53030;">S/ {{ number_format($budget['total_accrued'], 2) }}</td>
                <td class="text-end" style="color: #276749;">S/ {{ number_format($budget['total_paid'], 2) }}</td>
                <td class="text-end">S/ {{ number_format($budget['total_available'], 2) }}</td>
                <td class="text-center">{{ $budget['overall_percentage'] }}%</td>
                <td class="text-center">
                    <span class="badge {{ match($budget['traffic_light']) { 'RED' => 'badge-red', 'YELLOW' => 'badge-yellow', default => 'badge-green' } }}">
                        {{ $budget['traffic_light'] }}
                    </span>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Institutional Signatures -->
    <table class="signature-section">
        <tr>
            <td class="signature-box">
                <div class="signature-line">JEFATURA DE CONTABILIDAD Y PRESUPUESTO</div>
                <div class="signature-role">IESTP "Francisco Vigo Caballero"</div>
            </td>
            <td class="signature-box">
                <div class="signature-line">JEFATURA DE ADMINISTRACIÓN</div>
                <div class="signature-role">Conformidad Institucional</div>
            </td>
            <td class="signature-box">
                <div class="signature-line">DIRECCIÓN GENERAL</div>
                <div class="signature-role">Resolución de Aprobación</div>
            </td>
        </tr>
    </table>

</body>
</html>
