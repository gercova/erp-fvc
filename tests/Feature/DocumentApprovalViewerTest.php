<?php

namespace Tests\Feature;

use App\Models\DocumentApproval;
use App\Models\FuelControlSlip;
use App\Models\User;
use Tests\TestCase;

class DocumentApprovalViewerTest extends TestCase
{
    public function test_approval_show_renders_fuel_control_slip_even_when_soft_deleted(): void
    {
        $user = User::first();
        $this->actingAs($user);

        // Approval #7 corresponds to the FuelControlSlip #3 which was soft deleted (ANULADO)
        $approval = DocumentApproval::find(7);
        $this->assertNotNull($approval);
        $this->assertNotNull($approval->document);
        $this->assertEquals('0000042', $approval->document->correlativo);

        $response = $this->get(route('approvals.show', 7));

        $response->assertStatus(200);
        $response->assertSee('0000042');
        $response->assertSee('GRIFO PEPITO');
        $response->assertSee('Visor:');
        $response->assertSee('ANULADO');
    }

    public function test_cannot_approve_anulado_document(): void
    {
        $user = User::first();
        $this->actingAs($user);

        $response = $this->postJson(route('approvals.approve'), [
            'approval_id' => 7,
            'observations' => 'Intentando aprobar documento anulado',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'type' => 'error',
        ]);
    }
}
