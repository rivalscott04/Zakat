<?php

namespace Tests\Feature;

use App\Enums\MembershipStatus;
use App\Enums\MemberType;
use App\Enums\ReportExportFormat;
use App\Enums\ReportRunStatus;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Permission;
use App\Models\Report;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\Role;
use App\Models\User;
use App\Reports\ReportRegistry;
use App\Services\ReportRunService;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** PRD 19 — katalog, parameter, snapshot, ekspor, jadwal, dan kontrol akses laporan. */
class ReportingTest extends TestCase
{
    use RefreshDatabase;

    private function systemReport(string $code): Report
    {
        return Report::query()->whereNull('organization_id')->where('report_code', $code)->firstOrFail();
    }

    private function fund(Organization $organization, string $balance = '1000000.00'): Fund
    {
        $fund = new Fund;
        $fund->fill(['fund_code' => 'ZKT'.random_int(100, 999), 'name' => 'Dana Zakat', 'fund_type' => 'zakat', 'category' => 'zakat', 'restriction_type' => 'restricted']);
        $fund->organization_id = $organization->getKey();
        $fund->status = 'active';
        $fund->currency = 'IDR';
        $fund->opening_balance = $balance;
        $fund->current_balance = $balance;
        $fund->available_balance = $balance;
        $fund->save();

        return $fund;
    }

    // ---------------------------------------------------------------- katalog

    public function test_katalog_laporan_bawaan_tersedia_untuk_semua_organisasi(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $response = $this->getJson('/api/v1/reports?per_page=100')->assertOk();
        $codes = array_column($response->json('data'), 'report_code');

        foreach (ReportRegistry::codes() as $code) {
            $this->assertContains($code, $codes);
        }
    }

    public function test_laporan_bawaan_tidak_dapat_diubah(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $report = $this->systemReport('FUNDPOSITION');

        $this->patchJson("/api/v1/reports/{$report->getKey()}", ['name' => 'Diubah'])->assertForbidden();
    }

    /** PRD 19W §6 — laporan hanya membaca data organisasi aktif. */
    public function test_laporan_hanya_membaca_data_organisasi_aktif(): void
    {
        $first = $this->organization();
        $second = $this->organization();

        $this->fund($first, '5000000.00');
        $this->fund($second, '9000000.00');

        $this->loginAs($this->member($first), $first);

        $run = $this->postJson('/api/v1/reports/'.$this->systemReport('FUNDPOSITION')->getKey().'/run')->assertCreated();
        $rows = $run->json('data.snapshot.rows');

        $this->assertCount(1, $rows);
        $this->assertSame('5000000.00', $rows[0]['current_balance']);
    }

    // -------------------------------------------------------------- parameter

    /** PRD 19W §10 — parameter wajib tidak boleh kosong. */
    public function test_parameter_wajib_divalidasi(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $report = $this->systemReport('ZAKATCOLLECTION');

        $this->postJson("/api/v1/reports/{$report->getKey()}/run", ['parameters' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_from', 'date_to']);
    }

    /** PRD 19W §11 — rentang tanggal terbalik tidak diproses. */
    public function test_rentang_tanggal_terbalik_ditolak(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $report = $this->systemReport('ZAKATCOLLECTION');

        $run = $this->postJson("/api/v1/reports/{$report->getKey()}/run", [
            'parameters' => ['date_from' => '2026-03-01', 'date_to' => '2026-01-01'],
        ])->assertCreated();

        $this->assertSame(ReportRunStatus::Failed->value, $run->json('data.status'));
        $this->assertStringContainsString('melewati tanggal akhir', $run->json('data.error_message'));
    }

    // --------------------------------------------------------------- snapshot

    /** PRD 19B §4 — snapshot tidak ikut berubah ketika data sumbernya berubah. */
    public function test_snapshot_tidak_berubah_setelah_data_sumber_berubah(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $fund = $this->fund($organization, '1000000.00');
        $report = $this->systemReport('FUNDPOSITION');

        $runId = $this->postJson("/api/v1/reports/{$report->getKey()}/run")->assertCreated()->json('data.id');

        $fund->current_balance = '7777777.00';
        $fund->save();

        $this->assertSame(
            '1000000.00',
            $this->getJson("/api/v1/report-runs/{$runId}")->assertOk()->json('data.snapshot.rows.0.current_balance')
        );
    }

    public function test_run_mendapat_nomor_dan_tercatat_di_riwayat(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $response = $this->postJson('/api/v1/reports/'.$this->systemReport('FUNDPOSITION')->getKey().'/run')->assertCreated();

        $this->assertMatchesRegularExpression('/^RPR\d{4}\d{6}$/', $response->json('data.run_number'));
        $this->assertSame(ReportRunStatus::Completed->value, $response->json('data.status'));

        $this->getJson('/api/v1/report-runs')->assertOk()->assertJsonPath('data.0.run_number', $response->json('data.run_number'));
    }

    /** PRD 19V §58 dan PRD 19W §13. */
    public function test_run_gagal_dapat_diulang(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);
        OrganizationContext::set($organization);

        $report = $this->systemReport('ZAKATCOLLECTION');
        $run = app(ReportRunService::class)->run($report, ['date_from' => '2026-03-01', 'date_to' => '2026-01-01']);

        $this->assertSame(ReportRunStatus::Failed, $run->status);

        $repaired = ReportRun::query()->findOrFail($run->getKey());
        $repaired->parameters = ['date_from' => '2026-01-01', 'date_to' => '2026-03-01'];
        $repaired->save();

        $this->postJson("/api/v1/report-runs/{$run->getKey()}/retry")
            ->assertOk()
            ->assertJsonPath('data.status', ReportRunStatus::Completed->value);
    }

    // ----------------------------------------------------------------- ekspor

    public function test_ekspor_tersedia_dalam_csv_xlsx_dan_pdf(): void
    {
        Storage::fake('private');

        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);
        $this->fund($organization);

        $runId = $this->postJson('/api/v1/reports/'.$this->systemReport('FUNDPOSITION')->getKey().'/run')
            ->assertCreated()->json('data.id');

        foreach (ReportExportFormat::cases() as $format) {
            $export = $this->postJson("/api/v1/report-runs/{$runId}/export", ['format' => $format->value])
                ->assertCreated()
                ->assertJsonPath('data.format', $format->value);

            $this->assertGreaterThan(0, $export->json('data.file_size'));

            $this->get("/api/v1/report-exports/{$export->json('data.id')}/download")
                ->assertOk()
                ->assertDownload();
        }
    }

