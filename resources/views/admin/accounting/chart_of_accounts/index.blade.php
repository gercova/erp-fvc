@extends('admin.layout')
@section('content')

<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-xl px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="book"></i></div>
                        Plan Contable General Empresarial (PCGE 2019)
                    </h1>
                    <p class="text-muted small mb-0">Catálogo oficial de cuentas, clasificación, naturaleza y configuración de imputabilidad.</p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm mb-3 shadow-sm btn-new-account">
                        <i class="fas fa-plus-circle me-1"></i> Nueva Cuenta Contable
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-xl px-4 mt-2">
    <!-- Filtros de Búsqueda -->
    <div class="card mb-4 border-start-lg border-start-primary shadow-sm">
        <div class="card-body py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="small text-muted fw-bold">Elemento del PCGE:</label>
                    <select id="filter_element" class="form-select form-select-sm">
                        <option value="ALL">-- Todos los Elementos (Clase 0 al 9) --</option>
                        @foreach($elements as $elKey => $elName)
                            <option value="{{ $elKey }}">{{ $elName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small text-muted fw-bold">Tipo de Imputación:</label>
                    <select id="filter_movements" class="form-select form-select-sm">
                        <option value="ALL">-- Todas las Cuentas --</option>
                        <option value="1">Imputables (Acepta Movimientos)</option>
                        <option value="0">Resumen / Títulos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small text-muted fw-bold">Estado:</label>
                    <select id="filter_active" class="form-select form-select-sm">
                        <option value="ALL">-- Todos los Estados --</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="btn_filter" class="btn btn-sm btn-outline-primary w-100 mt-auto">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navegación por Pestañas -->
    <ul class="nav nav-tabs nav-tabs-bottom mb-3" id="accountTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-pane" type="button" role="tab">
                <i class="fas fa-table me-1"></i> Vista Tabla (DataTables)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="tree-tab" data-bs-toggle="tab" data-bs-target="#tree-pane" type="button" role="tab">
                <i class="fas fa-sitemap me-1"></i> Vista Árbol Jerárquico
            </button>
        </li>
    </ul>

    <div class="tab-content" id="accountTabContent">
        <!-- Pestaña 1: Tabla DataTables -->
        <div class="tab-pane fade show active" id="table-pane" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="accounts_table" class="table table-hover table-striped table-sm align-middle w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th>Código</th>
                                    <th>Denominación de la Cuenta</th>
                                    <th>Nivel</th>
                                    <th>Naturaleza</th>
                                    <th>Clasificación</th>
                                    <th>Imputación</th>
                                    <th>Estado</th>
                                    <th class="text-center" style="width: 100px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña 2: Árbol Jerárquico -->
        <div class="tab-pane fade" id="tree-pane" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-folder-tree me-1"></i> Estructura Jerárquica del Plan Contable</h6>
                    <button class="btn btn-sm btn-outline-secondary" id="btn_refresh_tree">
                        <i class="fas fa-sync-alt me-1"></i> Recargar Árbol
                    </button>
                </div>
                <div class="card-body">
                    <div id="tree_container" class="p-2 border rounded bg-light" style="max-height: 600px; overflow-y: auto;">
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                            <p>Cargando estructura arbórea del PCGE...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registro / Edición de Cuenta -->
<div class="modal fade" id="modalAccount" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalAccountTitle"><i class="fas fa-book-open me-2"></i> Cuenta Contable</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAccount">
                <input type="hidden" id="account_id" name="id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Código PCGE <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm font-monospace fw-bold" id="account_code" name="code" required placeholder="ej. 1012, 40111">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Denominación / Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="account_name" name="name" required placeholder="ej. Caja Chica / Mostrador POS">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Elemento <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="account_element" name="element" required>
                                <option value="1">1 - Activo Disponible / Exigible</option>
                                <option value="2">2 - Activo Realizable</option>
                                <option value="3">3 - Activo Inmovilizado</option>
                                <option value="4">4 - Pasivo</option>
                                <option value="5">5 - Patrimonio Neto</option>
                                <option value="6">6 - Gastos por Naturaleza</option>
                                <option value="7">7 - Ingresos</option>
                                <option value="8">8 - Saldos Intermediarios</option>
                                <option value="9">9 - Costos / Función</option>
                                <option value="0">0 - Cuentas de Orden</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nivel Jerárquico <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="account_level" name="level" required>
                                <option value="1">1 - Elemento (1 dígito)</option>
                                <option value="2">2 - Cuenta (2 dígitos)</option>
                                <option value="3">3 - Subcuenta (3 dígitos)</option>
                                <option value="4">4 - Divisionaria (4 dígitos)</option>
                                <option value="5">5 - Subdivisionaria (5+ dígitos)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Naturaleza <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="account_nature" name="nature" required>
                                <option value="DEBIT">Deudora (Saldo DEUDOR)</option>
                                <option value="CREDIT">Acreedora (Saldo ACREEDOR)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Clasificación EEFF <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="account_classification" name="classification" required>
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="PASIVO">PASIVO</option>
                                <option value="PATRIMONIO">PATRIMONIO</option>
                                <option value="GASTOS_NATURALEZA">GASTOS POR NATURALEZA (Elemento 6)</option>
                                <option value="INGRESOS">INGRESOS (Elemento 7)</option>
                                <option value="COSTOS_PRODUCCION">COSTOS DE PRODUCCIÓN (Elemento 9)</option>
                                <option value="GASTOS_FUNCION">GASTOS POR FUNCIÓN (Elemento 9)</option>
                                <option value="ORDEN">CUENTAS DE ORDEN (Elemento 0)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cuenta Padre</label>
                            <select class="form-select form-select-sm" id="account_parent_id" name="parent_id">
                                <option value="">-- Sin cuenta padre (Raíz) --</option>
                            </select>
                        </div>

                        <div class="col-12 mt-3">
                            <label class="small text-muted fw-bold d-block mb-2">Parámetros de Imputabilidad y Restricciones:</label>
                            <div class="row g-2 p-3 bg-light rounded border">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="account_accepts_movements" name="accepts_movements" value="1">
                                        <label class="form-check-label small fw-bold text-success" for="account_accepts_movements">
                                            Acepta Movimientos (Cuenta Imputable en Libro Diario)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="account_active" name="active" value="1" checked>
                                        <label class="form-check-label small fw-bold" for="account_active">
                                            Cuenta Activa
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="account_req_third" name="requires_third_party" value="1">
                                        <label class="form-check-label small" for="account_req_third">
                                            Requiere Tercero (RUC / DNI)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="account_req_cost" name="requires_cost_center" value="1">
                                        <label class="form-check-label small" for="account_req_cost">
                                            Requiere Centro de Costo (APE)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="account_req_fund" name="requires_fund_source" value="1">
                                        <label class="form-check-label small" for="account_req_fund">
                                            Requiere Banco / Fuente
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm" id="btnSaveAccount">
                        <i class="fas fa-save me-1"></i> Guardar Cuenta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 1. Inicialización de DataTables Yajra Server-Side
    let table = $('#accounts_table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('accounting.chart_of_accounts.data') }}",
            data: function(d) {
                d.element = $('#filter_element').val();
                d.accepts_movements = $('#filter_movements').val();
                d.active = $('#filter_active').val();
            }
        },
        columns: [
            { data: 'code', name: 'code', className: 'font-monospace fw-bold text-primary' },
            { data: 'name', name: 'name' },
            { data: 'level_badge', name: 'level', className: 'text-center' },
            { data: 'nature_badge', name: 'nature', className: 'text-center' },
            { data: 'classification', name: 'classification' },
            { data: 'movements_badge', name: 'accepts_movements', className: 'text-center' },
            { data: 'status_badge', name: 'active', className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[0, 'asc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });

    $('#btn_filter, #filter_element, #filter_movements, #filter_active').on('change click', function() {
        table.draw();
    });

    // 2. Abrir Modal Nueva Cuenta
    $('.btn-new-account').on('click', function() {
        $('#formAccount')[0].reset();
        $('#account_id').val('');
        $('#modalAccountTitle').html('<i class="fas fa-plus-circle me-2"></i> Nueva Cuenta Contable');
        loadParentAccounts();
        $('#modalAccount').modal('show');
    });

    // 3. Cargar Cuentas Padre en Select
    function loadParentAccounts(selectedId = null) {
        $.getJSON("{{ route('accounting.chart_of_accounts.tree') }}", function(res) {
            let select = $('#account_parent_id');
            select.empty().append('<option value="">-- Sin cuenta padre (Raíz) --</option>');
            if (res.tree) {
                function appendOptions(nodes, depth = 0) {
                    nodes.forEach(function(node) {
                        let indent = '&nbsp;&nbsp;'.repeat(depth);
                        let isSelected = selectedId == node.id ? 'selected' : '';
                        select.append(`<option value="${node.id}" ${isSelected}>${indent}${node.code} - ${node.name}</option>`);
                        if (node.children && node.children.length > 0) {
                            appendOptions(node.children, depth + 1);
                        }
                    });
                }
                appendOptions(res.tree);
            }
        });
    }

    // 4. Guardar Cuenta (Crear o Actualizar)
    $('#formAccount').on('submit', function(e) {
        e.preventDefault();
        let id = $('#account_id').val();
        let url = id ? `/accounting/chart-of-accounts/${id}` : "{{ route('accounting.chart_of_accounts.store') }}";
        let method = id ? 'PUT' : 'POST';

        let formData = $(this).serializeArray();
        let payload = {};
        formData.forEach(function(item) {
            payload[item.name] = item.value;
        });
        payload['accepts_movements'] = $('#account_accepts_movements').is(':checked') ? 1 : 0;
        payload['requires_third_party'] = $('#account_req_third').is(':checked') ? 1 : 0;
        payload['requires_cost_center'] = $('#account_req_cost').is(':checked') ? 1 : 0;
        payload['requires_fund_source'] = $('#account_req_fund').is(':checked') ? 1 : 0;
        payload['active'] = $('#account_active').is(':checked') ? 1 : 0;

        $('#btnSaveAccount').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: url,
            type: method,
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                $('#modalAccount').modal('hide');
                table.draw();
                Swal.fire('Éxito', res.message, 'success');
            },
            error: function(xhr) {
                let msg = 'Ocurrió un error al procesar la solicitud.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                $('#btnSaveAccount').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Cuenta');
            }
        });
    });

    // 5. Editar Cuenta
    $(document).on('click', '.btn-edit-account', function() {
        let id = $(this).data('id');
        $.getJSON(`/accounting/chart-of-accounts/${id}`, function(res) {
            let acc = res.account;
            $('#account_id').val(acc.id);
            $('#account_code').val(acc.code);
            $('#account_name').val(acc.name);
            $('#account_element').val(acc.element);
            $('#account_level').val(acc.level);
            $('#account_nature').val(acc.nature);
            $('#account_classification').val(acc.classification);
            $('#account_accepts_movements').prop('checked', acc.accepts_movements);
            $('#account_active').prop('checked', acc.active);
            $('#account_req_third').prop('checked', acc.requires_third_party);
            $('#account_req_cost').prop('checked', acc.requires_cost_center);
            $('#account_req_fund').prop('checked', acc.requires_fund_source);
            loadParentAccounts(acc.parent_id);
            $('#modalAccountTitle').html('<i class="fas fa-edit me-2"></i> Editar Cuenta: ' + acc.code);
            $('#modalAccount').modal('show');
        });
    });

    // 6. Eliminar Cuenta
    $(document).on('click', '.btn-delete-account', function() {
        let id = $(this).data('id');
        let code = $(this).data('code');

        Swal.fire({
            title: '¿Eliminar cuenta contable?',
            text: `Se eliminará la cuenta ${code}. Esta acción no se puede deshacer si no registra movimientos.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/accounting/chart-of-accounts/${id}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        table.draw();
                        Swal.fire('Eliminado', res.message, 'success');
                    },
                    error: function(xhr) {
                        let msg = 'No se pudo eliminar la cuenta.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    });

    // 7. Cargar Árbol Jerárquico en Tab 2
    function renderTree() {
        $('#tree_container').html('<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><p>Cargando árbol...</p></div>');
        $.getJSON("{{ route('accounting.chart_of_accounts.tree') }}", function(res) {
            if (!res.tree || res.tree.length === 0) {
                $('#tree_container').html('<p class="text-muted p-3">No hay cuentas contables registradas.</p>');
                return;
            }

            function buildHtml(nodes) {
                let html = '<ul class="list-unstyled ps-3 mb-1">';
                nodes.forEach(function(node) {
                    let badgeClass = node.accepts_movements ? 'badge bg-success' : 'badge bg-light text-dark border';
                    let badgeText = node.accepts_movements ? 'Imputable' : 'Título';
                    let natureClass = node.nature === 'DEBIT' ? 'text-success' : 'text-danger';

                    html += `<li class="py-1">
                        <span class="font-monospace fw-bold text-primary">${node.code}</span> - 
                        <span class="fw-semibold text-dark">${node.name}</span>
                        <span class="${badgeClass} ms-2 small">${badgeText}</span>
                        <small class="${natureClass} ms-1 fw-bold">(${node.nature})</small>
                    `;
                    if (node.children && node.children.length > 0) {
                        html += buildHtml(node.children);
                    }
                    html += '</li>';
                });
                html += '</ul>';
                return html;
            }

            $('#tree_container').html(buildHtml(res.tree));
        });
    }

    $('#tree-tab, #btn_refresh_tree').on('click', function() {
        renderTree();
    });
});
</script>
@endsection
