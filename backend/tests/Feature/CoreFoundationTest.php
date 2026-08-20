<?php

namespace Tests\Feature;

use App\Services\AuditService;
use App\Services\BusinessNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/** PRD 00 — identitas, numbering, envelope API, dan audit. */
class CoreFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_number_berurutan_dan_tidak_dipakai_ulang(): void
    {
        $service = app(BusinessNumberService::class);

        $pertama = $service->next('ORG', 2026);
        $kedua = $service->next('ORG', 2026);

        $this->assertSame('ORG2026000001', $pertama);
        $this->assertSame('ORG2026000002', $kedua);
        $this->assertSame('AML2026000001', $service->next('AML', 2026));
        $this->assertSame('ORG2027000001', $service->next('ORG', 2027));
    }

    public function test_business_code_tak_terdaftar_ditolak(): void
    {
        $this->expectException(RuntimeException::class);

        app(BusinessNumberService::class)->next('XXX');
    }

    public function test_primary_key_memakai_ulid(): void
    {
        $organization = $this->organization();

        $this->assertTrue(Str::isUlid($organization->getKey()));
    }

    public function test_envelope_sukses_memuat_data_dan_meta(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $this->loginAs($admin, $organization)
            ->getJson('/api/v1/amils')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total']]);
    }

    public function test_envelope_error_memuat_code_dan_request_id(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $response = $this->loginAs($admin, $organization)
            ->postJson('/api/v1/amils', ['name' => '']);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors', 'code', 'request_id'])
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->assertTrue(Str::isUlid($response->json('request_id')));
    }

    public function test_request_id_dikembalikan_pada_header(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $this->assertTrue(Str::isUlid($response->headers->get('X-Request-Id')));
    }

    public function test_request_id_dari_client_hanya_diterima_bila_berbentuk_ulid(): void
    {
        $ulid = (string) Str::ulid();

        $this->withHeader('X-Request-Id', $ulid)->getJson('/api/v1/health')
            ->assertHeader('X-Request-Id', $ulid);

        $palsu = $this->withHeader('X-Request-Id', '"><script>')->getJson('/api/v1/health');

        $this->assertNotSame('"><script>', $palsu->headers->get('X-Request-Id'));
    }

    public function test_audit_service_menyamarkan_field_sensitif(): void
    {
        $hasil = app(AuditService::class)->redact([
            'name' => 'Rival',
            'password' => 'rahasia',
            'nested' => ['token' => 'abc', 'email' => 'a@b.test'],
        ]);

        $this->assertSame('Rival', $hasil['name']);
        $this->assertSame('[redacted]', $hasil['password']);
        $this->assertSame('[redacted]', $hasil['nested']['token']);
        $this->assertSame('a@b.test', $hasil['nested']['email']);
    }

    public function test_kolom_uang_memakai_numeric_bukan_float(): void
    {
        // PRD 00 §12 — macro money() dipakai modul keuangan berikutnya.
        Schema::create('money_macro_probe', function ($table) {
            $table->ulid('id')->primary();
            $table->money('amount');
        });

        $tipe = DB::selectOne(
            "SELECT data_type, numeric_precision, numeric_scale FROM information_schema.columns
             WHERE table_name = 'money_macro_probe' AND column_name = 'amount'"
        );

        $this->assertSame('numeric', $tipe->data_type);
        $this->assertSame(20, (int) $tipe->numeric_precision);
        $this->assertSame(2, (int) $tipe->numeric_scale);

        Schema::drop('money_macro_probe');
    }

    public function test_waktu_disimpan_utc_dan_diserialisasi_iso8601(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('Asia/Makassar', config('zakat.display_timezone'));

        $response = $this->loginAs($admin, $organization)->getJson("/api/v1/organizations/{$organization->getKey()}");

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/',
            $response->json('data.created_at')
        );
    }
}
