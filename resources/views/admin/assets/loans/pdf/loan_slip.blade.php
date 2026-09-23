<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Papeleta de Préstamo - {{ $loan->loan_code }}</title>
    <style>
        @page {
            margin: 20px 25px;
            size: a4 portrait;
        }

        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
        }

        body {
            font-size: 9.5px;
            line-height: 1.35;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #003366;
            padding-bottom: 8px;
        }

        .header-logo {
            width: 80px;
            text-align: left;
            vertical-align: middle;
        }

        .header-logo img {
            max-width: 75px;
            max-height: 55px;
        }

        .header-text {
            text-align: center;
            vertical-align: middle;
        }

        .inst-title {
            font-size: 13px;
            font-weight: 800;
            color: #003366;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .inst-subtitle {
            font-size: 11px;
            font-weight: bold;
            color: #222;
            margin: 2px 0;
        }

        .doc-title {
            font-size: 12px;
            font-weight: bold;
            color: #003366;
            text-align: center;
            text-transform: uppercase;
            background: #f0f4f8;
            padding: 6px;
            border: 1px solid #c2d1e0;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .folio-box {
            width: 140px;
            vertical-align: middle;
            text-align: right;
        }

        .folio-table {
            border-collapse: collapse;
            float: right;
        }

        .folio-table td {
            border: 1px solid #003366;
            padding: 3px 6px;
            font-size: 9px;
        }

        .folio-label {
            background: #003366;
            color: #fff !important;
            font-weight: bold;
            text-align: center;
        }

        .folio-number {
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            font-weight: bold;
            color: #c00;
            text-align: center;
            background: #fff;
        }

        .section-header {
            background: #003366;
            color: #ffffff;
            font-weight: bold;
            font-size: 9.5px;
            padding: 4px 8px;
            margin-top: 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .data-table td, .data-table th {
            border: 1px solid #bbb;
            padding: 4.5px 6px;
            vertical-align: top;
        }

        .data-label {
            background: #f4f6f8;
            font-weight: bold;
            width: 25%;
            color: #333;
        }

        .data-val {
            background: #ffffff;
            width: 25%;
        }

        .terms-box {
            border: 1px dashed #888;
            padding: 6px 10px;
            margin-top: 10px;
            margin-bottom: 15px;
            background: #fdfdfd;
            font-size: 8px;
            color: #444;
            text-align: justify;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        .signatures-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }

        .sig-line {
            border-top: 1px solid #222;
            padding-top: 4px;
            font-size: 8.5px;
            margin-top: 40px;
        }

        .sig-name {
            font-weight: bold;
            font-size: 9px;
            color: #111;
        }

        .sig-role {
            font-size: 8px;
            color: #555;
        }

        .stamp-box {
            border: 1px dashed #bbb;
            height: 45px;
            margin-bottom: 5px;
            font-size: 7.5px;
            color: #999;
            line-height: 45px;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 6px;
            font-weight: bold;
            font-size: 8.5px;
            border-radius: 3px;
            border: 1px solid #999;
        }
    </style>
</head>
<body>
    <!-- Encabezado Institucional -->
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if ($business && $business->logo)
                    <img src="{{ public_path($business->logo) }}" alt="Logo">
                @endif
            </td>
            <td class="header-text">
                <div class="inst-title">{{ $business->nombre_comercial ?? 'INSTITUTO DE EDUCACIÓN SUPERIOR TECNOLÓGICO' }}</div>
                <div class="inst-subtitle">{{ $business->razon_social ?? 'UNIDAD DE PATRIMONIO Y CONTROL DE ACTIVOS' }}</div>
                <div style="font-size: 8px; color: #555;">RUC: {{ $business->ruc ?? '20000000000' }} &bull; DIRECCIÓN: {{ $business->direccion ?? 'Campus Institucional' }}</div>
            </td>
            <td class="folio-box">
                <table class="folio-table">
                    <tr>
                        <td class="folio-label">PAPELETA N°</td>
                    </tr>
                    <tr>
                        <td class="folio-number">{{ $loan->loan_code }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 7.5px; text-align: center; background: #fafafa;">{{ $loan->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="doc-title">PAPELETA DE PRÉSTAMO Y DEVOLUCIÓN DE BIEN PATRIMONIAL</div>

    <!-- 1. Datos del Solicitante / Beneficiario -->
    <div class="section-header">1. DATOS DEL SOLICITANTE / PRESTATARIO</div>
    <table class="data-table">
        <tr>
            <td class="data-label">Tipo de Solicitante:</td>
            <td class="data-val" colspan="3">
                <strong>{{ $loan->borrower_type_label }}</strong>
                @if ($loan->borrower_type === 'STUDENT')
                    (Alumno de Carrera Profesional)
                @elseif ($loan->borrower_type === 'FACULTY')
                    (Personal Docente Institucional)
                @else
                    (Personal Administrativo)
                @endif
            </td>
        </tr>
        <tr>
            <td class="data-label">Apellidos y Nombres:</td>
            <td class="data-val"><strong>{{ $loan->borrower_name }}</strong></td>
            <td class="data-label">N° Documento (DNI/CE):</td>
            <td class="data-val" style="font-family: monospace; font-weight: bold;">{{ $loan->borrower_document }}</td>
        </tr>
        <tr>
            <td class="data-label">Código / Matrícula:</td>
            <td class="data-val" style="font-family: monospace;">{{ $loan->borrower_code ?: 'NO ASIGNADO' }}</td>
            <td class="data-label">Programa / Departamento:</td>
            <td class="data-val">{{ $loan->borrower_career_or_area ?: 'NO ESPECIFICADO' }}</td>
        </tr>
        <tr>
            <td class="data-label">Teléfono de Contacto:</td>
            <td class="data-val">{{ $loan->borrower_phone ?: 'NO REGISTRADO' }}</td>
            <td class="data-label">Correo Institucional / Personal:</td>
            <td class="data-val">{{ $loan->borrower_email ?: 'NO REGISTRADO' }}</td>
        </tr>
    </table>

    <!-- 2. Datos del Bien Patrimonial Prestado -->
    <div class="section-header">2. IDENTIFICACIÓN DEL BIEN PATRIMONIAL</div>
    <table class="data-table">
        <tr>
            <td class="data-label">Código Interno / Patrimonial:</td>
            <td class="data-val" style="font-family: monospace; font-weight: bold;">{{ $loan->asset?->codigo ?: 'ORD #' . $loan->asset?->orden }}</td>
            <td class="data-label">Código de Catálogo (SBN):</td>
            <td class="data-val" style="font-family: monospace;">{{ $loan->asset?->codigo_producto ?: 'NO APLICA' }}</td>
        </tr>
        <tr>
            <td class="data-label">Descripción del Activo:</td>
            <td class="data-val" colspan="3"><strong>{{ $loan->asset?->descripcion }}</strong></td>
        </tr>
        <tr>
            <td class="data-label">Marca y Modelo:</td>
            <td class="data-val">{{ $loan->asset?->marca ?: 'SIN MARCA' }} / {{ $loan->asset?->modelo ?: 'SIN MODELO' }}</td>
            <td class="data-label">N° de Serie:</td>
            <td class="data-val" style="font-family: monospace;">{{ $loan->asset?->serie ?: 'SIN SERIE' }}</td>
        </tr>
        <tr>
            <td class="data-label">Departamento de Origen:</td>
            <td class="data-val">{{ $loan->area?->name }} ({{ $loan->area?->code }})</td>
            <td class="data-label">Ubicación Habitual:</td>
            <td class="data-val">{{ $loan->asset?->ubicacion }}</td>
        </tr>
    </table>

    <!-- 3. Plazos, Destino y Condiciones -->
    <div class="section-header">3. PLAZOS, DESTINO Y CONDICIONES DEL PRÉSTAMO</div>
    <table class="data-table">
        <tr>
            <td class="data-label">Fecha y Hora de Entrega:</td>
            <td class="data-val"><strong>{{ $loan->loan_date?->format('d/m/Y H:i') }}</strong></td>
            <td class="data-label">Fecha y Hora Límite de Retorno:</td>
            <td class="data-val" style="color: #b00; font-weight: bold;">{{ $loan->expected_return_date?->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="data-label">Ambiente / Ubicación de Uso:</td>
            <td class="data-val" colspan="3"><strong>{{ $loan->destination }}</strong></td>
        </tr>
        <tr>
            <td class="data-label">Finalidad / Motivo del Uso:</td>
            <td class="data-val" colspan="3">{{ $loan->purpose ?: 'Actividades académicas / institucionales.' }}</td>
        </tr>
        <tr>
            <td class="data-label">Condición Física al Entregar:</td>
            <td class="data-val"><strong>{{ $loan->initial_condition_label }}</strong></td>
            <td class="data-label">Estado Actual del Préstamo:</td>
            <td class="data-val"><span class="badge-status">{{ $loan->status_label }}</span></td>
        </tr>
        <tr>
            <td class="data-label">Accesorios y Observaciones de Salida:</td>
            <td class="data-val" colspan="3">{{ $loan->observations ?: 'Se entrega sin observaciones adicionales.' }}</td>
        </tr>
    </table>

    <!-- 4. Control de Devolución -->
    <div class="section-header">4. REGISTRO DE RECEPCIÓN Y DEVOLUCIÓN</div>
    <table class="data-table">
        <tr>
            <td class="data-label">Fecha Real de Devolución:</td>
            <td class="data-val">
                @if ($loan->actual_return_date)
                    <strong>{{ $loan->actual_return_date->format('d/m/Y H:i') }}</strong>
                @else
                    <span style="color: #777; font-style: italic;">[ PENDIENTE DE RETORNO ]</span>
                @endif
            </td>
            <td class="data-label">Condición al Momento del Retorno:</td>
            <td class="data-val">
                @if ($loan->return_condition)
                    <strong>{{ $loan->return_condition_label }}</strong>
                @else
                    <span style="color: #777; font-style: italic;">[ PENDIENTE ]</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="data-label">Observaciones al Momento de la Devolución:</td>
            <td class="data-val" colspan="3">
                {{ $loan->return_observations ?: ($loan->actual_return_date ? 'Devuelto conforme.' : 'Pendiente de verificación física.') }}
            </td>
        </tr>
    </table>

    <!-- Cláusula de Compromiso -->
    <div class="terms-box">
        <strong>COMPROMISO DE CUSTODIA Y RESPONSABILIDAD:</strong> El solicitante abajo firmante declara haber recibido el bien patrimonial detallado en la presente papeleta en las condiciones indicadas, asumiendo la total responsabilidad por su custodia, conservación, buen uso y restitución oportuna. En caso de pérdida, sustracción, deterioro o desperfecto atribuible a negligencia, el solicitante se compromete a la reposición o reparación integral del bien conforme al reglamento institucional.
    </div>

    <!-- Bloques de Firmas -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="stamp-box">HUELLA / SELLO</div>
                <div class="sig-line">
                    <div class="sig-name">{{ $loan->borrower_name }}</div>
                    <div class="sig-role">DNI: {{ $loan->borrower_document }}</div>
                    <div class="sig-role">SOLICITANTE / PRESTATARIO</div>
                </div>
            </td>
            <td>
                <div class="stamp-box">SELLO DE ÁREA</div>
                <div class="sig-line">
                    <div class="sig-name">{{ $loan->user?->nombres ?: 'RESPONSABLE' }}</div>
                    <div class="sig-role">ENTREGADO / AUTORIZADO POR</div>
                    <div class="sig-role">{{ $loan->area?->name ?: 'CUSTODIO DE PATRIMONIO' }}</div>
                </div>
            </td>
            <td>
                <div class="stamp-box">CONFORMIDAD RECEPCIÓN</div>
                <div class="sig-line">
                    <div class="sig-name">{{ $loan->receivedByUser?->nombres ?: '_______________________' }}</div>
                    <div class="sig-role">RECIBIDO EN DEVOLUCIÓN</div>
                    <div class="sig-role">FECHA: _____ / _____ / 202__</div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
