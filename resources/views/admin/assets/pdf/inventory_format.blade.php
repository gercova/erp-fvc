<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $area->name }} - Formato Oficial de Inventario</title>
    <style>
        @page {
            margin: 15px 20px;
            size: a4 landscape;
        }

        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            color: #111;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-logo {
            width: 70px;
            vertical-align: middle;
            text-align: center;
        }

        .header-logo img {
            max-width: 65px;
            max-height: 50px;
        }

        .header-text {
            text-align: center;
            vertical-align: middle;
        }

        .inst-title {
            font-size: 11px;
            font-weight: 800;
            color: #003366;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .inst-subtitle {
            font-size: 12px;
            font-weight: bold;
            color: #003366;
            margin: 1px 0;
        }

        .inst-tagline {
            font-size: 7.5px;
            font-style: italic;
            color: #444;
        }

        .inst-resolutions {
            font-size: 7px;
            color: #555;
            margin-top: 1px;
        }

        .doc-title-bar {
            text-align: center;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 3px 0;
            margin: 4px 0 6px 0;
            color: #000;
            letter-spacing: 0.5px;
        }

        .meta-bar {
            width: 100%;
            margin-bottom: 6px;
            font-size: 8px;
        }

        .meta-bar td {
            padding: 2px 0;
        }

        /* Tabla Principal del Formato Oficial (Screenshots 2 y 3) */
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .inventory-table th {
            background-color: #f59e0b; /* Color amarillo oro como en screenshot */
            color: #000;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #111;
            padding: 3px 2px;
            font-size: 7.5px;
        }

        .inventory-table td {
            border: 1px solid #333;
            padding: 3px 3px;
            font-size: 7.5px;
            vertical-align: middle;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        /* Casilleros de las 5 Firmas Oficiales */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .signature-cell {
            width: 20%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 6px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 4px;
            font-weight: bold;
            font-size: 7.5px;
            color: #000;
            text-transform: uppercase;
        }

        .signature-stamp {
            border: 1px dashed #16a34a;
            background-color: #f0fdf4;
            color: #16a34a;
            border-radius: 3px;
            padding: 2px;
            font-size: 6.5px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .footer-note {
            text-align: right;
            font-size: 7px;
            color: #666;
            margin-top: 6px;
        }
    </style>
</head>
<body>

    <!-- Encabezado Oficial -->
    <table class="header-table">
        <tr>
            <td class="header-logo" style="width: 80px;">
                @php
                    $peruLogo = public_path('assets/img/peru.png');
                    if (!file_exists($peruLogo)) {
                        $peruLogo = public_path('files/logos/logo.jpg');
                    }
                @endphp
                @if(file_exists($peruLogo))
                    <img src="{{ $peruLogo }}" alt="Perú">
                @endif
            </td>
            <td class="header-text">
                <div class="inst-title">INSTITUTO DE EDUCACIÓN SUPERIOR TECNOLÓGICO PÚBLICO</div>
                <div class="inst-subtitle">"FRANCISCO VIGO CABALLERO"</div>
                <div class="inst-tagline">UCHIZA &bull; "Formando técnicos líderes en el Alto Huallaga"</div>
                <div class="inst-resolutions">CREADO: R. M. N° 0868-94-ED &bull; REVALIDADO: R. D. N° 0296-2006-ED</div>
            </td>
            <td class="header-logo" style="width: 80px;">
                @php
                    $instLogo = public_path('files/logos/logo.jpg');
                    if (!file_exists($instLogo)) {
                        $instLogo = public_path('assets/img/logo.png');
                    }
                @endphp
                @if(file_exists($instLogo))
                    <img src="{{ $instLogo }}" alt="IESTP FVC">
                @endif
            </td>
        </tr>
    </table>

    <!-- Título del Documento -->
    <div class="doc-title-bar">
        INVENTARIO GENERAL DEL ÁREA {{ strtoupper($area->name) }} - {{ $periodo }}
    </div>

    <!-- Metadatos de Responsables -->
    <table class="meta-bar">
        <tr>
            <td style="width: 65%;">
                <strong>RESPONSABLE:</strong> {{ strtoupper($responsableArea) }}<br>
                <strong>REALIZADO POR:</strong> {{ strtoupper($realizadoPor) }}
            </td>
            <td style="width: 35%; text-align: right; vertical-align: bottom;">
                <strong>REALIZADO EL INVENTARIO AL:</strong>
                {{ \Carbon\Carbon::parse($fechaInventario)->translatedFormat('d \d\e F \d\e\l Y') }}
            </td>
        </tr>
    </table>

    <!-- Tabla Principal con las Columnas Oficiales (Screenshots 2 y 3) -->
    <table class="inventory-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 25px;">N°<br>ORD</th>
                <th rowspan="2" style="width: 55px;">CÓDIGO<br>PRODUCTO</th>
                <th rowspan="2" style="width: 50px;">CÓDIGO</th>
                <th rowspan="2">DESCRIPCIÓN</th>
                <th rowspan="2" style="width: 60px;">MARCA</th>
                <th rowspan="2" style="width: 60px;">MODELO</th>
                <th rowspan="2" style="width: 65px;">SERIE</th>
                <th rowspan="2" style="width: 45px;">COSTO</th>
                <th colspan="4" style="width: 60px;">CONDICIÓN</th>
                <th colspan="2" style="width: 35px;">TIPO ADQ.</th>
                <th rowspan="2" style="width: 45px;">AÑO<br>ADQ.</th>
                <th rowspan="2" style="width: 70px;">UBICACIÓN /<br>ENC. AREA</th>
                <th rowspan="2" style="width: 80px;">OBSERVACIÓN</th>
            </tr>
            <tr>
                <th style="width: 15px;">B</th>
                <th style="width: 15px;">R</th>
                <th style="width: 15px;">M</th>
                <th style="width: 15px;">BAJA</th>
                <th style="width: 17px;">C</th>
                <th style="width: 18px;">D</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCosto = 0; @endphp
            @forelse ($assets as $asset)
                @php $totalCosto += (float)$asset->costo; @endphp
                <tr>
                    <td class="text-center font-mono fw-bold">{{ $asset->formatted_orden }}</td>
                    <td class="font-mono">{{ $asset->codigo_producto }}</td>
                    <td class="font-mono">{{ $asset->codigo }}</td>
                    <td>{{ $asset->descripcion }}</td>
                    <td>{{ $asset->marca }}</td>
                    <td>{{ $asset->modelo }}</td>
                    <td class="font-mono">{{ $asset->serie }}</td>
                    <td class="text-end">
                        {{ (float)$asset->costo > 0 ? 'S/. ' . number_format((float)$asset->costo, 2) : '' }}
                    </td>
                    <!-- Condición: B, R, M, BAJA -->
                    <td class="text-center fw-bold">{{ $asset->condicion === 'B' ? 'X' : '' }}</td>
                    <td class="text-center fw-bold">{{ $asset->condicion === 'R' ? 'X' : '' }}</td>
                    <td class="text-center fw-bold">{{ $asset->condicion === 'M' ? 'X' : '' }}</td>
                    <td class="text-center fw-bold">{{ $asset->condicion === 'BAJA' ? 'X' : '' }}</td>
                    <!-- Tipo Adquisición: C, D -->
                    <td class="text-center fw-bold">{{ $asset->tipo_adquisicion === 'C' ? 'X' : '' }}</td>
                    <td class="text-center fw-bold">{{ $asset->tipo_adquisicion === 'D' ? 'X' : '' }}</td>
                    <!-- Año Adq. -->
                    <td class="text-center">
                        {{ $asset->fecha_adquisicion ? $asset->fecha_adquisicion->format('d/m/Y') : ($asset->anio_adquisicion ?: '') }}
                    </td>
                    <!-- Ubicación o Enc. Área -->
                    <td>
                        {{ $asset->custodio ? $asset->custodio : $asset->ubicacion }}
                    </td>
                    <!-- Observación -->
                    <td>{{ $asset->observaciones }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="text-center py-3">No se registran bienes en esta área.</td>
                </tr>
            @endforelse
            <!-- Fila de Total -->
            <tr>
                <td colspan="7" class="text-end fw-bold">TOTAL GENERAL VALORIZADO:</td>
                <td class="text-end fw-bold font-mono">S/. {{ number_format($totalCosto, 2) }}</td>
                <td colspan="9" class="small text-muted">&nbsp;Total bienes: {{ $assets->count() }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Casilleros de las 5 Firmas Institucionales (Exacto como Screenshots 2 y 3) -->
    @php
        $approvals = $inventoryDoc?->approvals ?? collect();
        $findApproval = function($role) use ($approvals) {
            return $approvals->firstWhere('role_name', $role);
        };
        $directorSign = $findApproval('DIRECTOR_GENERAL');
        $unidadAdmSign = $findApproval('UNIDAD_ADMINISTRATIVA');
        $adminSign = $findApproval('ADMINISTRACION');
        $areaSign = $findApproval('JEFE_AREA');
        $abastSign = $findApproval('ABASTECIMIENTO');
    @endphp

    <table class="signatures-table">
        <tr>
            <!-- 1. DIRECTOR -->
            <td class="signature-cell">
                @if ($directorSign && $directorSign->isApproved())
                    <div class="signature-stamp">
                        FIRMADO DIGITALMENTE<br>
                        {{ $directorSign->approver_name }}<br>
                        {{ $directorSign->signed_at?->format('d/m/Y H:i') }}
                    </div>
                @endif
                <div class="signature-line">DIRECTOR</div>
            </td>

            <!-- 2. JEFE DE UNIDAD ADMINISTRATIVA -->
            <td class="signature-cell">
                @if ($unidadAdmSign && $unidadAdmSign->isApproved())
                    <div class="signature-stamp">
                        FIRMADO DIGITALMENTE<br>
                        {{ $unidadAdmSign->approver_name }}<br>
                        {{ $unidadAdmSign->signed_at?->format('d/m/Y H:i') }}
                    </div>
                @endif
                <div class="signature-line">JEFE DE UNIDAD ADMINISTRATIVA</div>
            </td>

            <!-- 3. ADMINISTRADOR -->
            <td class="signature-cell">
                @if ($adminSign && $adminSign->isApproved())
                    <div class="signature-stamp">
                        FIRMADO DIGITALMENTE<br>
                        {{ $adminSign->approver_name }}<br>
                        {{ $adminSign->signed_at?->format('d/m/Y H:i') }}
                    </div>
                @endif
                <div class="signature-line">ADMINISTRADOR</div>
            </td>

            <!-- 4. RESP. DEL AREA -->
            <td class="signature-cell">
                @if ($areaSign && $areaSign->isApproved())
                    <div class="signature-stamp">
                        FIRMADO DIGITALMENTE<br>
                        {{ $areaSign->approver_name }}<br>
                        {{ $areaSign->signed_at?->format('d/m/Y H:i') }}
                    </div>
                @endif
                <div class="signature-line">RESP. DEL AREA</div>
            </td>

            <!-- 5. RESP. DE ABASTECIMIENTO -->
            <td class="signature-cell">
                @if ($abastSign && $abastSign->isApproved())
                    <div class="signature-stamp">
                        FIRMADO DIGITALMENTE<br>
                        {{ $abastSign->approver_name }}<br>
                        {{ $abastSign->signed_at?->format('d/m/Y H:i') }}
                    </div>
                @endif
                <div class="signature-line">RESP. DE ABASTECIMIENTO</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Documento oficial emitido por el Sistema Integrado de Gestión Institucional &bull; IESTP "Francisco Vigo Caballero"
    </div>

</body>
</html>
