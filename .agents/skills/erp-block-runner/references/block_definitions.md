# Block Definitions & Gate Commands — ERP-FVC

## Block Naming Convention

Blocks are organized in **tiers** (A–F) reflecting development complexity and dependency order:

| Tier | Domain | Complexity |
|---|---|---|
| A | Core setup: migrations, permissions, base seeders, RBAC | Foundational |
| B | Transactional modules: billings, purchases, stock, POS | High |
| C | Financial closing: accounting, reconciliation, agreements, services | Critical |
| D | Institutional workflows: approvals, trámites, assets | Medium |
| E | Productive activities: APE, RDR, closures | High |
| F | Agro & livestock: plots, harvests, conciliation | Specialized |

---

## Block A — Core Infrastructure

### A1 — RBAC, Roles & Base Permissions
- **Scope:** Spatie permission seeder, 18 roles, 65+ permissions, `RoleSeeder`, `PermissionSeeder`
- **Key Files:**
  - `database/seeders/AccountingSecurityRoleSeeder.php`
  - `database/seeders/AgreementRoleAndPermissionSeeder.php`
  - `app/Http/Middleware/`
- **Gate:**
  ```bash
  php artisan test --filter="AreaAndUserManagement"
  php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count() . ' roles';"
  ```
- **Invariants:**
  - Seeders must be idempotent (`firstOrCreate`).
  - All 18 roles must be present after seeding.

### A2 — Chart of Accounts & Accounting Periods
- **Scope:** `ChartOfAccountsSeeder`, `AccountingPeriod` model, `accounting_periods` table,
  `accounting_accounts` table, `AccountingClosePeriodCommand`, `AccountingReopenPeriodCommand`
- **Key Files:**
  - `database/seeders/ChartOfAccountsSeeder.php`
  - `app/Models/AccountingPeriod.php`
  - `app/Console/Commands/AccountingClosePeriodCommand.php`
  - `app/Console/Commands/AccountingReopenPeriodCommand.php`
- **Gate:**
  ```bash
  php artisan test --filter="AccountingDataStructure"
  php artisan route:list --path=accounting-periods
  php artisan tinker --execute="echo \App\Models\AccountingPeriod::count() . ' periods';"
  ```
- **Invariants:**
  - Period status must be `OPEN` before posting any journal entry.
  - Period closure is irreversible without explicit reopen command.
  - Chart of accounts follows SUNAT PCGE hierarchy (1xx Assets, 2xx…, 4xx…, 6xx…, 7xx…).

---

## Block B — Transactional Modules

### B1 — Products, Warehouses & Stock
- **Scope:** `products`, `warehouses`, `stock_products`, `StockService`, `StockServiceTest`
- **Key Files:**
  - `app/Services/StockService.php`
  - `app/Models/Product.php`, `Warehouse.php`, `StockProduct.php`
- **Gate:**
  ```bash
  php artisan test --filter="StockService|WarehouseProducts"
  php artisan route:list --path=products
  ```
- **Invariants:**
  - `StockService` is the only allowed writer to `stock_products`.
  - Stock cannot go negative (exception must be thrown).

### B2 — Sales, POS & Billings (UBL 2.1)
- **Scope:** `billings`, `detail_billings`, `BillingController`, `PosController`, SUNAT dispatch,
  IGV types, credit notes, `BillingFactory`
- **Key Files:**
  - `app/Http/Controllers/BillingController.php`
  - `app/Http/Controllers/PosController.php`
  - `app/Services/Ebilling/`
  - `database/factories/BillingFactory.php`
- **Gate:**
  ```bash
  php artisan test --filter="BillingManagement|PosManagement"
  php artisan route:list --path=billings
  php artisan route:list --path=pos
  ```
- **Invariants:**
  - `billings.monto_credito` is NOT NULL, default 0.00.
  - Credit notes (`idtipo_comprobante = 3`) reference parent via `idfactura_anular`.
  - Delete credit notes before parent billings in cleanup.
  - IGV types: 10 = Gravado, 20 = Exonerado, 30 = Inafecto.
  - `detail_payments` uses `idfactura` + `idtipo_comprobante` (NOT `idfacturacion`).

### B3 — Purchases (Buys) & Providers
- **Scope:** `buys`, `detail_buys`, `BuyController`, `BuyFactory`, `ProviderFactory`
- **Key Files:**
  - `app/Http/Controllers/BuyController.php`
  - `database/factories/BuyFactory.php`
  - `database/factories/ProviderFactory.php`
- **Gate:**
  ```bash
  php artisan test --filter="BuyManagement|ProviderManagement"
  php artisan route:list --path=buys
  ```
