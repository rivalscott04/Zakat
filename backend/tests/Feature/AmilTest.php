<?php

namespace Tests\Feature;

use App\Models\Amil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PRD 02D dan §37 — amil dan assignment. */
class AmilTest extends TestCase
{
    use RefreshDatabase;

    public function test_amil_dapat_dibuat_tanpa_user_account(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $response = $this->loginAs($admin, $organization)
            ->postJson('/api/v1/amils', ['name' => 'Amil Lapangan']);

        $response->assertStatus(201)
            ->assertJsonPath('data.has_user_account', false)
            ->assertJsonPath('data.status', 'active');

        $this->assertMatchesRegularExpression('/^AML\d{4}\d{6}$/', $response->json('data.business_number'));
    }

    public function test_amil_hanya_dapat_dikaitkan_dengan_member_aktif(): void
    {
        $organizationA = $this->organization();
        $organizationB = $this->organization();

        $admin = $this->member($organizationA, 'ADMIN');
        $orangLuar = $this->member($organizationB, 'ADMIN');

        $this->loginAs($admin, $organizationA)
            ->postJson('/api/v1/amils', ['name' => 'Amil Asing', 'user_id' => $orangLuar->getKey()])
            ->assertStatus(409);
    }

    public function test_satu_user_hanya_boleh_punya_satu_amil_aktif(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $this->loginAs($admin, $organization)
            ->postJson('/api/v1/amils', ['name' => 'Amil Satu', 'user_id' => $admin->getKey()])
            ->assertStatus(201);

        $this->postJson('/api/v1/amils', ['name' => 'Amil Dua', 'user_id' => $admin->getKey()])
            ->assertStatus(409)
            ->assertJsonPath('code', 'DUPLICATE_RESOURCE');
    }

    public function test_amil_ended_tidak_menerima_assignment_baru(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $amilId = $this->loginAs($admin, $organization)
            ->postJson('/api/v1/amils', ['name' => 'Amil Berakhir'])
            ->json('data.id');

        $this->postJson("/api/v1/amils/{$amilId}/end")->assertOk()->assertJsonPath('data.status', 'ended');

        $this->postJson("/api/v1/amils/{$amilId}/assignments", ['assignment_type' => 'Collection Officer'])
            ->assertStatus(409);
    }

    public function test_assignment_aktif_tidak_boleh_ganda(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $amilId = $this->loginAs($admin, $organization)
            ->postJson('/api/v1/amils', ['name' => 'Amil Ganda'])
            ->json('data.id');

        $this->postJson("/api/v1/amils/{$amilId}/assignments", ['assignment_type' => 'Verifier'])
            ->assertStatus(201);

        $this->postJson("/api/v1/amils/{$amilId}/assignments", ['assignment_type' => 'Verifier'])
            ->assertStatus(409);
    }

    public function test_mengakhiri_amil_mengakhiri_assignment_aktifnya(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $amilId = $this->loginAs($admin, $organization)
            ->postJson('/api/v1/amils', ['name' => 'Amil Tutup'])
            ->json('data.id');

        $this->postJson("/api/v1/amils/{$amilId}/assignments", ['assignment_type' => 'Field Officer'])
            ->assertStatus(201);

        $this->postJson("/api/v1/amils/{$amilId}/end")->assertOk();

        // PRD 02 §37.5 — histori assignment tetap ada, hanya statusnya berubah.
        $this->assertDatabaseHas('amil_assignments', [
            'amil_id' => $amilId,
            'assignment_type' => 'Field Officer',
            'status' => 'ended',
        ]);
    }

    public function test_business_number_bersifat_immutable(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $amilId = $this->loginAs($admin, $organization)
            ->postJson('/api/v1/amils', ['name' => 'Amil Nomor'])
            ->json('data.id');

        $amil = Amil::query()->acrossOrganizations()->findOrFail($amilId);
        $asli = $amil->business_number;

        $amil->business_number = 'AML2000000001';
        $amil->save();

        $this->assertSame($asli, $amil->fresh()->business_number);
    }

    public function test_perubahan_amil_tercatat_pada_audit_trail(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $amilId = $this->loginAs($admin, $organization)
            ->postJson('/api/v1/amils', ['name' => 'Amil Audit'])
            ->json('data.id');

        $this->patchJson("/api/v1/amils/{$amilId}", ['name' => 'Amil Audit Diubah'])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'amil_created',
            'entity_id' => $amilId,
            'actor_id' => $admin->getKey(),
            'organization_id' => $organization->getKey(),
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'amil_updated', 'entity_id' => $amilId]);
    }
}
