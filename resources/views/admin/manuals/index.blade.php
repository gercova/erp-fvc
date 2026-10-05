@extends('admin.layout')

@section('styles')
<style>
    /* Estilos del Portal de Manuales de Usuario */
    .manual-hero-banner {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 0.75rem;
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }
    .manual-hero-banner::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 380px;
        height: 380px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, rgba(255, 255, 255, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .module-selector-card {
        transition: all 0.2s ease-in-out;
        border: 2px solid transparent;
        cursor: pointer;
    }
    .module-selector-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    }
    .module-selector-card.active {
        border-color: var(--bs-primary) !important;
        background: #f8faff !important;
    }
    .process-card {
        transition: box-shadow 0.2s ease;
        scroll-margin-top: 80px;
    }
    .process-card:hover {
        box-shadow: 0 6px 18px rgba(0,0,0,0.08) !important;
    }
    .timeline-steps .step-item {
        position: relative;
    }
    .timeline-steps .step-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 13px;
        top: 30px;
        bottom: -15px;
        width: 2px;
        background-color: #e2e8f0;
    }
    .badge-process {
        font-family: monospace;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }

    /* Print Styles */
    @media print {
        #layoutSidenav_nav,
        .topnav,
        .manual-hero-banner,
        .manual-nav-tabs,
        .manual-actions-bar,
        .btn,
        .sidenav-footer,
        footer {
            display: none !important;
        }
        #layoutSidenav_content {
            margin: 0 !important;
            padding: 0 !important;
        }
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
        .card {
            box-shadow: none !important;
            border: 1px solid #cccccc !important;
            page-break-inside: avoid;
            margin-bottom: 20px !important;
        }
        .manual-module-content {
            display: block !important;
            page-break-before: always;
        }
        .process-card {
            display: block !important;
            page-break-inside: avoid;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb y Título -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small mb-2">
            <li class="breadcrumb-item"><a href="{{ route('admin.home') }}"><i class="fas fa-home me-1"></i>Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Manual de Usuario</li>
        </ol>
    </nav>

    <!-- Banner Principal con Buscador en Vivo -->
    <div class="manual-hero-banner p-4 p-md-5 mb-4 shadow">
        <div class="row align-items-center position-relative" style="z-index: 1;">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary text-white px-2 py-1"><i class="fas fa-book-reader me-1"></i> Documentación Oficial</span>
                    <span class="badge bg-success text-white px-2 py-1"><i class="fas fa-check-circle me-1"></i> {{ $totalProcesses }} Procesos Paso a Paso</span>
                </div>
                <h1 class="h2 fw-bold text-white mb-2">Manual de Usuario del Sistema ERP</h1>
                <p class="text-white-50 mb-4" style="max-width: 650px;">
                    Guía de procedimientos institucionales detallados paso a paso para los módulos operativos de 
                    <strong>Actividades (APE)</strong>, <strong>Convenios y Servicios</strong>, <strong>Producción</strong>, 
                    <strong>Agropecuaria y Forestal</strong> y <strong>Contabilidad y Tesorería</strong>.
                </p>

                <!-- Input de Búsqueda Rápida Instantánea -->
                <div class="input-group input-group-lg shadow-sm" style="max-width: 600px;">
                    <span class="input-group-text bg-white border-0 text-primary">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="manualGlobalSearch" class="form-control border-0" 
                           placeholder="Buscar por proceso, palabra clave o código (ej. CUT, cosecha, asiento, addenda, jornales)..." 
                           aria-label="Buscar en el manual">
                    <button class="btn btn-outline-light bg-white border-0 text-secondary" type="button" id="btnClearSearch" title="Limpiar búsqueda" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="searchStats" class="small text-white-50 mt-2" style="display: none;"></div>
            </div>
            <div class="col-lg-4 text-end d-none d-lg-block">
                <img src="{{ asset('assets/img/illustrations/profiles/profile-2.png') }}" alt="ERP Manual" class="img-fluid" style="max-height: 180px; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.3));">
            </div>
        </div>
    </div>

    <!-- Barra de Herramientas y Acciones Rápidas -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 manual-actions-bar">
        <!-- Pestañas de Filtrado por Módulo -->
        <ul class="nav nav-pills flex-grow-1 manual-nav-tabs" id="manualTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ empty($activeModule) ? 'active' : '' }} fw-semibold rounded-pill px-3 py-2" 
                        id="tab-all" data-bs-toggle="pill" data-bs-target="#content-all" type="button" role="tab" data-module="all">
                    <i class="fas fa-th-large me-1"></i> Todos los Módulos ({{ $totalProcesses }})
                </button>
            </li>
            @foreach($modules as $modKey => $mod)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeModule === $modKey ? 'active' : '' }} fw-semibold rounded-pill px-3 py-2" 
                        id="tab-{{ $modKey }}" data-bs-toggle="pill" data-bs-target="#content-{{ $modKey }}" type="button" role="tab" data-module="{{ $modKey }}">
                    <i class="{{ $mod['icon'] }} me-1"></i> {{ $mod['short_name'] }}
                    <span class="badge bg-secondary-subtle text-dark ms-1">{{ $mod['process_count'] }}</span>
                </button>
            </li>
            @endforeach
        </ul>

        <!-- Botones de Acción Global -->
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="btnToggleAllSections" title="Expandir o colapsar secciones">
                <i class="fas fa-compress-alt me-1"></i> <span id="toggleText">Colapsar / Expandir</span>
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="window.print();" title="Imprimir o guardar como PDF">
                <i class="fas fa-print me-1"></i> Imprimir / PDF
            </button>
        </div>
    </div>

    <!-- Tarjetas de Resumen de Módulos (Acceso Rápido) -->
    <div class="row g-3 mb-4" id="moduleCardsContainer">
        @foreach($modules as $modKey => $mod)
        <div class="col-md-6 col-xl">
            <div class="card h-100 shadow-sm module-selector-card {{ $activeModule === $modKey ? 'active' : '' }}" 
                 onclick="switchManualModule('{{ $modKey }}')" data-module-target="{{ $modKey }}">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-{{ $mod['color'] }}-subtle text-{{ $mod['color'] }} fw-bold px-2 py-1">
                                {{ $mod['code'] }}
                            </span>
                            <span class="badge bg-light text-muted border small">{{ $mod['process_count'] }} procesos</span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                            <i class="{{ $mod['icon'] }} text-{{ $mod['color'] }}"></i>
                            <span class="text-truncate">{{ $mod['short_name'] }}</span>
                        </h6>
                        <p class="small text-muted mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px;">
                            {{ $mod['description'] }}
                        </p>
                    </div>
                    <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                        <span class="small fw-semibold text-primary">Ver Manual &rarr;</span>
                        <a href="{{ route($mod['main_route']) }}" class="btn btn-xs btn-outline-secondary" onclick="event.stopPropagation();" title="Ir a la pantalla del módulo">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Contenido de los Manuales -->
    <div class="tab-content" id="manualTabsContent">
        <!-- VISTA: TODOS LOS MÓDULOS -->
        <div class="tab-pane fade {{ empty($activeModule) ? 'show active' : '' }}" id="content-all" role="tabpanel">
            @include('admin.manuals.modules.ape')
            <hr class="my-5 border-2">
            @include('admin.manuals.modules.agreements_services')
            <hr class="my-5 border-2">
            @include('admin.manuals.modules.production')
            <hr class="my-5 border-2">
            @include('admin.manuals.modules.agrolivestock')
            <hr class="my-5 border-2">
            @include('admin.manuals.modules.accounting')
        </div>

        <!-- VISTAS INDIVIDUALES POR MÓDULO -->
        <div class="tab-pane fade {{ $activeModule === 'ape' ? 'show active' : '' }}" id="content-ape" role="tabpanel">
            @include('admin.manuals.modules.ape')
        </div>

        <div class="tab-pane fade {{ $activeModule === 'agreements' ? 'show active' : '' }}" id="content-agreements" role="tabpanel">
            @include('admin.manuals.modules.agreements_services')
        </div>

        <div class="tab-pane fade {{ $activeModule === 'production' ? 'show active' : '' }}" id="content-production" role="tabpanel">
            @include('admin.manuals.modules.production')
        </div>

        <div class="tab-pane fade {{ $activeModule === 'agrolivestock' ? 'show active' : '' }}" id="content-agrolivestock" role="tabpanel">
            @include('admin.manuals.modules.agrolivestock')
        </div>

        <div class="tab-pane fade {{ $activeModule === 'accounting' ? 'show active' : '' }}" id="content-accounting" role="tabpanel">
            @include('admin.manuals.modules.accounting')
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Función para cambiar de pestaña de módulo dinámicamente
    function switchManualModule(moduleKey) {
        const tabBtn = document.getElementById('tab-' + moduleKey);
        if (tabBtn) {
            const tabInstance = bootstrap.Tab.getOrCreateInstance(tabBtn);
            tabInstance.show();
        }

        // Actualizar tarjetas activas
        document.querySelectorAll('.module-selector-card').forEach(c => {
            if (c.getAttribute('data-module-target') === moduleKey) {
                c.classList.add('active');
            } else {
                c.classList.remove('active');
            }
        });

        // Actualizar URL sin recargar
        const url = new URL(window.location);
        url.searchParams.set('module', moduleKey);
        window.history.replaceState({}, '', url);

        // Desplazar suavemente hacia el contenido
        document.getElementById('manualTabsContent').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Sincronizar tarjetas al hacer clic en pestañas
        const tabButtons = document.querySelectorAll('#manualTabs button[data-bs-toggle="pill"]');
        tabButtons.forEach(btn => {
            btn.addEventListener('shown.bs.tab', function (event) {
                const targetMod = event.target.getAttribute('data-module');
                document.querySelectorAll('.module-selector-card').forEach(c => {
                    if (targetMod === 'all' || c.getAttribute('data-module-target') === targetMod) {
                        c.classList.add('active');
                    } else {
                        c.classList.remove('active');
                    }
                });

                const url = new URL(window.location);
                if (targetMod === 'all') {
                    url.searchParams.delete('module');
                } else {
                    url.searchParams.set('module', targetMod);
                }
                window.history.replaceState({}, '', url);
            });
        });

        // BUSCADOR EN VIVO DE PROCESOS
        const searchInput = document.getElementById('manualGlobalSearch');
        const clearBtn = document.getElementById('btnClearSearch');
        const statsEl = document.getElementById('searchStats');

        function performSearch() {
            const query = searchInput.value.trim().toLowerCase();
            const processCards = document.querySelectorAll('.process-card');
            
            if (query.length > 0) {
                clearBtn.style.display = 'block';
                // Si estamos en una pestaña específica, cambiar temporalmente a "all" para buscar en todo el sistema
                const allTabBtn = document.getElementById('tab-all');
                if (allTabBtn && !allTabBtn.classList.contains('active')) {
                    bootstrap.Tab.getOrCreateInstance(allTabBtn).show();
                }

                let matches = 0;
                processCards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    const searchTerms = (card.getAttribute('data-search-terms') || '').toLowerCase();
                    
                    if (text.includes(query) || searchTerms.includes(query)) {
                        card.style.display = '';
                        card.classList.add('border-primary');
                        matches++;
                    } else {
                        card.style.display = 'none';
                        card.classList.remove('border-primary');
                    }
                });

                statsEl.style.display = 'block';
                statsEl.innerHTML = `<i class="fas fa-info-circle me-1"></i> Se encontraron <strong>${matches}</strong> procesos coincidentes con "<em>${escapeHtml(query)}</em>".`;
            } else {
                clearBtn.style.display = 'none';
                statsEl.style.display = 'none';
                processCards.forEach(card => {
                    card.style.display = '';
                    card.classList.remove('border-primary');
                });
            }
        }

        function escapeHtml(text) {
            return text.replace(/[&<>"']/g, function(m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
            });
        }

        searchInput.addEventListener('input', performSearch);

        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            performSearch();
            searchInput.focus();
        });

        // Soporte para Hash en la URL (ej. manuals#proc-ape-01)
        if (window.location.hash) {
            const targetEl = document.querySelector(window.location.hash);
            if (targetEl) {
                setTimeout(() => {
                    targetEl.scrollIntoView({ behavior: 'smooth' });
                    targetEl.classList.add('border-primary', 'shadow');
                }, 300);
            }
        }
    });
</script>
@endsection
