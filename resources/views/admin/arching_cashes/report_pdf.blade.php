<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cierre de Caja</title>
    <style>
        @page {
            margin: 25px 30px;
        }

        body {
            margin: 0;
            color: #1f2937;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .company-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e3a8a;
        }

        .doc-title-box {
            text-align: right;
        }

        .doc-badge {
            display: inline-block;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        .info-grid td {
            padding: 8px 12px;
            vertical-align: top;
            width: 25%;
        }

        .info-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e3a8a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 14px;
            margin-bottom: 8px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .data-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }

        .data-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: 700;
        }

        .reconciliation-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 14px;
        }

        .reconciliation-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reconciliation-table td {
            padding: 5px 0;
            font-size: 11px;
        }

        .highlight-row {
            border-top: 1px solid #cbd5e1;
            font-size: 13px !important;
            font-weight: 700;
            color: #1e3a8a;
            padding-top: 8px !important;
        }

        .diff-badge {
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .diff-positive {
            color: #166534;
            background: #dcfce7;
        }

        .diff-negative {
            color: #991b1b;
            background: #fee2e2;
        }

        .diff-zero {
            color: #1e40af;
            background: #dbeafe;
        }

        .signatures {
            width: 100%;
            margin-top: 50px;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 40px;
        }

        .sign-line {
            border-top: 1px solid #64748b;
            padding-top: 6px;
            font-weight: 600;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="company-title">{{ $business?->nombre_comercial ?: 'EasyStock ERP' }}</div>
                    @if (!empty($business?->razon_social))
                        <div>{{ $business->razon_social }}</div>
                    @endif
                    @if (!empty($business?->ruc))
                        <div><strong>RUC:</strong> {{ $business->ruc }}</div>
                    @endif
                    @if (!empty($business?->direccion))
                        <div>{{ $business->direccion }}</div>
                    @endif
                </td>
                <td class="doc-title-box">
                    <div class="doc-badge">CIERRE DE CAJA #{{ str_pad($archingCash->id, 6, '0', STR_PAD_LEFT) }}</div>
                    <div style="margin-top: 6px; color: #64748b;">
                        Fecha de emisión: {{ date('d/m/Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-grid">
        <tr>
            <td>
                <div class="info-label">Caja</div>
                <div class="info-value">{{ $archingCash->cash?->descripcion ?? 'Caja principal' }}</div>
            </td>
            <td>
                <div class="info-label">Cajero / Responsable</div>
                <div class="info-value">{{ $archingCash->user?->nombres ?? 'Usuario' }}</div>
            </td>
            <td>
                <div class="info-label">Fecha Apertura</div>
                <div class="info-value">{{ optional($archingCash->fecha_inicio)->format('d/m/Y') ?? '-' }}</div>
            </td>
            <td>
                <div class="info-label">Fecha Cierre</div>
                <div class="info-value">{{ optional($archingCash->fecha_fin)->format('d/m/Y') ?? 'Abierta' }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">1. Recaudación por Medios de Pago (POS)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="70%">Medio de Pago</th>
                <th width="30%" class="text-right">Monto Recaudado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summary['payment_summary'] as $payment)
                <tr>
                    <td>{{ $payment['label'] }}</td>
                    <td class="text-right fw-bold">{{ $signo }} {{ number_format((float) $payment['total'], 2, '.', '') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center" style="color: #94a3b8;">Sin cobros registrados en el turno.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc;">
                <td class="fw-bold">TOTAL RECAUDACIÓN POS</td>
                <td class="text-right fw-bold" style="color: #1e3a8a;">
                    {{ $signo }} {{ number_format((float) ($summary['collections_total'] ?? 0), 2, '.', '') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">2. Movimientos de Caja (Ingresos y Egresos)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="50%">Concepto</th>
                <th width="25%" class="text-center">Cantidad</th>
                <th width="25%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Ingresos de efectivo adicionales (depósitos / entradas)</td>
                <td class="text-center">{{ $summary['inflows_count'] ?? 0 }}</td>
                <td class="text-right fw-bold" style="color: #166534;">+ {{ $signo }} {{ number_format((float) ($summary['inflows_total'] ?? 0), 2, '.', '') }}</td>
            </tr>
            <tr>
                <td>Egresos de efectivo (salidas / gastos menores)</td>
                <td class="text-center">{{ $summary['outflows_count'] ?? 0 }}</td>
                <td class="text-right fw-bold" style="color: #991b1b;">- {{ $signo }} {{ number_format((float) ($summary['outflows_total'] ?? 0), 2, '.', '') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">3. Conciliación y Cuadre de Caja</div>
    <div class="reconciliation-card">
        <table class="reconciliation-table">
            <tr>
                <td>Monto Inicial (Fondo de apertura / Float):</td>
                <td class="text-right fw-bold">{{ $signo }} {{ number_format((float) $summary['opening_amount'], 2, '.', '') }}</td>
            </tr>
            <tr>
                <td>(+) Total Recaudación por Medios de Pago:</td>
                <td class="text-right fw-bold">{{ $signo }} {{ number_format((float) ($summary['collections_total'] ?? 0), 2, '.', '') }}</td>
            </tr>
            <tr>
                <td>(+) Total Ingresos de Caja:</td>
                <td class="text-right fw-bold">{{ $signo }} {{ number_format((float) ($summary['inflows_total'] ?? 0), 2, '.', '') }}</td>
            </tr>
            <tr>
                <td>(-) Total Egresos de Caja:</td>
                <td class="text-right fw-bold">{{ $signo }} {{ number_format((float) ($summary['outflows_total'] ?? 0), 2, '.', '') }}</td>
            </tr>
            <tr class="highlight-row">
                <td>(=) MONTO ESPERADO EN CAJA:</td>
                <td class="text-right" style="font-size: 14px;">{{ $signo }} {{ number_format((float) $summary['expected_final'], 2, '.', '') }}</td>
            </tr>
            @if ((int) $archingCash->estado === 2)
                <tr>
                    <td style="padding-top: 10px;"><strong>MONTO CONTADO (ARQUEADO):</strong></td>
                    <td class="text-right fw-bold" style="padding-top: 10px; font-size: 13px;">
                        {{ $signo }} {{ number_format((float) ($archingCash->monto_final ?? $summary['expected_final']), 2, '.', '') }}
                    </td>
                </tr>
                <tr>
                    @php
                        $diff = (float) ($archingCash->diferencia ?? 0);
                        $badgeClass = $diff > 0 ? 'diff-positive' : ($diff < 0 ? 'diff-negative' : 'diff-zero');
                        $diffLabel = $diff > 0 ? 'Sobrante' : ($diff < 0 ? 'Faltante' : 'Exacto');
                    @endphp
                    <td><strong>DIFERENCIA:</strong></td>
                    <td class="text-right">
                        <span class="diff-badge {{ $badgeClass }}">
                            {{ $diff >= 0 ? '+' : '' }}{{ $signo }} {{ number_format($diff, 2, '.', '') }} ({{ $diffLabel }})
                        </span>
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sign-line">{{ $archingCash->user?->nombres ?? 'Cajero' }}<br><small>Cajero Responsable</small></div>
            </td>
            <td>
                <div class="sign-line">Administrador / Tesorería<br><small>Supervisor de Turno</small></div>
            </td>
        </tr>
    </table>
</body>
</html>
