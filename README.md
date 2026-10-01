# ERP-FVC: Sistema Integral de Gestión Empresarial e Institucional

![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![SUNAT](<https://img.shields.io/badge/SUNAT-Facturación%20Electrónica%20%26%20GRE-blue?style=for-the-badge>)
![Version](<https://img.shields.io/badge/Version-3.0%20Enterprise-success?style=for-the-badge>)

Plataforma integral y modular de nivel empresarial diseñada para articular la gestión comercial, facturación electrónica conforme a la normativa SUNAT (UBL 2.1 y GRE Remitente), control de inventarios multi-almacén valorizados, flujo documental y trámites internos con aprobaciones jerárquicas digitales, control técnico de **Bienes Patrimoniales** con rotulación QR y subsistema de **Préstamos de Bienes**, gestión de **Actividades Productivas y Empresariales (APE)** con centro de costos analítico y módulo de tesorería **RDR / CUT**, manufactura y **Producción**, y el subsistema integral **Agropecuario, Forestal y Pecuario (AgroLivestock)** con trazabilidad georreferenciada y conciliación de pesaje en balanza contra comprobantes de venta.

Para una especificación exhaustiva de arquitectura técnica, procesos de negocio, flujos de datos y diseño de base de datos, consulte el [Documento PDR (Product Definition Report)](PDR.md) o [docs/PDR.md](docs/PDR.md).

---

## Módulos y Componentes del Sistema

### 1. Bienes Patrimoniales y Activos Fijos

- **Inventario Institucional:** Ficha técnica de bienes según catálogo oficial SBN, código patrimonial interno, marca, modelo, serie, ubicación departamental, condición física (`Bueno`, `Regular`, `Malo`, `Baja`), tipo de adquisición (`Compra` o `Donación`), costos históricos y auditoría física en campo con marcado de conciliación.
- **Rótulos y Códigos QR:** Generación e impresión de etiquetas con código QR vectorial SVG para verificación pública móvil inmediata sin requerir inicio de sesión previo (`/inventory/verify/{uuid}`).
- **Actas de Inventario Anual:** Apertura de actas por área y periodo fiscal con circuito de firmas digitales (Responsable del Área, Abastecimiento, Administración y Dirección General) y exportación oficial a PDF A4 apaisado.
- **Préstamos de Bienes (Loans):**
  - Perfiles soportados: **Estudiantes** (*alumnos*), **Docentes** y **Personal Administrativo**.
  - Control de disponibilidad física en tiempo real (impide prestar activos ya en uso o en mantenimiento/baja).
  - Cálculo automático de préstamos vencidos por fecha esperada de retorno.
  - Devolución rápida con registro de condición física recibida y sincronización automática del estado del activo en el inventario general.
  - Emisión de Papeleta Oficial de Préstamo en PDF con cláusula de custodia y 3 casillas de firma reglamentarias.

### 2. Trámites Internos y Aprobaciones Jerárquicas

- **Cadena Jerárquica Dinámica (`Area::approvalChain` & `DocumentApprovalService`):** Ascenso por el organigrama arbóreo institucional desde el área del solicitante hasta Dirección General, omitiendo áreas asesoras.
- **9 Tipos de Documentos Soportados con Flujo de Aprobación:**
  1. *Requerimientos de Bienes y Servicios:* Pedidos internos de compras y materiales con especificaciones y flujo de firmas.
  2. *Declaraciones Juradas de Gastos:* Rendición de viáticos y movilidad local con sustento legal y PDF oficial.
  3. *Papeletas de Salida de Personal:* Permisos por comisión de servicio, salud o asuntos particulares con cómputo de horas.
  4. *Papeletas de Salida de Vehículos:* Asignación de conductor, kilometraje de salida/retorno, ruta y estado mecánico.
  5. *Papeletas de Vacaciones:* Solicitud y registro formal del descanso vacacional anual con visación académica.
  6. *Vales de Control de Combustible:* Despacho controlado de galones/litros de combustible a unidades y maquinaria.
  7. *Actas Departamentales de Inventario Anual:* Congelamiento de estado de bienes y suscripción multi-nivel.
  8. *Apertura de Actividades Productivas (APE):* Expediente formal de inicio de actividad productiva institucional.
  9. *Cierres Mensuales y Balances de Actividades (RDR):* Liquidación económica aprobada por Contabilidad, Administración y Dirección General.
- **Bandeja Centralizada de Aprobaciones (`/approvals`):** Bandeja unificada para directores y jefaturas con opciones de Aprobar (con token y firma digital), Observar o Rechazar, con contador badge en tiempo real.

### 3. Ventas y Facturación Electrónica (SUNAT)

- **Punto de Venta (POS):** Venta rápida con lector de código de barras, selección de medios de pago (Efectivo, Yape, Plin, Tarjeta, Transferencia), selector de clientes y enlace con caja activa.
- **Comprobantes Electrónicos UBL 2.1:** Facturas (`01`), Boletas de Venta (`03`), Notas de Crédito (`07`), Notas de Débito (`08`) con firmado digital XML (PFX/PEM), catálogos oficiales de motivos, envío SOAP/REST a SUNAT, recepción de CDR y código QR tributario reglamentario impreso.
- **Guías de Remisión Electrónica (GRE Remitente):** Integración con la API REST de SUNAT (OAuth2), soporte de transporte privado y público, placas primarias y secundarias (carretas), conductores y transportistas.
- **Notas de Venta y Ventas al Crédito:** Comprobantes internos con cronograma de pagos y registro sucesivo de amortizaciones en cuotas.
- **Cotizaciones:** Emisión formal de propuestas comerciales con conversión directa en un clic a comprobante de venta.
- **Entidades (Clientes y Proveedores):** Consulta en línea con autocompletado vía API de RENIEC (DNI) y SUNAT (RUC) y gestión de Ubigeos oficiales a 6 dígitos.

### 4. Compras y Proveedores Fiscales

- **Proveedores Formales:** Registro tributario con RUC/DNI, razón social y datos fiscales homologados con clientes.
- **Gestión de Compras:** Ingreso de mercadería por factura de proveedor, actualización automática de costos de compra (promedio ponderado / último costo), compras al contado o crédito y afectación directa al stock del almacén destino.

### 5. Inventario, Multi-Almacén y Kardex

- **Catálogo de Productos y Servicios:** Código de barras, código SUNAT, unidad de medida, categoría, precios de lista y mínimos, tipo de afectación IGV.
- **Multi-Almacén Activo:** Control de existencias independientes por almacén (`stock_products`) y middleware `EnsureWarehouseSelection` para forzar la selección explícita del establecimiento de trabajo asignado.
- **Órdenes de Traslado:** Movimientos formales de existencias entre almacenes con control de salida y confirmación de recepción en destino.
- **Kardex Físico y Valorizado:** Trazabilidad cronológica de entradas, salidas y saldos por almacén.

### 6. Cajas y Tesorería

- **Cajas Registradoras:** Definición de cajas físicas por sucursal y usuario.
- **Arqueos de Caja Chica (Arching Cash):** Apertura de turno con fondo inicial, registro de egresos e ingresos en efectivo, consolidación de cobros de ventas por medio de pago y cuadre final con detección de sobrantes y faltantes.

### 7. Actividades Productivas y Empresariales (APE) & Centro de Costos

- **Catálogo de Actividades APE:** Registro estructurado por sectores (Agrícola, Forestal, Piscícola, Pecuario, Institucional, Servicios) con bitácora de seguimiento de metas físicas y avance porcentual (`ActivityTrackingLog`).
- **Tablero de Centro de Costos:** Matriz analítica de doble entrada **Actividad × Mes (Enero a Diciembre)** con cálculo en tiempo real de ingresos, egresos y saldo neto.
- **Exportación e Importación Excel Oficial:** Exportación de consolidado ejecutivo (`ActivityIncomeExpenseExport`), informe detallado multi-hoja con libros mayores individuales (`ActivityDetailedReportExport`) e importación de reportes económicos históricos (`EconomicReportHistoricalImport`).
- **Transacciones de Ingresos y Egresos:** Registro clasificado por árbol de categorías y fuentes de financiamiento, con búsqueda asistida de comprobantes del core (compras, facturas, boletas) para evitar duplicidad de registros y dinero.
- **Subsistema RDR y Tesorería Institucional:**
  - Transferencias a la Cuenta Única del Tesoro (CUT).
  - Préstamos internos y habilitaciones de viáticos para operaciones de campo con amortizaciones.
  - Conciliación bancaria mensual de extractos bancarios vs saldos del sistema (Banco de la Nación, Cooperativa Tocache, Caja Chica).
  - Balances y cierres mensuales suscritos en 4 niveles de aprobación jerárquica digital.

### 8. Producción Industrial, Transformación y Comercialización

- **Planes y Campañas de Producción:** Definición de campañas productivas con metas cuantitativas, presupuestos asignados y cronogramas.
- **Fases, Lotes y Costeo de Mano de Obra:** Desglose por etapas productivas y registro de jornales, horas trabajadas y tarifas de operarios para determinación de costos directos de mano de obra (`ProductionLaborCost`).
- **Catálogo Abierto de Insumos:** Insumos y materias primas (fertilizantes, enmiendas, semillas, alimentos balanceados, fármacos veterinarios) con trazabilidad de movimientos de compras y salidas por aplicación directa en campo.
- **Productos Producidos y Publicación a Ventas (`/publish`):** Registro de bienes transformados y publicación directa al catálogo de ventas del ERP para comercialización inmediata en el mostrador POS.
- **Cosechas y Rendimientos:** Partes formales de producción con tickets de báscula de campo y destino a almacén.
- **Rentabilidad y Retorno de Inversión (ROI):** Consolidación de costos directos (insumos + jornales) frente a ventas facturadas para determinar margen bruto, margen neto y ROI por producto.
- **Comercialización (Preventas y Pedidos):** Registro de pedidos comerciales de actividad (`ActivityOrder`) con anticipos, saldos pendientes y facturación al despacho.

### 9. Producción Agropecuaria, Forestal y Pecuaria (AgroLivestock)

- **Parcelas y Lotes Georreferenciados:** Registro agronómico con superficie en hectáreas, topografía, tipo de suelo, coordenadas GPS y polígonos delimitados en formato GeoJSON.
- **Cultivos y Plantaciones:** Cultivos permanentes y de ciclo corto (Palma aceitera, Cacao, Café, Teca, Bolaina), variedad genética, distanciamiento, densidad de siembra, edad y rendimientos proyectados.
- **Viveros Forestales:** Manejo de plantines y almacigado, fases de desarrollo (germinación, repique, rustificación, despacho) y tasa de supervivencia.
- **Manejo Pecuario y Zootécnico:** Trazabilidad individual (arete, tatuaje, genealogía) o por lotes/pozas (bovinos, porcinos, cuyes, aves, peces), control de peso y fases reproductivas.
- **Eventos Sanitarios y Alimentación:** Registro de tratamientos veterinarios, calendarios de vacunación, fases de alimentación (`STARTER`, `GROWER`, `FINISHER`) y costeo zootécnico.
- **Carga Rápida de Cosechas:** Captura ágil de pesajes diarios en campo y báscula móvil sin redundancia.
- **Analítica Técnica y Conciliación Balanza/Factura:**
  - Comparativo Interanual mensual (Year-over-Year) de producción.
  - Estructura analítica de costos por hectárea, insumos y labores.
  - Conciliación Balanza/Campo vs Factura Oficial SUNAT con cálculo de mermas de transporte y enlace a comprobantes de venta.

### 10. Reportes y Contabilidad

- **Registro de Ventas e Ingresos (PLE / SUNAT):** Formato oficial para declaraciones tributarias (PDF y Excel).
- **Reporte de Ventas por Producto, Vendedor, Cliente y Almacén.**
- **Reporte de Cobranzas y Medios de Pago.**

### 11. Configuración, Seguridad y RBAC

- **Empresa Emisora:** Parámetros de emisor fiscal, credenciales SOL, certificado digital tributario y credenciales API GRE.
- **Organigrama y Áreas:** Estructura jerárquica con dependencias y jefaturas designadas.
- **Control de Acceso (RBAC):** 18 roles preconfigurados en Spatie Permission y middlewares de aislamiento por área (`CheckAssetAccess`) y por actividad productiva (`CheckProductiveActivityAccess`).

---

## Requisitos del Sistema

- **PHP:** Versión 8.1 o superior (Recomendado PHP 8.3 con extensiones `pdo_mysql`, `mbstring`, `openssl`, `xml`, `curl`, `gd`, `zip`, `soap`).
- **Base de Datos:** MySQL 8.0+ o MariaDB 10.6+ con soporte transaccional InnoDB.
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

Cree las tablas y cargue los datos maestros de ubigeos, catálogo SUNAT, áreas institucionales, roles, permisos y usuarios iniciales:

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

## Roles y Perfiles del Sistema (18 Roles)

| Rol                            | Alcance y Responsabilidades Principales                                                               |
| ------------------------------ | ----------------------------------------------------------------------------------------------------- |
| `SUPERADMIN`                 | Control total e irrestricto de configuraciones, auditoría y base de datos                            |
| `ADMIN`                      | Administración operativa global de la institución/empresa                                           |
| `DIRECTOR_GENERAL`           | Máxima autoridad; supervisión ejecutiva consolidada y suscripción final de actas y cierres         |
| `ADMINISTRACION`             | Jefatura administrativa y financiera; revisión presupuestal y conformidad de transferencias CUT      |
| `CONTABILIDAD`               | Acceso consolidado de auditoría; registros tributarios SUNAT y conciliaciones bancarias RDR          |
| `PATRIMONIO`                 | Gestión técnica de bienes muebles, rotulación QR, actas anuales y préstamos de activos            |
| `ABASTECIMIENTO`             | Gestión de compras, proveedores, órdenes de ingreso y tramitación de requerimientos                |
| `JEFE_AREA`                  | Administración de bienes de su área, autorización de préstamos y visación de requerimientos      |
| `RESPONSABLE_ACTIVIDAD`      | Gestión técnica y financiera de su APE: planes, insumos, mano de obra, cosechas y balance de cierre |
| `COORDINADOR` / `COORD_PE` | Coordinación de áreas y primer eslabón en visación de requerimientos de especialidad              |
| `JEFE_INMEDIATO`             | Supervisión primaria en autorizaciones de personal y papeletas de salida                             |
| `TRAMITE_DOCUMENTARIO`       | Recepción, derivación y seguimiento del flujo documentario institucional                            |
| `UNIDAD_ACADEMICA`           | Supervisión de actividades formativas y visación de descansos vacacionales docentes                 |
| `DOCENTE`                    | Solicitante de bienes patrimoniales y generador de requerimientos de aula/laboratorio                 |
| `CHOFER`                     | Conductor oficial asignado a vehículos; suscribe papeletas vehiculares y vales de combustible        |
| `VENDEDOR`                   | Operador en mostrador comercial, punto de venta (POS) y emisión de cotizaciones                      |
| `CAJERO`                     | Operador de caja física asignada, cobro de comprobantes y cuadre de turnos                           |

---

## Comandos Útiles de Mantenimiento

- **Limpiar Caché General:**
  ```bash
  php artisan optimize:clear
  ```
- **Verificar Rutas Activas del Sistema:**
  ```bash
  php artisan route:list
  ```
- **Revisar Estado de Migraciones:**
  ```bash
  php artisan migrate:status
  ```
- **Ejecutar Pruebas Unitarias y de Integración:**
  ```bash
  php artisan test
  ```

---

## Estructura del Proyecto

```
erp-fvc/
├── app/
│   ├── Exports/            # Exportación Excel (ActivityDetailedReport, IncomeExpense, Asset, Billing, Users)
│   ├── Http/
│   │   ├── Controllers/    # 56 controladores MVC organizados por dominios funcionales
│   │   │   └── Api/        # Endpoints API SUNAT (Dispatch, Payload, Validation)
│   │   ├── Middleware/     # CheckAssetAccess, CheckProductiveActivityAccess, EnsureWarehouseSelection, RBAC
│   │   └── Requests/       # 25 Form Requests con validación estricta de esquemas
│   ├── Imports/            # Importación Excel (AssetsImport, EconomicReportHistorical, Users, Products)
│   ├── Models/             # 76 Modelos Eloquent con relaciones y casts
│   ├── Notifications/      # Notificaciones en base de datos para aprobaciones
│   └── Services/
│       ├── DocumentApprovalService.php  # Motor dinámico de aprobación para 9 tipos de documentos
│       └── Ebilling/                    # Suite de facturación electrónica SUNAT UBL 2.1 y GRE
├── config/                 # Configuraciones del framework y paquetes
├── database/
│   ├── migrations/         # 73 migraciones estructuradas por módulos
│   └── seeders/            # Semillas de roles (18), permisos, ubigeos, catálogos SUNAT y datos iniciales
├── docs/                   # Documentación técnica, formatos y PDR v3.0
│   ├── PDR.md              # Product Definition Report integral (Copia sincronizada)
│   └── formats/            # Formatos de referencia institucionales
├── public/                 # Punto de entrada público (index.php, uploads, assets)
├── resources/
│   └── views/              # Vistas Blade organizadas en 30 subdirectorios en admin/
│       └── admin/
│           ├── agrolivestock/           # Módulo Agropecuario, Forestal y Pecuario
│           ├── approvals/               # Bandeja Centralizada de Aprobaciones
│           ├── assets/                  # Bienes Patrimoniales y Préstamos
│           ├── billings/                # Facturación Electrónica SUNAT (UBL 2.1)
│           ├── commercialization/       # Preventas y Pedidos de Actividad
│           ├── pos/                     # Punto de Venta
│           ├── production/              # Producción, Campañas, Insumos, Cosechas
│           ├── productive_activities/   # APE, Centro de Costos, Transacciones y RDR/CUT
│           └── layout.blade.php         # Navegación lateral y arquitectura SB Admin Pro
├── routes/
│   ├── api.php             # Rutas API REST (SUNAT Dispatch / Validaciones)
│   └── web.php             # Rutas Web agrupadas por dominios operativos
├── PDR.md                  # Product Definition Report v3.0 en raíz del proyecto
└── README.md               # Documentación general y guía del sistema
```

---

## Licencia y Soporte

Sistema desarrollado para uso empresarial e institucional exclusivo. Todos los derechos reservados.
Para consultas y requerimientos técnicos, remitirse al equipo de desarrollo institucional.
