<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - EasyStock</title>

    <link href="{{ asset('css/styles.css') }}" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/img/favicon-white.ico') }}" type="image/x-icon">
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
</head>

<body>

    <div class="login-wrapper">
        <!-- Form Area -->
        <div class="login-form-area">
            <div class="brand-logo-container">
                @if (empty($logo))
                    <img src="{{ asset('files/empty_logo.png') }}" alt="Logo EasyStock">
                @else
                    <img src="{{ asset('files/logos/' . $logo) }}" alt="Logo EasyStock">
                @endif
            </div>

            <h2 class="fw-bold mb-1" style="color: #212529;">Bienvenido</h2>
            <p class="text-muted mb-4" style="font-size: 0.95rem;">Ingresa tus credenciales para continuar.</p>

            <form method="POST" action="{{ route('login.login') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="user" class="form-label text-muted fw-semibold"
                        style="font-size: 0.85rem;">Usuario</label>
                    <input id="user" type="text" name="user"
                        class="form-control @if (session('message')) is-invalid @endif"
                        placeholder="Ingresa tu nombre de usuario" required autocomplete="username" autofocus
                        value="testuser">
                    @if (session('message'))
                        <div class="invalid-feedback">{{ session('message') }}</div>
                    @endif
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label text-muted fw-semibold"
                        style="font-size: 0.85rem;">Contraseña</label>
                    <div class="input-group">
                        <input id="password" type="password" name="password" class="form-control border-end-0"
                            placeholder="••••••••" required autocomplete="current-password" value="Test1234$$.">
                        <button class="btn border border-start-0 bg-light" type="button" id="togglePassword"
                            style="border-radius: 0 10px 10px 0; border-color:#dee2e6;">
                            <i class="fas fa-eye text-muted"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="btn btn-primary btn-primary-custom w-100 text-white d-flex justify-content-center align-items-center">
                    <span>Acceder</span>
                    <i class="fas fa-arrow-right ms-2" style="font-size: 0.85rem;"></i>
                </button>
            </form>

            <div class="mt-4 text-center text-muted" style="font-size: 0.8rem;">
                &copy; {{ date('Y') }} EasyStock POS.
            </div>
        </div>

        <!-- Info Area -->
        <div class="login-info-area">
            <h3 class="fw-bold mb-2" style="color:#212529;">Agiliza tus operaciones.</h3>
            <p class="text-muted mb-4" style="font-size:0.95rem;">Un espacio diseñado para hacer tu inventario rápido,
                preciso y confiable.</p>

            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                <div class="feature-text">
                    <h5>Rápido y Fluido</h5>
                    <p>Punto de venta súper optimizado para alta demanda.</p>
                </div>
            </div>

            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
                <div class="feature-text">
                    <h5>Información Clara</h5>
                    <p>Toma decisiones con reportes detallados y concisos.</p>
                </div>
            </div>

            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="feature-text">
                    <h5>Respaldo Seguro</h5>
                    <p>La información siempre disponible y protegida.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('ajax/libs/font-awesome/6.3.0/js/all.min.js') }}" defer></script>
    <script src="{{ asset('npm/bootstrap%405.2.3/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        (function() {
            const btn = document.getElementById('togglePassword');
            const input = document.getElementById('password');
            if (!btn || !input) return;

            btn.addEventListener('click', function() {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                this.innerHTML = isPassword ? '<i class="fas fa-eye-slash text-muted"></i>' :
                    '<i class="fas fa-eye text-muted"></i>';
            });
        })();
    </script>
</body>

</html>
