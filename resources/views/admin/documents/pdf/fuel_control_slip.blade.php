<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vale de Control N° {{ $slip->correlativo }}</title>
    <style>
        @page {
            margin: 20px 25px;
        }
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #111;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-logo {
            width: 80px;
            vertical-align: middle;
            text-align: center;
        }
        .header-logo img {
            max-width: 75px;
            max-height: 75px;
        }
        .header-text {
            text-align: center;
            vertical-align: middle;
        }
        .inst-title {
            font-size: 13px;
            font-weight: bold;
            color: #003366;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.2;
        }
        .inst-subtitle {
            font-size: 8.5px;
            color: #444;
            margin: 2px 0;
        }
        .doc-title-box {
            border: 1.5px solid #003366;
            border-radius: 4px;
            padding: 5px 8px;
            margin-top: 4px;
            background-color: #f8fafc;
            text-align: center;
        }
        .doc-title {
            font-size: 12px;
            font-weight: bold;
            color: #003366;
            letter-spacing: 0.5px;
        }
        .doc-number {
            font-size: 12px;
            font-weight: bold;
            color: #dc3545;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .meta-table td {
            padding: 4px 6px;
            border: 1px solid #777;
            vertical-align: middle;
        }
        .meta-label {
            font-weight: bold;
            background-color: #f1f5f9;
            width: 20%;
            color: #1e293b;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items-table th {
            background-color: #003366;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 5px 4px;
            border: 1px solid #003366;
            font-size: 9px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 5px 6px;
            border: 1px solid #ccc;
            font-size: 9.5px;
        }
        .items-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .total-box {
            font-size: 10.5px;
            font-weight: bold;
            text-align: right;
            padding: 6px;
            background-color: #f1f5f9;
            border: 1px solid #999;
            margin-bottom: 10px;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .signatures-table td {
            width: 25%;
            padding: 4px;
            vertical-align: bottom;
            text-align: center;
        }
        .sign-box {
            border: 1px solid #333;
            border-radius: 4px;
            height: 95px;
            padding: 4px;
            text-align: center;
            background-color: #fff;
        }
        .sign-content {
            font-size: 8px;
            color: #333;
            margin-top: 2px;
        }
        .sign-stamp {
            border: 1px dashed #198754;
            color: #198754;
            padding: 2px;
            font-size: 7.5px;
            font-weight: bold;
            border-radius: 3px;
            background-color: #f0fdf4;
            margin-bottom: 2px;
        }
        .sign-title {
            font-weight: bold;
            font-size: 8px;
            border-top: 1px solid #333;
            padding-top: 3px;
            margin-top: 4px;
            color: #000;
        }
        .footer-note {
            margin-top: 15px;
            font-size: 8px;
            color: #666;
            text-align: center;
            border-top: 0.5px solid #ccc;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="header-logo">
                @php
                    $logoPath = public_path('files/logos/logo.jpg');
                    if (!file_exists($logoPath)) {
                        $logoPath = public_path('assets/img/logo.png');
                    }
                @endphp
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo">
                @endif
            </td>
            <td class="header-text">
                <div class="inst-title">INSTITUTO DE EDUCACIÓN SUPERIOR TECNOLÓGICO PÚBLICO</div>
                <div class="inst-title" style="font-size: 14px; color: #b91c1c;">"FRANCISCO VIGO CABALLERO"</div>
                <div class="inst-subtitle">Creado por R.M. N° 0868-94-ED &bull; Revalidado con R.D. N° 0296-2006-ED</div>
                <div class="inst-subtitle">Uchiza - Tocache - San Martín</div>
                <div class="doc-title-box">
                    <span class="doc-title">VALE DE CONTROL INTERNO DE COMBUSTIBLE Y LUBRICANTES</span>
                    &nbsp;&mdash;&nbsp;
                    <span class="doc-number">N° {{ $slip->correlativo }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="meta-label">FECHA Y HORA:</td>
            <td>{{ $slip->fecha ? $slip->fecha->format('d/m/Y') : '' }} &nbsp;|&nbsp; <strong>{{ substr((string)$slip->hora, 0, 5) }}</strong></td>
            <td class="meta-label">NOMBRE DEL GRIFO:</td>
            <td><strong>{{ $slip->nombre_grifo }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">REQUERIMIENTO N°:</td>
            <td>{{ $slip->requerimiento_nro ?: 'S/N' }}</td>
            <td class="meta-label">ORDEN DE COMPRA N°:</td>
            <td>{{ $slip->orden_compra_nro ?: 'S/N' }}</td>
        </tr>
        <tr>
            <td class="meta-label">VEHÍCULO / MÁQUINA:</td>
            <td><strong>{{ $slip->vehiculo_maquina }}</strong></td>
            <td class="meta-label">PLACA / CÓDIGO:</td>
            <td><strong>{{ $slip->placa ?: 'S/P' }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">KM / HORÓMETRO:</td>
            <td>{{ $slip->kilometraje_horometro ?: 'No registrado' }}</td>
            <td class="meta-label">ÁREA SOLICITANTE:</td>
            <td>{{ $slip->area?->name ?? 'Área Institucional' }}</td>
        </tr>
        <tr>
            <td class="meta-label">ACTIVIDAD / COMISIÓN:</td>
            <td colspan="3">{{ $slip->actividad_comision }}</td>
        </tr>
        <tr>
            <td class="meta-label">FACTURAR A:</td>
            <td colspan="3"><strong>{{ $slip->facturar_a }}</strong></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;">N°</th>
                <th style="width: 60px;">CANTIDAD</th>
                <th style="width: 80px;">U. MEDIDA</th>
                <th>DESCRIPCIÓN DEL COMBUSTIBLE O LUBRICANTE</th>
                <th style="width: 85px;">P. UNIT. (S/)</th>
                <th style="width: 90px;">TOTAL (S/)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($slip->items as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($item->cantidad, 2) }}</td>
                    <td style="text-align: center;">{{ $item->unidad_medida }}</td>
                    <td>{{ $item->descripcion }}</td>
                    <td style="text-align: right;">{{ $item->precio_unitario ? number_format($item->precio_unitario, 2) : '-' }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        TOTAL ESTIMADO COMBUSTIBLE: <span style="color: #b91c1c; font-size: 11.5px;">S/ {{ number_format($slip->total_general, 2) }}</span>
    </div>

    @if($slip->observaciones)
    <div style="margin-bottom: 10px; font-size: 9px; padding: 4px 6px; border: 1px solid #ccc; background-color: #fafafa;">
        <strong>OBSERVACIONES:</strong> {{ $slip->observaciones }}
    </div>
    @endif

    <!-- Firmas -->
    @php
        $approvalsMap = $slip->approvals->keyBy('step_order');
        $step1 = $approvalsMap->get(1); // Jefe Inmediato
        $step2 = $approvalsMap->get(2); // Administración / Abastecimiento
    @endphp

    <table class="signatures-table">
        <tr>
            <!-- Conductor / Solicitante -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        <div class="sign-stamp">CONDUCTOR / RESP.</div>
                        <div class="sign-content"><strong>{{ $slip->user?->name }}</strong></div>
                    </div>
                    <div class="sign-title">CONDUCTOR / OPERADOR</div>
                </div>
            </td>

            <!-- Atendido Grifo -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        <div style="font-size: 8px; color: #666; margin-top: 4px;">Sello y Firma del Grifo</div>
                        <div style="height: 16px;"></div>
                        <div style="border-bottom: 1px dotted #888; width: 85%; margin: 0 auto;"></div>
                    </div>
                    <div class="sign-title">ATENDIDO POR (GRIFO)</div>
                </div>
            </td>

            <!-- Jefe Inmediato -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        @if($step1 && $step1->status === 'APROBADO')
                            <div class="sign-stamp">FIRMADO DIGITALMENTE</div>
                            <div class="sign-content"><strong>{{ $step1->approver_name }}</strong></div>
                            <div class="sign-content">{{ $step1->signed_at ? $step1->signed_at->format('d/m/Y H:i') : '' }}</div>
                            <div style="font-size: 6.5px; color: #555;">Token: {{ substr($step1->signature_token, 0, 16) }}...</div>
                        @else
                            <div style="height: 30px;"></div>
                            <div style="border-bottom: 1px dotted #888; width: 85%; margin: 0 auto;"></div>
                        @endif
                    </div>
                    <div class="sign-title">V° B° JEFE INMEDIATO</div>
                </div>
            </td>

            <!-- Administración / Abastecimiento -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        @if($step2 && $step2->status === 'APROBADO')
                            <div class="sign-stamp">FIRMADO DIGITALMENTE</div>
                            <div class="sign-content"><strong>{{ $step2->approver_name }}</strong></div>
                            <div class="sign-content">{{ $step2->signed_at ? $step2->signed_at->format('d/m/Y H:i') : '' }}</div>
                            <div style="font-size: 6.5px; color: #555;">Token: {{ substr($step2->signature_token, 0, 16) }}...</div>
                        @else
                            <div style="height: 30px;"></div>
                            <div style="border-bottom: 1px dotted #888; width: 85%; margin: 0 auto;"></div>
                        @endif
                    </div>
                    <div class="sign-title">V° B° ABASTECIMIENTO</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Documento generado a través del Sistema ERP - IESTP Francisco Vigo Caballero &bull; Impreso el {{ date('d/m/Y H:i:s') }}
    </div>

</body>
</html>
