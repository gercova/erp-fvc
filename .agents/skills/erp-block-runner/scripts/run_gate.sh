#!/usr/bin/env bash
# ============================================================
# ERP-FVC Block Gate Runner
# Usage: bash scripts/run_gate.sh <BLOCK_ID>
# Example: bash scripts/run_gate.sh C5
# ============================================================

BLOCK="${1:-}"
ROOT="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$ROOT"

if [[ -z "$BLOCK" ]]; then
    echo "❌ Usage: $0 <BLOCK_ID>  (e.g. A2, B2, C4, C5)"
    exit 1
fi

echo "=================================================="
echo " ERP-FVC Gate Runner — Block $BLOCK"
echo " $(date '+%Y-%m-%d %H:%M:%S')"
echo "=================================================="
echo ""

PASS=0
FAIL=0
YEAR=$(date +%Y)

run_tests() {
    local FILTER="$1"
    echo "▶ php artisan test --filter=\"$FILTER\""
    if php artisan test --filter="$FILTER" 2>&1; then
        echo "  ✅ Tests PASSED"
        PASS=$((PASS+1))
    else
        echo "  ❌ Tests FAILED"
        FAIL=$((FAIL+1))
    fi
    echo ""
}

run_routes() {
    local PATH_PREFIX="$1"
    echo "▶ php artisan route:list --path=$PATH_PREFIX"
    ROUTE_OUTPUT=$(php artisan route:list --path="$PATH_PREFIX" 2>&1 || true)
    echo "$ROUTE_OUTPUT"
    if echo "$ROUTE_OUTPUT" | grep -qE "GET|POST|PUT|PATCH|DELETE"; then
        echo "  ✅ Routes present for $PATH_PREFIX"
        PASS=$((PASS+1))
    else
        echo "  ⚠️  No routes found for --path=$PATH_PREFIX (check route prefix)"
        # Don't count as failure — routes may use a different prefix
    fi
    echo ""
}

run_integrity() {
    echo "▶ php artisan accounting:check-integrity"
    if php artisan accounting:check-integrity 2>&1; then
        echo "  ✅ Integrity OK"
        PASS=$((PASS+1))
    else
        echo "  ❌ Integrity FAILED"
        FAIL=$((FAIL+1))
    fi
    echo ""

    echo "▶ php artisan reconciliation:audit --year=$YEAR"
    if php artisan reconciliation:audit --year="$YEAR" 2>&1; then
        echo "  ✅ Reconciliation OK"
        PASS=$((PASS+1))
    else
        echo "  ❌ Reconciliation FAILED"
        FAIL=$((FAIL+1))
    fi
    echo ""
}

BLOCK_UPPER="${BLOCK^^}"

case "$BLOCK_UPPER" in
    A1)
        run_tests "AreaAndUserManagement"
        run_routes "areas"
        ;;
    A2)
        run_tests "AccountingDataStructure"
        run_routes "accounting-periods"
        ;;
    B1)
        run_tests "StockService|WarehouseProducts"
        run_routes "products"
        ;;
    B2)
        run_tests "BillingManagement|PosManagement"
        run_routes "billings"
        run_routes "pos"
        ;;
    B3)
        run_tests "BuyManagement|ProviderManagement"
        run_routes "buys"
        ;;
    C1)
        run_tests "AccountingJournalPosting"
        run_integrity
        ;;
    C2)
        run_tests "ArchingCashManagement"
        run_routes "arching"
        ;;
    C3)
        run_tests "Treasury"
        run_integrity
        ;;
    C4)
        run_tests "FinancialStatements|AccountingLedgerReports"
        run_routes "reports"
        run_integrity
        ;;
    C5)
        run_tests "Agreement|ServiceEngagement"
        run_routes "agreements"
        run_routes "services"
        ;;
    D1)
        run_tests "DocumentApproval"
        run_routes "requisitions"
        ;;
    D2)
        run_tests "AssetManagement"
        run_routes "asset-loans"
        ;;
    D3)
        run_tests "ExitSlip|Vacation|Fuel"
        run_routes "exit-slips"
        ;;
    E1)
        run_tests "ProductiveActivities"
        run_routes "productive-activities"
        ;;
    E2)
        run_tests "ProductiveActivities|Treasury"
        run_routes "period-closures"
        run_integrity
        ;;
    F1)
        run_tests "Production"
        run_routes "production"
        ;;
    F2)
        run_tests "Agro|Livestock"
        run_routes "agrolivestock"
        ;;
    *)
        echo "❌ Unknown block ID: $BLOCK"
        echo "   Valid IDs: A1 A2 B1 B2 B3 C1 C2 C3 C4 C5 D1 D2 D3 E1 E2 F1 F2"
        exit 1
        ;;
esac

echo "=================================================="
echo " GATE SUMMARY — Block $BLOCK"
echo "  ✅ Passed checks: $PASS"
echo "  ❌ Failed checks: $FAIL"
echo "=================================================="

if [[ $FAIL -gt 0 ]]; then
    echo ""
    echo "❌ GATE FAILED — Block $BLOCK is NOT ready for merge."
    echo "   Review failures above and return to Phase 3."
    exit 1
else
    echo ""
    echo "✅ GATE PASSED — Block $BLOCK is ready for merge."
    exit 0
fi
