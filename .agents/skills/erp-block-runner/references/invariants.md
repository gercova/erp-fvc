# System Invariants — ERP-FVC

These are hard constraints that must NEVER be violated, regardless of block or context.
Violating any CRITICAL invariant is a blocker (gate will not pass).

---

## 🔴 CRITICAL — Accounting & Financial Integrity

### INV-ACC-01: Double-Entry Balance
Every `journal_entry` must satisfy: `SUM(journal_entry_lines.debit) == SUM(journal_entry_lines.credit)`.
- **Detected by:** `php artisan accounting:check-integrity` and `php artisan reconciliation:audit`
- **Fix:** Review `JournalPostingService::post()` call; ensure every debit line has a matching credit.

### INV-ACC-02: Journal Entry Immutability
Posted `journal_entries` (status = `POSTED`) cannot be modified.
- **Fix:** Never `$je->update()` on a posted entry. Reverse via a new offsetting entry.

### INV-ACC-03: Open Period Guard
Journal entries can only be posted to `OPEN` accounting periods.
- **Check before posting:**
  ```php
  if ($period->status !== 'OPEN') throw new \Exception("Period is closed.");
  ```

### INV-ACC-04: source_id Before JE Create
`BankMovement` (or other source records) must be persisted (`save()`/`create()`) BEFORE creating the linked `JournalEntry` that references its `id` as `source_id`.

### INV-ACC-05: Bank Reconciliation Balance
`SUM(bank_movements.amount WHERE status=MATCHED AND direction=CREDIT) - SUM(... DEBIT) == accounting balance (account 104x)`.

---

## 🔴 CRITICAL — Database Schema

### INV-DB-01: buys.monto_credito NOT NULL
Column `buys.monto_credito` is `NOT NULL DEFAULT 0.00`. Always pass `'monto_credito' => 0.00` for cash purchases. Passing `null` causes MySQL error 1048.

### INV-DB-02: billings.monto_credito NOT NULL
Same as INV-DB-01 for `billings` table.

### INV-DB-03: detail_payments Columns
Table `detail_payments` uses `idfactura` and `idtipo_comprobante`. It does NOT have `idfacturacion` or `idnotaventa`.

### INV-DB-04: cash_movements Required Columns
`cash_movements` requires: `idusuario`, `tipo`, `monto`, `motivo`, `fecha`, `hora`, `estado`.
- The column is `motivo`, NOT `descripcion`.

### INV-DB-05: Credit Note FK Ordering
Credit notes (`billings` where `idtipo_comprobante = 3`) reference parent billings via `idfactura_anular`.
Always DELETE credit notes BEFORE deleting parent invoices/receipts in cleanup operations.

---

## 🔴 CRITICAL — Security & Authorization

### INV-SEC-01: Route Authorization
Every route that mutates data must have both `auth` middleware AND a `can:<permission>` gate.
No bare `Route::post()` without authorization.

### INV-SEC-02: No IDOR
Every record fetch must scope to the authenticated user's ownership or role.
Never fetch records by raw ID without verifying the user can access them.

### INV-SEC-03: Mass Assignment Protection
All models must declare `$fillable` (allowlist) or `$guarded = ['id']`. Never use `$guarded = []` in production models.

### INV-SEC-04: No Raw SQL with User Input
Use Eloquent query builder bindings, never string-interpolate user input into raw SQL.

---

## 🟠 HIGH — Concurrency

### INV-CON-01: Sequential Code Generation
All sequential code generators (billing series, agreement codes, service codes) must use `lockForUpdate()` inside a DB transaction:
```php
DB::transaction(function () use ($prefix) {
    $last = Agreement::where('code', 'like', $prefix . '%')
        ->whereRaw("code REGEXP '^{$prefix}[0-9]+$'")
        ->lockForUpdate()
        ->orderByDesc('code')
        ->first();
    // generate next code
});
```

### INV-CON-02: Balance Updates
Any operation updating a running balance (cash balance, stock quantity, account balance) must use `lockForUpdate()` on the parent record.

---

## 🟠 HIGH — Service Boundaries

### INV-SVC-01: Stock Mutations via StockService Only
Never write directly to `stock_products`. Always call:
```php
app(StockService::class)->increment($productId, $warehouseId, $qty, $cost);
app(StockService::class)->decrement($productId, $warehouseId, $qty);
```

### INV-SVC-02: Journal Posting via JournalPostingService Only
Never insert into `journal_entries` or `journal_entry_lines` directly. Always call:
```php
app(JournalPostingService::class)->post($journalData);
```

### INV-SVC-03: Agreement Code via AgreementCodeService Only
Never generate agreement/service codes manually. Call:
```php
app(AgreementCodeService::class)->generateAgreementCode($prefix);
app(AgreementCodeService::class)->generateServiceCode($prefix);
app(AgreementCodeService::class)->generateEngagementCode($prefix);
```

---

## 🟡 MEDIUM — Data Integrity

### INV-DAT-01: ServiceSessionStatus Enum
Completed sessions use `ServiceSessionStatus::CONDUCTED`. The value `COMPLETED` does NOT exist.

### INV-DAT-02: IGV Type Codes
- `10` = Gravado Operación Onerosa (taxable, IGV applies)
- `20` = Exonerado (exempt, no IGV)
- `30` = Inafecto (non-taxable, no IGV)
- `40` = Exportación

### INV-DAT-03: Demo Seeder Environment Guard
Every demo/test seeder MUST include:
```php
if (!app()->environment(['local', 'testing'])) {
    $this->command->warn('Skipped: not local/testing.');
    return;
}
```

### INV-DAT-04: Deterministic Seeders
Demo seeders must set fixed random seeds for reproducibility:
```php
mt_srand(20261003);
fake()->seed(20261003);
```

### INV-DAT-05: Idempotent Seeders
Permission and role seeders must use `firstOrCreate`/`updateOrCreate`. Running a seeder twice must produce identical state (no duplicates).

---

## 🟡 MEDIUM — UI/UX

### INV-UI-01: No Extravagant Gradients
UI follows SB Admin Pro style. No multi-color gradients, intrusive animations, or decorative icons on form buttons.

### INV-UI-02: Monospace Numeric Fields
Monetary amounts, codes, and document numbers must use `font-monospace` CSS class.

### INV-UI-03: Blade XSS
Only use `{!! !!}` for values generated exclusively by the application (e.g., QR SVG output). All user-provided strings must use `{{ }}`.

