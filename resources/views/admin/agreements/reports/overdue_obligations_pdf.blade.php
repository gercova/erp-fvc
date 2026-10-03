<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Compromisos Vencidos</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
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
            color: #9b2c2c;
            margin: 10px 0 4px 0;
            text-transform: uppercase;
        }
        .report-subtitle {
            text-align: center;
            font-size: 9pt;
            color: #555;
            margin-bottom: 15px;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .kpi-card {
            border: 1px solid #fed7d7;
            background-color: #fff5f5;
            padding: 8px;
            text-align: center;
            width: 25%;
        }
        .kpi-val {
            font-size: 13pt;
            font-weight: bold;
            color: #9b2c2c;
            margin-top: 3px;
        }
        .kpi-lbl {
            font-size: 7.5pt;
            color: #742a2a;
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
            padding: 5px 7px;
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
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-danger { background-color: #fed7d7; color: #9b2c2c; }
        .badge-warning { background-color: #feebc8; color: #744210; }
        .badge-institution { background-color: #bee3f8; color: #2a4365; }
        .badge-counterparty { background-color: #e9d8fd; color: #44337a; }
        .footer {
            margin-top: 20px;
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
                <div class="header-sub">Módulo: Cumplimiento de Convenios</div>
            </td>
        </tr>
    </table>

    <div class="report-title">REPORTE DE COMPROMISOS Y OBLIGACIONES VENCIDAS</div>
    <div class="report-subtitle">Monitoreo de Vencimientos Institucionales y de Contraparte</div>

    <table class="kpi-table">
        <tr>
            <td class="kpi-card">
                <div class="kpi-lbl">Total Vencidos</div>
                <div class="kpi-val">{{ $report['total_overdue'] }}</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-lbl">Nuestra Institución</div>
                <div class="kpi-val" style="color: #2b6cb0;">{{ $report['institution_count'] }}</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-lbl">Contraparte</div>
                <div class="kpi-val" style="color: #6b46c1;">{{ $report['counterparty_count'] }}</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-lbl">Mancomunados</div>
                <div class="kpi-val" style="color: #744210;">{{ $report['joint_count'] }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 14%;">Convenio</th>
                <th style="width: 25%;">Obligación / Compromiso</th>
                <th style="width: 16%;">Parte Responsable</th>
                <th style="width: 16%;">Responsable Asignado</th>
                <th style="width: 11%;">Fecha Límite</th>
                <th style="width: 10%;">Retraso</th>
                <th style="width: 8%;">Evidencia</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['obligations'] as $obl)
                <tr>
                    <td class="font-bold text-center">
                        {{ $obl->agreement?->code }}<br>
                        <small style="color: #718096; font-weight: normal;">{{ Str::limit($obl->agreement?->title, 25) }}</small>
                    </td>
                    <td>
                        <strong>{{ $obl->title }}</strong>
                        @if($obl->description)
                            <br><small style="color: #4a5568;">{{ Str::limit($obl->description, 60) }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($obl->responsible_party === 'OUR_INSTITUTION')
                            <span class="badge badge-institution">Nuestra Institución</span>
                        @elseif($obl->responsible_party === 'COUNTERPARTY')
                            <span class="badge badge-counterparty">Contraparte</span>
                        @else
                            <span class="badge badge-warning">Mancomunado</span>
                        @endif
                    </td>
                    <td>
                        {{ $obl->responsibleUser?->name ?? 'No asignado' }}
                    </td>
                    <td class="text-center font-bold" style="color: #9b2c2c;">
                        {{ $obl->due_date ? \Carbon\Carbon::parse($obl->due_date)->format('d/m/Y') : 'Sin fecha' }}
                    </td>
                    <td class="text-center font-bold">
                        <span class="badge badge-danger">
                            {{ $obl->days_overdue ?? 0 }} días
                        </span>
                    </td>
                    <td class="text-center">
                        {{ $obl->evidence_file_path || $obl->evidence ? 'Sí' : 'No' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #38a169;">
                        ¡Excelente! No existen obligaciones ni compromisos vencidos pendientes de regularización.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer" style="width: 100%;">
        <tr>
            <td style="width: 50%;">ERP-FVC - Sistema de Gestión de Convenios y Servicios Institucionales</td>
            <td style="width: 50%; text-align: right;">Documento de auditoría y control de cumplimiento.</td>
        </tr>
    </table>
</body>
</html>
