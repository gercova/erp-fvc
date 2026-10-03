<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado - {{ $attendee->certificate_code }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 30px;
            background-color: #ffffff;
            color: #2c3e50;
        }
        .border-outer {
            border: 8px solid #1a365d;
            padding: 10px;
            height: 90%;
            position: relative;
        }
        .border-inner {
            border: 2px solid #c59b27;
            padding: 25px 40px;
            height: 94%;
            text-align: center;
            background: radial-gradient(circle at center, #ffffff 0%, #fafbfc 100%);
        }
        .header-inst {
            font-size: 16px;
            font-weight: bold;
            color: #1a365d;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .sub-inst {
            font-size: 11px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        .title-cert {
            font-size: 32px;
            font-weight: 800;
            color: #c59b27;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin: 10px 0;
        }
        .text-granted {
            font-size: 13px;
            color: #4a5568;
            text-transform: uppercase;
            margin-bottom: 12px;
            font-style: italic;
        }
        .recipient-name {
            font-size: 26px;
            font-weight: bold;
            color: #1a202c;
            border-bottom: 2px solid #cbd5e0;
            display: inline-block;
            padding-bottom: 4px;
            margin-bottom: 8px;
            min-width: 480px;
        }
        .recipient-doc {
            font-size: 12px;
            color: #4a5568;
            margin-bottom: 16px;
        }
        .course-description {
            font-size: 14px;
            line-height: 1.6;
            color: #2d3748;
            max-width: 720px;
            margin: 0 auto 20px auto;
        }
        .course-title {
            font-weight: bold;
            color: #1a365d;
            font-size: 16px;
        }
        .footer-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: bottom;
            text-align: center;
        }
        .sig-line {
            width: 200px;
            border-top: 1px solid #718096;
            margin: 0 auto 5px auto;
        }
        .sig-name {
            font-size: 11px;
            font-weight: bold;
            color: #2d3748;
        }
        .sig-title {
            font-size: 9px;
            color: #718096;
        }
        .qr-section {
            text-align: center;
            width: 160px;
        }
        .qr-caption {
            font-size: 8px;
            color: #718096;
            margin-top: 4px;
        }
        .cert-code {
            font-family: monospace;
            font-size: 10px;
            font-weight: bold;
            color: #1a365d;
        }
    </style>
</head>
<body>
    <div class="border-outer">
        <div class="border-inner">
            <div class="header-inst">Instituto de Educación Superior Tecnológico Público</div>
            <div class="header-inst" style="font-size: 18px; color: #c59b27;">"Francisco Vigo Caballero"</div>
            <div class="sub-inst">R.M. N° 0124-1980-ED &bull; Tocache, Región San Martín &bull; Servicios Tecnológicos Especializados</div>

            <div class="title-cert">Certificado de Capacitación</div>
            <div class="text-granted">Otorgado a:</div>

            <div class="recipient-name">{{ $attendee->full_name }}</div>
            <div class="recipient-doc">Documento Nacional de Identidad N° <strong>{{ $attendee->dni_or_document }}</strong></div>

            <div class="course-description">
                Por haber completado satisfactoriamente el curso / capacitación técnica en:
                <div class="course-title">{{ $engagement->technologicalService?->name ?? 'Servicio Tecnológico Especializado' }}</div>
                con una duración total de <strong>{{ $totalHours }} horas académicas</strong> y un porcentaje de asistencia verificado del <strong>{{ $attendancePercent }}%</strong>, organizado bajo la orden <strong>{{ $engagement->code }}</strong>.
            </div>

            <table class="footer-table">
                <tr>
                    <td style="width: 35%;">
                        <div class="sig-line"></div>
                        <div class="sig-name">{{ $engagement->responsibleUser?->nombres ?? 'Ing. Coordinador Técnico' }}</div>
                        <div class="sig-title">Especialista Responsable / Instructor<br>IESTP Francisco Vigo Caballero</div>
                    </td>
                    <td style="width: 30%;" class="qr-section">
                        @if(!empty($qrBase64))
                            <img src="data:image/svg+xml;base64,{{ $qrBase64 }}" width="90" height="90" alt="QR de Verificación">
                        @endif
                        <div class="qr-caption">
                            Verificación de Autenticidad QR<br>
                            <span class="cert-code">{{ $attendee->certificate_code }}</span>
                        </div>
                    </td>
                    <td style="width: 35%;">
                        <div class="sig-line"></div>
                        <div class="sig-name">Dirección General</div>
                        <div class="sig-title">IESTP "Francisco Vigo Caballero"<br>Tocache - San Martín</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
