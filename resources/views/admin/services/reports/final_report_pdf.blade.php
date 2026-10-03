<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Final - {{ $engagement->code }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 15mm 20mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333333;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1a365d;
            padding-bottom: 8px;
            margin-bottom: 15px;
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
            font-size: 14pt;
            font-weight: bold;
            color: #1a365d;
            margin: 15px 0 5px 0;
            text-transform: uppercase;
        }
        .report-code {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            color: #c59b27;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1a365d;
            background-color: #edf2f7;
            padding: 4px 8px;
            margin: 15px 0 8px 0;
            border-left: 4px solid #1a365d;
            text-transform: uppercase;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9pt;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e0;
            padding: 5px 8px;
            text-align: left;
        }
        table.data-table th {
            background-color: #f7fafc;
            color: #2d3748;
            font-weight: bold;
        }
        .meta-grid {
            width: 100%;
            margin-bottom: 10px;
        }
        .meta-grid td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 9pt;
        }
        .meta-label {
            font-weight: bold;
            color: #4a5568;
            width: 25%;
        }
        .meta-value {
            color: #1a202c;
            width: 75%;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8pt;
            font-weight: bold;
            border-radius: 4px;
        }
        .badge-success { background: #c6f6d5; color: #22543d; }
        .badge-info { background: #bee3f8; color: #2a4365; }
        .signatures-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }
        .sig-line {
            border-top: 1px solid #718096;
            margin-bottom: 5px;
        }
        .sig-name {
            font-size: 9pt;
            font-weight: bold;
        }
        .sig-cargo {
            font-size: 8pt;
            color: #718096;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 75%;">
                <div class="header-title">IESTP "Francisco Vigo Caballero"</div>
                <div class="header-sub">Servicios Tecnológicos Especializados &bull; Tocache, Región San Martín</div>
                <div class="header-sub">R.M. N° 0124-1980-ED &bull; RUC: 20123456789</div>
            </td>
            <td style="width: 25%; text-align: right;">
                <div style="font-size: 8pt; color: #718096;">Fecha de Emisión:</div>
                <div style="font-weight: bold; font-size: 9pt;">{{ $generatedAt->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="report-title">Informe Técnico de Cierre y Conformidad</div>
    <div class="report-code">{{ $engagement->code }} &bull; {{ $engagement->technologicalService?->name ?? 'Servicio Tecnológico' }}</div>

    <div class="section-title">1. Resumen Contractual e Institucional</div>
    <table class="meta-grid">
        <tr>
            <td class="meta-label">Cliente / Contraparte:</td>
            <td class="meta-value">{{ $engagement->client?->nombres }} (RUC/Doc: {{ $engagement->client?->nro_documento }})</td>
        </tr>
        <tr>
            <td class="meta-label">Marco Legal:</td>
            <td class="meta-value">{{ $engagement->agreement_id ? 'Convenio: ' . $engagement->agreement?->code . ' - ' . $engagement->agreement?->name : 'Contratación Directa / Fuera de Convenio' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Centro de Costo APE:</td>
            <td class="meta-value">{{ $engagement->productiveActivity?->name ?? 'Actividad Productiva Institucional' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Especialista Responsable:</td>
            <td class="meta-value">{{ $engagement->responsibleUser?->nombres }}</td>
        </tr>
        <tr>
            <td class="meta-label">Modalidad & Vigencia:</td>
            <td class="meta-value">{{ $engagement->delivery_modality }} &bull; Del {{ $engagement->start_date?->format('d/m/Y') }} al {{ $engagement->expected_delivery_date?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Estado de Cierre:</td>
            <td class="meta-value"><span class="badge badge-success">CONCLUIDO</span> (Cerrado el {{ $engagement->closed_at?->format('d/m/Y') ?? date('d/m/Y') }})</td>
        </tr>
    </table>

    <div class="section-title">2. Balance de Bolsa de Horas y Recursos Financieros</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Horas Contratadas</th>
                <th>Horas Ejecutadas</th>
                <th>Saldo de Horas</th>
                <th>Tarifa / Hora</th>
                <th>Monto Total Facturado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ (float)$engagement->contracted_hours }} hrs</strong></td>
                <td><strong>{{ (float)$engagement->consumed_hours }} hrs</strong></td>
                <td>{{ (float)$engagement->remainingHours() }} hrs</td>
                <td>{{ $engagement->hourly_rate ? $engagement->currency . ' ' . number_format($engagement->hourly_rate, 2) : '-' }}</td>
                <td style="font-weight: bold; color: #1a365d;">{{ $engagement->currency }} {{ number_format($engagement->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">3. Jornadas y Sesiones Técnicas Realizadas</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>N°</th>
                <th>Tema / Actividad</th>
                <th>Fecha</th>
                <th>Horario / Horas</th>
                <th>Instructor</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($engagement->sessions as $s)
            <tr>
                <td>{{ $s->session_number }}</td>
                <td>{{ $s->topic }}</td>
                <td>{{ $s->session_date?->format('d/m/Y') }}</td>
                <td>{{ $s->start_time }} - {{ $s->end_time }} ({{ (float)$s->duration_hours }} hrs)</td>
                <td>{{ $s->instructor?->nombres }}</td>
                <td>{{ $s->status?->value ?? $s->status }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #718096;">No se registraron sesiones de capacitación en aula.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">4. Asistencia y Certificaciones Emitidas</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>DNI / Doc</th>
                <th>Participante</th>
                <th>Organización</th>
                <th>% Asistencia</th>
                <th>Código Certificado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($uniqueAttendees as $uAtt)
            <tr>
                <td>{{ $uAtt->dni_or_document }}</td>
                <td>{{ $uAtt->full_name }}</td>
                <td>{{ $uAtt->organization ?? '-' }}</td>
                <td><strong>{{ $uAtt->attendance_percent }}%</strong></td>
                <td>{{ $uAtt->certificate_code ?? 'No emitido' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #718096;">Sin participantes registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">5. Registro de Visitas Técnicas / Asesoría en Campo</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Horas</th>
                <th>Modalidad</th>
                <th>Actividad Ejecutada</th>
                <th>Especialista</th>
                <th>Conformidad Cliente</th>
            </tr>
        </thead>
        <tbody>
            @forelse($engagement->hourLogs as $log)
            <tr>
                <td>{{ $log->log_date?->format('d/m/Y') }}</td>
                <td><strong>{{ (float)$log->hours }} hrs</strong></td>
                <td>{{ $log->modality }}</td>
                <td>{{ $log->activity_performed }}</td>
                <td>{{ $log->specialist?->nombres }}</td>
                <td>{{ $log->client_signed ? 'Firmada' : ($log->client_contact_name ?? 'Conforme') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #718096;">Sin registros de visitas técnicas por horas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">6. Entregables e Informes de Conformidad</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Entregable</th>
                <th>F. Compromiso</th>
                <th>F. Entrega</th>
                <th>Estado</th>
                <th>Sign-off Cliente</th>
            </tr>
        </thead>
        <tbody>
            @forelse($engagement->deliverables as $del)
            <tr>
                <td>{{ $del->deliverable_name }}</td>
                <td>{{ $del->due_date?->format('d/m/Y') }}</td>
                <td>{{ $del->submission_date?->format('d/m/Y') ?? 'Pendiente' }}</td>
                <td>{{ $del->status?->value ?? $del->status }}</td>
                <td>{{ $del->client_signoff_name ? $del->client_signoff_name . ' (' . $del->client_signoff_date?->format('d/m/Y') . ')' : 'No registrado' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #718096;">Sin entregables formales requeridos.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">7. Conclusiones y Liquidación Final</div>
    <div style="font-size: 9pt; padding: 5px 0;">
        {{ $engagement->closure_summary ?? $engagement->settlement_notes ?? 'El servicio tecnológico ha sido ejecutado en su totalidad conforme a los términos y especificaciones acordadas, cumpliendo las metas técnicas y la bolsa de horas pactada.' }}
    </div>

    <table class="signatures-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $engagement->responsibleUser?->nombres }}</div>
                <div class="sig-cargo">Especialista Responsable<br>IESTP "Francisco Vigo Caballero"</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $engagement->client?->nombres }}</div>
                <div class="sig-cargo">Conformidad de Contraparte / Cliente<br>RUC/Doc: {{ $engagement->client?->nro_documento }}</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $engagement->closedBy?->nombres ?? 'Dirección General' }}</div>
                <div class="sig-cargo">Jefatura / Dirección<br>IESTP "Francisco Vigo Caballero"</div>
            </td>
        </tr>
    </table>
</body>
</html>
