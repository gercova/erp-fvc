# Block → PDR Section Map

Use this table to locate the exact section in `docs/PDR.md` for each block.
Pass the line range to `view_file` to read only the relevant portion.

| Block ID | PDR Section | Approximate Lines | Key Terms |
|---|---|---|---|
| A1 | §3 — RBAC & Roles | 89–123 | roles, permisos, Spatie, middleware |
| A2 | §4 Module Accounting + §5 Table accounting_* | 490–550 | accounting_periods, journal_entries, chart_of_accounts |
| B1 | §5 Module Inventario + §4 Module 5 | 248–300 | stock_products, warehouses, StockService, Kardex |
| B2 | §3 Module Ventas §3.2–3.4 + §4 Module 3 | 202–232 | billings, detail_billings, POS, UBL 2.1, IGV |
| B3 | §4 Module Compras §4.1 | 234–246 | buys, detail_buys, providers |
| C1 | §5 Table journal_entries | 490–550 | journal_entries, journal_entry_lines, JournalPostingService |
| C2 | §5 Table arching_cashes | 520–530 | cashes, arching_cashes, cash_movements |
| C3 | §5 Table rdr_bank_reconciliations | 530–532 | bank_reconciliations, bank_movements, conciliación bancaria |
| C4 | §5 Table financial_statement_templates | 490–550 | financial statements, Balance Sheet, P&L |
| C5 | §2 DocumentApprovalService + §4 Module agreements | 170–190 | agreements, service_engagements, installments |
| D1 | §4 Module 2 §2.1–2.2 + §5 Table requisitions | 169–190 | requisitions, expense_declarations, approvals |
| D2 | §4 Module 1 §1.2–1.4 + §5 Table assets | 130–167 | assets, asset_loans, asset_inventories |
| D3 | §5 Table exit_slips + vacation + fuel | 504–510 | exit_slips, vacation_exit_slips, fuel_control_slips |
| E1 | §4 Module APE/RDR + §5 Table activity_transactions | 524–530 | productive_activities, activity_transactions |
| E2 | §5 Table activity_period_closures + rdr_cut_transfers | 529–534 | period_closures, CUT, rdr_bank_reconciliations |
| F1 | §4 Module 11 §11.1 + §5 Table production_campaigns | 430–455 | production_campaigns, batches, harvests |
| F2 | §4 Module 11 §11.2–11.5 + §5 Table agricultural_plots | 450–490 | plots, nurseries, livestock, agro_reconciliation |

## How to Use

1. Find your block ID in the table above.
2. Run `view_file docs/PDR.md --StartLine=<X> --EndLine=<Y>` to load only the relevant section.
3. Use the **Key Terms** column to target your graphify queries.

## Quick PDR Scan Commands

```bash
# Find all mentions of a domain in PDR:
grep -n "<keyword>" docs/PDR.md | head -20

# View a specific section:
# (use view_file tool with StartLine/EndLine)
```
