---
name: erp-block-runner
description: >-
  Use this skill when the user references a block ID (A1, A2, B1, B2, C1..C5, D1..D3, E1..E2, F1..F2)
  and requests development, code review, audit, or verification work on the ERP-FVC project.
  Orchestrates the full Prompt Maestro protocol: context load → diagnostics → plan → implementation →
  verification → gate execution. Activated by user phrases like "ejecuta el bloque", "implementa A2",
  "revisa B2", "corre el gate de C4", or any explicit block ID mention.
---

# ERP-FVC Block Runner

Orchestrates the full **Prompt Maestro** development protocol for any block ID (A1–F2) in the
ERP-FVC project. Follow every phase in order. Never skip a phase. Each phase must produce the
artifact described before proceeding to the next.

---

## Phase 0 — Context Load

Before anything else:

1. **Load the block definition** from [references/block_definitions.md](./references/block_definitions.md).
   Extract:
   - Deliverables list
   - Gate command(s)
   - Related models, controllers, migrations, routes
   - Known invariants (accounting rules, RBAC, foreign keys)

2. **Load the PDR** (master product requirements):
   ```
   view_file docs/PDR.md  (relevant section only — use block_pdr_map.md to find line range)
   ```

3. **Run graphify** to locate relevant code nodes:
   ```bash
   graphify query "<block topic, e.g. 'agreements approval installment'>"
   ```
   If specific relationships are needed:
   ```bash
   graphify path "ModelA" "ModelB"
   graphify explain "<concept>"
   ```

4. **Check Knowledge Items** in `.gemini/antigravity-ide/knowledge/` for any KI relevant to the block domain.

5. **Read existing tests** for the block domain:
   ```bash
   ls tests/Feature/ | grep -iE "<block keywords>"
   ```

**Output of Phase 0:** A bullet list confirming which files, models, and PDR sections apply.

---

## Phase 1 — Diagnostics

Inspect the current state of the codebase *before* writing any code.

### 1.1 Route Inspection
```bash
php artisan route:list --path=<block_route_prefix> 2>&1 | head -60
```
Expected: all CRUD routes present with correct middleware (`auth`, `can:*`).

### 1.2 Schema Inspection
For each table in the block's scope, use the MCP `database-schema` tool or:
```bash
php artisan tinker --execute="echo implode(', ', Schema::getColumnListing('<table>'));"
```

### 1.3 Existing Test Status
```bash
php artisan test --filter="<BlockTestClass>" 2>&1 | tail -20
```

### 1.4 Code Review Checklist
Run through each file in scope and annotate findings in this order:

| Priority | Category | Check |
|---|---|---|
| 🔴 CRITICAL | SECURITY | Missing `can:*` / `middleware('auth')`, IDOR (no ownership check), mass-assignment without `$fillable`, raw SQL injection, `{!! !!}` XSS |
| 🔴 CRITICAL | ACCOUNTING | Unbalanced journal entries, double-posted entries, periods not validated as OPEN before posting |
| 🟠 HIGH | CONCURRENCY | Sequential codes / balances without `lockForUpdate()`, missing DB transactions, retry race conditions |
| 🟡 MEDIUM | PERFORMANCE | N+1 queries, missing indexes, reports with no pagination or DB-level aggregation |
| 🟢 LOW | MAINTAINABILITY | Logic in controllers, states as loose strings, missing tests |

**Output of Phase 1:** Table of findings with `file | line | severity | description | fix`.

---

## Phase 2 — Plan

Produce an implementation plan before writing code. The plan must be **approved (proceed)** before Phase 3 starts.

Template:
```
### Block <ID> — Implementation Plan

**Deliverables:**
- [ ] Migrations (idempotent, reversible)
- [ ] Models with $fillable, casts, relationships
- [ ] FormRequest validation classes
- [ ] Controllers (thin — delegate to Services)
- [ ] Service classes (business logic)
- [ ] Routes (web.php / api.php with middleware)
- [ ] Blade views (index, create, edit, show)
- [ ] Seeder / Permission seeder (idempotent)
- [ ] Tests (unit + feature)
- [ ] Documentation (docs/<topic>.md)

**Schema Changes:**
| Table | Column | Type | Constraint | Reason |

**Service Interactions:**
- JournalPostingService::post() — called after <event>
- DocumentApprovalService — type <N> registered
- StockService — called for <reason>
- AgreementCodeService — generates code format <X>

**Constraints & Invariants:**
- List from block_definitions.md
```

---

## Phase 3 — Implementation

Follow these rules strictly:

