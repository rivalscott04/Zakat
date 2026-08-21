<?php

namespace Tests\Feature;

use App\Enums\CalculationMethod;
use App\Enums\MuzakiStatus;
use App\Enums\MuzakiType;
use App\Enums\ZakatStatus;
use App\Models\CollectionPayment;
use App\Models\Muzaki;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentProvider;
use App\Models\PaymentWebhook;
use App\Models\User;
use App\Models\ZakatCategory;
use App\Models\ZakatType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** PRD 13V — pengujian lifecycle, webhook, refund, dan keamanan payment gateway. */
class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'rahasia-webhook-yang-cukup-panjang-32';

    /** @return array{organization: Organization, maker: User, checker: User, provider: string, collection: string} */
    private function scenario(string $tagihan = '1000000'): array
    {
        $organization = $this->organization();
        $maker = $this->member($organization);
        $checker = $this->member($organization, 'ADMIN', ['email' => 'checker'.uniqid().'@example.test']);

        $muzaki = new Muzaki;
        $muzaki->fill(['display_name' => 'Muzaki Bayar', 'registration_source' => 'manual', 'registered_at' => now()]);
        $muzaki->forceFill(['organization_id' => $organization->id, 'muzaki_type' => MuzakiType::Individual, 'status' => MuzakiStatus::Active])->save();
        $category = new ZakatCategory;
        $category->fill(['code' => 'ZK_PAY', 'name' => 'Zakat Payment'])->forceFill(['organization_id' => $organization->id])->save();
        $type = new ZakatType;
        $type->fill(['zakat_category_id' => $category->id, 'code' => 'ZK_PAY', 'name' => 'Zakat Payment', 'calculation_method' => CalculationMethod::Fixed, 'status' => ZakatStatus::Active])->forceFill(['organization_id' => $organization->id])->save();

        $this->loginAs($maker, $organization);
        $collection = $this->postJson('/api/v1/collections', ['muzaki_id' => $muzaki->id, 'zakat_type_id' => $type->id, 'expected_amount' => $tagihan, 'source' => 'manual', 'reason' => 'Uji payment'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/collections/{$collection}/confirm")->assertOk();

        $provider = $this->postJson('/api/v1/payment-providers', [
            'provider_code' => 'MANUAL',
            'name' => 'Manual Provider',
            'driver' => 'manual',
            'webhook_secret' => self::SECRET,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/payment-providers/{$provider}/activate")->assertOk();

        return compact('organization', 'maker', 'checker', 'provider', 'collection');
    }

    private function createPayment(array $s, string $amount = '1000000'): array
    {
        return $this->postJson('/api/v1/payments', [
            'provider_id' => $s['provider'],
            'source_type' => 'zakat',
            'source_id' => $s['collection'],
            'amount' => $amount,
            'payer_name' => 'Donatur Uji',
        ])->assertCreated()->json('data');
    }

    /** @param array<string, mixed> $payload */
    private function webhook(string $providerId, array $payload, ?string $secret = self::SECRET): TestResponse
    {
        $body = json_encode($payload);
        $headers = $secret === null ? [] : ['X-Signature' => hash_hmac('sha256', $body, $secret)];

        return $this->call('POST', "/api/v1/webhooks/payments/{$providerId}", [], [], [], $this->transformHeadersToServerVars($headers + ['Content-Type' => 'application/json', 'Accept' => 'application/json']), $body);
    }

    // ------------------------------------------------------------- lifecycle

    public function test_payment_dibuat_dengan_nomor_dan_status_pending(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        $this->assertMatchesRegularExpression('/^PAY\d{4}\d{6}$/', $payment['payment_number']);
        $this->assertSame('pending', $payment['status']);
        $this->assertNotNull($payment['provider_reference']);
        $this->assertNotNull($payment['expires_at']);
    }

    public function test_provider_tidak_aktif_menolak_pembuatan_payment(): void
    {
        $s = $this->scenario();
        $this->postJson("/api/v1/payment-providers/{$s['provider']}/deactivate")->assertOk();

        $this->postJson('/api/v1/payments', [
            'provider_id' => $s['provider'],
            'source_type' => 'zakat',
            'source_id' => $s['collection'],
            'amount' => 1000,
        ])->assertStatus(409);
    }

    /** PRD 13U §3 dan PRD 13V §44 soal manipulasi amount. */
    public function test_nominal_melebihi_sisa_tagihan_ditolak(): void
    {
        $s = $this->scenario('1000000');

        $this->postJson('/api/v1/payments', [
            'provider_id' => $s['provider'],
            'source_type' => 'zakat',
            'source_id' => $s['collection'],
            'amount' => 5000000,
        ])->assertStatus(409);
    }

    // --------------------------------------------------------------- webhook

    public function test_webhook_sah_menandai_lunas_dan_meneruskan_ke_modul_sumber(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        $this->webhook($s['provider'], [
            'event_id' => 'evt-1',
            'event_type' => 'payment.paid',
            'provider_reference' => $payment['provider_reference'],
            'status' => 'paid',
            'amount' => '1000000.00',
        ])->assertStatus(202)->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('payments', ['id' => $payment['id'], 'status' => 'paid']);
        $this->assertDatabaseHas('payment_webhooks', ['event_id' => 'evt-1', 'status' => 'processed', 'signature_valid' => true]);

        // PRD 13L §24 — modul sumber yang menjalankan bisnisnya.
        $this->assertDatabaseHas('collection_payments', ['payment_reference' => $payment['payment_number'], 'status' => 'settled']);
        $this->assertDatabaseHas('collections', ['id' => $s['collection'], 'status' => 'completed', 'paid_amount' => '1000000.00']);
    }

    /** PRD 13V §44 — tanda tangan tidak valid. */
    public function test_webhook_dengan_tanda_tangan_salah_ditolak(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        $this->webhook($s['provider'], [
            'event_id' => 'evt-palsu',
            'provider_reference' => $payment['provider_reference'],
            'status' => 'paid',
            'amount' => '1000000.00',
        ], 'rahasia-yang-salah-tetapi-panjang-32')->assertStatus(202)->assertJsonPath('status', 'rejected');

        $this->assertDatabaseHas('payments', ['id' => $payment['id'], 'status' => 'pending']);
        $this->assertDatabaseHas('payment_webhooks', ['event_id' => 'evt-palsu', 'signature_valid' => false, 'status' => 'failed']);
    }

    public function test_webhook_tanpa_tanda_tangan_ditolak(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        $this->webhook($s['provider'], [
            'event_id' => 'evt-kosong',
            'provider_reference' => $payment['provider_reference'],
            'status' => 'paid',
            'amount' => '1000000.00',
        ], null)->assertStatus(202)->assertJsonPath('status', 'rejected');

        $this->assertDatabaseHas('payments', ['id' => $payment['id'], 'status' => 'pending']);
    }

    /** PRD 13J §20 dan PRD 13V §44 soal replay. */
    public function test_webhook_duplikat_tidak_diproses_dua_kali(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        $payload = [
            'event_id' => 'evt-sama',
            'provider_reference' => $payment['provider_reference'],
            'status' => 'paid',
            'amount' => '1000000.00',
        ];

        $this->webhook($s['provider'], $payload)->assertJsonPath('status', 'ok');
        $this->webhook($s['provider'], $payload)->assertJsonPath('status', 'duplicate');
        $this->webhook($s['provider'], $payload)->assertJsonPath('status', 'duplicate');

        $this->assertSame(1, PaymentWebhook::where('event_id', 'evt-sama')->count());
        // Collection hanya menerima satu pelunasan.
        $this->assertSame(1, CollectionPayment::where('collection_id', $s['collection'])->count());
        $this->assertDatabaseHas('collections', ['id' => $s['collection'], 'paid_amount' => '1000000.00']);
    }

    /** PRD 13K §21 — nominal wajib cocok. */
    public function test_webhook_dengan_nominal_berbeda_ditolak(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        $this->webhook($s['provider'], [
            'event_id' => 'evt-nominal',
            'provider_reference' => $payment['provider_reference'],
            'status' => 'paid',
            'amount' => '9999999.00',
        ])->assertJsonPath('status', 'rejected');

        $this->assertDatabaseHas('payments', ['id' => $payment['id'], 'status' => 'failed', 'failure_reason' => 'invalid_amount']);
    }

    public function test_webhook_ke_provider_tidak_dikenal_diabaikan_tanpa_bocor(): void
    {
        $this->webhook((string) Str::ulid(), ['event_id' => 'x', 'status' => 'paid'])
            ->assertStatus(202)
            ->assertJsonPath('status', 'ignored');
    }

    // --------------------------------------------------- verifikasi dan status

    public function test_verifikasi_manual_wajib_beralasan_dan_tercatat(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        $this->postJson("/api/v1/payments/{$payment['id']}/verify", ['reason' => ''])->assertStatus(422);

        $this->postJson("/api/v1/payments/{$payment['id']}/verify", ['reason' => 'Bukti transfer diterima via WhatsApp'])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('audit_logs', ['action' => 'payment_manually_verified', 'entity_id' => $payment['id']]);
        $this->assertDatabaseHas('collections', ['id' => $s['collection'], 'status' => 'completed']);
    }

    public function test_payment_lunas_tidak_dapat_kembali_pending_atau_dibatalkan(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);
        $this->postJson("/api/v1/payments/{$payment['id']}/verify", ['reason' => 'Sudah dibayar tunai'])->assertOk();

        $this->postJson("/api/v1/payments/{$payment['id']}/cancel", ['reason' => 'Coba batalkan'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_STATE_TRANSITION');
    }

    /** PRD 13M §25. */
    public function test_payment_kedaluwarsa_ditutup_penjadwal_dan_tidak_dapat_dipakai(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        Payment::withoutGlobalScopes()->where('id', $payment['id'])->update(['expires_at' => now()->subHour()]);

        $this->artisan('zakat:expire-pending-payments')->assertSuccessful();

        $this->assertDatabaseHas('payments', ['id' => $payment['id'], 'status' => 'expired']);

        $this->postJson("/api/v1/payments/{$payment['id']}/verify", ['reason' => 'Terlambat dibayar'])->assertStatus(409);
    }

    // ---------------------------------------------------------------- refund

    public function test_refund_butuh_payment_lunas_dan_tidak_boleh_melebihi_nominal(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        $this->postJson("/api/v1/payments/{$payment['id']}/refunds", ['amount' => 1000, 'reason' => 'Belum lunas'])
            ->assertStatus(409);

        $this->postJson("/api/v1/payments/{$payment['id']}/verify", ['reason' => 'Dibayar tunai'])->assertOk();

        $this->postJson("/api/v1/payments/{$payment['id']}/refunds", ['amount' => 2000000, 'reason' => 'Kelebihan'])
            ->assertStatus(409);

        $refund = $this->postJson("/api/v1/payments/{$payment['id']}/refunds", ['amount' => 1000000, 'reason' => 'Salah transfer'])
            ->assertCreated()->json('data.id');

        // Maker checker: pemohon tidak boleh menyetujui sendiri.
        $this->postJson("/api/v1/payment-refunds/{$refund}/approve")->assertStatus(403);

        $this->loginAs($s['checker'], $s['organization']);
        $this->postJson("/api/v1/payment-refunds/{$refund}/approve")->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('payments', ['id' => $payment['id'], 'status' => 'refunded']);
    }

    // ------------------------------------------------------- keamanan dan isolasi

    /** PRD 13T §40 dan PRD 13V §44 soal kebocoran kredensial. */
    public function test_kredensial_provider_tidak_pernah_dikembalikan(): void
    {
        $s = $this->scenario();

        $response = $this->getJson("/api/v1/payment-providers/{$s['provider']}")->assertOk();

        $this->assertStringNotContainsString(self::SECRET, $response->getContent());
        $this->assertTrue($response->json('data.webhook_secret_configured'));

        // Terenkripsi di database, bukan tersimpan apa adanya.
        $stored = PaymentProvider::withoutGlobalScopes()->find($s['provider']);
        $this->assertNotSame(self::SECRET, $stored->getRawOriginal('webhook_secret_encrypted'));
        $this->assertSame(self::SECRET, $stored->webhook_secret_encrypted);
    }

    public function test_payment_organisasi_lain_tidak_dapat_diakses(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        $organizationB = $this->organization();
        $adminB = $this->member($organizationB, 'ADMIN', ['email' => 'lainpay@example.test']);

        $this->loginAs($adminB, $organizationB);
        $this->getJson("/api/v1/payments/{$payment['id']}")->assertNotFound();
        $this->postJson("/api/v1/payments/{$payment['id']}/verify", ['reason' => 'Coba tembus'])->assertNotFound();
        $this->getJson('/api/v1/payments')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_permission_kurang_ditolak(): void
    {
        $s = $this->scenario();
        $payment = $this->createPayment($s);

        $viewer = $this->member($s['organization'], 'VIEWER', ['email' => 'viewerpay@example.test']);
        $this->loginAs($viewer, $s['organization']);

        $this->postJson("/api/v1/payments/{$payment['id']}/verify", ['reason' => 'Tanpa izin'])->assertForbidden();
        $this->getJson('/api/v1/payment-providers')->assertForbidden();
    }

    public function test_provider_tanpa_webhook_secret_tidak_dapat_diaktifkan(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);

        $provider = $this->postJson('/api/v1/payment-providers', [
            'provider_code' => 'TANPASECRET',
            'name' => 'Tanpa Secret',
            'driver' => 'manual',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/payment-providers/{$provider}/activate")->assertStatus(409);
    }

    public function test_driver_yang_belum_tersedia_ditolak(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);

        $this->postJson('/api/v1/payment-providers', [
            'provider_code' => 'MIDTRANS',
            'name' => 'Midtrans',
            'driver' => 'midtrans',
        ])->assertStatus(409);
    }
}
