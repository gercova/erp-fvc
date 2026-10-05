{{-- MANUAL DE USUARIO: PRODUCCIÓN Y COMERCIALIZACIÓN --}}
<div class="manual-module-content" id="module-content-production">
    <!-- Header del Módulo -->
    <div class="card border-0 shadow-sm mb-4 border-start-lg border-start-warning bg-light-subtle">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-warning text-dark rounded-3 shadow-sm">
                        <i class="fas fa-industry fa-2x"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-warning text-dark">MÓDULO PRODUCCIÓN</span>
                            <span class="badge bg-light text-dark border">Transformación y Comercialización</span>
                            <span class="badge bg-success-subtle text-success border border-success">8 Procesos Documentados</span>
                        </div>
                        <h2 class="h4 fw-bold text-gray-800 mb-1">Manual de Usuario: Producción y Comercialización</h2>
                        <p class="text-muted small mb-0">Planificación de campañas agrícolas y pecuarias, fases de cultivo, costeo de mano de obra por jornales, consumo de insumos, cosechas, catálogo de ventas y preventas.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('production.plans.index') }}" class="btn btn-warning btn-sm text-dark fw-semibold">
                        <i class="fas fa-external-link-alt me-1"></i> Planes y Campañas
                    </a>
                    <a href="{{ route('production.harvests.index') }}" class="btn btn-outline-warning btn-sm text-dark">
                        <i class="fas fa-apple-alt me-1"></i> Cosechas
                    </a>
                    <a href="{{ route('production.profitability.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-chart-line me-1"></i> Rentabilidad
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Índice de Procesos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-list-ol me-2 text-warning"></i>Índice de Procesos de Producción</h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-prod-01" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark rounded-pill">01</span>
                        <div class="small fw-semibold text-truncate">Planes y Campañas</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-prod-02" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark rounded-pill">02</span>
                        <div class="small fw-semibold text-truncate">Fases y Mano de Obra</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-prod-03" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark rounded-pill">03</span>
                        <div class="small fw-semibold text-truncate">Insumos y Materiales</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-prod-04" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark rounded-pill">04</span>
                        <div class="small fw-semibold text-truncate">Consumos en Campo</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-prod-05" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark rounded-pill">05</span>
                        <div class="small fw-semibold text-truncate">Cosechas y Rendimiento</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-prod-06" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark rounded-pill">06</span>
                        <div class="small fw-semibold text-truncate">Publicar a Catálogo Central</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-prod-07" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark rounded-pill">07</span>
                        <div class="small fw-semibold text-truncate">Preventas y Pedidos</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="#proc-prod-08" class="text-decoration-none list-group-item list-group-item-action border rounded p-2 d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark rounded-pill">08</span>
                        <div class="small fw-semibold text-truncate">Análisis de Rentabilidad</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- PROC-PROD-01 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-prod-01" data-search-terms="planes campanas de produccion planificar siembra metas kilos toneladas">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark px-2 py-1">PROC-PROD-01</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Creación y Configuración de Planes / Campañas de Producción</h5>
            </div>
            <a href="{{ route('production.plans.create') }}" target="_blank" class="btn btn-outline-warning btn-sm text-dark">
                <i class="fas fa-plus me-1"></i> Crear Nueva Campaña
            </a>
        </div>
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-bullseye me-1 text-warning"></i> Objetivo:</h6>
            <p class="small text-muted">Aperturar una campaña productiva (ej. <em>"Campaña de Maíz Amarillo Duro 2026-I"</em> o <em>"Campaña de Trucha Arcoíris Lote A"</em>), estableciendo metas cuantitativas de producción, fechas de ciclo y presupuesto proyectado.</p>

            <h6 class="fw-bold text-gray-800 mb-2">Instrucciones Paso a Paso:</h6>
            <div class="timeline-steps">
                <div class="step-item d-flex gap-3 mb-2">
                    <div class="step-badge bg-warning text-dark rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">1</div>
                    <div class="small text-muted">Navegue a <strong>Producción &rarr; Planes y Campañas</strong> y haga clic en <strong>"Nuevo Plan de Producción"</strong>.</div>
                </div>
                <div class="step-item d-flex gap-3 mb-2">
                    <div class="step-badge bg-warning text-dark rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">2</div>
                    <div class="small text-muted">Seleccione la <strong>Actividad APE matriz</strong> a la cual tributará financieramente la producción.</div>
                </div>
                <div class="step-item d-flex gap-3 mb-2">
                    <div class="step-badge bg-warning text-dark rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">3</div>
                    <div class="small text-muted">Indique el <strong>Código de Campaña</strong> (ej. <code>CAMP-MAIZ-2026-1</code>), nombre descriptivo, fecha de inicio y fecha estimada de cosecha/cierre.</div>
                </div>
                <div class="step-item d-flex gap-3 mb-2">
                    <div class="step-badge bg-warning text-dark rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">4</div>
                    <div class="small text-muted">Defina la <strong>Meta de Producción</strong>: cantidad objetivo (ej. <code>15000.00</code>) y unidad de medida (<code>Kilogramos</code>, <code>Toneladas</code>, <code>Unidades</code>, <code>Sacos</code>).</div>
                </div>
                <div class="step-item d-flex gap-3 mb-2">
                    <div class="step-badge bg-warning text-dark rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;">5</div>
                    <div class="small text-muted">Presione <strong>"Guardar Plan"</strong>. La campaña quedará en estado inicial <code>planned</code> (Planificada) lista para desglosarse en fases.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROC-PROD-02 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-prod-02" data-search-terms="fases lotes mano de obra jornales cuadrillas labores culturales">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark px-2 py-1">PROC-PROD-02</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Fases / Lotes y Asignación de Costos de Mano de Obra</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Permite registrar cada etapa cronológica de la producción y costear los jornales de campo o planta:</p>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Abra la ficha de la campaña (botón <i class="fas fa-eye text-primary"></i> <strong>Ver Fases</strong>).</li>
                <li class="mb-2">Presione <strong>"Agregar Fase / Lote"</strong> y nombre la etapa (ej. <em>"Fase 1: Preparación de terreno y enmiendas"</em>, <em>"Fase 2: Siembra e inoculación"</em>, <em>"Fase 3: Cosecha y embolsado"</em>).</li>
                <li class="mb-2">Dentro de la fase, presione <strong>"Registrar Mano de Obra"</strong>:
                    <ul class="mt-1 ps-3">
                        <li>Indique la labor ejecutada (ej. <em>"Deshierbe manual"</em>, <em>"Poda de formación"</em>, <em>"Aplicación de biol"</em>).</li>
                        <li>Ingrese el número de jornales trabajados (ej. <code>12 jornales</code>).</li>
                        <li>Estipule el costo unitario por jornal (ej. <code>S/ 50.00</code>). El sistema multiplicará y acumulará el costo de mano de obra directo (<code>S/ 600.00</code>).</li>
                    </ul>
                </li>
                <li>Los costos de mano de obra se computan automáticamente en la hoja de liquidación de la campaña.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-PROD-03 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-prod-03" data-search-terms="insumos materias primas catalogo fertilizantes abonos semillas">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark px-2 py-1">PROC-PROD-03</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Catálogo de Insumos y Materias Primas de Producción</h5>
            </div>
            <a href="{{ route('production.raw_materials.index') }}" target="_blank" class="btn btn-outline-warning btn-sm text-dark">
                <i class="fas fa-boxes me-1"></i> Insumos y Materiales
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Mantiene el inventario técnico de todos los insumos utilizados en campo (semillas certificadas, fertilizantes, abonos orgánicos, agroquímicos, empaques, reactivos, alimento balanceado):</p>
            <ul class="small text-muted ps-3 mb-0">
                <li><strong>Registro de Insumo:</strong> Ingrese el nombre técnico, código SKU, categoría agronómica, unidad de medida oficial (kg, litro, saco, bolsa) y costo unitario estándar.</li>
                <li><strong>Control de Alerta de Stock:</strong> Configure el stock mínimo de seguridad; el sistema emitirá avisos visuales cuando se requiera reabastecimiento antes de una labor crítica.</li>
            </ul>
        </div>
    </div>

    <!-- PROC-PROD-04 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-prod-04" data-search-terms="consumo de insumos salidas de almacen aplicacion en campo lote">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark px-2 py-1">PROC-PROD-04</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Movimientos de Insumos y Consumos Directos en Campo</h5>
            </div>
            <a href="{{ route('production.input_movements.index') }}" class="btn btn-outline-warning btn-sm text-dark">
                <i class="fas fa-truck-loading me-1"></i> Movimientos de Insumos
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Cada retiro de insumo de almacén para ser aplicado a un lote agrícola o pecuario debe imputarse al proyecto correspondiente:</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <span class="badge bg-success me-1 mb-2">ENTRADAS</span>
                        <div class="fw-bold small mb-1">Ingreso de Insumos a Producción</div>
                        <p class="small text-muted mb-0">Recepción de compras institucionales o transferencias entre almacenes hacia el depósito de producción.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <span class="badge bg-danger me-1 mb-2">SALIDAS / CONSUMOS</span>
                        <div class="fw-bold small mb-1">Consumo en Lote Específico</div>
                        <p class="small text-muted mb-0">Al registrar la salida, seleccione el <strong>Plan de Producción y la Fase</strong>. Esto cargará el costo del insumo directamente a la estructura de costos de ese lote.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROC-PROD-05 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-prod-05" data-search-terms="cosechas pesaje rendimiento calidades merma descarte produccion">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark px-2 py-1">PROC-PROD-05</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Registro de Cosechas y Control de Rendimiento</h5>
            </div>
            <a href="{{ route('production.harvests.index') }}" target="_blank" class="btn btn-outline-warning btn-sm text-dark">
                <i class="fas fa-balance-scale me-1"></i> Ver Cosechas
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Formaliza la recolección de los frutos, granos, carne o derivados:</p>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Diríjase a <strong>Producción &rarr; Cosechas y Rendimiento</strong> &rarr; botón <strong>"Nueva Cosecha"</strong>.</li>
                <li class="mb-2">Seleccione el Plan de Producción de procedencia.</li>
                <li class="mb-2">Ingrese la fecha de cosecha, peso bruto, tara de recipientes/jabas y peso neto obtenido.</li>
                <li class="mb-2">Clasifique la calidad: <code>Primera Calidad</code> (para venta directa premium), <code>Segunda Calidad</code> o <code>Merma/Descarte</code>.</li>
                <li>El sistema comparará el volumen acumulado contra la meta planificada en el <em>PROC-PROD-01</em>, mostrando el % de cumplimiento del rendimiento.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-PROD-06 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-prod-06" data-search-terms="productos obtenidos catalogo central ventas pos kardex publicar">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark px-2 py-1">PROC-PROD-06</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Transformación y Publicación al Catálogo Central de Ventas</h5>
            </div>
            <a href="{{ route('production.produced_items.index') }}" target="_blank" class="btn btn-outline-warning btn-sm text-dark">
                <i class="fas fa-store me-1"></i> Productos Obtenidos
            </a>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-success py-2 px-3 small mb-3">
                <i class="fas fa-magic me-1"></i> <strong>Integración Clave:</strong> Este proceso convierte la cosecha de campo en un producto comercializable que el cajero de Ventas / POS puede facturar de inmediato.
            </div>
            <p class="small text-muted">Pasos para publicar al Catálogo Central:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li class="mb-2">En <strong>Productos Obtenidos</strong>, seleccione el producto cosechado o procesado (ej. <em>"Miel de Abeja en Frasco de 500g"</em> o <em>"Saco de Café Grano Especial 50kg"</em>).</li>
                <li class="mb-2">Presione el botón <strong>"Publicar al Catálogo Central"</strong>.</li>
                <li class="mb-2">Especifique el almacén comercial de destino, el precio unitario de venta (incluido o más IGV) y la cantidad de unidades empaquetadas.</li>
                <li>El sistema creará o actualizará automáticamente el producto en el módulo de Inventario central y generará una <strong>Entrada por Producción en el Kardex</strong> sin necesidad de doble digitación.</li>
            </ul>
        </div>
    </div>

    <!-- PROC-PROD-07 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-prod-07" data-search-terms="preventas pedidos comercializacion reservas anticipos clientes">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark px-2 py-1">PROC-PROD-07</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Preventas y Pedidos de Comercialización</h5>
            </div>
            <a href="{{ route('commercialization.orders.index') }}" target="_blank" class="btn btn-outline-warning btn-sm text-dark">
                <i class="fas fa-clipboard-list me-1"></i> Preventas y Pedidos
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Permite asegurar la venta de cosechas futuras o lotes en proceso de maduración:</p>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-2">Vaya a <strong>Producción &rarr; Preventas y Pedidos</strong> &rarr; botón <strong>"Nuevo Pedido"</strong>.</li>
                <li class="mb-2">Seleccione al cliente, la actividad APE proveedora, fecha pactada de entrega y los productos/cantidades reservados.</li>
                <li class="mb-2">Registre el anticipo o seña cobrada en efectivo o transferencia bancaria.</li>
                <li>Al momento del recojo de la cosecha, cambie el estado a <code>ENTREGADO</code>; el sistema liquidará el saldo pendiente y emitirá la Nota de Venta o Boleta correspondiente.</li>
            </ol>
        </div>
    </div>

    <!-- PROC-PROD-08 -->
    <div class="card border-0 shadow-sm mb-4 process-card" id="proc-prod-08" data-search-terms="rentabilidad liquidacion costos ingresos margen utilidad produccion">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark px-2 py-1">PROC-PROD-08</span>
                <h5 class="h6 fw-bold mb-0 text-dark">Liquidación Económica y Análisis de Rentabilidad</h5>
            </div>
            <a href="{{ route('production.profitability.index') }}" target="_blank" class="btn btn-outline-warning btn-sm text-dark">
                <i class="fas fa-chart-pie me-1"></i> Tablero de Rentabilidad
            </a>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted">Evalúa el éxito financiero de la campaña agrícola o pecuaria mediante la fórmula:</p>
            <div class="p-3 bg-light rounded text-center small mb-3">
                <span class="fw-bold text-success">Ingresos por Ventas Realizadas</span> &minus; 
                (<span class="fw-bold text-danger">Costos de Insumos</span> + <span class="fw-bold text-danger">Costos de Mano de Obra</span> + <span class="fw-bold text-danger">Gastos Indirectos</span>) = 
                <span class="badge bg-primary fs-6">Margen de Ganancia Neta</span>
            </div>
            <p class="small text-muted mb-0">Este tablero calcula el <strong>Costo Unitario Real de Producción</strong> (ej. <em>S/ 2.45 por kilo de cacao producido</em>), permitiendo ajustar los precios mínimos de venta para futuras campañas.</p>
        </div>
    </div>
</div>
