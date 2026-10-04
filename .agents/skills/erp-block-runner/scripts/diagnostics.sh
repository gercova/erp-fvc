#!/usr/bin/env bash
# ============================================================
# ERP-FVC Block Diagnostics Script
# Usage: bash scripts/diagnostics.sh <BLOCK_ID>
# Runs Phase 1 diagnostics: routes, schema, test baseline.
# ============================================================
set -euo pipefail

BLOCK="${1:-}"
ROOT="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$ROOT"

if [[ -z "$BLOCK" ]]; then
    echo "❌ Usage: $0 <BLOCK_ID>"
    exit 1
fi

echo "=================================================="
echo " ERP-FVC Diagnostics — Block $BLOCK"
echo " $(date '+%Y-%m-%d %H:%M:%S')"
echo "=================================================="
echo ""

# ---- Helper ----
check_routes() {
    local prefix="$1"
    echo "▶ Routes: --path=$prefix"
    php artisan route:list --path="$prefix" 2>&1 | head -30
    echo ""
}

check_tests() {
    local filter="$1"
    echo "▶ Test baseline: --filter=$filter"
    php artisan test --filter="$filter" 2>&1 | tail -10
    echo ""
}

check_table() {
    local table="$1"
    echo "▶ Schema: $table"
    php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('$table'));" 2>&1 | grep -v "^>" | grep -v "Psy Shell"
    echo ""
}

# ---- Block-specific diagnostics ----
case "${BLOCK^^}" in
    A1) check_routes "areas"; check_tests "AreaAndUser" ;;
    A2)
        check_routes "accounting-periods"
        check_tests "AccountingDataStructure"
        check_table "accounting_periods"
        check_table "accounting_accounts"
        check_table "journal_entries"
        ;;
    B1)
        check_routes "products"
        check_tests "StockService|WarehouseProducts"
        check_table "stock_products"
        check_table "warehouses"
        ;;
    B2)
        check_routes "billings"
        check_routes "pos"
        check_tests "BillingManagement|PosManagement"
        check_table "billings"
        check_table "detail_billings"
        check_table "detail_payments"
        ;;
    B3)
        check_routes "buys"
        check_tests "BuyManagement|ProviderManagement"
        check_table "buys"
        check_table "detail_buys"
        ;;
    C1)
        check_tests "AccountingJournalPosting"
        check_table "journal_entries"
        check_table "journal_entry_lines"
        ;;
    C2)
        check_routes "arching"
        check_tests "ArchingCashManagement"
        check_table "arching_cashes"
        check_table "cash_movements"
        ;;
    C3)
        check_tests "Treasury"
        check_table "rdr_bank_reconciliations"
        check_table "bank_movements"
        ;;
    C4)
        check_routes "reports"
        check_tests "FinancialStatements|AccountingLedgerReports"
        ;;
    C5)
        check_routes "agreements"
        check_routes "services"
        check_tests "Agreement|ServiceEngagement"
        check_table "agreements"
        check_table "agreement_installments"
        check_table "service_engagements"
        check_table "service_sessions"
        check_table "service_attendees"
        ;;
    D1)
        check_routes "requisitions"
        check_tests "DocumentApproval"
        check_table "requisitions"
        ;;
    D2)
        check_routes "asset-loans"
        check_tests "AssetManagement"
        check_table "assets"
        check_table "asset_loans"
        ;;
    D3)
        check_routes "exit-slips"
        check_tests "ExitSlip|Vacation|Fuel"
        ;;
    E1)
        check_routes "productive-activities"
        check_tests "ProductiveActivities"
        check_table "productive_activities"
        check_table "activity_transactions"
        ;;
    E2)
        check_routes "period-closures"
        check_tests "ProductiveActivities|Treasury"
        ;;
    F1)
        check_routes "production"
        check_tests "Production"
        check_table "production_campaigns"
        check_table "production_batches"
        ;;
    F2)
        check_routes "agrolivestock"
        check_tests "Agro|Livestock"
        check_table "agricultural_plots"
        check_table "livestock_units"
        ;;
    *)
        echo "❌ Unknown block: $BLOCK"
        exit 1
        ;;
esac

echo "=================================================="
echo " Diagnostics complete for Block $BLOCK"
echo " Review output above before writing code (Phase 3)."
echo "=================================================="
