<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Oficial de Certificado - IESTP FVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: system-ui, -apple-system, sans-serif;
            color: #333;
        }
        .cert-card {
            max-width: 650px;
            margin: 40px auto;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
        }
        .cert-header {
            background: linear-gradient(135deg, #1a365d 0%, #2b6cb0 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        .cert-badge-success {
            background-color: #38a169;
            color: white;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 15px;
        }
        .cert-badge-danger {
            background-color: #e53e3e;
            color: white;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 15px;
        }
        .detail-item {
            padding: 12px 0;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #718096;
            font-size: 0.9rem;
        }
        .detail-value {
            font-weight: 600;
            color: #1a202c;
            text-align: right;
            max-width: 65%;
        }
        .hash-code {
            font-family: monospace;
            font-size: 0.75rem;
            word-break: break-all;
            background: #edf2f7;
            padding: 6px 10px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card cert-card">
            <div class="cert-header">
                <i class="fas fa-graduation-cap fa-3x mb-2 text-warning"></i>
                <h4 class="mb-1 fw-bold">IESTP "Francisco Vigo Caballero"</h4>
                <div class="small opacity-75">Sistema Oficial de Verificación de Certificados y Constancias Técnicas</div>

                @if(!empty($data) && $data['valid'])
                    <div class="cert-badge-success">
                        <i class="fas fa-check-circle"></i> Certificado Auténtico y Vigente
                    </div>
                @else
                    <div class="cert-badge-danger">
                        <i class="fas fa-times-circle"></i> Certificado No Encontrado o Inválido
                    </div>
                @endif
            </div>

            <div class="card-body p-4 bg-white">
                @if(!empty($data) && $data['valid'])
                    <div class="alert alert-light border small text-center mb-3 text-muted">
                        Este documento digital cuenta con firma y visación institucional registrada en la base de datos oficial del IESTP FVC.
                    </div>

                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-barcode me-1 text-primary"></i> Código de Certificado</span>
                        <span class="detail-value text-primary fs-6 font-monospace">{{ $data['certificate_code'] }}</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-user me-1 text-primary"></i> Participante</span>
                        <span class="detail-value">{{ $data['participant_name'] }}</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-id-card me-1 text-primary"></i> N° de Documento (DNI)</span>
                        <span class="detail-value font-monospace">{{ $data['participant_document'] }}</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-book-open me-1 text-primary"></i> Curso / Servicio Tecnológico</span>
                        <span class="detail-value text-dark">{{ $data['service_name'] }}</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-building me-1 text-primary"></i> Organización / Contraparte</span>
                        <span class="detail-value">{{ $data['organization'] }}</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-clock me-1 text-primary"></i> Horas Académicas</span>
                        <span class="detail-value">{{ $data['total_hours'] }} horas</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-check-double me-1 text-primary"></i> Porcentaje de Asistencia</span>
                        <span class="detail-value text-success">{{ $data['attendance_percent'] }}%</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-calendar-alt me-1 text-primary"></i> Fecha de Emisión</span>
                        <span class="detail-value">{{ $data['issued_at'] ?? 'Registrada' }}</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-user-tie me-1 text-primary"></i> Especialista / Instructor</span>
                        <span class="detail-value">{{ $data['responsible_name'] }}</span>
                    </div>

                    @if(!empty($data['hash']))
                    <div class="mt-3 pt-3 border-top">
                        <div class="small text-muted mb-1"><i class="fas fa-fingerprint me-1"></i> Firma Digital SHA-256:</div>
                        <div class="hash-code text-muted">{{ $data['hash'] }}</div>
                    </div>
                    @endif
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5 class="fw-bold">No se encontró registro para el código:</h5>
                        <div class="font-monospace text-muted fs-6 mb-3">{{ $code }}</div>
                        <p class="text-muted small">
                            Verifique que el código escaneado sea correcto o comuníquese con el Área de Servicios Tecnológicos del IESTP Francisco Vigo Caballero (Tocache, San Martín).
                        </p>
                    </div>
                @endif
            </div>

            <div class="card-footer bg-light text-center py-3 small text-muted">
                &copy; {{ date('Y') }} IESTP "Francisco Vigo Caballero" &bull; Servicios Tecnológicos Oficiales
            </div>
        </div>
    </div>
</body>
</html>
