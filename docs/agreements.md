# MÓDULO DE CONVENIOS Y SERVICIOS TECNOLÓGICOS (ERP-FVC)

Documentación técnica y operativa del módulo de **Convenios Institucionales** y **Servicios Tecnológicos** del IESTP "Francisco Vigo Caballero".

---

## 1. Visión General del Módulo

El módulo gestiona integralmente el ciclo de vida de alianzas institucionales y la provisión de servicios técnicos especializados:

1. **Convenios Marco y Específicos (`agreements`):**
   - Formulación, aprobación jerárquica con firma digital, control de compromisos bilaterales, adendas modificatorias y liquidación final.
2. **Servicios Tecnológicos (`service_engagements`):**
   - Contratación de servicios de catálogo institucional (`technological_services`), calendarización de sesiones técnicas, listas de asistencia de participantes, emisión y verificación pública de certificados con código QR, control de horas consumidas y reportes técnicos finales en PDF.
3. **Integración Financiera y Contable (Track B):**
   - Emisión de Facturas, Boletas y Notas de Venta vinculadas a cronogramas de cuotas (`agreement_installments`) con imputación a centros de costo (`productive_activities`) y registro en el PCGE sin duplicidad de asientos.

---

## 2. Permisos y Roles (RBAC)

El módulo implementa un control de acceso basado en roles (RBAC) idempotente gestionado por `AgreementRoleAndPermissionSeeder`.

### Matriz de Permisos

| Permiso | Descripción |
| :--- | :--- |
| `agreements.view` | Visualizar catálogo de convenios, adendas y compromisos |
| `agreements.create` | Crear convenios y adendas institucionales |
| `agreements.edit` | Modificar convenios, adendas y cronogramas de facturación |
| `agreements.manage` | Gestionar estados, cuotas, evidencias y liquidación |
| `agreements.approve` | Aprobar visaciones en la cadena oficial de firmas |
| `agreements.delete` | Anular o eliminar convenios |
| `services.view` | Visualizar catálogo de servicios y órdenes de trabajo |
| `services.create` | Crear órdenes de servicio tecnológico |
| `services.edit` | Modificar órdenes de servicio, sesiones y entregables |
| `services.manage` | Gestionar sesiones, asistencias, certificados y cierre |
| `services.approve` | Aprobar órdenes y entregables de servicios |
| `services.delete` | Anular órdenes de servicio |

### Asignación por Roles Institucionales

- **SUPERADMIN / ADMIN / DIRECTOR_GENERAL:** Todos los permisos (`*`).
- **ADMINISTRACION:** `agreements.view`, `agreements.manage`, `agreements.approve`, `services.view`, `services.manage`, `services.approve`.
- **CONTABILIDAD:** `agreements.view`, `services.view`.
- **COORDINADOR:** `agreements.view`, `agreements.create`, `agreements.edit`, `agreements.manage`, `services.view`, `services.create`, `services.edit`, `services.manage`.
- **JEFE_AREA:** `agreements.view`, `agreements.create`, `agreements.edit`, `agreements.manage`, `agreements.approve`, `services.view`, `services.create`, `services.edit`, `services.manage`, `services.approve`.

---

## 3. Cadena de Aprobación Oficial (`DocumentApprovalService`)

El módulo integra los tipos 12 y 13 al flujo oficial de firmas digitales institucionales y a la bandeja centralizada `/approvals`:

### Tipología Oficial

- **Tipo 12: Convenio Institucional (`Agreement`)**
  - Paso 1 (`SOLICITANTE`): Coordinador del Convenio / Responsable Técnico (Auto-aprobado).
  - Paso 2 (`JEFE_AREA`): Jefe de Área / Unidad Responsable.
  - Paso 3 (`ADMINISTRACION`): Jefatura de Administración IESTP "FVC".
  - Paso 4 (`DIRECTOR_GENERAL`): Dirección General IESTP "FVC" (Firma definitiva que activa el convenio).

- **Tipo 13a: Adenda de Convenio (`AgreementAddendum`)**
  - Cadena jerárquica de 4 pasos que consolida la prórroga de plazo y/o modificación presupuestal recalculando el estado del convenio padre.

- **Tipo 13b: Servicio Tecnológico (`ServiceEngagement`)**
  - Cadena de revisión y aprobación técnica institucional que habilita la ejecución formal de la orden de servicio.

### Bandeja de Aprobaciones (`/approvals`)
- Filtro por tipo de formato (`agreement`, `agreement_addendum`, `service_engagement`).
- Visores interactivos con datos clave (monto, fechas, contraparte, especialista).
- Generación de token criptográfico interno y sellado digital por paso.

---

## 4. Auditoría de Cambios de Estado

Toda transición de estado es inmutablemente registrada en la tabla `agreement_audit_logs`:
- **Campos Auditados:** `agreement_id`, `user_id`, `action`, `previous_status`, `new_status`, `reason`, `ip_address`, `metadata`, `created_at`.
- **Acciones Auditadas:**
  - `AGREEMENT_CREATED`: Creación inicial del expediente en borrador.
  - `STEP_APPROVED`: Visación por etapa en la cadena de firmas.
  - `FINAL_APPROVAL`: Aprobación definitiva por Dirección General.
  - `APPROVAL_OBSERVED` / `APPROVAL_REJECTED`: Observaciones o rechazo con sustento.
  - `ADDENDUM_APPLIED`: Aplicación de adendas con histórico de montos y fechas.
  - `STATUS_CHANGE_MANUAL`: Transiciones manuales con motivo obligatorio sustentatorio.

---

## 5. Contador de Convenios por Vencer en Sidebar

El menú lateral (`resources/views/admin/layout.blade.php`) incluye un indicador en tiempo real:
- Calcula automáticamente los convenios en estado `EXPIRING_SOON` o `ACTIVE` cuya fecha de término (`end_date`) vence en 30 días o menos.
- Despliega insignias amarillas (*warning*) tanto en el encabezado del menú colapsable como en el enlace directo a "Catálogo de Convenios".

---

## 6. Reglas Anti-Duplicidad e Integración Financiera (Track B)

1. **Una Cuota, Un Comprobante:**
   - La cuota (`AgreementInstallment`) valida `canBeInvoiced()` asegurando que no exista `billing_id` ni `sale_note_id`. Todo intento posterior de facturación es rechazado con error 422.
2. **Asientos Contables Únicos:**
   - El reconocimiento contable del devengo y cobro se realiza exclusivamente mediante el motor contable del comprobante emitido (`AccountingPostingService`).
   - Se prohíbe y previene la creación de asientos paralelos o duplicados bajo el origen `AGREEMENT_INSTALLMENT`.
3. **Atribución de Ingresos a Centros de Costo (APE):**
   - El registro en `activity_sales_attributions` se realiza exactamente una vez por cada línea de detalle de comprobante.
4. **Participantes Únicos:**
   - La inscripción de participantes en capacitaciones actualiza registros existentes por número de documento evitando filas redundantes en el mismo servicio.

---

## 7. Comandos y Verificación del Sistema

### Ejecución de Evaluador de Alertas
```bash
php artisan agreements:alerts
```
Evalúa convenios, compromisos y cuotas pendientes notificando a responsables en los umbrales de 30, 15 y 7 días previos.

### Puerta de Verificación (Gate)
```bash
php artisan test --filter="Agreement|ServiceEngagement" && php artisan route:list --path=agreements
```
