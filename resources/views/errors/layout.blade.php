<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Error') — ERP-FVC</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon-white.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f3f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 2rem 1rem;
            color: #212529;
        }

        /* ─── Background grid pattern ─── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 51, 102, 0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 51, 102, 0.025) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .error-card {
            position: relative;
            z-index: 1;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04);
            padding: 3.5rem 4rem;
            max-width: 560px;
            width: 100%;
            text-align: center;
        }

        /* ─── Code badge ─── */
        .error-code-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: @yield('badge-bg', '#fff3cd');
            color: @yield('badge-color', '#664d03');
            border: 1px solid @yield('badge-border', '#ffecb5');
            border-radius: 50px;
            padding: 0.35rem 1rem;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }

        .error-code-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: @yield('badge-color', '#664d03');
        }

        /* ─── Illustration number ─── */
        .error-number {
            font-size: clamp(5rem, 20vw, 8rem);
            font-weight: 800;
            letter-spacing: -0.05em;
            line-height: 1;
            margin: 0 0 1rem;
            background: linear-gradient(135deg, @yield('grad-from', '#003366') 0%, @yield('grad-to', '#0d6efd') 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            user-select: none;
        }

        .error-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #212529;
            margin: 0 0 0.75rem;
        }

        .error-description {
            font-size: 0.95rem;
            color: #6c757d;
            line-height: 1.65;
            margin: 0 0 2rem;
        }

        /* ─── Actions ─── */
        .error-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary-erp {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #003366;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.65rem 1.4rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.18s, transform 0.12s, box-shadow 0.18s;
            cursor: pointer;
        }
        .btn-primary-erp:hover {
            background: #00254d;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 51, 102, 0.25);
        }
        .btn-primary-erp:active { transform: translateY(0); }

        .btn-secondary-erp {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 0.65rem 1.4rem;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.18s, border-color 0.18s, transform 0.12s;
            cursor: pointer;
        }
        .btn-secondary-erp:hover {
            background: #e9ecef;
            border-color: #ced4da;
            color: #212529;
            transform: translateY(-1px);
        }

        /* ─── Footer watermark ─── */
        .error-footer {
            position: fixed;
            bottom: 1.25rem;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.72rem;
            color: #adb5bd;
            white-space: nowrap;
            z-index: 1;
        }

        /* ─── Detail block (for debug info) ─── */
        .error-detail-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 0.85rem 1rem;
            font-size: 0.8rem;
            color: #6c757d;
            text-align: left;
            margin-top: 1.5rem;
            font-family: 'SFMono-Regular', Consolas, monospace;
            word-break: break-all;
        }

        @media (max-width: 480px) {
            .error-card { padding: 2.5rem 1.75rem; }
            .error-number { font-size: 5rem; }
        }
    </style>
</head>
<body>

<div class="error-card">
    @yield('badge')

    <div class="error-number">@yield('code', 'ERR')</div>

    <h1 class="error-title">@yield('title', 'Ocurrió un error')</h1>
    <p class="error-description">@yield('description')</p>

    @yield('extra')

    <div class="error-actions">
        @yield('actions')
    </div>

    @yield('detail')
</div>

<div class="error-footer">ERP-FVC &nbsp;·&nbsp; Sistema Integral de Gestión Empresarial</div>

</body>
</html>
