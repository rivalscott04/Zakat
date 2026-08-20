<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MustahikTest extends TestCase
{
    use RefreshDatabase;

    public function test_mustahik_menyimpan_identity_terenkripsi_dan_mendeteksi_duplicate(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);
        $id = $this->postJson('/api/v1/mustahiks', ['full_name' => 'Siti Aminah', 'phone' => '08123456789', 'identity_type' => 'nik', 'identity_number' => '3201000000000001', 'address' => ['address_line' => 'Jl. Test', 'is_primary' => true], 'profile' => ['occupation' => 'Pedagang', 'monthly_income' => 1000000]])->assertCreated()->assertJsonPath('data.verification_status', 'unverified')->json('data.id');
        $this->assertDatabaseHas('mustahik_identities', ['mustahik_id' => $id, 'identity_number_hash' => hash('sha256', '3201000000000001')]);
        $this->assertDatabaseMissing('mustahik_identities', ['identity_number_encrypted' => '3201000000000001']);
        $this->postJson('/api/v1/mustahiks/check-duplicate', ['full_name' => 'Siti Aminah', 'phone' => '08123456789'])->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/mustahiks/{$id}/asnaf", ['asnaf_code' => 'miskin', 'primary_asnaf' => true, 'reason' => 'Hasil verifikasi lapangan'])->assertCreated();
        $this->postJson("/api/v1/mustahiks/{$id}/verify", ['status' => 'verified'])->assertOk()->assertJsonPath('data.verification_status', 'verified');
    }
}
