<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas QR de Bienes Patrimoniales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #111;
        }

        .labels-container {
            max-width: 900px;
            margin: 20px auto;
        }

        .sticker-card {
            border: 1.5px dashed #003366;
            border-radius: 8px;
            background-color: #ffffff;
            padding: 8px 10px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            page-break-inside: avoid;
        }

        .sticker-qr {
            width: 100px;
            min-width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sticker-qr svg {
            width: 100%;
            height: 100%;
        }

        .sticker-info {
            flex-grow: 1;
            font-size: 11px;
            line-height: 1.25;
        }

        .sticker-inst-title {
            font-size: 10px;
            font-weight: 800;
            color: #003366;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .sticker-inst-sub {
            font-size: 8.5px;
            color: #b91c1c;
            font-weight: bold;
        }

        .sticker-code-box {
            background-color: #003366;
            color: #ffffff;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
            font-family: monospace;
            font-size: 12px;
            font-weight: bold;
            margin: 3px 0;
        }

        .sticker-desc {
            font-weight: bold;
            font-size: 11px;
            color: #1e293b;
            margin-bottom: 2px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sticker-meta {
            font-size: 9px;
            color: #475569;
        }

        @media print {
            body {
                background-color: #ffffff;
            }
            .no-print {
                display: none !important;
            }
            .labels-container {
                max-width: 100%;
                margin: 0;
            }
            .sticker-card {
                border: 1px solid #333;
                box-shadow: none;
                margin-bottom: 8px;
            }
        }
    </style>
</head>
<body>

    <div class="no-print bg-dark text-white py-3 shadow-sm mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fas fa-qrcode me-2"></i>Impresión de Rótulos y Etiquetas QR</h5>
                <small class="text-light">IESTP "Francisco Vigo Caballero" - Total etiquetas: {{ $labels->count() }}</small>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-success fw-bold me-2">
                    <i class="fas fa-print me-1"></i> Mandar a Imprimir
                </button>
                <button onclick="window.close()" class="btn btn-outline-light">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <div class="labels-container px-3">
        <div class="row g-3">
            @forelse ($labels as $item)
                @php $asset = $item['asset']; @endphp
                <div class="col-md-6 col-sm-12">
                    <div class="sticker-card">
                        <div class="sticker-qr">
                            {!! $item['qr_svg'] !!}
                        </div>
                        <div class="sticker-info">
                            <div class="sticker-inst-title">IESTP "FRANCISCO VIGO CABALLERO"</div>
                            <div class="sticker-inst-sub">CONTROL PATRIMONIAL INSTITUCIONAL</div>
                            <div class="sticker-code-box">
                                {{ $asset->codigo ?: ($asset->codigo_producto ?: 'ACT-' . str_pad($asset->id, 5, '0', STR_PAD_LEFT)) }}
                            </div>
                            <div class="sticker-desc">{{ $asset->descripcion }}</div>
                            <div class="sticker-meta">
                                <strong>ÁREA:</strong> {{ $asset->area?->name ?? 'INSTITUCIONAL' }}<br>
                                <strong>SERIE:</strong> {{ $asset->serie }} &bull; <strong>COND.:</strong> {{ $asset->condicion_label }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <div class="alert alert-warning">No hay bienes patrimoniales disponibles para generar etiquetas en este momento.</div>
                </div>
            @endforelse
        </div>
    </div>

</body>
</html>
