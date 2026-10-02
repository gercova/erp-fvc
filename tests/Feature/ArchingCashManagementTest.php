<?php

namespace Tests\Feature;

use App\Events\CashSessionClosed;
use App\Models\ArchingCash;
use App\Models\Billing;
use App\Models\Business;
use App\Models\Cash;
use App\Models\CashMovement;
use App\Models\Client;
use App\Models\DetailPayment;
use App\Models\PayMode;
use App\Models\SaleNote;
use App\Models\TypeDocument;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArchingCashManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $otherUser;
    protected Warehouse $warehouse;
    protected Cash $cash1;
    protected Cash $cash2;
    protected PayMode $payCash;
    protected PayMode $payYape;
    protected PayMode $payCard;
    protected Client $client;
    protected TypeDocument $docTypeNotaVenta;
    protected TypeDocument $docTypeBoleta;

    protected function setUp(): void
    {
        parent::setUp();

        Business::firstOrCreate(
            ['id' => 1],
            [
                'ruc' => '20601234567',
                'razon_social' => 'EMPRESA FVC DEMO SAC',
                'nombre_comercial' => 'FVC DEMO',
                'direccion' => 'AV. PRINCIPAL 123',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '987654321',
                'cobrar_igv' => true,
            ]
        );

        $this->warehouse = Warehouse::firstOrCreate(
            ['id' => 1],
            ['descripcion' => 'ALMACEN PRINCIPAL FVC', 'direccion' => 'CALLE PRINCIPAL 100']
        );

        $this->cash1 = Cash::firstOrCreate(
            ['id' => 1],
            ['descripcion' => 'CAJA PRINCIPAL 01', 'idalmacen' => $this->warehouse->id, 'estado' => 1]
        );

        $this->cash2 = Cash::firstOrCreate(
            ['id' => 2],
            ['descripcion' => 'CAJA SECUNDARIA 02', 'idalmacen' => $this->warehouse->id, 'estado' => 1]
        );

        $this->user = User::where('user', 'admin')->first() ?? User::factory()->create([
            'user' => 'admin',
            'nombres' => 'ADMINISTRADOR TEST',
            'estado' => 1,
            'idcaja' => $this->cash1->id,
            'idalmacen' => $this->warehouse->id,
        ]);
        $this->user->update([
            'idcaja' => $this->cash1->id,
            'idalmacen' => $this->warehouse->id,
        ]);

        $this->otherUser = User::firstOrCreate(
            ['user' => 'cajero2'],
            [
                'nombres' => 'CAJERO DOS',
                'password' => bcrypt('password'),
                'estado' => 1,
                'idcaja' => $this->cash2->id,
                'idalmacen' => $this->warehouse->id,
            ]
        );

        if (! $this->user->hasRole('SUPERADMIN')) {
            $role = Role::firstOrCreate(['name' => 'SUPERADMIN']);
            $this->user->assignRole($role);
        }

        foreach (['admin.arching_cashes', 'admin.pos', 'admin.sale_notes', 'admin.billings'] as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName]);
            $this->user->givePermissionTo($perm);
            $this->otherUser->givePermissionTo($perm);
        }

        $this->payCash = PayMode::firstOrCreate(['id' => 1], ['descripcion' => 'Efectivo']);
        $this->payYape = PayMode::firstOrCreate(['id' => 2], ['descripcion' => 'Yape']);
        $this->payCard = PayMode::firstOrCreate(['id' => 5], ['descripcion' => 'Tarjeta de crédito']);

        $this->client = Client::firstOrCreate(
            ['nro_documento' => '00000000'],
            [
                'nombres' => 'CLIENTES VARIOS',
                'idtipo_documento' => 1,
                'estado' => 1,
            ]
        );

        $this->docTypeNotaVenta = TypeDocument::firstOrCreate(['codigo' => '02'], ['descripcion' => 'NOTA DE VENTA', 'estado' => 1]);
        $this->docTypeBoleta = TypeDocument::firstOrCreate(['codigo' => '03'], ['descripcion' => 'BOLETA DE VENTA', 'estado' => 1]);

        // Close any lingering open archings for clean testing
        ArchingCash::where('estado', 1)->update(['estado' => 2]);
    }

    /**
     * Opening cash register session with initial float.
     */
    public function test_user_can_open_cash_register_session_with_initial_float(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('arching_cash.save'), [
                'monto_inicial' => '150.00',
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'type' => 'success',
            ]);

        $this->assertDatabaseHas('arching_cashes', [
            'idcaja' => $this->cash1->id,
            'idusuario' => $this->user->id,
            'monto_inicial' => 150.00,
            'estado' => 1,
        ]);

        // Cannot open a second session while current is open
        $dupResponse = $this->actingAs($this->user)
            ->postJson(route('arching_cash.save'), [
                'monto_inicial' => '100.00',
            ]);

        $dupResponse->assertStatus(422)
            ->assertJson([
                'status' => false,
                'msg' => 'Primero debe cerrar la caja actual.',
            ]);
    }

    /**
     * ACCEPTANCE TEST:
     * Expected amount reconciles with sales and payment methods in a test involving a mix of cash, Yape, and card.
     * Collections are read from actual payments recorded in the POS (not recalculated from sales totals).
     */
    public function test_expected_amount_reconciles_with_actual_pos_payments_mix_cash_yape_card_and_movements(): void
    {
        // 1. Initial Float = 100.00
        $arching = ArchingCash::create([
            'idcaja' => $this->cash1->id,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => now()->toDateString(),
            'monto_inicial' => 100.00,
            'monto_final' => null,
            'total_ventas' => 0,
            'estado' => 1,
        ]);

        // 2. Mix of actual payments recorded in POS (DetailPayment):
        // Cash: 50.00
        DetailPayment::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'idfactura' => 1001,
            'idpago' => $this->payCash->id,
            'monto' => 50.00,
            'idarqueocaja' => $arching->id,
            'estado' => 1,
        ]);

        // Yape: 30.00
        DetailPayment::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'idfactura' => 1002,
            'idpago' => $this->payYape->id,
            'monto' => 30.00,
            'idarqueocaja' => $arching->id,
            'estado' => 1,
        ]);

        // Card: 70.00
        DetailPayment::create([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'idfactura' => 2001,
            'idpago' => $this->payCard->id,
            'monto' => 70.00,
            'idarqueocaja' => $arching->id,
            'estado' => 1,
        ]);

        // 3. Cash Inflow (+20.00) and Cash Outflow (-10.00)
        CashMovement::create([
            'idarqueocaja' => $arching->id,
            'idusuario' => $this->user->id,
            'tipo' => 'ingreso',
            'monto' => 20.00,
            'motivo' => 'Fondo adicional cambio',
            'fecha' => now()->toDateString(),
            'hora' => now()->toTimeString(),
            'estado' => 1,
        ]);

        CashMovement::create([
            'idarqueocaja' => $arching->id,
            'idusuario' => $this->user->id,
            'tipo' => 'egreso',
            'monto' => 10.00,
            'motivo' => 'Compra útiles de limpieza',
            'fecha' => now()->toDateString(),
            'hora' => now()->toTimeString(),
            'estado' => 1,
        ]);

        // 4. Create an additional SaleNote with credit of 500.00 and NO immediate payment
        // to prove collections are NOT recalculated from sales totals!
        SaleNote::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'serie' => 'NV01',
            'correlativo' => '00000999',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'hora' => now()->toTimeString(),
            'idcliente' => $this->client->id,
            'idmoneda' => 1,
            'modo_pago' => 2, // credito
            'subtotal' => 500.00,
            'igv' => 0.00,
            'total' => 500.00,
            'idarqueocaja' => $arching->id,
            'idusuario' => $this->user->id,
            'estado' => 1, // marked active/non-blocking for this test
            'idalmacen' => $this->warehouse->id,
        ]);

        // Request shift detail
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.get_detail_cash'), [
                'id' => $arching->id,
            ]);

        $response->assertOk()
            ->assertJson(['status' => true]);

        $summary = $response->json('summary');

        // Check payment methods breakdown
        $paymentSummary = collect($summary['payment_summary'])->keyBy('label');
        $this->assertEquals('50.00', $paymentSummary['Efectivo']['total']);
        $this->assertEquals('30.00', $paymentSummary['Yape']['total']);
        $this->assertEquals('70.00', $paymentSummary['Tarjeta']['total']);

        // Check collections total = 50 + 30 + 70 = 150.00 (NOT 650.00!)
        $this->assertEquals('150.00', $summary['collections_total']);
        $this->assertEquals('20.00', $summary['inflows_total']);
        $this->assertEquals('10.00', $summary['outflows_total']);

        // Expected amount = float (100) + collections (150) + inflows (20) - outflows (10) = 260.00
        $this->assertEquals('260.00', $summary['expected_amount']);
        $this->assertEquals('260.00', $summary['expected_final']);
    }

    /**
     * Closing records actual counted amount, calculates surplus/shortage, and dispatches CashSessionClosed event.
     */
    public function test_cash_session_closing_records_counted_amount_calculates_surplus_and_dispatches_event(): void
    {
        Event::fake([CashSessionClosed::class]);

        $arching = ArchingCash::create([
            'idcaja' => $this->cash1->id,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => now()->toDateString(),
            'monto_inicial' => 100.00,
            'estado' => 1,
            'total_ventas' => 0,
        ]);

        // Add payment: 150.00
        DetailPayment::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'idfactura' => 3001,
            'idpago' => $this->payCash->id,
            'monto' => 150.00,
            'idarqueocaja' => $arching->id,
            'estado' => 1,
        ]);

        // Expected amount = 100 + 150 = 250.00
        // Cashier counts 255.00 (+5.00 surplus / sobrante)
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.close_cash'), [
                'id' => $arching->id,
                'monto_final' => 255.00,
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'monto_estimado' => 250.00,
                'monto_final' => 255.00,
                'diferencia' => 5.00,
            ]);

        $arching->refresh();
        $this->assertEquals(2, $arching->estado);
        $this->assertEquals(255.00, (float) $arching->monto_final);
        $this->assertEquals(250.00, (float) $arching->monto_estimado);
        $this->assertEquals(5.00, (float) $arching->diferencia);

        Event::assertDispatched(CashSessionClosed::class, function (CashSessionClosed $event) use ($arching) {
            return $event->archingCash->id === $arching->id
                && $event->expectedAmount == 250.00
                && $event->countedAmount == 255.00
                && $event->difference == 5.00;
        });
    }

    /**
     * Closing calculates shortage when actual counted amount is less than expected.
     */
    public function test_cash_session_closing_calculates_shortage_when_counted_less_than_expected(): void
    {
        Event::fake([CashSessionClosed::class]);

        $arching = ArchingCash::create([
            'idcaja' => $this->cash1->id,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => now()->toDateString(),
            'monto_inicial' => 100.00,
            'estado' => 1,
            'total_ventas' => 0,
        ]);

        DetailPayment::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'idfactura' => 3002,
            'idpago' => $this->payCash->id,
            'monto' => 150.00,
            'idarqueocaja' => $arching->id,
            'estado' => 1,
        ]);

        // Expected = 250.00. Counted = 240.00 (-10.00 shortage / faltante)
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.close_cash'), [
                'id' => $arching->id,
                'monto_final' => 240.00,
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'diferencia' => -10.00,
            ]);

        $arching->refresh();
        $this->assertEquals(-10.00, (float) $arching->diferencia);

        Event::assertDispatched(CashSessionClosed::class, function (CashSessionClosed $event) {
            return $event->difference == -10.00;
        });
    }

    /**
     * Block closing if there are sales from the shift with incomplete payment.
     */
    public function test_closing_blocked_if_shift_has_sales_with_incomplete_payment(): void
    {
        $arching = ArchingCash::create([
            'idcaja' => $this->cash1->id,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => now()->toDateString(),
            'monto_inicial' => 100.00,
            'estado' => 1,
            'total_ventas' => 0,
        ]);

        // Create a SaleNote in this shift that is pending (estado = 0)
        $saleNote = SaleNote::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'serie' => 'NV01',
            'correlativo' => '00000055',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'hora' => now()->toTimeString(),
            'idcliente' => $this->client->id,
            'idmoneda' => 1,
            'modo_pago' => 1, // contado but unpaid
            'subtotal' => 80.00,
            'igv' => 0.00,
            'total' => 80.00,
            'idarqueocaja' => $arching->id,
            'idusuario' => $this->user->id,
            'estado' => 0, // Pendiente / incomplete payment!
            'idalmacen' => $this->warehouse->id,
        ]);

        // Attempt to close cash session
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.close_cash'), [
                'id' => $arching->id,
                'monto_final' => 100.00,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'msg' => 'No se puede cerrar la caja porque existen ventas del turno con pagos incompletos.',
            ]);

        // Verify register remained open
        $arching->refresh();
        $this->assertEquals(1, $arching->estado);

        // Now record full payment for the sale note
        DetailPayment::create([
            'idtipo_comprobante' => $saleNote->idtipo_comprobante,
            'idfactura' => $saleNote->id,
            'idpago' => $this->payCash->id,
            'monto' => 80.00,
            'idarqueocaja' => $arching->id,
            'estado' => 1,
        ]);
        $saleNote->update(['estado' => 1]); // Paid

        // Now closing must succeed!
        $successResponse = $this->actingAs($this->user)
            ->postJson(route('admin.close_cash'), [
                'id' => $arching->id,
                'monto_final' => 180.00,
            ]);

        $successResponse->assertOk()
            ->assertJson([
                'status' => true,
                'msg' => 'Caja cerrada correctamente.',
            ]);

        $arching->refresh();
        $this->assertEquals(2, $arching->estado);
    }

    /**
     * Block closing if a billing from the shift has incomplete payment.
     */
    public function test_closing_blocked_if_shift_has_billing_with_incomplete_payment(): void
    {
        $arching = ArchingCash::create([
            'idcaja' => $this->cash1->id,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => now()->toDateString(),
            'monto_inicial' => 100.00,
            'estado' => 1,
            'total_ventas' => 0,
        ]);

        // Create billing with total 120.00, but only 40.00 paid
        $billing = Billing::create([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'serie' => 'B001',
            'correlativo' => '00000088',
            'fecha_emision' => now()->toDateString(),
            'hora' => now()->toTimeString(),
            'idcliente' => $this->client->id,
            'idmoneda' => 1,
            'idpago' => $this->payCash->id,
            'modo_pago' => 1, // contado
            'sunat_forma_pago' => 'Contado',
            'total' => 120.00,
            'idarqueocaja' => $arching->id,
            'idusuario' => $this->user->id,
            'anulado' => false,
            'idalmacen' => $this->warehouse->id,
        ]);

        DetailPayment::create([
            'idtipo_comprobante' => $billing->idtipo_comprobante,
            'idfactura' => $billing->id,
            'idpago' => $this->payCash->id,
            'monto' => 40.00,
            'idarqueocaja' => $arching->id,
            'estado' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.close_cash'), [
                'id' => $arching->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'msg' => 'No se puede cerrar la caja porque existen ventas del turno con pagos incompletos.',
            ]);
    }

    /**
     * Datatable filters by date, register, status, and user.
     */
    public function test_datatable_filters_by_date_register_status_and_user(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Arching 1: today, cash1, user1, closed (estado = 2)
        $arch1 = ArchingCash::create([
            'idcaja' => $this->cash1->id,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => $today,
            'monto_inicial' => 100.00,
            'monto_final' => 100.00,
            'total_ventas' => 0,
            'estado' => 2,
        ]);

        // Arching 2: yesterday, cash2, otherUser, open (estado = 1)
        $arch2 = ArchingCash::create([
            'idcaja' => $this->cash2->id,
            'idusuario' => $this->otherUser->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => $yesterday,
            'monto_inicial' => 50.00,
            'monto_final' => null,
            'total_ventas' => 0,
            'estado' => 1,
        ]);

        // Filter by date
        $resDate = $this->actingAs($this->user)->getJson(route('arching_cashes.get', ['filter_date' => $yesterday]));
        $resDate->assertOk();
        $dataDate = $resDate->json('data');
        $this->assertTrue(collect($dataDate)->contains('id', $arch2->id));
        $this->assertFalse(collect($dataDate)->contains('id', $arch1->id));

        // Filter by register (cash)
        $resCash = $this->actingAs($this->user)->getJson(route('arching_cashes.get', ['filter_cash' => $this->cash2->id]));
        $resCash->assertOk();
        $dataCash = $resCash->json('data');
        $this->assertTrue(collect($dataCash)->contains('id', $arch2->id));
        $this->assertFalse(collect($dataCash)->contains('id', $arch1->id));

        // Filter by status (1 = open)
        $resStatus = $this->actingAs($this->user)->getJson(route('arching_cashes.get', ['filter_status' => 1]));
        $resStatus->assertOk();
        $dataStatus = $resStatus->json('data');
        $this->assertTrue(collect($dataStatus)->contains('id', $arch2->id));
        $this->assertFalse(collect($dataStatus)->contains('id', $arch1->id));

        // Filter by user
        $resUser = $this->actingAs($this->user)->getJson(route('arching_cashes.get', ['filter_user' => $this->otherUser->id]));
        $resUser->assertOk();
        $dataUser = $resUser->json('data');
        $this->assertTrue(collect($dataUser)->contains('id', $arch2->id));
        $this->assertFalse(collect($dataUser)->contains('id', $arch1->id));
    }

    /**
     * Cash inflows (deposits) and outflows (withdrawals) functionality.
     */
    public function test_cash_inflows_and_outflows_recording(): void
    {
        $arching = ArchingCash::create([
            'idcaja' => $this->cash1->id,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => now()->toDateString(),
            'monto_inicial' => 100.00,
            'estado' => 1,
            'total_ventas' => 0,
        ]);

        // Deposit / Inflow
        $inflowRes = $this->actingAs($this->user)
            ->postJson(route('admin.save_deposit'), [
                'id' => $arching->id,
                'monto' => 45.50,
                'motivo' => 'Ingreso de sencillo',
            ]);

        $inflowRes->assertOk()
            ->assertJson([
                'status' => true,
                'msg' => 'Ingreso de caja registrado correctamente.',
            ]);

        $this->assertDatabaseHas('cash_movements', [
            'idarqueocaja' => $arching->id,
            'tipo' => 'ingreso',
            'monto' => 45.50,
            'motivo' => 'Ingreso de sencillo',
        ]);

        // Withdrawal / Outflow
        $outflowRes = $this->actingAs($this->user)
            ->postJson(route('admin.save_withdrawal'), [
                'id' => $arching->id,
                'monto' => 15.00,
                'motivo' => 'Pago de transporte',
            ]);

        $outflowRes->assertOk()
            ->assertJson([
                'status' => true,
                'msg' => 'Egreso de caja registrado correctamente.',
            ]);

        $this->assertDatabaseHas('cash_movements', [
            'idarqueocaja' => $arching->id,
            'tipo' => 'egreso',
            'monto' => 15.00,
            'motivo' => 'Pago de transporte',
        ]);

        // Check movements datatable endpoint
        $movRes = $this->actingAs($this->user)
            ->postJson(route('admin.get_detail_cashes'), [
                'id' => $arching->id,
            ]);

        $movRes->assertOk();
    }

    /**
     * PDF closing report and ticket generation.
     */
    public function test_pdf_closing_report_and_ticket_generation(): void
    {
        $arching = ArchingCash::create([
            'idcaja' => $this->cash1->id,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => now()->toDateString(),
            'monto_inicial' => 100.00,
            'monto_final' => 150.00,
            'monto_estimado' => 150.00,
            'diferencia' => 0.00,
            'estado' => 2,
            'total_ventas' => 1,
        ]);

        // Ticket PDF
        $ticketRes = $this->actingAs($this->user)
            ->postJson(route('admin.print_summary'), [
                'id' => $arching->id,
            ]);

        $ticketRes->assertOk()
            ->assertJson(['status' => true])
            ->assertJsonStructure(['pdf', 'url']);

        // A4 Report PDF AJAX
        $reportAjax = $this->actingAs($this->user)
            ->postJson(route('admin.arching_cash.report_pdf_post', ['id' => $arching->id]));

        $reportAjax->assertOk()
            ->assertJson(['status' => true])
            ->assertJsonStructure(['pdf', 'url']);

        // A4 Report PDF GET stream
        $reportGet = $this->actingAs($this->user)
            ->get(route('admin.arching_cash.report_pdf', ['id' => $arching->id]));

        $reportGet->assertOk();
        $this->assertEquals('application/pdf', $reportGet->headers->get('Content-Type'));
    }
}
