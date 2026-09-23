# ERP-FVC: Sistema Integral de Gestión Empresarial e Institucional

![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![SUNAT](https://img.shields.io/badge/SUNAT-Facturación%20Electrónica%20%26%20GRE-blue?style=for-the-badge)

Sistema unificado para la gestión comercial, facturación electrónica conforme a la normativa SUNAT (UBL 2.1 y GRE Remitente), control de inventarios multi-almacén valorizados, flujo documental y trámites internos con aprobaciones jerárquicas digitales, y control técnico de **Bienes Patrimoniales** con rotulación QR y subsistema de **Préstamos de Bienes** para estudiantes, docentes y personal administrativo.

Para una especificación técnica detallada de los procesos de negocio, flujos y modelo de datos, consulte el [Documento PDR (Product Definition Report)](PDR.md).

---

## Módulos del Sistema

### 1. Bienes Patrimoniales y Activos Fijos
- **Inventario Institucional:** Ficha técnica de bienes según catálogo SBN, código interno, marca, modelo, serie, ubicación física, condición (`Bueno`, `Regular`, `Malo`, `Baja`), tipo de adquisición (`Compra` o `Donación`), costos y conciliación/auditoría física en campo.
- **Rótulos y Códigos QR:** Generación e impresión de etiquetas con código QR en SVG vectorial para verificación pública móvil sin necesidad de autenticación previa (`/inventory/verify/{uuid}`).
- **Actas de Inventario Anual:** Apertura de actas por área y periodo fiscal con circuito de firmas digitales (Responsable del Área, Patrimonio y Dirección General) y exportación oficial a PDF A4 apaisado.
- **Préstamos de Bienes (Loans):**
  - Perfiles soportados: **Estudiantes** (*alumnos*), **Docentes** y **Personal Administrativo**.
  - Control de disponibilidad física en tiempo real (impide prestar activos ya en uso o de baja).
  - Plazos con cálculo automático de préstamos vencidos.
  - Registro de devoluciones rápidas con sincronización opcional de la condición física en el inventario general.
  - Emisión de Papeleta Oficial de Préstamo en PDF con cláusula de custodia y 3 casillas de firma.

### 2. Trámites Internos y Aprobaciones Jerárquicas
- **Cadena Jerárquica Dinámica (`Area::approvalChain`):** Ascenso por el organigrama arbóreo institucional desde el área del solicitante hasta Dirección General.
- **Requerimientos de Bienes y Servicios:** Pedidos internos de compra y abastecimiento con especificaciones, cantidades y flujo de firmas.
- **Declaraciones Juradas de Gastos:** Rendición de viáticos y movilidad local con sustento legal y PDF oficial.
- **Papeletas de Salida de Personal:** Permisos por comisión de servicio, salud o asuntos particulares con cómputo de horas.
- **Papeletas de Salida de Vehículos:** Asignación de conductor, control de kilometraje de ida/retorno, ruta y combustible.
- **Papeletas de Vacaciones:** Solicitud y registro formal del descanso vacacional anual.
- **Vales de Control de Combustible:** Asignación de galones/litros de combustible a unidades y maquinaria.
- **Bandeja Centralizada de Aprobaciones (`/approvals`):** Bandeja unificada para directores y jefes con opciones de Aprobar (firma digital), Observar o Rechazar.

### 3. Ventas y Facturación Electrónica (SUNAT)
- **Punto de Venta (POS):** Venta rápida con lector de código de barras, selección de medios de pago (efectivo, Yape, Plin, tarjeta, transferencia) y enlace con caja activa.
- **Comprobantes Electrónicos:** Facturas (`01`), Boletas de Venta (`03`), Notas de Crédito (`07`), Notas de Débito (`08`) con emisión UBL 2.1, firmado digital XML, consulta de CDR y código QR impreso.
- **Guías de Remisión Electrónica (GRE Remitente):** Integración con API REST SUNAT, transporte público y privado, placas primarias y secundarias, conductores y transportistas.
- **Notas de Venta y Créditos:** Documentos internos de venta con cronograma de pagos y registro de amortizaciones en cuotas.
- **Cotizaciones:** Emisión formal de propuestas comerciales con conversión directa a comprobante de venta.
- **Clientes:** Consulta en línea de DNI (RENIEC) y RUC (SUNAT) con autocompletado y gestión de ubigeos oficiales a 6 dígitos.

### 4. Compras y Proveedores
- **Proveedores Fiscales:** Registro tributario con RUC/DNI, razón social y datos fiscales.
- **Gestión de Compras:** Ingreso de mercadería por factura de proveedor, actualización de costos de compra (promedio/último) y afectación automática de inventarios.

### 5. Inventario, Almacenes y Kardex
- **Catálogo de Productos y Servicios:** Código de barras, código SUNAT, unidad de medida, categoría, precios de lista y mínimos, tipo de afectación IGV.
- **Multi-Almacén:** Stock independiente por sucursal física (`stock_products`) con alertas automáticas de reposición.
- **Órdenes de Traslado:** Movimientos formales de existencias entre almacenes con confirmación de recepción.
- **Kardex:** Trazabilidad física y valorizada de entradas, salidas y saldos por almacén.

### 6. Cajas y Tesorería
- **Cajas Registradoras:** Definición de cajas físicas por sucursal y usuario.
- **Arqueos de Caja Chica (Arching Cash):** Apertura de turno con fondo inicial, registro de egresos e ingresos en efectivo, consolidación de cobros y cuadre final con detección de sobrantes y faltantes.

### 7. Reportes y Contabilidad
- **Registro de Ventas e Ingresos (PLE / SUNAT):** Reporte oficial contable para declaraciones tributarias.
- **Reporte de Ventas por Producto, Vendedor y Almacén.**
- **Reporte de Cobranzas y Amortizaciones de Créditos.**

### 8. Configuración y Seguridad
- **Empresa Emisora:** RUC, razón social, certificado digital (PFX/PEM), credenciales SOL y API GRE.
- **Organigrama y Áreas:** Estructura jerárquica con jefaturas y niveles de reporte.
- **Control de Acceso (RBAC):** 17 roles preconfigurados en Spatie y middleware de control departamental.

---

## Requisitos del Sistema

- **PHP:** Versión 8.1 o superior (Recomendado PHP 8.3 con extensiones `pdo_mysql`, `mbstring`, `openssl`, `xml`, `curl`, `gd`, `zip`).
- **Base de Datos:** MySQL 8.0+ o MariaDB 10.6+.
- **Composer:** Versión 2.x.
- **Node.js:** Versión 18+ y NPM (para compilación de assets).
- **Servidor Web:** Apache / Nginx con soporte de reescritura de URL (`mod_rewrite`).

---

## Instalación y Configuración Local

### 1. Clonar el Repositorio
```bash
git clone <url-del-repositorio> erp-fvc
cd erp-fvc
```

### 2. Instalar Dependencias de PHP
```bash
composer install
```

### 3. Configurar el Entorno (`.env`)
Copie el archivo de ejemplo y genere la clave de aplicación:
```bash
cp .env.example .env
php artisan key:generate
```

Configure los parámetros de conexión a su base de datos en `.env`:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp_fvc_db
DB_USERNAME=root
DB_PASSWORD=su_contraseña
```

### 4. Ejecutar Migraciones y Seeders
Cree las tablas y cargue los datos maestros de ubigeo, catálogo SUNAT, áreas institucionales, roles y usuarios iniciales:
```bash
php artisan migrate --seed
```

### 5. Enlazar el Almacenamiento Público
```bash
php artisan storage:link
```

### 6. Instalar y Compilar Dependencias Frontend
```bash
npm install
npm run build
```

### 7. Iniciar el Servidor de Desarrollo
```bash
php artisan serve
```
El sistema estará accesible en `http://127.0.0.1:8000`.

---

## Credenciales y Roles Iniciales

| Usuario | Rol Principal | Contraseña por Defecto | Alcance |
|---|---|---|---|
| `admin` / SuperAdmin | `SUPERADMIN` | *Configurada en Seeder* | Control total del sistema |
| `patrimonio` | `PATRIMONIO` | *Configurada en Seeder* | Gestión de bienes, QR, actas y préstamos |
| `abastecimiento` | `ABASTECIMIENTO` | *Configurada en Seeder* | Compras, proveedores y logística |
| `director` | `DIRECTOR_GENERAL` | *Configurada en Seeder* | Aprobaciones institucionales y reportes |
| `cajero` | `CAJERO` | *Configurada en Seeder* | Punto de venta y arqueo de caja |
| `vendedor` | `VENDEDOR` | *Configurada en Seeder* | Ventas, POS y cotizaciones |

---

## Comandos Útiles de Mantenimiento

- **Limpiar Caché General:**
  ```bash
  php artisan optimize:clear
  ```
- **Verificar Rutas Activas:**
  ```bash
  php artisan route:list
  ```
- **Revisar Estado de Migraciones:**
  ```bash
  php artisan migrate:status
  ```
- **Ejecutar Pruebas Unitarias:**
  ```bash
  php artisan test
  ```

---

## Estructura del Proyecto

```
erp-fvc/
├── app/
│   ├── Exports/            # Clases para exportación Excel
│   ├── Http/
│   │   ├── Controllers/    # Controladores de todos los módulos
│   │   ├── Middleware/     # CheckAssetAccess, RBAC, Authenticate
│   │   └── Requests/       # Form Requests (validación de datos)
│   ├── Imports/            # Clases para importación Excel
│   └── Models/             # Modelos Eloquent (Asset, AssetLoan, User, Area, etc.)
├── config/                 # Archivos de configuración de Laravel
├── database/
│   ├── migrations/         # Esquema de base de datos
│   └── seeders/            # Semillas de roles, permisos, ubigeos y catálogos
├── docs/                   # Documentación técnica, formatos y PDR
│   ├── PDR.md              # Product Definition Report integral
│   └── formats/            # Formatos de referencia institucionales
├── public/                 # Punto de entrada público (index.php, uploads, assets)
├── resources/
│   └── views/              # Vistas Blade estructuradas por módulos
│       ├── admin/
│       │   ├── assets/     # Vistas de Bienes Patrimoniales
│       │   │   ├── loans/  # Subsistema de Préstamos (Index, Create, Edit, PDF)
│       │   │   └── pdf/    # Formatos de inventario en PDF
│       │   ├── billings/   # Comprobantes y Facturación Electrónica
│       │   ├── pos/        # Punto de Venta
│       │   └── layout.blade.php  # Plantilla base y navegación
├── routes/
│   └── web.php             # Definición de rutas del sistema
├── PDR.md                  # Copia raíz del Product Definition Report
└── README.md               # Este documento
```

---

## Licencia y Soporte
Sistema desarrollado para uso empresarial e institucional exclusivo. Todos los derechos reservados.
Para consultas y requerimientos técnicos, remitirse al equipo de desarrollo institucional.
