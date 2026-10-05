{{-- MANUAL DE USUARIO: CONTABILIDAD Y TESORERÍA --}}
<div class="manual-module-content" id="module-content-accounting">
    <!-- Header del Módulo -->
    <div class="card border-0 shadow-sm mb-4 border-start-lg border-start-indigo bg-light-subtle">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-indigo text-white rounded-3 shadow-sm" style="background-color: #5800e8 !important;">
                        <i class="fas fa-book-open fa-2x"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge text-white" style="background-color: #5800e8;">MÓDULO CONTABILIDAD</span>
                            <span class="badge bg-light text-dark border">PCGE & Tesorería</span>
                            <span class="badge bg-success-subtle text-success border border-success">10 Procesos Documentados</span>
                        </div>
                        <h2 class="h4 fw-bold text-gray-800 mb-1">Manual de Usuario: Contabilidad y Tesorería</h2>
                        <p class="text-muted small mb-0">Plan Contable General Empresarial (PCGE), Libro Diario Formato SUNAT 5.1, Balance de Comprobación, Estados Financieros, Presupuestos PIA/PIM, Conciliación Bancaria y Cierres de Período.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('accounting.journal.index') }}" class="btn text-white btn-sm" style="background-color: #5800e8;">
                        <i class="fas fa-book me-1"></i> Libro Diario
                    </a>
                    <a href="{{ route('accounting.chart_of_accounts.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-sitemap me-1"></i> Plan de Cuentas
                    </a>
                    <a href="{{ route('treasury.reconciliations.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-university me-1"></i> Conciliación Bancaria
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Índice de Procesos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold" style="color: #5800e8;"><i class="fas fa-list-ol me-2"></i>Índice de Procesos de Contabilidad y Tesorería</h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-cont-01" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #5800e8;">01</span>
                        <div class="small fw-semibold text-truncate">Plan de Cuentas (PCGE)</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-cont-02" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #5800e8;">02</span>
                        <div class="small fw-semibold text-truncate">Libro Diario (SUNAT 5.1)</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-cont-03" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #5800e8;">03</span>
                        <div class="small fw-semibold text-truncate">Mayor y Balance Comprobación</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-cont-04" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #5800e8;">04</span>
                        <div class="small fw-semibold text-truncate">Estados Financieros</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-cont-05" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #5800e8;">05</span>
                        <div class="small fw-semibold text-truncate">Cierre y Reapertura Períodos</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-cont-06" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #5800e8;">06</span>
                        <div class="small fw-semibold text-truncate">Presupuestos PIA / PIM</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-cont-07" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #5800e8;">07</span>
                        <div class="small fw-semibold text-truncate">Bancos y Extractos</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-cont-08" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #5800e8;">08</span>
                        <div class="small fw-semibold text-truncate">Conciliación Bancaria</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-cont-09" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #5800e8;">09</span>
                        <div class="small fw-semibold text-truncate">Transferencias Tesorería</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-cont-10" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #5800e8;">10</span>
                        <div class="small fw-semibold text-truncate">Fallos de Asiento (Reprocesar)</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- PROC-CONT-01 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-cont-01" data-search-terms="plan de cuentas pcge catalogo subcuentas arbol estructura contable">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #5800e8;">PROC-CONT-01</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Plan de Cuentas (PCGE) y Estructura Jerárquica</h5>
            </div>
            <a href="{{ route('accounting.chart_of_accounts.index') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-sitemap me-1"></i> Ir al Plan de Cuentas
            </a>
        </div>
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-bullseye me-1" style="color: #5800e8;"></i> Objetivo:</h6>
            <p class="small text-muted">Consultar y configurar las cuentas contables bajo la estructura oficial del <strong>Plan Contable General Empresarial (PCGE)</strong> modificado por SUNAT, administrando los niveles de Elemento (1 dígito), Cuenta (2 dígitos), Subcuenta (3 dígitos), Divisionaria (4 dígitos) y Subdivisionaria (5 dígitos).</p>

            <h6 class="fw-bold text-gray-800 mb-2">Instrucciones Paso a Paso:</h6>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Diríjase a <strong>Contabilidad &rarr; Plan de Cuentas (PCGE)</strong>.</li>
                <li class="mb-2">Puede alternar entre la vista en <strong>Lista de Cuentas</strong> o el modo <strong>Árbol Jerárquico</strong> para desplegar visualmente las ramificaciones contables.</li>
                <li class="mb-2">Para crear una subcuenta analítica nueva, presione <strong>"Nueva Cuenta"</strong>:
                    <ul class="mt-1 ps-3">
                        <li>Seleccione la cuenta padre (ej. <code>104 - Cuentas corrientes en instituciones financieras</code>).</li>
                        <li>Ingrese el código numérico (ej. <code>10411</code>) y la denominación formal (ej. <em>"Banco de la Nación - Recaudación APE"</em>).</li>
                        <li>Indique la naturaleza (<code>Deudora</code> o <code>Acreedora</code>) y si admite asientos directos (solo cuentas de último nivel).</li>
                    </ul>
                </li>
                <li>Presione <strong>"Guardar Cuenta"</strong>. La nueva subcuenta estará lista para ser utilizada en asientos contables y reglas de contabilización automática.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-CONT-02 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-cont-02" data-search-terms="libro diario formato sunat 5.1 asientos debe haber partida doble voucher">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #5800e8;">PROC-CONT-02</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Libro Diario Oficial (Formato SUNAT 5.1)</h5>
            </div>
            <a href="{{ route('accounting.journal.index') }}" target="_blank" class="btn text-white btn-sm" style="background-color: #5800e8;">
                <i class="fas fa-book me-1"></i> Abrir Libro Diario
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">El Libro Diario compila cronológicamente todos los asientos del ejercicio fiscal. El sistema genera asientos de forma <strong>automatizada</strong> en tiempo real:</p>
            <div class="row g-2 mb-3 small">
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-light">
                        <span class="badge bg-success mb-1">VENTAS</span>
                        <div>Asiento automático: <code>12 Clientes / 40 IGV / 70 Ventas</code>.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-light">
                        <span class="badge bg-danger mb-1">COMPRAS</span>
                        <div>Asiento automático: <code>60 Compras / 40 IGV / 42 Proveedores</code> con amarre de destino <code>20 / 61</code>.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-light">
                        <span class="badge bg-info mb-1">COBROS Y PAGOS</span>
                        <div>Asiento automático: <code>10 Efectivo-Banco / 12 Cuentas por cobrar</code>.</div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-gray-800 mb-2">Consulta y Exportación:</h6>
            <ul class="small text-muted ps-3 mb-0">
                <li>Seleccione el período contable (mes y año). El sistema verificará la <strong>Partida Doble</strong> (<code>Total Debe = Total Haber</code>) y mostrará un distintivo verde de <span class="badge bg-success">Balanceado</span>.</li>
                <li>Haga clic en <strong>"Exportar PDF (SUNAT 5.1)"</strong> o <strong>"Exportar Excel"</strong> para obtener el formato oficial reglamentario para fiscalizaciones o auditorías.</li>
            </ul>
        </div>
    </div>

    <!-- PROC-CONT-03 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-cont-03" data-search-terms="libro mayor balance de comprobacion hoja de trabajo saldos deudor acreedor">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #5800e8;">PROC-CONT-03</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Libro Mayor y Balance de Comprobación (Hoja de Trabajo)</h5>
            </div>
            <div class="d-flex gap-1">
                <a href="{{ route('accounting.ledger.index') }}" class="btn btn-outline-primary btn-sm">Mayor</a>
                <a href="{{ route('accounting.trial_balance.index') }}" class="btn text-white btn-sm" style="background-color: #5800e8;">Balance Comprobación</a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <h6 class="fw-bold small mb-1"><i class="fas fa-book text-primary me-1"></i> Libro Mayor</h6>
                        <p class="small text-muted mb-0">Agrupa los movimientos cuenta por cuenta. Permite rastrear la historia de débitos, créditos y saldos acumulados de cualquier subcuenta del balance con un solo clic.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <h6 class="fw-bold small mb-1"><i class="fas fa-balance-scale text-success me-1"></i> Balance de Comprobación</h6>
                        <p class="small text-muted mb-0">Hoja de trabajo de 10 columnas: Sumas del Mayor (Debe/Haber), Saldos (Deudor/Acreedor), Ajustes de Inventario y Resultados por Naturaleza y Función.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROC-CONT-04 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-cont-04" data-search-terms="estados financieros balance general estado de resultados situacion financiera naturaleza funcion">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #5800e8;">PROC-CONT-04</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Generación y Análisis de Estados Financieros</h5>
            </div>
            <a href="{{ route('accounting.statements.balance_sheet') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-invoice-dollar me-1"></i> Balance General
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Emisión de reportes de alta gerencia conforme a Normas Internacionales de Información Financiera (NIIF):</p>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2"><strong>Estado de Situación Financiera (Balance General):</strong> Muestra la ecuación contable básica: <code>Activo = Pasivo + Patrimonio</code> al corte de cualquier fecha.</li>
                <li class="mb-2"><strong>Estado de Resultados por Naturaleza:</strong> Refleja la producción económica, consumo de materias primas, valor agregado, excedente bruto y resultado final.</li>
                <li class="mb-2"><strong>Estado de Resultados por Función:</strong> Agrupa por Costo de Ventas, Gastos Operativos, Gastos Administrativos y de Ventas.</li>
                <li>Todos los estados pueden imprimirse o descargarse directamente a formato PDF oficial o Excel con fórmulas.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-CONT-05 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-cont-05" data-search-terms="cierre contable mensual anual reapertura periodo bloqueo transacciones">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #5800e8;">PROC-CONT-05</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Cierre Mensual, Cierre Anual y Reapertura Contable</h5>
            </div>
            <a href="{{ route('accounting.period_closing.index') }}" target="_blank" class="btn text-white btn-sm" style="background-color: #5800e8;">
                <i class="fas fa-lock me-1"></i> Cierre de Período
            </a>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-danger py-2 px-3 small mb-3">
                <i class="fas fa-shield-alt me-1"></i> <strong>Blindaje de Seguridad:</strong> Un período en estado <code>CERRADO</code> bloquea automáticamente la creación, modificación o anulación de comprobantes de venta, compras o movimientos de almacén en esa fecha.
            </div>
            <h6 class="fw-bold text-gray-800 mb-2">Procedimiento para Cierre de Mes:</h6>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Vaya a <strong>Contabilidad &rarr; Cierre de Período</strong>.</li>
                <li class="mb-2">El sistema ejecutará la <em>Validación Previa al Cierre</em> (comprueba que no existan asientos descuadrados ni transacciones pendientes de procesar).</li>
                <li class="mb-2">Haga clic en <strong>"Ejecutar Cierre Mensual"</strong>. El período cambiará de <code>OPEN</code> a <code>CLOSED</code>.</li>
                <li><strong>Reapertura Extraordinaria:</strong> En caso de auditoría o corrección autorizada, presione <strong>"Solicitar Reapertura"</strong> indicando el motivo justificado y la firma del Contador General.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-CONT-06 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-cont-06" data-search-terms="presupuesto pia pim certificaciones compromisos devengados modificaciones">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #5800e8;">PROC-CONT-06</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Gestión Presupuestal Institucional (PIA / PIM)</h5>
            </div>
            <a href="{{ route('accounting.budgets.index') }}" target="_blank" class="btn text-white btn-sm" style="background-color: #5800e8;">
                <i class="fas fa-coins me-1"></i> Presupuestos
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Control de la ejecución del gasto y techos presupuestales:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li class="mb-2"><strong>Apertura del PIA:</strong> Registre el Presupuesto Institucional de Apertura dividiéndolo por partidas clasificadoras de gasto y áreas orgánicas.</li>
                <li class="mb-2"><strong>Modificaciones Presupuestarias (PIM):</strong> Registre créditos suplementarios o transferencias internas entre partidas para conformar el Presupuesto Institucional Modificado.</li>
                <li><strong>Control de Fases del Gasto:</strong> El sistema audita la cadena presupuestaria: <em>Certificación &rarr; Compromiso Anual &rarr; Devengado &rarr; Girado</em>, impidiendo compras que superen el saldo disponible de la partida.</li>
            </ul>
        </div>
    </div>

    <!-- PROC-CONT-07 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-cont-07" data-search-terms="cuentas bancarias extractos importacion movimientos plantilla excel csv bancos tesoreria">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #5800e8;">PROC-CONT-07</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Cuentas Bancarias e Importación de Extractos</h5>
            </div>
            <a href="{{ route('treasury.bank_accounts.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-university me-1"></i> Cuentas Bancarias
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Administración de cuentas corrientes en moneda nacional (PEN) o dólares (USD):</p>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">En <strong>Cuentas Bancarias</strong>, registre la entidad financiera (Banco de la Nación, BCP, BBVA), número de cuenta corriente y código interbancario (CCI).</li>
                <li class="mb-2">Asocie la subcuenta contable del PCGE (ej. <code>10411</code>).</li>
                <li class="mb-2">Haga clic en <strong>"Descargar Plantilla de Extracto"</strong>.</li>
                <li>Pegue los movimientos bancarios descargados de la banca por internet y presione <strong>"Importar Extracto"</strong>. Los movimientos quedarán cargados para la conciliación.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-CONT-08 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-cont-08" data-search-terms="conciliacion bancaria matching sugerencias ajustes cuenta 104 bancos extracto">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #5800e8;">PROC-CONT-08</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Conciliación Bancaria Asistida y Asientos de Ajuste</h5>
            </div>
            <a href="{{ route('treasury.reconciliations.index') }}" target="_blank" class="btn text-white btn-sm" style="background-color: #5800e8;">
                <i class="fas fa-check-double me-1"></i> Conciliación Bancaria
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Compara los movimientos contables del Libro Diario vs los cargos y abonos del banco:</p>
            <div class="p-3 bg-light rounded small mb-3">
                <div class="fw-bold text-dark mb-1">Mecanismo Asistido:</div>
                <ul class="ps-3 mb-0">
                    <li>Presione <strong>"Ver Sugerencias Automáticas"</strong>: el algoritmo emparejará transacciones que coincidan en número de operación bancaria, fecha y monto exacto.</li>
                    <li>Para cargos no registrados en el sistema (ej. comisiones bancarias, ITF, portes), presione <strong>"Generar Asiento de Ajuste"</strong>: se creará de inmediato el gasto bancario (cuenta <code>67</code>) saldando la partida.</li>
                    <li>Cuando la diferencia llegue a <code>S/ 0.00</code>, presione <strong>"Cerrar Conciliación del Mes"</strong>.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- PROC-CONT-09 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-cont-09" data-search-terms="transferencias internas tesoreria banco a banco caja a banco transferencias cut">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #5800e8;">PROC-CONT-09</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Transferencias Internas de Tesorería (Banco, Caja y CUT)</h5>
            </div>
            <a href="{{ route('treasury.transfers.index') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-exchange-alt me-1"></i> Transferencias Tesorería
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Garantiza que el flujo de efectivo entre cuentas institucionales genere su correspondiente asiento contable:</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100 bg-white">
                        <i class="fas fa-university text-primary fa-2x mb-2"></i>
                        <h6 class="fw-bold mb-1">Banco a Banco</h6>
                        <p class="small text-muted mb-0">Traspaso de fondos entre cuentas corrientes de la institución. Asiento: <code>104(Destino) / 104(Origen)</code>.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100 bg-white">
                        <i class="fas fa-cash-register text-success fa-2x mb-2"></i>
                        <h6 class="fw-bold mb-1">Caja a Banco</h6>
                        <p class="small text-muted mb-0">Depósito bancario de la recaudación física acumulada en ventanilla de caja. Asiento: <code>104 / 101</code>.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100 bg-white">
                        <i class="fas fa-landmark text-danger fa-2x mb-2"></i>
                        <h6 class="fw-bold mb-1">Transferencia a CUT</h6>
                        <p class="small text-muted mb-0">Remesa formal de RDR hacia la Cuenta Única del Tesoro en el Banco de la Nación conforme a ley.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROC-CONT-10 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-cont-10" data-search-terms="fallos de asiento posting failures reprocesar errores transacciones no contabilizadas">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #5800e8;">PROC-CONT-10</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Monitoreo y Reprocesamiento de Fallos de Asiento (Posting Failures)</h5>
            </div>
            <a href="{{ route('accounting.failures.index') }}" target="_blank" class="btn text-white btn-sm" style="background-color: #5800e8;">
                <i class="fas fa-tools me-1"></i> Ver Fallos de Asiento
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Si un comprobante comercial se emite cuando una cuenta contable fue dada de baja o existe una desconfiguración temporal de reglas, el sistema aísla la transacción en esta bandeja sin interrumpir la atención al cliente:</p>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Acceda a <strong>Contabilidad &rarr; Fallos de Asiento</strong>.</li>
                <li class="mb-2">La tabla detallará el documento comercial afectado (ej. <em>Factura F001-00042</em>) y la causa del fallo (ej. <em>"Falta asignar cuenta contable para el producto"</em>).</li>
                <li class="mb-2">Corrija la configuración requerida en el Plan de Cuentas o Catálogo.</li>
                <li>Presione <strong>"Reprocesar Todos los Fallos"</strong>. El sistema regenerará los asientos pendientes y los integrará limpiamente al Libro Diario.</li>
            </ol>
        </div>
    </div>
</div>
