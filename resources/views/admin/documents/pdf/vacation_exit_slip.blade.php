<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Papeleta de Vacaciones N° {{ $slip->correlativo }}</title>
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
            padding: 6px 8px;
            border: 1px solid #777;
            vertical-align: middle;
        }
        .meta-label {
            font-weight: bold;
            background-color: #f1f5f9;
            width: 25%;
            color: #1e293b;
        }
        .vacation-highlight-box {
            border: 1.5px solid #003366;
            background-color: #f0f9ff;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 12px;
            text-align: center;
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
            margin-top: 25px;
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
                    <span class="doc-title">PAPELETA DE SALIDA POR VACACIONES</span>
                    &nbsp;&mdash;&nbsp;
                    <span class="doc-number">N° {{ $slip->correlativo }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="meta-label">APELLIDOS Y NOMBRES:</td>
            <td><strong style="font-size: 11px;">{{ $slip->apellidos_nombres }}</strong></td>
            <td class="meta-label" style="width: 15%;">D.N.I.:</td>
            <td style="width: 25%;"><strong>{{ $slip->dni }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">CONDICIÓN LABORAL:</td>
            <td><strong>{{ $slip->condicion_laboral }}</strong></td>
            <td class="meta-label">CARGO / ESPECIALIDAD:</td>
            <td>{{ $slip->cargo_especialidad }}</td>
        </tr>
        <tr>
            <td class="meta-label">ÁREA / PROGRAMA EST.:</td>
            <td>{{ $slip->area_programa_estudios ?: ($slip->area?->name ?? '-') }}</td>
            <td class="meta-label">R.D. AUTORIZACIÓN:</td>
            <td>{{ $slip->resolucion_directoral ?: 'En trámite' }}</td>
        </tr>
        <tr>
            <td class="meta-label">MOTIVO:</td>
            <td colspan="3">{{ $slip->motivo }}</td>
        </tr>
    </table>

    <div class="vacation-highlight-box">
        <div style="font-size: 11px; font-weight: bold; color: #003366; margin-bottom: 5px;">
            PERIODO VACACIONAL AUTORIZADO
        </div>
        <div style="font-size: 10.5px;">
            DESDE: <strong>{{ $slip->fecha_desde ? $slip->fecha_desde->format('d/m/Y') : '-' }}</strong>
            &nbsp;&nbsp;&nbsp;&nbsp;&mdash;&nbsp;&nbsp;&nbsp;&nbsp;
            HASTA: <strong>{{ $slip->fecha_hasta ? $slip->fecha_hasta->format('d/m/Y') : '-' }}</strong>
        </div>
        <div style="margin-top: 5px; font-size: 10.5px;">
            TOTAL DÍAS DE DESCANSO FÍSICO: <strong style="color: #b91c1c; font-size: 12px;">{{ $slip->total_dias }} DÍAS</strong>
        </div>
    </div>

    @if($slip->declaracion)
    <div style="font-size: 9px; font-style: italic; color: #555; text-align: justify; margin-bottom: 15px; padding: 4px 8px; border: 1px solid #ddd; background: #fafafa;">
        "{{ $slip->declaracion }}"
    </div>
    @endif

    <div style="text-align: right; margin-bottom: 10px; font-size: 9.5px;">
        {{ $slip->lugar_emision ?: 'Uchiza' }}, {{ $slip->fecha_desde ? $slip->fecha_desde->translatedFormat('d \d\e F \d\e Y') : date('d/m/Y') }}
    </div>

    <!-- Firmas según flujo institucional -->
    @php
        $approvalsMap = $slip->approvals->keyBy('step_order');
        $step1 = $approvalsMap->get(1); // Servidor
        $step2 = $approvalsMap->get(2); // Jefe Inmediato
        $step3 = $approvalsMap->get(3); // Unidad Académica
        $step4 = $approvalsMap->get(4); // Dirección General
    @endphp

    <table class="signatures-table">
        <tr>
            <!-- Servidor -->
            <td>
                <div class="sign-box">
                    <div style="height: 48px;">
                        <div class="sign-stamp">SERVIDOR(A)</div>
                        <div class="sign-content"><strong>{{ $slip->apellidos_nombres }}</strong></div>
                        <div class="sign-content">DNI: {{ $slip->dni }}</div>
                    </div>
                    <div class="sign-title">DOCENTE / SERVIDOR</div>
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
                    <div class="sign-title">V° B° JEFE ÁREA / INMED.</div>
                </div>
            </td>

            <!-- Unidad Académica -->
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
                    <div class="sign-title">V° B° UNIDAD ACADÉMICA</div>
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
