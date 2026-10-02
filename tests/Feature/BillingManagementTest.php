<?php

namespace Tests\Feature;

use App\Models\ArchingCash;
use App\Models\Billing;
use App\Models\Business;
use App\Models\Cash;
use App\Models\Category;
use App\Models\Client;
use App\Models\CreditNoteType;
use App\Models\DebitNoteType;
use App\Models\DetailBilling;
use App\Models\IdentityDocumentType;
use App\Models\IgvTypeAffection;
use App\Models\PayMode;
use App\Models\Product;
use App\Models\Serie;
use App\Models\TypeDocument;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Ebilling\SunatDispatchService;
use App\Services\Ebilling\Support\BusinessStoragePath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillingManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Business $business;
    protected Warehouse $warehouse;
    protected Cash $cash;
    protected ArchingCash $archingCash;
    protected TypeDocument $docTypeFactura;
    protected TypeDocument $docTypeBoleta;
    protected TypeDocument $docTypeNotaCredito;
    protected TypeDocument $docTypeNotaDebito;
    protected Client $clientJuan;
    protected Client $clientMaria;
    protected Product $testProduct;
    protected PayMode $payCash;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Business
        $this->business = Business::updateOrCreate(
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

        // 2. Warehouse
        $this->warehouse = Warehouse::find(1) ?? Warehouse::create([
            'descripcion' => 'ALMACEN PRINCIPAL FVC',
            'direccion' => 'CALLE PRINCIPAL 100',
        ]);

        // 3. Cash
        $this->cash = Cash::find(1) ?? Cash::create([
            'descripcion' => 'CAJA PRINCIPAL 01',
            'idalmacen' => $this->warehouse->id,
            'estado' => 1,
        ]);

        // 4. User
        $this->adminUser = User::where('user', 'admin')->first() ?? User::factory()->create([
            'user' => 'admin',
            'estado' => 1,
            'idcaja' => $this->cash->id,
            'idalmacen' => $this->warehouse->id,
        ]);
        $this->adminUser->update([
            'idcaja' => $this->cash->id,
            'idalmacen' => $this->warehouse->id,
        ]);

        if (! $this->adminUser->hasRole('SUPERADMIN')) {
            $role = Role::firstOrCreate(['name' => 'SUPERADMIN']);
            $this->adminUser->assignRole($role);
        }

        $perm = Permission::firstOrCreate(['name' => 'admin.billings']);
        $this->adminUser->givePermissionTo($perm);

        // 5. ArchingCash
        $this->archingCash = ArchingCash::create([
            'idcaja' => $this->cash->id,
            'idusuario' => $this->adminUser->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => now()->toDateString(),
            'monto_inicial' => 100.00,
            'monto_final' => 0.00,
            'total_ventas' => 0.00,
            'estado' => 1,
        ]);

        // 6. Document Types
        $this->docTypeFactura = TypeDocument::firstOrCreate(['codigo' => '01'], ['descripcion' => 'FACTURA ELECTRONICA', 'estado' => 1]);
        $this->docTypeBoleta = TypeDocument::firstOrCreate(['codigo' => '03'], ['descripcion' => 'BOLETA DE VENTA ELECTRONICA', 'estado' => 1]);
        $this->docTypeNotaCredito = TypeDocument::firstOrCreate(['codigo' => '07'], ['descripcion' => 'NOTA DE CREDITO ELECTRONICA', 'estado' => 1]);
        $this->docTypeNotaDebito = TypeDocument::firstOrCreate(['codigo' => '08'], ['descripcion' => 'NOTA DE DEBITO ELECTRONICA', 'estado' => 1]);

        // 7. Identity Document Types
        $docDni = IdentityDocumentType::firstOrCreate(['codigo' => '1'], ['descripcion' => 'DNI', 'estado' => 1]);
        $docRuc = IdentityDocumentType::firstOrCreate(['codigo' => '6'], ['descripcion' => 'RUC', 'estado' => 1]);

        // 8. Clients
        $this->clientJuan = Client::firstOrCreate(
            ['nro_documento' => '10203040'],
            [
                'nombres' => 'JUAN PEREZ GONZALES',
                'iddoc' => $docDni->id,
                'direccion' => 'CALLE LOS PINOS 123',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '977777777',
            ]
        );

        $this->clientMaria = Client::firstOrCreate(
            ['nro_documento' => '20556677889'],
            [
                'nombres' => 'MARIA LOPEZ SAC',
                'iddoc' => $docRuc->id,
                'direccion' => 'AV. INDUSTRIAL 456',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '988888888',
            ]
        );

        // 9. Unit & Tax Affection & Category
        $unit = Unit::firstOrCreate(['codigo' => 'NIU'], ['descripcion' => 'UNIDAD', 'estado' => 1]);
        $igvGravado = IgvTypeAffection::firstOrCreate(['codigo' => '10'], ['descripcion' => 'GRAVADO', 'tipo' => 'GRAVADA', 'estado' => 1]);
        $category = Category::firstOrCreate(['descripcion' => 'GENERAL']);

        // 10. Product
        $this->testProduct = Product::firstOrCreate(
            ['codigo_interno' => 'PROD-A4-001'],
            [
                'codigo_barras' => '7751234567890',
                'descripcion' => 'PRODUCTO TEST BILLING A4',
                'idunidad' => $unit->id,
                'idcategoria' => $category->id,
                'idcodigo_igv' => $igvGravado->id,
                'igv' => 18,
                'precio_compra' => 50.00,
                'precio_venta' => 100.00,
                'opcion' => 1,
                'stock_actual' => 100,
            ]
        );

        // 11. Payment Mode
        $this->payCash = PayMode::where('descripcion', 'Efectivo')->first() ?? PayMode::create(['descripcion' => 'Efectivo']);
    }

    /**
     * Helper to create a test billing document
     */
    protected function createBilling(array $overrides = []): Billing
    {
        $billing = Billing::create(array_merge([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'serie' => 'B001',
            'correlativo' => '00000001',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => null,
            'hora' => now()->format('H:i:s'),
            'idcliente' => $this->clientJuan->id,
            'idmoneda' => 1,
            'idpago' => $this->payCash->id,
            'modo_pago' => 1,
            'sunat_forma_pago' => 'Contado',
            'exonerada' => 0.00,
            'inafecta' => 0.00,
            'gravada' => 84.75,
            'anticipo' => 0.00,
            'igv' => 15.25,
            'icbper' => 0.00,
            'gratuita' => 0.00,
            'otros_cargos' => 0.00,
            'total' => 100.00,
            'monto_credito' => 0,
            'cdr' => null,
            'anulado' => false,
            'estado_cpe' => null,
            'errores' => null,
            'idusuario' => $this->adminUser->id,
            'idarqueocaja' => $this->archingCash->id,
            'idalmacen' => $this->warehouse->id,
        ], $overrides));

        DetailBilling::create([
            'idfacturacion' => $billing->id,
            'idproducto' => $this->testProduct->id,
            'cantidad' => 1,
            'descuento' => 0,
            'igv' => 15.25,
            'id_afectacion_igv' => 1,
            'precio_unitario' => 100.00,
            'valor_unitario' => 84.75,
            'valor_total' => 84.75,
            'precio_total' => 100.00,
        ]);

        return $billing;
    }

    public function test_billings_listing_returns_datatable_json()
    {
        $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000010',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('billings.get'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_billings_filter_by_date()
    {
        $billingOld = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000021',
            'fecha_emision' => '2026-08-15',
        ]);

        $billingNew = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000022',
            'fecha_emision' => '2026-08-20',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('billings.get', ['date' => '2026-08-15']));

        $response->assertStatus(200);
        $correlativos = collect($response->json('data'))->pluck('comprobante')->implode(' ');

        $this->assertStringContainsString('00000021', $correlativos);
        $this->assertStringNotContainsString('00000022', $correlativos);
    }

    public function test_billings_filter_by_series_and_sequence_number()
    {
        $billing1 = $this->createBilling([
            'idtipo_comprobante' => $this->docTypeFactura->id,
            'serie' => 'F001',
            'correlativo' => '00000301',
        ]);

        $billing2 = $this->createBilling([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'serie' => 'B002',
            'correlativo' => '00000302',
        ]);

        // Filter by series F001
        $respSerie = $this->actingAs($this->adminUser)
            ->getJson(route('billings.get', ['serie' => 'F001']));

        $respSerie->assertStatus(200);
        $dataSerie = collect($respSerie->json('data'))->pluck('comprobante')->implode(' ');
        $this->assertStringContainsString('F001-00000301', $dataSerie);
        $this->assertStringNotContainsString('B002-00000302', $dataSerie);

        // Filter by correlativo (sequence) 302
        $respSequence = $this->actingAs($this->adminUser)
            ->getJson(route('billings.get', ['correlativo' => '302']));

        $respSequence->assertStatus(200);
        $dataSeq = collect($respSequence->json('data'))->pluck('comprobante')->implode(' ');
        $this->assertStringContainsString('B002-00000302', $dataSeq);
        $this->assertStringNotContainsString('F001-00000301', $dataSeq);
    }

    public function test_billings_filter_by_customer()
    {
        $billingJuan = $this->createBilling([
            'idcliente' => $this->clientJuan->id,
            'serie' => 'B001',
            'correlativo' => '00000401',
        ]);

        $billingMaria = $this->createBilling([
            'idcliente' => $this->clientMaria->id,
            'serie' => 'F001',
            'correlativo' => '00000402',
        ]);

        $responseJuan = $this->actingAs($this->adminUser)
            ->getJson(route('billings.get', ['customer' => 'JUAN PEREZ']));

        $responseJuan->assertStatus(200);
        $docsJuan = collect($responseJuan->json('data'))->pluck('comprobante')->implode(' ');
        $this->assertStringContainsString('B001-00000401', $docsJuan);
        $this->assertStringNotContainsString('F001-00000402', $docsJuan);

        $responseMaria = $this->actingAs($this->adminUser)
            ->getJson(route('billings.get', ['customer' => '20556677889']));

        $responseMaria->assertStatus(200);
        $docsMaria = collect($responseMaria->json('data'))->pluck('comprobante')->implode(' ');
        $this->assertStringContainsString('F001-00000402', $docsMaria);
        $this->assertStringNotContainsString('B001-00000401', $docsMaria);
    }

    public function test_billings_filter_by_issuance_status()
    {
        $billingPending = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000501',
            'cdr' => null,
            'anulado' => false,
        ]);

        $billingAccepted = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000502',
            'cdr' => 1,
            'estado_cpe' => 0,
            'anulado' => false,
        ]);

        $billingRejected = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000503',
            'cdr' => 1,
            'estado_cpe' => 1,
            'anulado' => false,
        ]);

        $billingAnulado = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000504',
            'anulado' => true,
        ]);

        // Filter: accepted
        $respAccepted = $this->actingAs($this->adminUser)
            ->getJson(route('billings.get', ['status' => 'accepted']));
        $dataAccepted = collect($respAccepted->json('data'))->pluck('comprobante')->implode(' ');
        $this->assertStringContainsString('00000502', $dataAccepted);
        $this->assertStringNotContainsString('00000501', $dataAccepted);
        $this->assertStringNotContainsString('00000503', $dataAccepted);
        $this->assertStringNotContainsString('00000504', $dataAccepted);

        // Filter: pending
        $respPending = $this->actingAs($this->adminUser)
            ->getJson(route('billings.get', ['status' => 'pending']));
        $dataPending = collect($respPending->json('data'))->pluck('comprobante')->implode(' ');
        $this->assertStringContainsString('00000501', $dataPending);
        $this->assertStringNotContainsString('00000502', $dataPending);

        // Filter: anulado
        $respAnulado = $this->actingAs($this->adminUser)
            ->getJson(route('billings.get', ['status' => 'anulado']));
        $dataAnulado = collect($respAnulado->json('data'))->pluck('comprobante')->implode(' ');
        $this->assertStringContainsString('00000504', $dataAnulado);
        $this->assertStringNotContainsString('00000502', $dataAnulado);
    }

    public function test_resubmission_of_accepted_document_is_rejected_idempotently()
    {
        $acceptedBilling = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000601',
            'cdr' => 1,
            'estado_cpe' => 0,
            'anulado' => false,
        ]);

        // Mock SunatDispatchService to ensure it is never called for accepted documents
        $dispatchMock = Mockery::mock(SunatDispatchService::class);
        $dispatchMock->shouldNotReceive('dispatch');
        $this->app->instance(SunatDispatchService::class, $dispatchMock);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.dispatch_billing', $acceptedBilling->id));

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'msg' => 'El comprobante ya fue aceptado por SUNAT y no puede ser reenviado.',
        ]);
    }

    public function test_resubmission_of_annulled_document_is_rejected()
    {
        $annulledBilling = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000602',
            'anulado' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.dispatch_billing', $annulledBilling->id));

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'msg' => 'No se puede enviar a SUNAT un comprobante anulado.',
        ]);
    }

    public function test_resubmission_of_pending_document_succeeds()
    {
        $pendingBilling = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000603',
            'cdr' => null,
            'estado_cpe' => null,
            'anulado' => false,
        ]);

        $dispatchMock = Mockery::mock(SunatDispatchService::class);
        $dispatchMock->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(fn ($b) => $b->id === $pendingBilling->id))
            ->andReturn([
                'ok' => true,
                'message' => 'Comprobante B001-00000603 aceptado por SUNAT.',
            ]);
        $this->app->instance(SunatDispatchService::class, $dispatchMock);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.dispatch_billing', $pendingBilling->id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'msg' => 'Comprobante B001-00000603 aceptado por SUNAT.',
        ]);
    }

    public function test_xml_and_cdr_download_endpoints()
    {
        $billing = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000701',
        ]);

        $storagePath = app(BusinessStoragePath::class);
        $xmlDir = $storagePath->xmlDirectory($this->business);
        $cdrDir = $storagePath->cdrDirectory($this->business);
        File::ensureDirectoryExists($xmlDir);
        File::ensureDirectoryExists($cdrDir);

        $baseName = $this->business->ruc . '-03-B001-00000701';
        $xmlFile = $xmlDir . DIRECTORY_SEPARATOR . $baseName . '.XML';
        $cdrFile = $cdrDir . DIRECTORY_SEPARATOR . 'R-' . $baseName . '.ZIP';

        File::put($xmlFile, '<xml>dummy invoice</xml>');
        File::put($cdrFile, 'dummy zip cdr');

        // Test XML view/download
        $xmlResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.billing_xml', $billing->id));

        $xmlResponse->assertStatus(200);
        $this->assertStringContainsString('dummy invoice', file_get_contents($xmlResponse->getFile()->getPathname()));

        // Test XML attachment download
        $xmlDownloadResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.billing_xml', ['id' => $billing->id, 'download' => 1]));
        $xmlDownloadResponse->assertStatus(200);
        $this->assertStringContainsString('dummy invoice', file_get_contents($xmlDownloadResponse->getFile()->getPathname()));

        // Test CDR view/download
        $cdrResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.billing_cdr', $billing->id));

        $cdrResponse->assertStatus(200);
        $this->assertStringContainsString('dummy zip cdr', file_get_contents($cdrResponse->getFile()->getPathname()));

        // Test CDR attachment download
        $cdrDownloadResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.billing_cdr', ['id' => $billing->id, 'download' => 1]));
        $cdrDownloadResponse->assertStatus(200);
        $this->assertStringContainsString('dummy zip cdr', file_get_contents($cdrDownloadResponse->getFile()->getPathname()));

        // Clean up dummy test files
        File::delete($xmlFile);
        File::delete($cdrFile);
    }

    public function test_ticket_and_a4_printing_with_qr_code()
    {
        $billing = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000801',
            'total' => 100.00,
        ]);

        // Print ticket
        $ticketResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.print_billing_ticket'), ['id' => $billing->id]);

        $ticketResponse->assertStatus(200);
        $ticketResponse->assertJson(['status' => true]);
        $this->assertNotEmpty($ticketResponse->json('pdf'));

        $ticketPdfPath = public_path('files/billings/ticket/' . $ticketResponse->json('pdf'));
        $this->assertTrue(file_exists($ticketPdfPath), 'Ticket PDF was not generated on disk');
        $this->assertGreaterThan(0, filesize($ticketPdfPath));

        // Check QR code image was generated
        $qrPath = public_path('files/billings/qr/B001-00000801.png');
        $this->assertTrue(file_exists($qrPath), 'QR Code image was not generated on disk');

        // Print A4
        $a4Response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.print_billing_a4'), ['id' => $billing->id]);

        $a4Response->assertStatus(200);
        $a4Response->assertJson(['status' => true]);
        $this->assertNotEmpty($a4Response->json('pdf'));

        $a4PdfPath = public_path('files/billings/a4/' . $a4Response->json('pdf'));
        $this->assertTrue(file_exists($a4PdfPath), 'A4 PDF was not generated on disk');
        $this->assertGreaterThan(0, filesize($a4PdfPath));

        // Clean up test PDFs
        @unlink($ticketPdfPath);
        @unlink($a4PdfPath);
        @unlink($qrPath);
    }

    public function test_pdf_reflects_current_tax_settings_from_business()
    {
        // 1. Business configured with Ley de la Amazonia (cobrar_igv = false)
        $this->business->update([
            'razon_social' => 'EMPRESA AMAZONICA S.A.C.',
            'ruc' => '20999888777',
            'cobrar_igv' => false,
        ]);

        $billing = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00000901',
            'total' => 120.00,
            'exonerada' => 120.00,
            'gravada' => 0.00,
            'igv' => 0.00,
        ]);

        $ticketResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.print_billing_ticket'), ['id' => $billing->id]);
        $ticketResponse->assertStatus(200);

        $a4Response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.print_billing_a4'), ['id' => $billing->id]);
        $a4Response->assertStatus(200);

        // 2. Business configured with Regimen General (cobrar_igv = true)
        $this->business->update([
            'razon_social' => 'EMPRESA GENERAL S.A.C.',
            'ruc' => '20111222333',
            'cobrar_igv' => true,
        ]);

        $ticketResponseGeneral = $this->actingAs($this->adminUser)
            ->postJson(route('admin.print_billing_ticket'), ['id' => $billing->id]);
        $ticketResponseGeneral->assertStatus(200);

        $a4ResponseGeneral = $this->actingAs($this->adminUser)
            ->postJson(route('admin.print_billing_a4'), ['id' => $billing->id]);
        $a4ResponseGeneral->assertStatus(200);
    }

    public function test_cancellation_hook()
    {
        $billing = $this->createBilling([
            'serie' => 'B001',
            'correlativo' => '00001001',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.cancel_billing', $billing->id), [
                'reason' => 'Error en datos del cliente',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'hook' => 'cancellation_registered',
        ]);

        $billing->refresh();
        $this->assertTrue((bool) $billing->anulado);
        $this->assertEquals('Error en datos del cliente', $billing->motivo);

        // Trying to cancel an already cancelled document should fail
        $responseRepeat = $this->actingAs($this->adminUser)
            ->postJson(route('admin.cancel_billing', $billing->id));

        $responseRepeat->assertStatus(422);
    }

    public function test_credit_note_and_debit_note_hooks_exist()
    {
        $billing = $this->createBilling([
            'idtipo_comprobante' => $this->docTypeFactura->id,
            'serie' => 'F001',
            'correlativo' => '00001002',
            'cdr' => 1,
            'estado_cpe' => 0,
        ]);

        // Credit note validation check
        $creditResp = $this->actingAs($this->adminUser)
            ->postJson(route('admin.create_credit_note_billing', $billing->id), []);

        $creditResp->assertStatus(422);
        $creditResp->assertJsonValidationErrors(['credit_note_type_id', 'reason']);

        // Debit note validation check
        $debitResp = $this->actingAs($this->adminUser)
            ->postJson(route('admin.create_debit_note_billing', $billing->id), []);

        $debitResp->assertStatus(422);
        $debitResp->assertJsonValidationErrors(['debit_note_type_id', 'reason']);
    }
}
