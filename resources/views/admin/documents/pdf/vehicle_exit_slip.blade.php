<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Papeleta de Salida Vehicular N° {{ $slip->correlativo }}</title>
    <style>
        @page {
            margin: 25px 30px;
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
            margin-bottom: 12px;
        }
        .meta-table td {
            padding: 5px 7px;
            border: 1px solid #777;
            vertical-align: middle;
        }
        .meta-label {
            font-weight: bold;
            background-color: #f1f5f9;
            width: 22%;
            color: #1e293b;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        .signatures-table td {
            width: 33.33%;
            padding: 5px;
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
            margin-top: 20px;
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
                    <span class="doc-title">PAPELETA DE SALIDA DE VEHÍCULOS</span>
                    &nbsp;&mdash;&nbsp;
                    <span class="doc-number">N° {{ $slip->correlativo }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="meta-label">SOLICITANTE:</td>
            <td><strong style="font-size: 10.5px;">{{ $slip->solicitante_nombre }}</strong></td>
            <td class="meta-label" style="width: 18%;">ÁREA:</td>
            <td style="width: 25%;">{{ $slip->area?->name ?? 'No especificado' }}</td>
        </tr>
        <tr>
            <td class="meta-label">CHOFER / CONDUCTOR:</td>
            <td><strong>{{ $slip->chofer_nombre }}</strong></td>
            <td class="meta-label">N° DE BREVETE:</td>
            <td><strong>{{ $slip->brevete_numero ?: 'No registrado' }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">VEHÍCULO:</td>
            <td colspan="3"><strong style="color: #003366;">{{ $slip->vehiculo }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">LUGAR / DESTINO:</td>
            <td colspan="3"><strong>{{ $slip->lugar }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">FECHA DE SALIDA:</td>
            <td>{{ $slip->fecha_salida ? $slip->fecha_salida->format('d/m/Y') : '' }}</td>
            <td class="meta-label">HORA SALIDA / RETORNO:</td>
            <td>
                Salida: <strong>{{ substr((string)$slip->hora_salida, 0, 5) }}</strong> &nbsp;|&nbsp;
                Retorno: <strong>{{ $slip->hora_retorno ? substr((string)$slip->hora_retorno, 0, 5) : '____:____' }}</strong>
            </td>
        </tr>
        <tr>
            <td class="meta-label">MOTIVO DE SALIDA:</td>
            <td colspan="3" style="min-height: 40px; text-align: justify; line-height: 1.3;">
                {{ $slip->motivo }}
            </td>
        </tr>
        @if($slip->observaciones)
        <tr>
            <td class="meta-label">OBSERVACIONES:</td>
            <td colspan="3">
                {{ $slip->observaciones }}
            </td>
        </tr>
        @endif
    </table>

    <div style="text-align: right; margin-bottom: 10px; font-size: 9.5px;">
        {{ $slip->lugar_emision ?: 'Uchiza' }}, {{ $slip->fecha_salida ? $slip->fecha_salida->translatedFormat('d \d\e F \d\e Y') : date('d/m/Y') }}
    </div>

    <!-- Firmas según flujo institucional -->
    @php
        $approvalsMap = $slip->approvals->keyBy('step_order');
        $step1 = $approvalsMap->get(1); // Solicitante
        $step2 = $approvalsMap->get(2); // Chofer
        $step3 = $approvalsMap->get(3); // Administración
    @endphp

    <table class="signatures-table">
        <tr>
            <!-- Solicitante -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        <div class="sign-stamp">SOLICITANTE</div>
                        <div class="sign-content"><strong>{{ $slip->solicitante_nombre }}</strong></div>
                    </div>
                    <div class="sign-title">FIRMA DE SOLICITANTE</div>
                </div>
            </td>

            <!-- Chofer -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        @if($step2 && $step2->status === 'APROBADO')
                            <div class="sign-stamp">FIRMADO DIGITALMENTE</div>
                            <div class="sign-content"><strong>{{ $step2->approver_name ?: $slip->chofer_nombre }}</strong></div>
                            <div class="sign-content">{{ $step2->signed_at ? $step2->signed_at->format('d/m/Y H:i') : '' }}</div>
                            <div style="font-size: 6.5px; color: #555;">Token: {{ substr($step2->signature_token, 0, 16) }}...</div>
                        @else
                            <div style="height: 30px;"></div>
                            <div style="border-bottom: 1px dotted #888; width: 85%; margin: 0 auto;"></div>
                        @endif
                    </div>
                    <div class="sign-title">FIRMA DEL CHOFER</div>
                </div>
            </td>

            <!-- Administración -->
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
                    <div class="sign-title">V° B° J.U. ADMINISTRACIÓN</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Documento generado a través del Sistema ERP - IESTP Francisco Vigo Caballero &bull; Impreso el {{ date('d/m/Y H:i:s') }}
    </div>

</body>
</html>
