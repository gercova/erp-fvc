{{-- MANUAL DE USUARIO: ACTIVIDADES PRODUCTIVAS Y EMPRESARIALES (APE) --}}
<div class="manual-module-content" id="module-content-ape">
    <!-- Header del Módulo -->
    <div class="card border-0 shadow-sm mb-4 border-start-lg border-start-primary bg-light-subtle">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary text-white rounded-3 shadow-sm">
                        <i class="fas fa-briefcase fa-2x"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary text-white">MÓDULO APE</span>
                            <span class="badge bg-light text-dark border">Bloque A1 / A2</span>
                            <span class="badge bg-success-subtle text-success border border-success">7 Procesos Documentados</span>
                        </div>
                        <h2 class="h4 fw-bold text-gray-800 mb-1">Manual de Usuario: Actividades Productivas y Empresariales (APE)</h2>
                        <p class="text-muted small mb-0">Gestión de unidades generadoras de ingresos, centros de costo, tesorería RDR, transferencias a la CUT y flujo de aprobación multiescalón.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('productive_activities.index') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> Abrir Catálogo APE
                    </a>
                    <a href="{{ route('productive_activities.cost_center.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-chart-line me-1"></i> Centro de Costos
                    </a>
                    <a href="{{ route('productive_activities.rdr.index') }}" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-university me-1"></i> Módulo RDR / CUT
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Índice Rápido del Módulo -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list-ol me-2"></i>Índice de Procesos de Actividades (APE)</h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-ape-01" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill">01</span>
                        <div class="small fw-semibold text-truncate">Registro de Actividad Productiva</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-ape-02" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill">02</span>
                        <div class="small fw-semibold text-truncate">Aprobación Institucional de APE</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-ape-03" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill">03</span>
                        <div class="small fw-semibold text-truncate">Panel de Centro de Costos y Matriz</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-ape-04" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill">04</span>
                        <div class="small fw-semibold text-truncate">Ingresos, Egresos y Vinculación Core</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-ape-05" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill">05</span>
                        <div class="small fw-semibold text-truncate">Transferencias a la Cuenta CUT</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-ape-06" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill">06</span>
                        <div class="small fw-semibold text-truncate">Préstamos Internos y Habilitaciones</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#proc-ape-07" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill">07</span>
                        <div class="small fw-semibold text-truncate">Conciliación RDR y Cierre Mensual</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- PROCESO 1: Registro de Nueva Actividad Productiva -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-ape-01" data-search-terms="registro catalogo ape actividad crear nueva sector area jefe">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-2 py-1">PROC-APE-01</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Registro y Catalogación de Actividad Productiva</h5>
            </div>
            <a href="{{ route('productive_activities.create') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Ir a Nuevo Registro
            </a>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <h6 class="fw-bold text-primary mb-2"><i class="fas fa-bullseye me-1"></i> Objetivo</h6>
                        <p class="small text-muted mb-0">Habilitar formalmente en el sistema una unidad productiva o de negocio generadora de recursos directamente recaudados (RDR), configurando sus responsables, área jerárquica y metas de ejecución.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <h6 class="fw-bold text-success mb-2"><i class="fas fa-user-shield me-1"></i> Roles y Prerrequisitos</h6>
                        <ul class="small text-muted mb-0 ps-3">
                            <li><strong>Roles requeridos:</strong> Administrador, Jefe de Área o Dirección General.</li>
                            <li><strong>Prerrequisito:</strong> Debe existir al menos un Área registrada en el sistema y usuarios con rol activo.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-gray-800 mb-3"><i class="fas fa-shoe-prints me-2 text-primary"></i>Pasos para la Ejecución:</h6>
            <div class="timeline-steps">
                <div class="step-item d-flex gap-3 mb-3">
                    <div class="step-badge bg-primary text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">1</div>
                    <div>
                        <div class="fw-bold text-dark">Navegación al formulario</div>
                        <div class="small text-muted">Diríjase al menú lateral izquierdo &rarr; <strong>Actividades (APE)</strong> &rarr; <strong>Catálogo APE</strong>. En la esquina superior derecha, haga clic en el botón azul <strong>"Nueva Actividad"</strong>.</div>
                    </div>
                </div>

                <div class="step-item d-flex gap-3 mb-3">
                    <div class="step-badge bg-primary text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">2</div>
                    <div>
                        <div class="fw-bold text-dark">Completar la Ficha General de la Actividad</div>
                        <div class="small text-muted">Llene los siguientes campos obligatorios del formulario:</div>
                        <ul class="small text-muted mt-1 ps-3">
                            <li><strong>Nombre de la Actividad:</strong> Denominación formal (ej. <em>"Producción y Venta de Cacao Clon CCN-51"</em> o <em>"Centro de Producción de Truchas"</em>).</li>
                            <li><strong>Tipo de Actividad:</strong> Seleccione la categoría económica:
                                <span class="badge bg-success-subtle text-success">Agrícola</span>,
                                <span class="badge bg-dark-subtle text-dark">Forestal</span>,
                                <span class="badge bg-info-subtle text-info">Piscícola</span>,
                                <span class="badge bg-warning-subtle text-dark">Pecuario</span>,
                                <span class="badge bg-primary-subtle text-primary">Institucional</span> o
                                <span class="badge bg-secondary-subtle text-secondary">Servicios</span>.</li>
                            <li><strong>Área Perteneciente:</strong> Seleccione el Área institucional o departamento responsable.</li>
                            <li><strong>Responsable / Jefe a Cargo:</strong> Funcionario titular asignado al proyecto.</li>
                            <li><strong>Fuente de Financiamiento Predeterminada:</strong> Seleccione la cuenta RDR o tesoro asignado.</li>
                        </ul>
                    </div>
                </div>

                <div class="step-item d-flex gap-3 mb-3">
                    <div class="step-badge bg-primary text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">3</div>
                    <div>
                        <div class="fw-bold text-dark">Definición de Fechas y Metas Operativas</div>
                        <div class="small text-muted">Establezca la fecha de inicio, fecha estimada de fin, presupuesto inicial proyectado, porcentaje inicial de avance físico (normalmente 0%) y descripción detallada del plan de negocio.</div>
                    </div>
                </div>

                <div class="step-item d-flex gap-3 mb-3">
                    <div class="step-badge bg-primary text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">4</div>
                    <div>
                        <div class="fw-bold text-dark">Guardar y Confirmar</div>
                        <div class="small text-muted">Presione el botón <strong>"Registrar Actividad"</strong>. El sistema creará automáticamente el registro, inicializará el Kardex/Centro de Costos asociado y generará una entrada en la bitácora de seguimiento.</div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mt-2 mb-0">
                <i class="fas fa-info-circle fa-lg"></i>
                <div><strong>Regla de Sistema:</strong> Una vez creada la actividad, quedará en estado inicial <code>ACTIVA</code> pero requerirá pasar por el flujo de firma institucional para consolidar transferencias a la CUT.</div>
            </div>
        </div>
    </div>

    <!-- PROCESO 2: Aprobación Institucional de APE -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-ape-02" data-search-terms="aprobacion institucional firmas ape autorizacion director visto bueno">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-2 py-1">PROC-APE-02</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Flujo de Aprobación Institucional de Actividades</h5>
            </div>
            <a href="{{ route('productive_activities.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-list me-1"></i> Ver Bandeja de Actividades
            </a>
        </div>
        <div class="card-body p-4">
            <div class="p-3 bg-light rounded-3 mb-3">
                <h6 class="fw-bold text-primary mb-1"><i class="fas fa-signature me-1"></i> Propósito del Flujo</h6>
                <p class="small text-muted mb-0">Garantizar que toda actividad productiva cuente con la cadena de firmas reglamentarias: <em>Jefe de Unidad/Área &rarr; Oficina de Administración &rarr; Dirección General</em>.</p>
            </div>

            <h6 class="fw-bold text-gray-800 mb-3"><i class="fas fa-shoe-prints me-2 text-primary"></i>Paso a Paso:</h6>
            <ol class="small text-muted ps-3 mb-3">
                <li class="mb-2">En el <strong>Catálogo APE</strong>, identifique la fila de la actividad recién registrada. Si la columna <em>"Aprobación"</em> muestra el estado <span class="badge bg-light text-muted border">Sin Enviar</span>, haga clic en el botón con ícono de documento y pluma <i class="fas fa-file-signature text-primary"></i> (<strong>"Enviar a Aprobación Institucional"</strong>).</li>
                <li class="mb-2">El sistema creará las etapas de aprobación secuencial y notificará a las autoridades mediante el sistema de alertas de cabecera.</li>
                <li class="mb-2">La autoridad ingresa a la bandeja de aprobaciones, revisa la ficha técnica y puede emitir su dictamen:
                    <span class="badge bg-success">Aprobar</span> con comentarios o
                    <span class="badge bg-danger">Observar / Rechazar</span> detallando el motivo de corrección.</li>
                <li>Al recibir el visto bueno final de la Dirección General, la actividad adquiere el distintivo <span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i> Aprobado</span> y queda blindada para cierres contables formales.</li>
            </ol>
        </div>
    </div>

    <!-- PROCESO 3: Panel de Centro de Costos -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-ape-03" data-search-terms="centro de costos cross tabulation matriz ingresos egresos reporte excel ape">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-2 py-1">PROC-APE-03</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Panel de Centro de Costos y Matriz Analítica (Cross-Tabulation)</h5>
            </div>
            <a href="{{ route('productive_activities.cost_center.index') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-table me-1"></i> Abrir Centro de Costos
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">El módulo de Centro de Costos permite examinar la ejecución financiera mes a mes de cada unidad APE, comparando ingresos brutos generados vs gastos operativos y determinando el superávit o déficit neto.</p>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100 bg-white">
                        <i class="fas fa-filter text-primary fa-2x mb-2"></i>
                        <h6 class="fw-bold mb-1">1. Filtrado Dinámico</h6>
                        <p class="small text-muted mb-0">Seleccione el año de ejercicio (ej. 2026), el tipo de actividad y la fuente de fondos. La tabla recalculará en tiempo real sin recargar la página.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100 bg-white">
                        <i class="fas fa-file-excel text-success fa-2x mb-2"></i>
                        <h6 class="fw-bold mb-1">2. Exportación Oficial</h6>
                        <p class="small text-muted mb-0">Haga clic en <strong>"Exportar Resumen Excel"</strong> o <strong>"Informe Detallado Multi-Hoja"</strong> para obtener el libro contable con fórmulas financieras listas para auditoría.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100 bg-white">
                        <i class="fas fa-file-import text-info fa-2x mb-2"></i>
                        <h6 class="fw-bold mb-1">3. Carga de Históricos</h6>
                        <p class="small text-muted mb-0">Si cuenta con balances o ejecuciones previas de años anteriores en hojas de cálculo, use la opción de importación para poblar el histórico.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROCESO 4: Ingresos, Egresos y Vinculación Core -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-ape-04" data-search-terms="movimientos ingresos egresos comprobantes transacciones ape facturas compras ventas">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-2 py-1">PROC-APE-04</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Registro de Movimientos (Ingresos y Egresos) y Vinculación Core</h5>
            </div>
            <a href="{{ route('productive_activities.transactions.index') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-exchange-alt me-1"></i> Ir a Transacciones
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Cada egreso (compra de fertilizantes, semillas, mano de obra, mantenimiento) o ingreso de la actividad debe registrarse para reflejar el estado real de la cuenta.</p>
            
            <h6 class="fw-bold text-gray-800 mb-2"><i class="fas fa-link me-2 text-primary"></i>Vinculación Automática con Documentos Core del Sistema:</h6>
            <div class="bg-light p-3 rounded mb-3 small">
                <p class="mb-2">El ERP permite asociar transacciones directamente con comprobantes ya emitidos en ventas o compras:</p>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <div class="p-2 border rounded bg-white">
                            <span class="badge bg-success me-1">INGRESOS</span>
                            Busque por número de Boleta, Factura electrónica o Nota de Venta para imputar la recaudación a la actividad.
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-2 border rounded bg-white">
                            <span class="badge bg-danger me-1">EGRESOS</span>
                            Enlace facturas de proveedores registradas en el módulo de Compras para justificar gastos de campo.
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-gray-800 mb-2">Instrucciones para Registro Manual:</h6>
            <ol class="small text-muted ps-3 mb-0">
                <li>Haga clic en <strong>"Nuevo Movimiento"</strong> en el listado de transacciones.</li>
                <li>Seleccione la actividad APE receptora o emisora.</li>
                <li>Seleccione el tipo (<code>Ingreso</code> o <code>Egreso</code>) y la fecha de la operación.</li>
                <li>Ingrese el monto en Soles (S/), concepto o glosa explicativa, y tipo de comprobante.</li>
                <li>Guarde el registro; la matriz financiera del Centro de Costos se actualizará inmediatamente.</li>
            </ol>
        </div>
    </div>

    <!-- PROCESO 5: Transferencias a la CUT -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-ape-05" data-search-terms="cut rdr cuenta unica del tesoro transferencias banco nacion deposito remesa ape">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-2 py-1">PROC-APE-05</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Transferencias a la Cuenta Única del Tesoro (CUT)</h5>
            </div>
            <a href="{{ route('productive_activities.rdr.index') }}" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="fas fa-university me-1"></i> Ir a Módulo RDR / CUT
            </a>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-warning py-2 px-3 small mb-3">
                <i class="fas fa-exclamation-triangle me-1"></i> <strong>Marco Normativo:</strong> Los fondos recaudados en efectivo o cuenta corriente de recaudación deben remesarse periódicamente a la CUT (Banco de la Nación) conforme a las directivas de tesorería del sector público.
            </div>

            <h6 class="fw-bold text-gray-800 mb-2">Pasos para registrar la transferencia al CUT:</h6>
            <div class="timeline-steps">
                <div class="step-item d-flex gap-3 mb-2">
                    <div class="step-badge bg-info text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">1</div>
                    <div class="small text-muted">Ingrese a <strong>Actividades (APE) &rarr; Módulo RDR / CUT</strong> y ubique la pestaña <strong>"Transferencias al CUT"</strong>.</div>
                </div>
                <div class="step-item d-flex gap-3 mb-2">
                    <div class="step-badge bg-info text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">2</div>
                    <div class="small text-muted">Haga clic en <strong>"Registrar Remesa al CUT"</strong>.</div>
                </div>
                <div class="step-item d-flex gap-3 mb-2">
                    <div class="step-badge bg-info text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">3</div>
                    <div class="small text-muted">Seleccione la actividad generadora de los fondos, la cuenta bancaria de origen y el monto a transferir.</div>
                </div>
                <div class="step-item d-flex gap-3 mb-2">
                    <div class="step-badge bg-info text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">4</div>
                    <div class="small text-muted">Ingrese el <strong>Número de Operación Bancaria</strong> del voucher del Banco de la Nación y adjunte opcionalmente el comprobante digitalizado en formato PDF o imagen.</div>
                </div>
                <div class="step-item d-flex gap-3 mb-2">
                    <div class="step-badge bg-info text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">5</div>
                    <div class="small text-muted">Al confirmar, el sistema rebajará el saldo en caja/banco local y acreditará el registro de remesas enviadas a la CUT del ejercicio.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROCESO 6: Préstamos Internos y Habilitaciones -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-ape-06" data-search-terms="prestamos internos habilitaciones amortizacion fondos actividades ape">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-2 py-1">PROC-APE-06</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Préstamos Internos, Habilitaciones de Fondos y Amortizaciones</h5>
            </div>
            <a href="{{ route('productive_activities.rdr.index') }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-hand-holding-usd me-1"></i> Ver Préstamos
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">En ocasiones, una actividad con saldo de caja financia temporalmente a otra actividad que iniciará campaña o requiere compra urgente de insumos. Este flujo registra y supervisa la devolución del préstamo.</p>
            <ul class="small text-muted ps-3 mb-3">
                <li><strong>Apertura del Préstamo:</strong> En la pestaña <em>"Préstamos Internos"</em>, ingrese el monto prestado, la actividad acreedora, el usuario o actividad beneficiaria y la fecha de compromiso de devolución.</li>
                <li><strong>Autorización:</strong> El sistema exige registrar el visto bueno de la jefatura de administración.</li>
                <li><strong>Amortizaciones:</strong> Conforme la actividad beneficiaria venda sus productos, use el botón <strong>"Registrar Amortización"</strong> ingresando el importe devuelto. El préstamo cambiará automáticamente de estado: <code>VIGENTE</code> &rarr; <code>AMORTIZADO PARCIAL</code> &rarr; <code>CANCELADO TOTAL</code>.</li>
            </ul>
        </div>
    </div>

    <!-- PROCESO 7: Conciliación RDR y Cierre Mensual -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-ape-07" data-search-terms="conciliacion bancaria rdr cierre mensual periodo extracto saldo diferencia ape">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-2 py-1">PROC-APE-07</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Conciliación Bancaria RDR y Cierre de Período Mensual</h5>
            </div>
            <a href="{{ route('productive_activities.rdr.index') }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-balance-scale me-1"></i> Panel de Conciliación
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Al finalizar cada mes calendario, el responsable de tesorería y el administrador de actividades productivas deben conciliar los saldos de cada cuenta bancaria RDR:</p>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered small">
                    <thead class="table-light">
                        <tr>
                            <th>Concepto</th>
                            <th>Fórmula del Sistema</th>
                            <th>Validación Exigida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold">Saldo Calculado por Sistema</td>
                            <td><code>Saldo Inicial + Total Ingresos - Total Egresos</code></td>
                            <td>Automático de los comprobantes registrados</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Saldo según Extracto Bancario</td>
                            <td>Ingresado por el usuario según estado de cuenta bancario</td>
                            <td>Debe coincidir con el estado oficial del banco</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Diferencia de Conciliación</td>
                            <td><code>Saldo Banco - Saldo Sistema</code></td>
                            <td>Debe ser <strong>S/ 0.00</strong> para autorizar el cierre formal</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h6 class="fw-bold text-gray-800 mb-2">Cierre y Envío de Acta:</h6>
            <p class="small text-muted mb-0">Cuando la diferencia es cero, presione <strong>"Crear Cierre de Período"</strong> y luego <strong>"Enviar a Aprobación Institucional"</strong>. El mes quedará cerrado para modificaciones garantizando la intangibilidad de la información contable.</p>
        </div>
    </div>
</div>
