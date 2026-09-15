@extends('admin.layout')

@section('content')
    <style>
        .business-profile-card .nav-tabs .nav-link {
            border: 0;
            border-bottom: 2px solid transparent;
            color: #6b7280;
            font-weight: 600;
            background: transparent;
        }

        .business-profile-card .nav-tabs .nav-link.active {
            color: #111827;
            border-bottom-color: #0d6efd;
        }

        .business-logo-box,
        .business-soft-panel,
        .business-cert-box,
        .business-input-addon,
        .business-upload-label {
            background: #f8fafc;
            border-color: #e5e7eb !important;
        }

        .business-upload-label {
            color: #fff !important;
            background: var(--bs-primary) !important;
            border: 1px solid var(--bs-primary) !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 110px;
        }

        .business-upload-label:hover,
        .business-upload-label:focus-within {
            color: #fff !important;
            background: #0b5ed7 !important;
            border-color: #0a58ca !important;
        }

        [data-bs-theme="dark"] .business-profile-card,
        [data-bs-theme="dark"] .business-tab-card {
            background: #111827 !important;
            border-color: #263244 !important;
            box-shadow: 0 14px 32px rgba(2, 6, 23, .14);
        }

        [data-bs-theme="dark"] .business-profile-card .nav-tabs {
            border-bottom-color: #223046 !important;
        }

        [data-bs-theme="dark"] .business-profile-card .nav-tabs .nav-link {
            color: #9fb0c4 !important;
        }

        [data-bs-theme="dark"] .business-profile-card .nav-tabs .nav-link.active {
            color: #f8fafc !important;
            border-bottom-color: #5a56ee !important;
        }

        [data-bs-theme="dark"] .business-tab-card .card-header {
            background: #111827 !important;
            border-bottom-color: #223046 !important;
        }

        [data-bs-theme="dark"] .business-soft-panel,
        [data-bs-theme="dark"] .business-cert-box,
        [data-bs-theme="dark"] .business-logo-box,
        [data-bs-theme="dark"] .business-input-addon {
            background: #172033 !important;
            border-color: #2a3649 !important;
            color: #dbe4ee !important;
        }

        [data-bs-theme="dark"] .business-upload-label {
            background: #3b82f6 !important;
            border-color: #3b82f6 !important;
            color: #fff !important;
        }

        [data-bs-theme="dark"] .business-soft-panel .text-dark {
            color: #f8fafc !important;
        }
    </style>

    <div class="row mb-4">
        <div class="col-12 d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 text-lg fw-bold">Configuración de Empresa</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0 text-sm">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Sistema</a></li>
                    <li class="breadcrumb-item active">Empresa</li>
                </ol>
            </div>
        </div>
    </div>

    @php
        $logoUrl = !empty($empresa->logo) ? asset('files/logos/' . $empresa->logo) : asset('files/empty_logo.png');
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 mb-0 business-profile-card" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
                <div class="card-body p-4 pb-0">
                    <div class="row align-items-center mb-4">
                        <div class="col-auto">
                            <img id="header-company-logo" src="{{ $logoUrl }}" alt="Logo" class="rounded border p-1 business-logo-box"
                                style="width: 85px; height: 85px; object-fit: contain;">
                        </div>
                        <div class="col ms-2">
                            <h5 class="fw-bold mb-1 d-flex align-items-center gap-2">
                                {{ $empresa->razon_social }}
                            </h5>
                            <div class="d-flex flex-wrap gap-3 mb-2">
                                <span class="text-muted fs-13">
                                    RUC: {{ $empresa->ruc }}
                                </span>
                                <span class="text-muted fs-13">
                                    {{ $empresa->direccion }}
                                </span>
                            </div>
                            <p class="text-muted fs-13 mb-0" style="max-width: 800px; line-height: 1.5; text-align: justify;">
                                Configura la información fiscal y las credenciales necesarias para facturación electrónica.
                            </p>
                        </div>
                    </div>

                    <ul class="nav nav-tabs nav-bordered mb-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active fw-semibold" data-bs-toggle="tab" href="#personal" role="tab">
                                <span class="d-md-inline-block">Datos de la Empresa</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#sunat_tab" role="tab">
                                <span class="d-md-inline-block">Usuario SUNAT</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane show active" id="personal" role="tabpanel">
                    <div class="card border-0 shadow-sm business-tab-card" style="border-top-left-radius: 0; border-top-right-radius: 0;">
                        <div class="card-header bg-transparent border-bottom">
                            <h6 class="card-title mb-0 fw-bold">Información General</h6>
                        </div>
                        <div class="card-body p-4">
                            <form id="form-info" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="text-gray-9 fw-bold mb-3 d-block">Logo de la Empresa <span class="text-muted fw-normal fs-12">(Opcional)</span></label>
                                        <div class="d-flex align-items-center flex-column flex-sm-row">
                                            <div class="position-relative me-sm-3 mb-3 mb-sm-0">
                                                <div id="logo-preview-container"
                                                    class="avatar avatar-xxl border border-dashed bg-light d-flex align-items-center justify-content-center business-logo-box"
                                                    style="width: 100px; height: 100px; border-radius: 10px; overflow: hidden;">
                                                    <img id="preview-logo" src="{{ $logoUrl }}" alt="Vista previa del logo" class="w-100 h-100 object-fit-contain">
                                                </div>
                                            </div>

                                            <div class="d-inline-flex flex-column align-items-center align-items-sm-start">
                                                <div class="drag-upload-btn btn btn-sm btn-primary position-relative mb-2 business-upload-label">
                                                    Subir Logo
                                                    <input type="file"
                                                        class="form-control opacity-0 position-absolute start-0 top-0 w-100 h-100 cursor-pointer"
                                                        id="company_logo" name="logo" accept="image/jpeg,.jpg,.jpeg,image/png,.png">
                                                </div>
                                                <small class="text-muted fs-12">JPG o PNG. Máx 2MB.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-sm">RUC</label>
                                        <input type="text" name="ruc" class="form-control" value="{{ $empresa->ruc }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-sm">Teléfono de Contacto</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light-subtle business-input-addon">Tel</span>
                                            <input type="text" name="telefono" class="form-control"
                                                placeholder="Ej. +51 999 999 999" value="{{ $empresa->telefono }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium text-sm">Razón Social</label>
                                        <input type="text" name="razon_social" class="form-control text-uppercase"
                                            value="{{ $empresa->razon_social }}">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium text-sm">Nombre Comercial</label>
                                        <input type="text" name="nombre_comercial" class="form-control text-uppercase"
                                            value="{{ $empresa->nombre_comercial }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-sm">Dirección</label>
                                        <input type="text" name="direccion" class="form-control text-uppercase"
                                            value="{{ $empresa->direccion }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-sm">País</label>
                                        <select name="pais" class="form-select" disabled>
                                            <option value="PE">Perú</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-sm">Departamento</label>
                                        <select name="departamento" class="form-control select2_department"></select>
                                    </div>
                                    <div id="wrapper_province" class="col-md-4 d-none">
                                        <label class="form-label fw-medium text-sm">Provincia</label>
                                        <select name="provincia" class="form-control select2_province"></select>
                                    </div>
                                    <div id="wrapper_district" class="col-md-4 d-none">
                                        <label class="form-label fw-medium text-sm">Distrito</label>
                                        <select name="distrito" class="form-control select2_district"></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-sm">Urbanización</label>
                                        <input type="text" name="urbanizacion" class="form-control text-uppercase"
                                            value="{{ $empresa->urbanizacion }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-sm">Local</label>
                                        <input type="text" name="local" class="form-control text-uppercase"
                                            value="{{ $empresa->local }}">
                                    </div>
                                    <div class="col-md-12 mt-3">
                                        <div class="p-3 border rounded-3 bg-light-subtle business-soft-panel">
                                            <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                                                <div>
                                                    <label class="form-check-label fw-bold text-dark d-block" for="cobrar_igv">
                                                        Estado del Impuesto: <span id="status-igv" class="{{ $empresa->cobrar_igv ? 'text-primary' : 'text-success' }}">
                                                            {{ $empresa->cobrar_igv ? 'Régimen General (18%)' : 'Exonerado (Ley Amazonía)' }}
                                                        </span>
                                                    </label>
                                                    <small class="text-muted">Active esta opción solo si su negocio está obligado a recaudar IGV.</small>
                                                </div>
                                                <input class="form-check-input ms-0" type="checkbox" role="switch" id="cobrar_igv" name="cobrar_igv"
                                                    style="width: 45px; height: 22px; cursor: pointer;"
                                                    {{ $empresa->cobrar_igv ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="button" class="btn btn-primary px-4 btn-save-info">
                                        Guardar cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="sunat_tab" role="tabpanel">
                    <div class="card border-0 shadow-sm business-tab-card" style="border-top-left-radius: 0; border-top-right-radius: 0;">
                        <div class="card-header bg-transparent border-bottom">
                            <h6 class="card-title mb-0 fw-bold">Credenciales SUNAT</h6>
                        </div>
                        <div class="card-body p-4">
                            <form id="form_info_user" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="border rounded-3 p-3 bg-light-subtle h-100 business-cert-box">
                                            <div class="text-muted small fw-bold mb-2">Estado del Entorno SUNAT</div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="sunat-environment-badge"
                                                    class="badge {{ (string) $empresa->servidor_sunat === '1' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                                    {{ (string) $empresa->servidor_sunat === '1' ? 'Produccion' : 'Beta' }}
                                                </span>
                                                <span id="sunat-environment-text" class="small text-muted">
                                                    {{ (string) $empresa->servidor_sunat === '1' ? 'Listo para entorno productivo.' : 'Modo pruebas activo para integraciones y validaciones.' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded-3 p-3 bg-light-subtle h-100 business-cert-box">
                                            <div class="text-muted small fw-bold mb-2">Estado del Certificado</div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="certificate-status-badge"
                                                    class="badge {{ !empty($empresa->certificado) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                    {{ !empty($empresa->certificado) ? 'Cargado' : 'Pendiente' }}
                                                </span>
                                                <span id="certificate-status-text" class="small text-muted">
                                                    {{ !empty($empresa->certificado) ? basename($empresa->certificado) : 'Aun no se ha cargado un certificado digital.' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium text-sm">Nombre Comercial</label>
                                        <input type="text" name="nombre_comercial" class="form-control"
                                            value="{{ $empresa->nombre_comercial }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-sm">Usuario Secundario (SOL)</label>
                                        <input type="text" name="usuario_sunat" class="form-control"
                                            value="{{ $empresa->usuario_sunat }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-sm">Clave SOL</label>
                                        <input type="text" name="clave_sunat" class="form-control"
                                            value="{{ $empresa->clave_sunat }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-sm">Clave Certificado</label>
                                        <input type="text" name="clave_certificado" class="form-control"
                                            value="{{ $empresa->clave_certificado }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-sm">GRE Client ID</label>
                                        <input type="text" name="gre_client_id" class="form-control"
                                            value="{{ $empresa->gre_client_id }}" placeholder="Credencial GRE">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-sm">GRE Client Secret</label>
                                        <input type="text" name="gre_client_secret" class="form-control"
                                            value="{{ $empresa->gre_client_secret }}" placeholder="Secreto GRE">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-sm">Vencimiento Certificado</label>
                                        <input type="date" name="vencimiento_certificado" class="form-control"
                                            value="{{ optional($empresa->vencimiento_certificado)->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium text-sm">Certificado Actual</label>
                                        <div id="certificate-current-badge"
                                            class="form-control bg-light-subtle d-flex align-items-center business-soft-panel">
                                            {{ !empty($empresa->certificado) ? basename($empresa->certificado) : 'Sin certificado cargado' }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-sm">Certificado Digital (.pfx)</label>
                                        <div class="input-group">
                                            <label class="input-group-text bg-light-subtle business-input-addon" for="certificado">
                                                Archivo
                                            </label>
                                            <input type="file" name="certificado" class="form-control"
                                                id="certificado" accept=".pfx,application/x-pkcs12">
                                        </div>
                                        <small id="certificate-selected-name" class="text-primary d-block mt-1" style="font-size: 11px;"></small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">ENTORNO SUNAT</label>
                                        <div class="btn-group w-100 shadow-none" role="group">
                                            <input type="radio" class="btn-check" name="servidor_sunat" id="beta"
                                                value="3" {{ $empresa->servidor_sunat == 3 ? 'checked' : '' }}>
                                            <label class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2 py-2" for="beta">
                                                <span class="small">Pruebas</span>
                                            </label>

                                            <input type="radio" class="btn-check" name="servidor_sunat" id="prod"
                                                value="1" {{ $empresa->servidor_sunat == 1 ? 'checked' : '' }}>
                                            <label class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2 py-2" for="prod">
                                                <span class="small">Producción</span>
                                            </label>
                                        </div>
                                    </div>

                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="button" class="btn btn-primary px-4 btn-save-user">
                                        Actualizar Credenciales
                                        <span class="text-save-user d-none">Actualizando...</span>
                                        <span class="spinner-border spinner-border-sm d-none text-saving-user"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('admin.business.js-home')
@endsection