    /** PRD 19W §17 — unduhan tercatat pada audit trail. */
    public function test_unduhan_tercatat_pada_audit(): void
    {
        Storage::fake('private');

        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);
        $this->fund($organization);

        $runId = $this->postJson('/api/v1/reports/'.$this->systemReport('FUNDPOSITION')->getKey().'/run')
            ->assertCreated()->json('data.id');
        $exportId = $this->postJson("/api/v1/report-runs/{$runId}/export", ['format' => 'CSV'])
            ->assertCreated()->json('data.id');

        $this->get("/api/v1/report-exports/{$exportId}/download")->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'report_downloaded']);
    }

    public function test_tautan_ekspor_kedaluwarsa_ditolak(): void
    {
        Storage::fake('private');

        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);
        $this->fund($organization);

        $runId = $this->postJson('/api/v1/reports/'.$this->systemReport('FUNDPOSITION')->getKey().'/run')
            ->assertCreated()->json('data.id');
        $exportId = $this->postJson("/api/v1/report-runs/{$runId}/export", ['format' => 'CSV'])
            ->assertCreated()->json('data.id');

        $export = ReportExport::query()->findOrFail($exportId);
        $export->expires_at = now()->subDay();
        $export->save();

        $this->get("/api/v1/report-exports/{$exportId}/download")->assertStatus(409);
    }

    public function test_run_belum_selesai_tidak_dapat_diekspor(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);
        OrganizationContext::set($organization);

        $report = $this->systemReport('ZAKATCOLLECTION');
        $run = app(ReportRunService::class)->run($report, ['date_from' => '2026-03-01', 'date_to' => '2026-01-01']);

        $this->postJson("/api/v1/report-runs/{$run->getKey()}/export", ['format' => 'CSV'])->assertStatus(409);
    }

    // ---------------------------------------------------------- kontrol akses

    /** PRD 19Z §27 — laporan rahasia butuh izin eksplisit. */
    public function test_laporan_rahasia_butuh_izin_tambahan(): void
    {
        $organization = $this->organization();
        $report = $this->systemReport('FINANCIALPOSITION');

        // Peran dengan izin membaca katalog dan laporan keuangan, tetapi tanpa
        // izin laporan rahasia.
        $this->loginAs(
            $this->userWithPermissions($organization, ['report.view', 'report.financial.view']),
            $organization,
        );
        $this->getJson("/api/v1/reports/{$report->getKey()}")->assertForbidden();

        $this->loginAs($this->member($organization), $organization);
        $this->getJson("/api/v1/reports/{$report->getKey()}")->assertOk();
    }

    /** @param array<int, string> $permissions */
    private function userWithPermissions(Organization $organization, array $permissions): User
    {
        $role = new Role;
        $role->fill(['name' => 'Peran uji', 'code' => 'UJI'.random_int(100, 999), 'is_active' => true]);
        $role->organization_id = $organization->getKey();
        $role->is_system = false;
        $role->saveQuietly();
        $role->permissions()->sync(Permission::query()->whereIn('name', $permissions)->pluck('id')->all());

        $user = User::factory()->create(['organization_id' => $organization->getKey()]);

        $member = new OrganizationMember;
        $member->fill(['member_type' => MemberType::Employee->value]);
        $member->organization_id = $organization->getKey();
        $member->user_id = $user->getKey();
        $member->status = MembershipStatus::Active;
        $member->joined_at = now();
        $member->save();

        $user->roles()->attach($role->getKey(), ['organization_id' => $organization->getKey()]);

        return $user->fresh();
    }

    // -------------------------------------------------------------- favorit

    public function test_favorit_dapat_ditambah_dan_dihapus(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $report = $this->systemReport('FUNDPOSITION');

        $this->postJson("/api/v1/reports/{$report->getKey()}/favorite")->assertNoContent();
        $this->getJson('/api/v1/reports/favorites')->assertOk()->assertJsonPath('data.0.report_code', 'FUNDPOSITION');

        $this->deleteJson("/api/v1/reports/{$report->getKey()}/favorite")->assertNoContent();
        $this->getJson('/api/v1/reports/favorites')->assertOk()->assertJsonCount(0, 'data');
    }

    // --------------------------------------------------------------- jadwal

    /** PRD 19W §19 dan §20 — jadwal dijalankan penjadwal dan hasilnya dikirim lewat notification. */
    public function test_jadwal_menghasilkan_run_dan_notifikasi(): void
    {
        Storage::fake('private');

        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);
        $this->fund($organization);

        $schedule = $this->postJson('/api/v1/report-schedules', [
            'report_id' => $this->systemReport('FUNDPOSITION')->getKey(),
            'name' => 'Posisi dana bulanan',
            'frequency' => 'MONTHLY',
            'output_format' => 'CSV',
            'recipient_configuration' => ['user_ids' => [$admin->getKey()], 'channels' => ['in_app']],
        ])->assertCreated();

        $this->postJson('/api/v1/report-schedules/'.$schedule->json('data.id').'/run-now')->assertOk();

        $this->assertSame(1, ReportRun::query()->count());
        $this->assertDatabaseHas('notifications', ['recipient_id' => $admin->getKey(), 'event_name' => 'report_schedule_executed']);
    }

    public function test_scheduler_menjalankan_jadwal_yang_jatuh_tempo(): void
    {
        Storage::fake('private');

        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);
        $this->fund($organization);

        $this->postJson('/api/v1/report-schedules', [
            'report_id' => $this->systemReport('FUNDPOSITION')->getKey(),
            'name' => 'Harian',
            'frequency' => 'DAILY',
        ])->assertCreated();

        $this->travel(2)->days();
        $this->artisan('zakat:run-due-report-schedules')->assertSuccessful();

        $this->assertSame(1, ReportRun::query()->withoutGlobalScopes()->count());
    }

    // -------------------------------------------------------------- template

    public function test_template_laporan_dapat_dibuat_dan_diaktifkan(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $template = $this->postJson('/api/v1/report-templates', [
            'template_code' => 'monthlycollection',
            'name' => 'Rekap bulanan',
            'report_id' => $this->systemReport('ZAKATCOLLECTION')->getKey(),
            'configuration' => ['columns' => ['zakat_code', 'paid_amount']],
        ])->assertCreated()->assertJsonPath('data.template_code', 'MONTHLYCOLLECTION');

        $this->postJson('/api/v1/report-templates/'.$template->json('data.id').'/activate')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    // -------------------------------------------------------------- dashboard

    public function test_dashboard_menampilkan_riwayat_dan_favorit(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);
        $this->fund($organization);

        $report = $this->systemReport('FUNDPOSITION');
        $this->postJson("/api/v1/reports/{$report->getKey()}/run")->assertCreated();
        $this->postJson("/api/v1/reports/{$report->getKey()}/favorite")->assertNoContent();

        $dashboard = $this->getJson('/api/v1/reports/dashboard')->assertOk();

        $this->assertNotEmpty($dashboard->json('data.recent_runs'));
        $this->assertSame('FUNDPOSITION', $dashboard->json('data.favorites.0.report_code'));
    }

    /** Laporan milik organisasi lain tidak boleh terlihat. */
    public function test_laporan_kustom_organisasi_lain_tidak_terlihat(): void
    {
        $first = $this->organization();
        $second = $this->organization();

        $this->loginAs($this->member($second), $second);
        $custom = $this->postJson('/api/v1/reports', [
            'report_code' => 'KHUSUSKEDUA',
            'name' => 'Laporan khusus',
            'category' => 'CUSTOM',
        ])->assertCreated()->json('data.id');

        $this->loginAs($this->member($first), $first);
        $this->getJson("/api/v1/reports/{$custom}")->assertNotFound();
    }

    public function test_user_tanpa_izin_tidak_dapat_menjalankan_laporan(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization, 'VIEWER'), $organization);

        $this->postJson('/api/v1/reports/'.$this->systemReport('FUNDPOSITION')->getKey().'/run')->assertForbidden();
    }
}