- **Invariants:**
  - `buys.monto_credito` is NOT NULL, default 0.00. Cash buys must pass `'monto_credito' => 0.00`.
  - Purchase triggers stock increment via `StockService`.

---

## Block C — Financial Closing & Advanced Accounting

### C1 — Journal Posting Service
- **Scope:** `JournalPostingService`, `journal_entries`, `journal_entry_lines`, `AccountingBackfillCommand`
- **Key Files:**
  - `app/Services/Accounting/JournalPostingService.php`
  - `app/Console/Commands/AccountingBackfillCommand.php`
- **Gate:**
  ```bash
  php artisan test --filter="AccountingJournalPosting"
  php artisan accounting:check-integrity
  ```
- **Invariants:**
  - Every `journal_entry` must have `SUM(debit) == SUM(credit)` across its lines.
  - Posted entries are IMMUTABLE. Never update a posted `journal_entry`.
  - `source_id` must be set before `JournalEntry::create()` (BankMovement first, then JE).

### C2 — Cash Register & Arching
- **Scope:** `cashes`, `arching_cashes`, `cash_movements`, `ArchingCashController`, `ArchingCashFactory`
- **Key Files:**
  - `app/Http/Controllers/ArchingCashController.php`
  - `database/factories/ArchingCashFactory.php`
- **Gate:**
  ```bash
  php artisan test --filter="ArchingCashManagement"
  php artisan route:list --path=arching
  ```
- **Invariants:**
  - `cash_movements` requires: `idusuario`, `tipo`, `monto`, `motivo`, `fecha`, `hora`, `estado`.
  - Use `motivo` (NOT `descripcion`) for movement description.

### C3 — Bank Reconciliation
- **Scope:** `rdr_bank_reconciliations`, `bank_movements`, `bank_accounts`,
  `BankMovementFactory`, `BankAccountFactory`, `RdrBankReconciliationFactory`
- **Key Files:**
  - `database/factories/BankMovementFactory.php`
  - `database/factories/BankAccountFactory.php`
  - `database/factories/RdrBankReconciliationFactory.php`
- **Gate:**
  ```bash
  php artisan test --filter="Treasury"
  php artisan reconciliation:audit --year=$(date +%Y)
  ```
- **Invariants:**
  - Reconciled bank balance must equal accounting balance (account `104x`).
  - `MATCHED` movements must have a valid `journal_entry_id`.

### C4 — Financial Statements & Ledger Reports
- **Scope:** `FinancialStatementTemplateSeeder`, `CashFlowMappingSeeder`, `FinancialStatementsTest`,
  `AccountingLedgerReportsTest`, ledger report controllers
- **Key Files:**
  - `database/seeders/FinancialStatementTemplateSeeder.php`
  - `database/seeders/CashFlowMappingSeeder.php`
  - `tests/Feature/FinancialStatementsTest.php`
  - `tests/Feature/AccountingLedgerReportsTest.php`
- **Gate:**
  ```bash
  php artisan test --filter="FinancialStatements|AccountingLedgerReports"
  php artisan route:list --path=reports
  ```
- **Invariants:**
  - Reports must use DB-level aggregation (no PHP-level loops over all rows).
  - Balance Sheet: Assets = Liabilities + Equity.
  - Income Statement: Net Income = Revenue − COGS − Expenses.

### C5 — Agreements & Technological Services
- **Scope:** `agreements`, `agreement_installments`, `service_engagements`, `service_sessions`,
  `service_attendees`, permissions `agreements.*` / `services.*`, `DocumentApprovalService` types 12 & 13,
  sidebar counter, status audit, `AgreementCodeService`
- **Key Files:**
  - `app/Models/Agreement.php`
  - `app/Models/AgreementInstallment.php`
  - `app/Models/ServiceEngagement.php`
  - `app/Models/ServiceSession.php`
  - `app/Models/ServiceAttendee.php`
  - `app/Services/Agreements/AgreementCodeService.php`
  - `database/factories/AgreementFactory.php`
  - `database/factories/AgreementInstallmentFactory.php`
  - `database/factories/ServiceEngagementFactory.php`
  - `database/factories/ServiceSessionFactory.php`
  - `database/factories/ServiceAttendeeFactory.php`
  - `database/seeders/AgreementRoleAndPermissionSeeder.php`
  - `docs/agreements.md`
- **Gate:**
  ```bash
  php artisan test --filter="Agreement|ServiceEngagement"
  php artisan route:list --path=agreements
  php artisan route:list --path=services
  ```
