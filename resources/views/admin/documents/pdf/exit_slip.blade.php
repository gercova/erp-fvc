<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Papeleta de Salida N° {{ $slip->correlativo }}</title>
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
            font-size: 13px;
            font-weight: bold;
            color: #003366;
            letter-spacing: 0.5px;
        }
        .doc-number {
            font-size: 13px;
            font-weight: bold;
            color: #dc3545;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .meta-table td {
            padding: 5px 8px;
            border: 1px solid #777;
            vertical-align: middle;
        }
        .meta-label {
            font-weight: bold;
            background-color: #f1f5f9;
            width: 25%;
            color: #1e293b;
        }
        .checkbox-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            text-align: center;
            line-height: 11px;
            font-weight: bold;
            font-size: 9px;
            margin-right: 4px;
            vertical-align: middle;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
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
                    <span class="doc-title">PAPELETA DE SALIDA DE PERSONAL</span>
                    &nbsp;&mdash;&nbsp;
                    <span class="doc-number">N° {{ $slip->correlativo }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="meta-label">APELLIDOS Y NOMBRES:</td>
            <td colspan="3"><strong style="font-size: 11px;">{{ $slip->nombres_apellidos }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">TIPO DE PERSONAL:</td>
            <td><strong>{{ $slip->tipo_personal }}</strong></td>
            <td class="meta-label" style="width: 20%;">ÁREA / PROGRAMA:</td>
            <td>{{ $slip->area?->name ?? 'No especificado' }}</td>
        </tr>
        <tr>
            <td class="meta-label">MOTIVO DE LA SALIDA:</td>
            <td colspan="3">
                <div style="margin: 2px 0;">
                    <span class="checkbox-box">{{ stripos($slip->motivo, 'Comisi') !== false ? 'X' : '' }}</span> Comisión de Servicios &nbsp;&nbsp;
                    <span class="checkbox-box">{{ stripos($slip->motivo, 'Personal') !== false || stripos($slip->motivo, 'Particular') !== false ? 'X' : '' }}</span> Asuntos Particulares &nbsp;&nbsp;
                    <span class="checkbox-box">{{ stripos($slip->motivo, 'Salud') !== false ? 'X' : '' }}</span> Salud / Cita Médica &nbsp;&nbsp;
                    <span class="checkbox-box">{{ stripos($slip->motivo, 'Judicial') !== false ? 'X' : '' }}</span> Asunto Judicial
                </div>
                @if($slip->motivo_especificar)
                    <div style="margin-top: 3px; font-size: 9px; color: #444;">
                        <strong>Especificación:</strong> {{ $slip->motivo_especificar }}
                    </div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="meta-label">LUGAR / DESTINO:</td>
            <td colspan="3"><strong>{{ $slip->destino ?: $slip->lugar }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">FECHA DE SALIDA:</td>
            <td style="width: 25%;">{{ $slip->fecha_salida ? $slip->fecha_salida->format('d/m/Y') : '' }}</td>
            <td class="meta-label" style="width: 25%;">HORA SALIDA / RETORNO:</td>
            <td style="width: 25%;">
                Salida: <strong>{{ substr((string)$slip->hora_salida, 0, 5) }}</strong> &nbsp;|&nbsp;
                Retorno: <strong>{{ $slip->hora_retorno ? substr((string)$slip->hora_retorno, 0, 5) : '____:____' }}</strong>
            </td>
        </tr>
        @if($slip->observaciones)
        <tr>
            <td class="meta-label">OBSERVACIONES:</td>
            <td colspan="3" style="text-align: justify; line-height: 1.3;">
                {{ $slip->observaciones }}
            </td>
        </tr>
        @endif
        @if($slip->nota_salud)
        <tr>
            <td class="meta-label">CONSTANCIA DE SALUD:</td>
            <td colspan="3" style="text-align: justify; line-height: 1.3; color: #003366;">
                {{ $slip->nota_salud }}
            </td>
        </tr>
        @endif
        <tr>
            <td class="meta-label">CONSTANCIA DE ATENCIÓN:</td>
            <td colspan="3" style="height: 55px; vertical-align: top; font-size: 8px; color: #666;">
                <em>(Sello y firma de la institución o entidad visitada donde se cumplió la comisión o diligencia)</em>
            </td>
        </tr>
    </table>

    <div style="text-align: right; margin-bottom: 10px; font-size: 9.5px;">
        {{ $slip->lugar_emision ?: 'Uchiza' }}, {{ $slip->fecha_salida ? $slip->fecha_salida->translatedFormat('d \d\e F \d\e Y') : date('d/m/Y') }}
    </div>

    <!-- Firmas según flujo institucional -->
    @php
        $approvalsMap = $slip->approvals->keyBy('step_order');
        $step1 = $approvalsMap->get(1); // Solicitante / Usuario
        $step2 = $approvalsMap->get(2); // Jefe Inmediato
        $step3 = $approvalsMap->get(3); // Administración
        $step4 = $approvalsMap->get(4); // Dirección General
    @endphp

    <table class="signatures-table">
        <tr>
            <!-- Servidor -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        <div class="sign-stamp">TRABAJADOR</div>
                        <div class="sign-content"><strong>{{ $slip->nombres_apellidos }}</strong></div>
                        <div class="sign-content">{{ $slip->tipo_personal }}</div>
                    </div>
                    <div class="sign-title">FIRMA DEL TRABAJADOR</div>
                </div>
            </td>

            <!-- Jefe Inmediato -->
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
                    <div class="sign-title">V° B° JEFE INMEDIATO</div>
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
                    <div class="sign-title">V° B° ADMINISTRACIÓN</div>
                </div>
            </td>

            <!-- Dirección General -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        @if($step4 && $step4->status === 'APROBADO')
                            <div class="sign-stamp">FIRMADO DIGITALMENTE</div>
                            <div class="sign-content"><strong>{{ $step4->approver_name }}</strong></div>
                            <div class="sign-content">{{ $step4->signed_at ? $step4->signed_at->format('d/m/Y H:i') : '' }}</div>
                            <div style="font-size: 6.5px; color: #555;">Token: {{ substr($step4->signature_token, 0, 16) }}...</div>
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
