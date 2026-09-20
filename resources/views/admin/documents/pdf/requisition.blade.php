<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Requerimiento N° {{ $requisition->correlativo }}</title>
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
            margin-bottom: 10px;
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
            border: 1px solid #333;
        }
        .meta-table td {
            padding: 4px 6px;
            border: 1px solid #999;
            vertical-align: top;
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
            padding: 5px 4px;
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
            margin-top: 10px;
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
                    <span class="doc-title">REQUERIMIENTO DE BIENES Y/O SERVICIOS</span>
                    &nbsp;&mdash;&nbsp;
                    <span class="doc-number">N° {{ $requisition->correlativo }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="meta-label">A (DESTINATARIO):</td>
            <td><strong>{{ $requisition->dirigido_a }}</strong></td>
            <td class="meta-label" style="width: 15%;">FECHA:</td>
            <td style="width: 20%;">{{ $requisition->fecha ? $requisition->fecha->format('d/m/Y') : '' }}</td>
        </tr>
        <tr>
            <td class="meta-label">DE (SOLICITANTE):</td>
            <td><strong>{{ $requisition->de }}</strong> ({{ $requisition->cargo }})</td>
            <td class="meta-label">ÁREA / PROGRAMA:</td>
            <td>{{ $requisition->area?->name ?? 'Área Solicitante' }}</td>
        </tr>
        <tr>
            <td class="meta-label">FINALIDAD:</td>
            <td colspan="3">{{ $requisition->finalidad }}</td>
        </tr>
        <tr>
            <td class="meta-label">FUENTE FINANCIAMIENTO:</td>
            <td colspan="3">
                <strong>{{ ucfirst(str_replace('_', ' ', $requisition->fuente_financiamiento)) }}</strong>
                @if($requisition->fuente_especificar)
                    &nbsp;&bull;&nbsp; {{ $requisition->fuente_especificar }}
                @endif
            </td>
        </tr>
        @if($requisition->justificacion)
        <tr>
            <td class="meta-label">JUSTIFICACIÓN:</td>
            <td colspan="3" style="text-align: justify; line-height: 1.3;">
                {{ $requisition->justificacion }}
            </td>
        </tr>
        @endif
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;">ÍTEM</th>
                <th style="width: 60px;">CANTIDAD</th>
                <th>DESCRIPCIÓN DEL BIEN O SERVICIO</th>
                <th style="width: 85px;">P. UNIT. (S/)</th>
                <th style="width: 90px;">TOTAL (S/)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requisition->items as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $item->item_number ?: ($index + 1) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($item->cantidad, 2) }}</td>
                    <td>{{ $item->descripcion }}</td>
                    <td style="text-align: right;">{{ $item->precio_unitario ? number_format($item->precio_unitario, 2) : '-' }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($item->precio_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        TOTAL ESTIMADO: <span style="color: #b91c1c; font-size: 11.5px;">S/ {{ number_format($requisition->total, 2) }}</span>
    </div>

    <!-- Firmas según flujo institucional -->
    @php
        $approvalsMap = $requisition->approvals->keyBy('step_order');
        $step1 = $approvalsMap->get(1); // Solicitante / Jefe Inmediato
        $step2 = $approvalsMap->get(2); // Unidad de Administración
        $step3 = $approvalsMap->get(3); // Dirección General
    @endphp

    <table class="signatures-table">
        <tr>
            <!-- Firma 1: Solicitante -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        <div class="sign-stamp">EMISOR REGISTRADO</div>
                        <div class="sign-content"><strong>{{ $requisition->de }}</strong></div>
                        <div class="sign-content">{{ $requisition->cargo }}</div>
                    </div>
                    <div class="sign-title">FIRMA DEL SOLICITANTE</div>
                </div>
            </td>

            <!-- Firma 2: Jefe de Área / Coordinador -->
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

            <!-- Firma 3: Administración / Abastecimiento -->
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
                    <div class="sign-title">V° B° ADMINISTRACIÓN</div>
                </div>
            </td>

            <!-- Firma 4: Dirección General -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        @if($step3 && $step3->status === 'APROBADO')
                            <div class="sign-stamp">FIRMADO DIGITALMENTE</div>
                            <div class="sign-content"><strong>{{ $step3->approver_name }}</strong></div>
                            <div class="sign-content">{{ $step3->signed_at ? $step3->signed_at->format('d/m/Y H:i') : '' }}</div>
                            <div style="font-size: 6.5px; color: #555;">Token: {{ substr($step3->signature_token, 0, 16) }}...</div>
                        @else
                            <div style="height: 30px;"></div>
                            <div style="border-bottom: 1px dotted #888; width: 85%; margin: 0 auto;"></div>
                        @endif
                    </div>
                    <div class="sign-title">V° B° DIRECCIÓN GENERAL</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Documento generado a través del Sistema ERP - IESTP Francisco Vigo Caballero &bull; Impreso el {{ date('d/m/Y H:i:s') }}
    </div>

</body>
</html>
