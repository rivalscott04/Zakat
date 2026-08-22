<?php

namespace Tests\Feature;

use App\Enums\EntityStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationPriority;
use App\Enums\NotificationRecipientStrategy;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationDelivery;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\NotificationWebhook;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\Channels\WebhookChannel;
use App\Services\NotificationService;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** PRD 16 — notification terpusat, template, rule, preference, queue, dan retry. */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function template(Organization $organization, array $overrides = []): NotificationTemplate
    {
        $template = new NotificationTemplate;
        $template->fill($overrides + [
            'template_code' => 'PAYMENTRECEIVED',
            'name' => 'Payment diterima',
            'channel' => NotificationChannel::InApp->value,
            'subject' => 'Pembayaran {{payment_number}}',
            'content' => 'Halo {{recipient_name}}, pembayaran {{payment_number}} sebesar {{amount}} telah diterima.',
            'variables' => ['payment_number', 'amount'],
        ]);
        $template->organization_id = $organization->getKey();
        $template->status = EntityStatus::Active;
        $template->save();

        return $template;
    }

    private function webhookEndpoint(Organization $organization, string $secret = 'rahasia'): NotificationWebhook
    {
        $webhook = new NotificationWebhook;
        $webhook->fill(['name' => 'Eksternal', 'url' => 'https://contoh.test/hook', 'events' => ['payment_paid']]);
        $webhook->organization_id = $organization->getKey();
        $webhook->status = EntityStatus::Active;
        $webhook->secret_encrypted = $secret;
        $webhook->save();

        return $webhook;
    }

    private function rule(Organization $organization, ?NotificationTemplate $template, array $overrides = []): NotificationRule
    {
        $rule = new NotificationRule;
        $rule->fill($overrides + [
            'event_name' => 'payment_paid',
            'template_id' => $template?->getKey(),
            'channels' => [NotificationChannel::InApp->value],
            'recipient_strategy' => NotificationRecipientStrategy::Role->value,
            'recipient_config' => ['roles' => ['ADMIN']],
            'priority' => NotificationPriority::Normal->value,
        ]);
        $rule->organization_id = $organization->getKey();
        $rule->enabled = true;
        $rule->save();

        return $rule;
    }

    // ------------------------------------------------------------ pemicu

    public function test_event_menghasilkan_notification_bernomor_dengan_template_terisi(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);
        OrganizationContext::set($organization);

        $this->rule($organization, $this->template($organization));

        $created = app(NotificationService::class)->dispatchEvent('payment_paid', [
            'payment_number' => 'PAY2026000001',
            'amount' => '250000.00',
        ], $organization->getKey());

        $this->assertCount(1, $created);

        $notification = Notification::query()->acrossOrganizations()->firstOrFail();

        $this->assertMatchesRegularExpression('/^NTF\d{4}\d{6}$/', $notification->notification_number);
        $this->assertSame('Pembayaran PAY2026000001', $notification->title);
        $this->assertStringContainsString($admin->name, $notification->message);
        $this->assertStringContainsString('250000.00', $notification->message);
        // In app dikirim langsung, tanpa queue.
        $this->assertSame(NotificationStatus::Sent, $notification->fresh()->status);
    }

    /** PRD 16Y §2 — tanpa recipient tidak ada notification yang dibuat. */
    public function test_rule_tanpa_recipient_tidak_membuat_notification(): void
    {
        $organization = $this->organization();
        OrganizationContext::set($organization);

        $this->rule($organization, $this->template($organization), [
            'recipient_strategy' => NotificationRecipientStrategy::Role->value,
            'recipient_config' => ['roles' => ['TIDAK_ADA']],
        ]);

        app(NotificationService::class)->dispatchEvent('payment_paid', ['payment_number' => 'X', 'amount' => '1'], $organization->getKey());

        $this->assertSame(0, Notification::query()->acrossOrganizations()->count());
    }

    /** PRD 16Y §7 — event yang sama tidak menghasilkan notification ganda. */
    public function test_idempotency_key_mencegah_notification_ganda(): void
    {
        $organization = $this->organization();
        $this->member($organization);
        OrganizationContext::set($organization);
        $this->rule($organization, $this->template($organization));

        $payload = ['payment_number' => 'PAY1', 'amount' => '1000'];

        foreach ([1, 2] as $ignored) {
            app(NotificationService::class)->dispatchEvent('payment_paid', $payload, $organization->getKey(), 'payment:PAY1');
        }

        $this->assertSame(1, Notification::query()->acrossOrganizations()->count());
    }

    /** PRD 16Y §15 — recipient hanya dari organisasi yang sama. */
    public function test_recipient_tidak_melintasi_organisasi(): void
    {
        $first = $this->organization();
        $second = $this->organization();
        $this->member($first);
        $outsider = $this->member($second);

        OrganizationContext::set($first);
        $this->rule($first, $this->template($first));

        app(NotificationService::class)->dispatchEvent('payment_paid', ['payment_number' => 'P', 'amount' => '1'], $first->getKey());

        $this->assertSame(
            0,
            Notification::query()->acrossOrganizations()->where('recipient_id', $outsider->getKey())->count()
        );
    }

    /** PRD 16B §3 — modul lain memancarkan event tanpa memanggil notification sendiri. */
    public function test_aksi_modul_lain_memicu_notification_lewat_rule(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);

        // Lewat API supaya sekaligus menguji pembatalan cache daftar event.
        $this->postJson('/api/v1/notification-rules', [
            'event_name' => 'amil_created',
            'channels' => [NotificationChannel::InApp->value],
            'recipient_strategy' => NotificationRecipientStrategy::OrganizationAdmin->value,
        ])->assertCreated();

        $this->postJson('/api/v1/amils', ['name' => 'Amil Baru'])->assertCreated();

        $notification = Notification::query()->acrossOrganizations()->where('event_name', 'amil_created')->first();

        $this->assertNotNull($notification);
        $this->assertSame($admin->getKey(), $notification->recipient_id);
    }

    // -------------------------------------------------------- preference

    public function test_preference_mematikan_channel_tertentu(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        OrganizationContext::set($organization);

        NotificationPreference::query()->create([
            'user_id' => $admin->getKey(),
            'organization_id' => $organization->getKey(),
            'event_name' => 'payment_paid',
            'channel' => NotificationChannel::InApp,
            'enabled' => false,
        ]);

        $this->rule($organization, $this->template($organization));
        app(NotificationService::class)->dispatchEvent('payment_paid', ['payment_number' => 'P', 'amount' => '1'], $organization->getKey());

        $notification = Notification::query()->acrossOrganizations()->firstOrFail();
        $this->assertSame(0, $notification->deliveries()->count());
    }

    /** PRD 16Y §12 — URGENT tetap dikirim walau preference dimatikan. */
    public function test_notifikasi_urgent_melewati_preference(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        OrganizationContext::set($organization);

        NotificationPreference::query()->create([
            'user_id' => $admin->getKey(),
            'organization_id' => $organization->getKey(),
            'event_name' => 'payment_paid',
            'channel' => NotificationChannel::InApp,
            'enabled' => false,
        ]);

        $this->rule($organization, $this->template($organization), ['priority' => NotificationPriority::Urgent->value]);
        app(NotificationService::class)->dispatchEvent('payment_paid', ['payment_number' => 'P', 'amount' => '1'], $organization->getKey());

        $this->assertSame(1, Notification::query()->acrossOrganizations()->firstOrFail()->deliveries()->count());
    }

    // ------------------------------------------------------------ template

    /** PRD 16Y §9 — variabel tak dikenal tidak boleh terkirim. */
    public function test_template_dengan_variabel_tak_dikenal_ditolak(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $this->postJson('/api/v1/notification-templates', [
            'template_code' => 'TESTVAR',
            'name' => 'Uji variabel',
            'channel' => NotificationChannel::InApp->value,
            'content' => 'Halo {{nama_asing}}',
        ])->assertStatus(409);
    }

    public function test_template_dengan_sintaks_tidak_berpasangan_ditolak(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $this->postJson('/api/v1/notification-templates', [
            'template_code' => 'TESTSYNTAX',
            'name' => 'Uji sintaks',
            'channel' => NotificationChannel::InApp->value,
            'content' => 'Halo {{recipient_name}',
        ])->assertStatus(409);
    }

    public function test_template_code_dinormalkan_uppercase_tanpa_dash(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $this->postJson('/api/v1/notification-templates', [
            'template_code' => 'paymentreceived',
            'name' => 'Uji kode',
            'channel' => NotificationChannel::InApp->value,
            'content' => 'Halo {{recipient_name}}',
        ])->assertCreated()->assertJsonPath('data.template_code', 'PAYMENTRECEIVED');
    }

    public function test_rule_menolak_template_yang_belum_aktif(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $template = $this->template($organization);
        $template->status = EntityStatus::Draft;
        $template->save();

        $this->postJson('/api/v1/notification-rules', [
            'event_name' => 'payment_paid',
            'template_id' => $template->getKey(),
            'channels' => [NotificationChannel::InApp->value],
            'recipient_strategy' => NotificationRecipientStrategy::OrganizationAdmin->value,
        ])->assertStatus(409);
    }

    // ------------------------------------------------------------- queue

    /** PRD 16O §32 — channel eksternal tidak dikirim di dalam request utama. */
    public function test_channel_eksternal_masuk_queue(): void
    {
        Queue::fake();

        $organization = $this->organization();
        $this->member($organization);
        OrganizationContext::set($organization);

        $this->rule($organization, $this->template($organization), [
            'channels' => [NotificationChannel::Email->value],
        ]);

        app(NotificationService::class)->dispatchEvent('payment_paid', ['payment_number' => 'P', 'amount' => '1'], $organization->getKey());

        Queue::assertPushed(SendNotificationDelivery::class);
        $this->assertSame(NotificationDeliveryStatus::Queued, NotificationDelivery::query()->firstOrFail()->status);
    }

    /** PRD 16P §33 — gagal berulang berhenti pada batas percobaan. */
    public function test_retry_berhenti_pada_batas_percobaan(): void
    {
        Http::fake(fn () => Http::response('nope', 500));
        // Job ditahan supaya percobaan dijalankan satu per satu di test ini.
        Queue::fake();

        $organization = $this->organization();
        $this->member($organization);
        OrganizationContext::set($organization);

        $this->webhookEndpoint($organization);
        $this->rule($organization, $this->template($organization), [
            'channels' => [NotificationChannel::Webhook->value],
        ]);

        app(NotificationService::class)->dispatchEvent('payment_paid', ['payment_number' => 'P', 'amount' => '1'], $organization->getKey());

        $delivery = NotificationDelivery::query()->firstOrFail();
        $service = app(NotificationService::class);

        for ($attempt = 0; $attempt < $delivery->max_attempts; $attempt++) {
            $service->deliver($delivery->fresh());
        }

        $delivery = $delivery->fresh();
        $this->assertSame(NotificationDeliveryStatus::Failed, $delivery->status);
        $this->assertSame($delivery->max_attempts, $delivery->attempt_count);
        $this->assertSame(NotificationStatus::Failed, $delivery->notification->fresh()->status);
    }

    // -------------------------------------------------------- terjadwal

    /** PRD 16Q §35 — yang terjadwal baru masuk antrean setelah waktunya tiba. */
    public function test_notification_terjadwal_dilepas_scheduler(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        OrganizationContext::set($organization);

        app(NotificationService::class)->send(
            organizationId: $organization->getKey(),
            userId: $admin->getKey(),
            content: ['title' => 'Dokumen kedaluwarsa', 'message' => 'Segera perbarui dokumen.'],
            channels: [NotificationChannel::InApp],
            scheduledAt: now()->addDay(),
        );

        $notification = Notification::query()->acrossOrganizations()->firstOrFail();
        $this->assertSame(NotificationStatus::Scheduled, $notification->status);

        $this->travel(2)->days();
        $this->artisan('zakat:dispatch-scheduled-notifications')->assertSuccessful();

        $this->assertSame(NotificationStatus::Sent, $notification->fresh()->status);
    }

    // -------------------------------------------------- notification center

    public function test_notification_hanya_dapat_dibaca_penerimanya(): void
    {
        $organization = $this->organization();
        $owner = $this->member($organization);
        $other = $this->member($organization);
        OrganizationContext::set($organization);

        app(NotificationService::class)->send(
            organizationId: $organization->getKey(),
            userId: $owner->getKey(),
            content: ['title' => 'Rahasia', 'message' => 'Hanya untuk penerima.'],
            channels: [NotificationChannel::InApp],
        );

        $notification = Notification::query()->acrossOrganizations()->firstOrFail();

        $this->loginAs($other, $organization);
        $this->getJson("/api/v1/notifications/{$notification->getKey()}")->assertNotFound();

        $this->loginAs($owner, $organization);
        $this->getJson("/api/v1/notifications/{$notification->getKey()}")->assertOk();
    }

    public function test_tandai_dibaca_belum_dibaca_dan_semua(): void
    {
        $organization = $this->organization();
        $owner = $this->member($organization);
        OrganizationContext::set($organization);

        foreach (['Satu', 'Dua'] as $title) {
            app(NotificationService::class)->send(
                organizationId: $organization->getKey(),
                userId: $owner->getKey(),
                content: ['title' => $title, 'message' => $title],
                channels: [NotificationChannel::InApp],
            );
        }

        $this->loginAs($owner, $organization);

        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('meta.unread_count', 2);

        $first = Notification::query()->acrossOrganizations()->firstOrFail();
        $this->postJson("/api/v1/notifications/{$first->getKey()}/read")->assertOk();
        $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.unread_count', 1);

        $this->postJson("/api/v1/notifications/{$first->getKey()}/unread")->assertOk();
        $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.unread_count', 2);

        $this->postJson('/api/v1/notifications/read-all')->assertOk()->assertJsonPath('data.marked', 2);
        $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.unread_count', 0);
    }

    // ------------------------------------------------------------- batch

    public function test_batch_membuat_notification_draft_lalu_mengirimnya(): void
    {
        $organization = $this->organization();
        $sender = $this->member($organization);
        $penerima = collect([$this->member($organization), $this->member($organization)]);
        $this->loginAs($sender, $organization);

        $response = $this->postJson('/api/v1/notification-batches', [
            'name' => 'Pengumuman',
            'title' => 'Rapat amil',
            'message' => 'Rapat amil hari Jumat.',
            'channels' => [NotificationChannel::InApp->value],
            'recipient_ids' => $penerima->map(fn (User $user) => $user->getKey())->all(),
        ])->assertCreated();

        $batchId = $response->json('data.id');

        $this->assertMatchesRegularExpression('/^NFB\d{4}\d{6}$/', $response->json('data.batch_number'));
        $this->assertSame(2, $response->json('data.total_recipient'));
        $this->assertSame(2, Notification::query()->acrossOrganizations()->where('status', NotificationStatus::Draft)->count());

        $this->postJson("/api/v1/notification-batches/{$batchId}/send")
            ->assertOk()
            ->assertJsonPath('data.total_success', 2)
            ->assertJsonPath('data.total_failed', 0);
    }

    // ----------------------------------------------------------- webhook

    /** PRD 16Y §16 — secret webhook terenkripsi dan tidak dapat dibaca ulang. */
    public function test_secret_webhook_hanya_muncul_sekali(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $created = $this->postJson('/api/v1/notification-webhooks', [
            'name' => 'Sistem eksternal',
            'url' => 'https://contoh.test/webhook',
            'events' => ['payment_paid'],
        ])->assertCreated();

        $secret = $created->json('data.secret');
        $this->assertNotEmpty($secret);

        $list = $this->getJson('/api/v1/notification-webhooks')->assertOk();
        $this->assertNull($list->json('data.0.secret'));
        $this->assertTrue($list->json('data.0.has_secret'));

        // Tersimpan sebagai ciphertext, bukan teks polos.
        $this->assertNotSame($secret, DB::table('notification_webhooks')->value('secret_encrypted'));
    }

    public function test_webhook_dikirim_dengan_tanda_tangan(): void
    {
        Http::fake();

        $organization = $this->organization();
        $this->member($organization);
        OrganizationContext::set($organization);

        $this->webhookEndpoint($organization);

        $this->rule($organization, $this->template($organization), [
            'channels' => [NotificationChannel::Webhook->value],
        ]);

        app(NotificationService::class)->dispatchEvent('payment_paid', ['payment_number' => 'P', 'amount' => '1'], $organization->getKey());
        app(NotificationService::class)->deliver(NotificationDelivery::query()->firstOrFail());

        Http::assertSent(fn ($request) => $request->hasHeader(WebhookChannel::SIGNATURE_HEADER)
            && $request->header(WebhookChannel::SIGNATURE_HEADER)[0]
                === hash_hmac('sha256', $request->body(), 'rahasia'));
    }
}
