{{-- MANUAL DE USUARIO: AGROPECUARIA Y FORESTAL --}}
<div class="manual-module-content" id="module-content-agrolivestock">
    <!-- Header del Módulo -->
    <div class="card border-0 shadow-sm mb-4 border-start-lg border-start-teal bg-light-subtle">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-teal text-white rounded-3 shadow-sm" style="background-color: #00ac69 !important;">
                        <i class="fas fa-seedling fa-2x"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge text-white" style="background-color: #00ac69;">MÓDULO AGROPECUARIO Y FORESTAL</span>
                            <span class="badge bg-light text-dark border">Gestión de Campo y Hato</span>
                            <span class="badge bg-success-subtle text-success border border-success">8 Procesos Documentados</span>
                        </div>
                        <h2 class="h4 fw-bold text-gray-800 mb-1">Manual de Usuario: Agropecuaria y Forestal</h2>
                        <p class="text-muted small mb-0">Catastro de predios y parcelas, viveros de propagación forestal, trazabilidad ganadera individual, control sanitario, pesaje rápido y conciliación de balanza.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('agrolivestock.plots.index') }}" class="btn text-white btn-sm" style="background-color: #00ac69;">
                        <i class="fas fa-external-link-alt me-1"></i> Parcelas y Lotes
                    </a>
                    <a href="{{ route('agrolivestock.nurseries.index') }}" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-tree me-1"></i> Viveros Forestales
                    </a>
                    <a href="{{ route('agrolivestock.livestock.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-paw me-1"></i> Manejo Pecuario
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Índice de Procesos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold" style="color: #00ac69;"><i class="fas fa-list-ol me-2"></i>Índice de Procesos de Agro y Forestal</h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-agro-01" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #00ac69;">01</span>
                        <div class="small fw-semibold text-truncate">Parcelas y Lotes</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-agro-02" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #00ac69;">02</span>
                        <div class="small fw-semibold text-truncate">Plantaciones y Cultivos</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-agro-03" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #00ac69;">03</span>
                        <div class="small fw-semibold text-truncate">Viveros Forestales</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-agro-04" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #00ac69;">04</span>
                        <div class="small fw-semibold text-truncate">Manejo del Hato Pecuario</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-agro-05" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #00ac69;">05</span>
                        <div class="small fw-semibold text-truncate">Eventos Sanitarios / Bajas</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-agro-06" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #00ac69;">06</span>
                        <div class="small fw-semibold text-truncate">Carga Rápida de Cosechas</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-agro-07" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #00ac69;">07</span>
                        <div class="small fw-semibold text-truncate">Conciliación Balanza/Factura</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-agro-08" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge text-white rounded-pill" style="background-color: #00ac69;">08</span>
                        <div class="small fw-semibold text-truncate">Estructura e Interanual</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- PROC-AGRO-01 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-agro-01" data-search-terms="parcelas lotes catastro predios hectareas sector fundo coordenadas">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #00ac69;">PROC-AGRO-01</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Catálogo de Parcelas, Predios y Lotes Agrícolas</h5>
            </div>
            <a href="{{ route('agrolivestock.plots.index') }}" target="_blank" class="btn btn-outline-success btn-sm">
                <i class="fas fa-map-marked-alt me-1"></i> Ver Parcelas
            </a>
        </div>
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-bullseye me-1" style="color: #00ac69;"></i> Objetivo:</h6>
            <p class="small text-muted">Georreferenciar y registrar formalmente cada unidad de terreno del fundo, centro experimental o estación agronómica, indicando su extensión física, tipo de suelo y disponibilidad hídrica.</p>

            <h6 class="fw-bold text-gray-800 mb-2">Instrucciones Paso a Paso:</h6>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Diríjase a <strong>Agro y Forestal &rarr; Parcelas y Lotes</strong> y haga clic en <strong>"Nueva Parcela"</strong>.</li>
                <li class="mb-2">Seleccione la Actividad APE encargada de la explotación agronómica.</li>
                <li class="mb-2">Ingrese el <strong>Código de Parcela</strong> (ej. <code>PARC-01-FUNDO-A</code>), nombre del lote y sector geográfico o paraje.</li>
                <li class="mb-2">Especifique el <strong>Área Total en Hectáreas</strong> (ej. <code>2.50 ha</code>), tipo de suelo (Franco-arenoso, arcilloso, etc.) y sistema de riego (Gravedad, goteo, aspersión, secano).</li>
                <li class="mb-2">Indique el estado operativo: <code>En Producción</code>, <code>Descanso / Barbecho</code> o <code>En Preparación</code>.</li>
                <li>Presione <strong>"Registrar Parcela"</strong>. La parcela quedará disponible para asignar plantaciones o rotación de cultivos.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-AGRO-02 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-agro-02" data-search-terms="plantaciones cultivos especie variedad densidad siembra fecha cosecha">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #00ac69;">PROC-AGRO-02</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Registro de Plantaciones y Cultivos en Parcela</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Permite asociar especies vegetales permanentes (frutales, cacao, café) o transitorias (maíz, hortalizas) a cada lote:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li class="mb-2">Ingrese al detalle de la parcela (ícono <i class="fas fa-eye text-primary"></i>).</li>
                <li class="mb-2">Presione <strong>"Registrar Cultivo / Plantación"</strong>.</li>
                <li class="mb-2">Indique la especie botánica (ej. <em>Theobroma cacao</em>), variedad comercial (ej. <em>CCN-51</em>), fecha de siembra e instalación y densidad de siembra (plantas/ha).</li>
                <li>Establezca la fecha probable de inicio de cosecha. El sistema alertará al equipo agronómico cuando el cultivo entre en etapa fenológica de maduración.</li>
            </ul>
        </div>
    </div>

    <!-- PROC-AGRO-03 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-agro-03" data-search-terms="viveros forestales plantas caoba pino eucalipto propagacion germinacion">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #00ac69;">PROC-AGRO-03</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Viveros Forestales y Camas de Propagación</h5>
            </div>
            <a href="{{ route('agrolivestock.nurseries.index') }}" target="_blank" class="btn btn-outline-success btn-sm">
                <i class="fas fa-tree me-1"></i> Ir a Viveros
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Control de la producción de plantones forestales y especies nativas para reforestación o venta institucional:</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <div class="fw-bold small mb-1">1. Registro de Camas y Lotes de Almácigo</div>
                        <p class="small text-muted mb-0">Ingrese el nombre del vivero, código de cama, especie forestal (ej. Caoba, Pino tecunumanii, Cedro rosado, Eucalipto urograndis) y fecha de siembra de semillas.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <div class="fw-bold small mb-1">2. Control de Germinación y Descarte</div>
                        <p class="small text-muted mb-0">Registre el número de semillas sembradas, porcentaje de germinación, repiques a bolsas y bajas por mortandad para obtener el stock neto de plantones listos para campo.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROC-AGRO-04 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-agro-04" data-search-terms="ganado pecuario vacunos porcinos cuyes aves arete raza peso hato">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #00ac69;">PROC-AGRO-04</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Manejo del Hato Ganadero y Trazabilidad Individual</h5>
            </div>
            <a href="{{ route('agrolivestock.livestock.index') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-paw me-1"></i> Ir a Manejo Pecuario
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Aplica a bovinos, porcinos, ovinos, cuyes o aves de postura/carne:</p>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Acceda a <strong>Agro y Forestal &rarr; Manejo Pecuario</strong> &rarr; botón <strong>"Nuevo Registro Animal"</strong>.</li>
                <li class="mb-2">Seleccione la especie animal y asigne el <strong>Código de Arete o Identificación Oficial</strong>.</li>
                <li class="mb-2">Especifique raza (ej. <em>Brown Swiss</em>, <em>Holstein</em>, <em>Landrace</em>), sexo, fecha de nacimiento, procedencia y peso vivo inicial.</li>
                <li>Asigne el estado productivo (<code>Lactante</code>, <code>En Crecimiento</code>, <code>Engorde</code>, <code>Preñada</code>, <code>Reproductor</code>).</li>
            </ol>
        </div>
    </div>

    <!-- PROC-AGRO-05 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-agro-05" data-search-terms="eventos sanitarios vacunacion tratamientos partos bajas decesos pecuario">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #00ac69;">PROC-AGRO-05</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Eventos Sanitarios, Reproductivos, Pesajes y Registro de Bajas</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Mantenga el historial clínico y zootécnico al día:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li class="mb-2"><strong>Eventos Sanitarios:</strong> Ingrese a la ficha del animal y registre vacunaciones (Fiebre aftosa, Carbunco), desparasitaciones periódicas o tratamientos con antibióticos especificando el período de retiro de leche/carne.</li>
                <li class="mb-2"><strong>Eventos Reproductivos:</strong> Registre montas naturales o inseminación artificial con pajilla, diagnóstico de preñez y fecha probable de parto.</li>
                <li><strong>Registro de Bajas / Decesos:</strong> Si un animal fallece, registre la fecha, causa probable de muerte o informe de necropsia; el animal pasará a estado inactivo sin alterar el histórico contable.</li>
            </ul>
        </div>
    </div>

    <!-- PROC-AGRO-06 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-agro-06" data-search-terms="carga rapida cosechas campo balanza pesaje kilos cajas recoleccion">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #00ac69;">PROC-AGRO-06</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Carga Rápida de Cosechas en Campo (Quick Entry)</h5>
            </div>
            <a href="{{ route('agrolivestock.harvests.quick_entry') }}" target="_blank" class="btn text-white btn-sm" style="background-color: #00ac69;">
                <i class="fas fa-bolt me-1"></i> Carga Rápida Cosechas
            </a>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-info py-2 px-3 small mb-3">
                <i class="fas fa-mobile-alt me-1"></i> Diseñado con interfaz compacta para que los jefes de campo o capataces anoten los pesajes de las balanzas directamente en tablet o teléfono móvil.
            </div>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Ingrese a <strong>Carga Rápida Cosechas</strong>.</li>
                <li class="mb-2">Seleccione la parcela de recolección y la cuadrilla o cosechador.</li>
                <li class="mb-2">Digite el número de jabas/sacos y el peso neto en kilos (kg).</li>
                <li>Presione <strong>"Registrar Pesaje de Campo"</strong>. El dato se almacena al instante y alimenta automáticamente el módulo de Producción sin duplicar el trabajo administrativo.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-AGRO-07 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-agro-07" data-search-terms="conciliacion balanza factura descalce merma deshidratacion pesaje acopiador">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #00ac69;">PROC-AGRO-07</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Conciliación Balanza de Campo vs Factura Comercial</h5>
            </div>
            <a href="{{ route('agrolivestock.reports.reconciliation') }}" target="_blank" class="btn btn-outline-success btn-sm">
                <i class="fas fa-balance-scale-right me-1"></i> Conciliación Balanza
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Resuelve la discrepancia habitual entre el pesaje en el fundo y el pesaje final liquidado por el comprador:</p>
            <div class="p-3 bg-light rounded small mb-0">
                <p class="mb-1"><strong>Flujo de Conciliación:</strong></p>
                <ol class="ps-3 mb-0">
                    <li>El sistema toma los tickets de pesaje de la balanza de campo (ej. <code>5,200 kg</code>).</li>
                    <li>Busque la Factura o Boleta emitida al cliente final (ej. <code>5,050 kg</code>).</li>
                    <li>Presione <strong>"Vincular Factura"</strong>: el sistema calcula la diferencia (<code>-150 kg</code>) y clasifica automáticamente si se trata de <em>merma permitida por deshidratación en flete</em> o una <em>discrepancia que amerita reclamo comercial</em>.</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- PROC-AGRO-08 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-agro-08" data-search-terms="estructura de costos comparativo interanual reportes hectarea rendimiento">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #00ac69;">PROC-AGRO-08</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Reportes de Estructura de Costos y Comparativo Interanual</h5>
            </div>
            <div class="d-flex gap-1">
                <a href="{{ route('agrolivestock.reports.cost_breakdown') }}" class="btn btn-outline-success btn-sm">Costos</a>
                <a href="{{ route('agrolivestock.reports.year_over_year') }}" class="btn btn-outline-primary btn-sm">Interanual</a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <h6 class="fw-bold small mb-1"><i class="fas fa-layer-group text-success me-1"></i> Estructura de Costos por Hectárea</h6>
                        <p class="small text-muted mb-0">Desglosa el porcentaje de inversión en: Maquinaria y preparación de suelos (%), Semillas y fertilizantes (%), Mano de obra en labores culturales (%) y Empaque/Transporte (%).</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <h6 class="fw-bold small mb-1"><i class="fas fa-chart-bar text-primary me-1"></i> Comparativo Interanual de Rendimiento</h6>
                        <p class="small text-muted mb-0">Compara los kilogramos por hectárea obtenidos en el año actual frente a los últimos 3 años para medir el impacto de mejoras tecnológicas o cambio climático.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
