<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Bien Patrimonial - IESTP FVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0b1e36 0%, #1e3a8a 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 15px;
        }

        .verify-card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 520px;
            overflow: hidden;
        }

        .verify-header {
            background: #003366;
            color: #ffffff;
            padding: 24px 20px;
            text-align: center;
            position: relative;
        }

        .verify-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #10b981;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 50rem;
            margin-top: 10px;
            letter-spacing: 0.5px;
        }

        .asset-code-pill {
            font-family: monospace;
            font-size: 1.25rem;
            font-weight: 800;
            color: #003366;
            background-color: #f1f5f9;
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-block;
            margin: 15px 0 10px 0;
            border: 1px solid #cbd5e1;
        }

        .data-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.92rem;
        }

        .data-label {
            color: #64748b;
            font-weight: 500;
        }

        .data-value {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="verify-card">
        <div class="verify-header">
            <h6 class="text-uppercase mb-1 fw-bold tracking-wider" style="font-size: 0.8rem; letter-spacing: 1px; color: #93c5fd;">
                Ministerio de Educación &bull; Perú
            </h6>
            <h5 class="mb-0 fw-bold" style="font-size: 1.15rem;">
                IESTP "FRANCISCO VIGO CABALLERO"
            </h5>
            <div class="small opacity-75">Uchiza - Tocache - San Martín</div>

            <div>
                <span class="verify-badge">
                    <i class="fas fa-check-circle"></i> ACTIVO PATRIMONIAL OFICIAL
                </span>
            </div>
        </div>

        <div class="p-4">
            <div class="text-center">
                <span class="text-muted small fw-bold text-uppercase d-block">Código de Activo</span>
                <div class="asset-code-pill">
                    {{ $asset->codigo ?: ($asset->codigo_producto ?: 'ACT-' . $asset->id) }}
                </div>
                <h5 class="fw-bold text-dark mb-3">{{ $asset->descripcion }}</h5>
            </div>

            @if ($asset->foto_path)
                <div class="text-center mb-3">
                    <img src="{{ asset($asset->foto_path) }}" alt="Foto del activo" class="img-fluid rounded border shadow-sm" style="max-height: 180px;">
                </div>
            @endif

            <div class="mt-3">
                <div class="data-row">
                    <span class="data-label">Departamento / Área:</span>
                    <span class="data-value">{{ $asset->area?->name }}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Ubicación Física:</span>
                    <span class="data-value text-primary"><i class="fas fa-map-marker-alt me-1"></i>{{ $asset->ubicacion }}</span>
                </div>
                @if ($asset->custodio)
                    <div class="data-row">
                        <span class="data-label">Custodio / Encargado:</span>
                        <span class="data-value">{{ $asset->custodio }}</span>
                    </div>
                @endif
                <div class="data-row">
                    <span class="data-label">Marca:</span>
                    <span class="data-value">{{ $asset->marca }}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Modelo:</span>
                    <span class="data-value">{{ $asset->modelo }}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Número de Serie:</span>
                    <span class="data-value font-monospace">{{ $asset->serie }}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Condición Física:</span>
                    <span class="data-value">{!! $asset->condicion_badge !!}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Tipo Adquisición:</span>
                    <span class="data-value">{!! $asset->tipo_adquisicion_badge !!}</span>
                </div>
                @if ($asset->fecha_adquisicion || $asset->anio_adquisicion)
                    <div class="data-row">
                        <span class="data-label">Fecha / Año Adq.:</span>
                        <span class="data-value">{{ $asset->fecha_adquisicion ? $asset->fecha_adquisicion->format('d/m/Y') : $asset->anio_adquisicion }}</span>
                    </div>
                @endif
                <div class="data-row">
                    <span class="data-label">Auditoría Física:</span>
                    <span class="data-value">
                        @if ($asset->is_reconciled)
                            <span class="text-success"><i class="fas fa-check-double me-1"></i>Conciliado</span>
                        @else
                            <span class="text-warning"><i class="fas fa-clock me-1"></i>Pendiente</span>
                        @endif
                    </span>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 0.78rem;">
                <i class="fas fa-shield-alt text-primary me-1"></i>
                Validado electrónicamente por el Sistema ERP Institucional &bull; UUID: <span class="font-monospace">{{ substr($asset->uuid, 0, 13) }}...</span>
            </div>
        </div>
    </div>

</body>
</html>