- **Invariants:**
  - `AgreementCodeService::generateAgreementCode()` must use `whereRaw("code REGEXP '^{$prefix}[0-9]+$'")` to prevent non-numeric suffixes breaking sequential numbering.
  - `ServiceSessionStatus::CONDUCTED` (not `COMPLETED`) for finished sessions.
  - Agreements nearing expiration (≤30 days) must show badge counter in sidebar.
  - DocumentApprovalService types: 12 = Agreement, 13 = ServiceEngagement.

---

## Block D — Institutional Workflow

### D1 — Requisitions & Expense Declarations
- **Scope:** `requisitions`, `expense_declarations`, `DocumentApprovalService`
- **Gate:**
  ```bash
  php artisan test --filter="DocumentApproval"
  php artisan route:list --path=requisitions
  ```

### D2 — Asset Loans & Patrimonial Inventory
- **Scope:** `assets`, `asset_loans`, `asset_inventories`, `AssetLoanController`
- **Gate:**
  ```bash
  php artisan test --filter="AssetManagement"
  php artisan route:list --path=asset-loans
  ```

### D3 — Exit Slips, Vacation & Fuel Vouchers
- **Scope:** `exit_slips`, `vacation_exit_slips`, `vehicle_exit_slips`, `fuel_control_slips`
- **Gate:**
  ```bash
  php artisan test --filter="ExitSlip|Vacation|Fuel"
  php artisan route:list --path=exit-slips
  ```

---

## Block E — Productive Activities (APE/RDR)

### E1 — Activity Transactions & Cost Centers
- **Scope:** `productive_activities`, `activity_transactions`, `activity_transaction_categories`
- **Gate:**
  ```bash
  php artisan test --filter="ProductiveActivities"
  php artisan route:list --path=productive-activities
  ```

### E2 — Period Closures & CUT Transfers
- **Scope:** `activity_period_closures`, `rdr_cut_transfers`, `rdr_internal_loans`
- **Gate:**
  ```bash
  php artisan test --filter="ProductiveActivities|Treasury"
  php artisan route:list --path=period-closures
  php artisan reconciliation:audit --year=$(date +%Y)
  ```

---

## Block F — Agro & Livestock

### F1 — Production Campaigns, Batches & Harvests
- **Scope:** `production_campaigns`, `production_batches`, `production_harvests`, `produced_items`
- **Gate:**
  ```bash
  php artisan test --filter="Production"
  php artisan route:list --path=production
  ```

### F2 — Plots, Nurseries, Livestock & Agro Reconciliation
- **Scope:** `agricultural_plots`, `agricultural_nurseries`, `livestock_units`, `agricultural_yield_logs`
- **Gate:**
  ```bash
  php artisan test --filter="Agro|Livestock"
  php artisan route:list --path=agrolivestock
  ```

---

## Worked Examples

### Example: Block A2

**Context:** Implementing Chart of Accounts + Accounting Periods.

**Phase 0 — Context Load:**
```
PDR Section: §4 (Module accounting) + §5 (Table: accounting_accounts, accounting_periods)
Graphify: graphify query "accounting periods chart of accounts"
Relevant tests: AccountingDataStructureTest.php
```

**Phase 1 — Diagnostics:**
```bash
php artisan route:list --path=accounting-periods
# Expected: index, create, store, show, edit, update, destroy
php artisan test --filter="AccountingDataStructure" 2>&1 | tail -5
# Baseline: know what currently passes/fails
```

**Phase 5 — Gate:**
```bash
php artisan test --filter="AccountingDataStructure"
php artisan route:list --path=accounting-periods
php artisan tinker --execute="echo \App\Models\AccountingPeriod::count() . ' periods in DB';"
```
Expected: ✅ Tests GREEN, routes present, at least 1 period.

---

### Example: Block B2

**Context:** Billings, POS, credit notes, IGV types.

**Critical Invariants to check first:**
1. `billings.monto_credito NOT NULL` → always include in factory defaults.
2. Credit note FK: `idfactura_anular` references parent billing.
3. Cleanup order: credit notes → billings (never reverse).

**Phase 5 — Gate:**
```bash
php artisan test --filter="BillingManagement|PosManagement"
php artisan route:list --path=billings
php artisan route:list --path=pos
```

---

### Example: Block C4

**Context:** Financial statements (Balance Sheet, P&L, Cash Flow).

**Phase 1 — Diagnostics:**
```bash
php artisan route:list --path=reports/financial
php artisan test --filter="FinancialStatements|AccountingLedgerReports" 2>&1 | tail -10
# Check for N+1: look for Eloquent lazy loading in report controllers
```

**Key check:**
```bash
php artisan accounting:check-integrity
# Must show: "0 imbalanced journal entries"
```

**Phase 5 — Gate:**
```bash
php artisan test --filter="FinancialStatements|AccountingLedgerReports"
php artisan route:list --path=reports
php artisan accounting:check-integrity
```

