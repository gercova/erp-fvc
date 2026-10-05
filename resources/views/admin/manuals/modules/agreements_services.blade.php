{{-- MANUAL DE USUARIO: CONVENIOS Y SERVICIOS TECNOLÓGICOS --}}
<div class="manual-module-content" id="module-content-agreements">
    <!-- Header del Módulo -->
    <div class="card border-0 shadow-sm mb-4 border-start-lg border-start-success bg-light-subtle">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-success text-white rounded-3 shadow-sm">
                        <i class="fas fa-file-contract fa-2x"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-success text-white">MÓDULO CONVENIOS Y SERVICIOS</span>
                            <span class="badge bg-light text-dark border">Bloques C1 - C5</span>
                            <span class="badge bg-success-subtle text-success border border-success">10 Procesos Documentados</span>
                        </div>
                        <h2 class="h4 fw-bold text-gray-800 mb-1">Manual de Usuario: Convenios y Servicios Tecnológicos</h2>
                        <p class="text-muted small mb-0">Gestión de alianzas estratégicas, obligaciones institucionales, cobranza por cuotas, capacitación técnica, certificados y bitácora de consultoría.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('agreements.index') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> Catálogo de Convenios
                    </a>
                    <a href="{{ route('services.engagements.index') }}" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-chalkboard-teacher me-1"></i> Servicios Tecnológicos
                    </a>
                    <a href="{{ route('agreements.reports.revenue') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-chart-pie me-1"></i> Reportes de Recaudación
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Índice de Procesos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-success"><i class="fas fa-list-ol me-2"></i>Índice de Procesos de Convenios y Servicios</h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-conv-01" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-success rounded-pill">01</span>
                        <div class="small fw-semibold text-truncate">Registro de Convenio Institucional</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-conv-02" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-success rounded-pill">02</span>
                        <div class="small fw-semibold text-truncate">Obligaciones y Evidencias</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-conv-03" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-success rounded-pill">03</span>
                        <div class="small fw-semibold text-truncate">Addendas y Modificaciones</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-conv-04" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-success rounded-pill">04</span>
                        <div class="small fw-semibold text-truncate">Cuotas, Facturación y Cobranza</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-conv-05" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-success rounded-pill">05</span>
                        <div class="small fw-semibold text-truncate">Tablero de Cumplimiento y Alertas</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-serv-01" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-info text-white rounded-pill">06</span>
                        <div class="small fw-semibold text-truncate">Apertura de Servicio (Engagement)</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-serv-02" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-info text-white rounded-pill">07</span>
                        <div class="small fw-semibold text-truncate">Sesiones e Importación de Asistentes</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-serv-03" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-info text-white rounded-pill">08</span>
                        <div class="small fw-semibold text-truncate">Emisión de Certificados Digitales</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-serv-04" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-info text-white rounded-pill">09</span>
                        <div class="small fw-semibold text-truncate">Bitácora de Horas de Asistencia</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-serv-05" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-info text-white rounded-pill">10</span>
                        <div class="small fw-semibold text-truncate">Entregables, Sign-off e Informe Final</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN A: CONVENIOS INSTITUCIONALES -->
    <h5 class="fw-bold text-dark mb-3"><i class="fas fa-university me-2 text-success"></i>Parte I: Gestión de Convenios Institucionales</h5>

    <!-- PROC-CONV-01 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-conv-01" data-search-terms="convenio registro crear nuevo institucional marco especifico cooperacion">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success px-2 py-1">PROC-CONV-01</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Registro y Formalización de Convenios Institucionales</h5>
            </div>
            <a href="{{ route('agreements.create') }}" target="_blank" class="btn btn-outline-success btn-sm">
                <i class="fas fa-plus me-1"></i> Ir a Registrar Convenio
            </a>
        </div>
        <div class="card-body p-4">
            <h6 class="fw-bold text-success mb-2"><i class="fas fa-bullseye me-1"></i> Objetivo del Proceso:</h6>
            <p class="small text-muted">Registrar en la base central los acuerdos suscritos entre la institución y entidades públicas, empresas privadas, ONGs o comunidades campesinas, fijando plazos de vigencia, compromisos y aportes financieros.</p>

            <h6 class="fw-bold text-gray-800 mb-2"><i class="fas fa-shoe-prints me-2 text-success"></i>Instrucciones Paso a Paso:</h6>
            <div class="timeline-steps">
                <div class="step-item d-flex gap-3 mb-3">
                    <div class="step-badge bg-success text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">1</div>
                    <div class="small text-muted">Acceda a <strong>Convenios y Servicios &rarr; Catálogo de Convenios</strong> y presione <strong>"Nuevo Convenio"</strong>.</div>
                </div>
                <div class="step-item d-flex gap-3 mb-3">
                    <div class="step-badge bg-success text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">2</div>
                    <div>
                        <div class="fw-bold text-dark small">Completar Datos Generales</div>
                        <ul class="small text-muted ps-3 mb-0">
                            <li><strong>Entidad Contraparte:</strong> Seleccione la empresa o institución desde el catálogo de Clientes/Entidades (o regístrela previamente si no existe).</li>
                            <li><strong>Tipo de Convenio:</strong> Elija entre <code>FRAMEWORK</code> (Convenio Marco), <code>SPECIFIC</code> (Convenio Específico), <code>INTER_INSTITUTIONAL</code> (Interinstitucional), <code>INTERNSHIP</code> (Prácticas Pre-profesionales) o <code>FINANCIAL_SUPPORT</code>.</li>
                            <li><strong>Objeto del Convenio:</strong> Síntesis clara y precisa de los fines de la cooperación.</li>
                            <li><strong>Área Coordinadora y Coordinador Asignado:</strong> Funcionario designado responsable del monitoreo.</li>
                        </ul>
                    </div>
                </div>
                <div class="step-item d-flex gap-3 mb-3">
                    <div class="step-badge bg-success text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">3</div>
                    <div>
                        <div class="fw-bold text-dark small">Definición de Vigencia y Presupuesto</div>
                        <ul class="small text-muted ps-3 mb-0">
                            <li><strong>Fecha de Suscripción, Fecha de Inicio y Fecha de Fin:</strong> El sistema calculará automáticamente los días restantes y alertará a los 30 días previos a la caducidad.</li>
                            <li><strong>Monto Total Pactado (S/):</strong> Ingrese el importe si el convenio contempla transferencias de recursos o financiamiento (deje 0.00 si es de cooperación sin transferencia).</li>
                        </ul>
                    </div>
                </div>
                <div class="step-item d-flex gap-3 mb-3">
                    <div class="step-badge bg-success text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">4</div>
                    <div class="small text-muted">Suba el archivo escaneado del convenio firmado en PDF y presione <strong>"Registrar Convenio"</strong>. El sistema generará el código oficial correlativo (ej. <code>CONV-2026-001</code>).</div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROC-CONV-02 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-conv-02" data-search-terms="obligaciones compromisos evidencias informes sustento convenio">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success px-2 py-1">PROC-CONV-02</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Gestión de Obligaciones y Carga de Evidencias</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Cada cláusula operativa del convenio se desglosa en compromisos con plazos y responsables definidos para asegurar el 100% de cumplimiento:</p>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Ingrese al detalle del convenio (ícono <i class="fas fa-eye text-primary"></i> <strong>Ver Ficha</strong>).</li>
                <li class="mb-2">En la pestaña <strong>"Obligaciones y Compromisos"</strong>, presione <strong>"Agregar Obligación"</strong>.</li>
                <li class="mb-2">Indique la parte responsable (<code>Nuestra Institución</code> o <code>La Contraparte</code>), descripción del hito, fecha límite de cumplimiento y ponderación o porcentaje.</li>
                <li class="mb-2">Cuando la obligación se ejecute, presione el botón <strong>"Subir Evidencia"</strong>: adjunte el informe técnico, acta de entrega o fotografías que sustenten la ejecución.</li>
                <li>Cambie el estado a <span class="badge bg-success">Cumplida</span>. El indicador de avance del convenio se actualizará en tiempo real.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-CONV-03 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-conv-03" data-search-terms="addenda ampliacion plazo presupuesto modificacion clausula convenio">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success px-2 py-1">PROC-CONV-03</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Registro de Addendas y Modificaciones Contractuales</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Si las partes acuerdan prorrogar el plazo o ampliar el monto financiero pactado, debe generarse una Addenda formal:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li>En la pestaña <strong>"Addendas"</strong> de la ficha del convenio, haga clic en <strong>"Nueva Addenda"</strong>.</li>
                <li>Seleccione el tipo: <em>Ampliación de Plazo</em>, <em>Ampliación Presupuestal</em> o <em>Modificación de Cláusulas</em>.</li>
                <li>Ingrese la nueva fecha de finalización o el monto adicional a incorporar.</li>
                <li>Adjunte la copia escaneada de la Addenda suscrita. El sistema actualizará las fechas del convenio principal manteniendo el historial y trazabilidad original.</li>
            </ul>
        </div>
    </div>

    <!-- PROC-CONV-04 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-conv-04" data-search-terms="cuotas facturacion cobranza cronograma pagos checkout cuota comprobante convenio">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success px-2 py-1">PROC-CONV-04</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Cronograma de Cuotas, Facturación y Control de Pagos</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Para convenios que involucran financiamiento por desembolsos o cobro de aportes por etapas:</p>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <div class="fw-bold text-dark small mb-1">1. Programar Cuotas</div>
                        <p class="small text-muted mb-0">En la pestaña <em>"Cronograma de Pagos"</em>, defina el número de cuota, fecha programada de vencimiento y monto en Soles. La suma total no puede exceder el presupuesto del convenio.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <div class="fw-bold text-dark small mb-1">2. Checkout y Facturación Electrónica</div>
                        <p class="small text-muted mb-0">Use el botón <strong>"Preparar Checkout"</strong> para emitir la Factura o Boleta Electrónica directamente en el sistema de facturación SUNAT con los datos de la cuota.</p>
                    </div>
                </div>
            </div>
            <div class="alert alert-info py-2 px-3 small mb-0">
                <i class="fas fa-check-circle me-1"></i> Una vez realizado el abono bancario, use <strong>"Registrar Pago"</strong> adjuntando el voucher; la cuota pasará a estado <code>PAID</code> (Pagada).
            </div>
        </div>
    </div>

    <!-- PROC-CONV-05 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-conv-05" data-search-terms="tablero cumplimiento alertas vencimiento semaforo reportes convenios">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success px-2 py-1">PROC-CONV-05</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Tablero de Cumplimiento, Alertas y Reportes Gerenciales</h5>
            </div>
            <a href="{{ route('agreements.reports.revenue') }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-chart-line me-1"></i> Ver Reporte de Recaudación
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">El sistema monitorea proactivamente el estado de los convenios:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li><strong>Semáforo de Vencimiento:</strong> Convenios a menos de 30 días de expirar se muestran con una etiqueta amarilla en el menú superior y lateral.</li>
                <li><strong>Reporte de Obligaciones Vencidas:</strong> Permite listar de inmediato qué entidades tienen compromisos pendientes fuera de plazo.</li>
                <li><strong>Exportación PDF/Excel:</strong> Genere informes ejecutivos de recaudación y cumplimiento para presentar ante la Dirección General o Consejo Directivo.</li>
            </ul>
        </div>
    </div>

    <!-- SECCIÓN B: SERVICIOS TECNOLÓGICOS -->
    <h5 class="fw-bold text-dark mb-3 mt-4"><i class="fas fa-chalkboard-teacher me-2 text-info"></i>Parte II: Gestión de Servicios Tecnológicos y Capacitaciones</h5>

    <!-- PROC-SERV-01 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-serv-01" data-search-terms="servicio tecnologico engagement contrato horas bolsa consultoria capacitacion">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info text-white px-2 py-1">PROC-SERV-01</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Apertura y Configuración del Servicio Tecnológico (Engagement)</h5>
            </div>
            <a href="{{ route('services.engagements.create') }}" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="fas fa-plus me-1"></i> Nuevo Servicio Tecnológico
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Aplica a cursos de capacitación, ensayos de laboratorio, asistencia técnica de campo, alquiler de maquinaria o servicios especializados prestados a terceros.</p>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Vaya a <strong>Convenios y Servicios &rarr; Servicios Tecnológicos</strong> &rarr; botón <strong>"Nuevo Servicio"</strong>.</li>
                <li class="mb-2">Seleccione el servicio del catálogo predeterminado (ej. <em>"Curso de Especialización en Inseminación Artificial"</em> o <em>"Análisis Fisicoquímico de Suelos"</em>).</li>
                <li class="mb-2">Asigne al Cliente solicitante y vincule opcionalmente al convenio marco de respaldo.</li>
                <li class="mb-2">Defina el número de horas contratadas (bolsa de horas) y el umbral de alerta (ej. avisar cuando queden 10 horas).</li>
                <li>Guarde el registro para habilitar la matrícula de asistentes y el cronograma de sesiones.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-SERV-02 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-serv-02" data-search-terms="asistencia sesiones capacitacion alumnos participantes importacion excel">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info text-white px-2 py-1">PROC-SERV-02</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Sesiones de Capacitación, Matrícula e Importación de Participantes</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Permite gestionar aulas presenciales o virtuales con control de quorum:</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <h6 class="fw-bold small mb-1"><i class="fas fa-file-excel text-success me-1"></i> Importación Masiva de Participantes</h6>
                        <p class="small text-muted mb-0">En la pestaña <em>"Participantes"</em>, descargue la plantilla Excel, pegue la lista con DNI, nombres completos, correo y teléfono, y use <strong>"Importar Asistentes"</strong> para cargarlos en segundos.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <h6 class="fw-bold small mb-1"><i class="fas fa-calendar-check text-primary me-1"></i> Control de Asistencia por Sesión</h6>
                        <p class="small text-muted mb-0">Programe las clases/sesiones con fecha y hora. Durante la jornada, marque la casilla de asistencia de cada alumno. El sistema computará el porcentaje de asistencia final.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROC-SERV-03 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-serv-03" data-search-terms="certificados emision pdf descarga diploma verificacion codigo unico">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info text-white px-2 py-1">PROC-SERV-03</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Emisión y Descarga de Certificados Digitales</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Una vez concluido el curso y validada la nota mínima y porcentaje de asistencia:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li>En la lista de participantes, presione el botón <strong>"Emitir Certificado"</strong>.</li>
                <li>El sistema generará automáticamente un documento PDF en alta resolución con el membrete institucional, firmas de autoridades y un <strong>Código Único Alfanumérico</strong> antifraude.</li>
                <li>Presione <strong>"Descargar Certificado PDF"</strong> para entregar al alumno o enviar por correo electrónico.</li>
            </ul>
        </div>
    </div>

    <!-- PROC-SERV-04 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-serv-04" data-search-terms="bitacora horas asistencia tecnica consultoria especialistas actividades">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info text-white px-2 py-1">PROC-SERV-04</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Bitácora de Horas de Asistencia Técnica (Technical Hour Logs)</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Para contratos de asesoría o consultoría externa:</p>
            <ol class="small text-muted ps-3 mb-0">
                <li>En la ficha del servicio, diríjase a la pestaña <strong>"Registro de Horas"</strong>.</li>
                <li>Presione <strong>"Registrar Horas de Especialista"</strong>, seleccione el técnico/docente, fecha, número de horas invertidas y detalle de las actividades de campo.</li>
                <li>El sistema descontará las horas del saldo contratado y mostrará advertencias cuando se alcance el umbral de alerta.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-SERV-05 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-serv-05" data-search-terms="entregables conformidad cliente signoff informe final cierre servicio">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info text-white px-2 py-1">PROC-SERV-05</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Gestión de Entregables, Conformidad (Sign-Off) e Informe Final</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Finalización y liquidación del servicio técnico:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li class="mb-2"><strong>Subida de Entregables:</strong> Adjunte los informes periódicos o finales (PDF) en la pestaña <em>"Entregables"</em>.</li>
                <li class="mb-2"><strong>Conformidad del Cliente:</strong> Registre el visto bueno del cliente marcando <em>"Sign-off de Conformidad"</em> y adjuntando el acta de recepción o correo de aceptación formal.</li>
                <li><strong>Cierre y Reporte Final:</strong> Presione <strong>"Cerrar Servicio"</strong>. El sistema emitirá el <strong>Informe Técnico Final en PDF</strong> compilando todas las sesiones, participantes, horas ejecutadas y entregables aprobados.</li>
            </ul>
        </div>
    </div>
</div>