### General Rules
- **Thin controllers**: All business logic lives in `app/Services/`. Controllers only validate, authorize, delegate, and respond.
- **Idempotent seeders**: Every seeder uses `updateOrCreate` or `firstOrCreate`. Never bare `create()`.
- **Double-entry accounting**: Every financial event calls `JournalPostingService::post()`. Debit total MUST equal credit total.
- **Permissions**: Register all new permissions via idempotent seeders using `Permission::firstOrCreate()`.
- **Sequential codes**: Always use `lockForUpdate()` when generating sequential document numbers.
- **Foreign keys**: Delete child records before parent records in cleanup/rollback operations.
- **Non-nullable columns**: Always provide defaults for `monto_credito`, `monto_estimado`, etc.

### Implementation Order (strictly follow)
1. **Migrations** — run `php artisan migrate` after each new migration.
2. **Models** — add `$fillable`, `$casts`, relationships, scopes.
3. **FormRequests** — one per operation (store, update).
4. **Services** — pure PHP classes under `app/Services/<Domain>/`.
5. **Controllers** — inject services via constructor.
6. **Routes** — add to `routes/web.php` with `middleware(['auth', 'can:<permission>'])`.
7. **Views** — Blade templates following SB Admin Pro structure.
8. **Seeders** — permissions seeder + optional demo data.
9. **Tests** — feature tests covering happy path, authorization, and validation errors.
10. **Documentation** — `docs/<topic>.md`.

### Key Invariants (always enforce)
See [references/invariants.md](./references/invariants.md) for the full list.

---

## Phase 4 — Verification

After implementation, verify each deliverable:

### 4.1 Migration Health
```bash
php artisan migrate:status 2>&1 | grep -E "Pending|Ran"
```

### 4.2 Route Registration
```bash
php artisan route:list --path=<block_prefix> 
```

### 4.3 Test Suite
```bash
php artisan test --filter="<BlockFilter>" 2>&1
```
Expected: all tests GREEN. Zero failures.

### 4.4 Accounting Integrity (for blocks involving financial data)
```bash
php artisan accounting:check-integrity 2>&1
php artisan reconciliation:audit --year=$(date +%Y) 2>&1
```
Expected output: `SISTEMA 100% RECONCILIADO` or `0 discrepancies`.

### 4.5 Permission Registration Check
```bash
php artisan tinker --execute="
\$perms = ['<perm1>', '<perm2>'];
foreach(\$perms as \$p) {
    echo \Spatie\Permission\Models\Permission::where('name', \$p)->exists() ? \"✅ \$p\n\" : \"❌ MISSING: \$p\n\";
}"
```

### 4.6 Code Review Re-check
Run the checklist from Phase 1 again. All 🔴 CRITICAL findings must be resolved.

---

## Phase 5 — Gate Execution

Run the exact gate command defined for the block. Gate commands are listed in
[references/block_definitions.md](./references/block_definitions.md).

General pattern:
```bash
# 1. Tests
php artisan test --filter="<BlockTestFilter>"

# 2. Routes
php artisan route:list --path=<prefix>

# 3. Integrity (financial blocks)
php artisan reconciliation:audit --year=<year>
```

**Gate PASSES when:**
- All tests are GREEN (0 failures, 0 errors).
- All expected routes are present with correct middleware.
- Accounting integrity shows 0 discrepancies (financial blocks).

**Gate FAILS when:**
- Any test is RED → return to Phase 3 for the failing area.
- Missing routes → add to `routes/web.php` and re-run.
- Accounting imbalance → inspect `journal_entries` and fix the posting service call.

**Report gate result as:**
```
✅ GATE PASSED — Block <ID> ready for merge.
   Tests: <N> passed, 0 failed (<M> assertions)
   Routes: <N> routes verified
   Integrity: SISTEMA 100% RECONCILIADO
```
or
```
❌ GATE FAILED — Block <ID>
   Failures: <list>
   Action: <specific fix needed>
```

---

## Cross-Cutting Rules (always apply)

1. **graphify update** after every code change:
   ```bash
   graphify update .
   ```

2. **Never use `{!! !!}`** unless the value is explicitly generated by the application (e.g., QR SVG).

3. **Never skip `lockForUpdate()`** when generating sequential codes or updating balances in concurrent-access paths.

4. **Always guard demo/test seeders**:
   ```php
   if (!app()->environment(['local', 'testing'])) {
       $this->command->warn('DemoSeeder omitted: not a local/testing environment.');
       return;
   }
   ```

5. **StockService is the only entry point** for stock mutations. Never write directly to `stock_products`.

6. **JournalPostingService::post()** is the only entry point for journal entries. Never insert `journal_entries` or `journal_entry_lines` directly.

---

## References

- [Block Definitions & Gates](./references/block_definitions.md)
- [Block → PDR Map](./references/block_pdr_map.md)
- [System Invariants](./references/invariants.md)
- [PDR Master Document](../../../docs/PDR.md)
