<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** PRD 15AC §59 — pengujian keamanan dan siklus dokumen. */
class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    private function pdf(string $name = 'bukti.pdf'): UploadedFile
    {
        // %PDF- di awal berkas membuat deteksi MIME menghasilkan application/pdf.
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n%%EOF\n");
    }

    private function upload(array $overrides = []): array
    {
        return $this->post('/api/v1/documents', $overrides + [
            'document_name' => 'Bukti Transfer',
            'document_type' => 'RECEIPT',
            'file' => $this->pdf(),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');
    }

    private function scenario(): array
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);

        return compact('organization', 'admin');
    }

    // ---------------------------------------------------------------- upload

    public function test_upload_menyimpan_checksum_dan_nama_simpan_buatan_sistem(): void
    {
        $this->scenario();
        $document = $this->upload();

        $this->assertSame(64, strlen($document['checksum']));
        $this->assertSame('pdf', $document['extension']);
        $this->assertTrue($document['previewable']);

        // PRD 15X — lokasi penyimpanan tidak pernah ikut keluar lewat API.
        $this->assertArrayNotHasKey('storage_path', $document);
        $this->assertArrayNotHasKey('storage_disk', $document);
    }

    /** PRD 15AC §59 — malicious filename. */
    public function test_nama_berkas_berbahaya_disterilkan(): void
    {
        $this->scenario();

        $document = $this->post('/api/v1/documents', [
            'document_name' => 'Berkas Nakal',
            'document_type' => 'OTHER',
            'file' => $this->pdf('../../../etc/passwd";rm -rf.pdf'),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');

        $this->assertStringNotContainsString('..', $document['original_filename']);
        $this->assertStringNotContainsString('/', $document['original_filename']);
        $this->assertStringNotContainsString('"', $document['original_filename']);
    }

    /** PRD 15 §19 — isi berkas harus cocok dengan ekstensinya. */
    public function test_berkas_yang_isinya_tidak_sesuai_ekstensi_ditolak(): void
    {
        $this->scenario();

        // Berkas HTML yang menyamar sebagai PDF.
        //
        // UploadedFile::fake() menurunkan MIME dari nama berkas, sehingga tidak
        // dapat dipakai menguji ini. Dibutuhkan berkas sungguhan agar deteksi
        // membaca isinya.
        $path = tempnam(sys_get_temp_dir(), 'doc').'.pdf';
        file_put_contents($path, '<html><script>alert(1)</script></html>');

        $this->post('/api/v1/documents', [
            'document_name' => 'Penyamaran',
            'document_type' => 'OTHER',
            'file' => new UploadedFile($path, 'jahat.pdf', null, null, true),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        @unlink($path);
    }

    public function test_ekstensi_terlarang_dan_berkas_kelebihan_ukuran_ditolak(): void
    {
        $this->scenario();

        $this->post('/api/v1/documents', [
            'document_name' => 'Skrip', 'document_type' => 'OTHER',
            'file' => UploadedFile::fake()->createWithContent('jahat.php', '<?php echo 1;'),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->post('/api/v1/documents', [
            'document_name' => 'Kebesaran', 'document_type' => 'OTHER',
            'file' => UploadedFile::fake()->create('besar.pdf', 11 * 1024),
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    // -------------------------------------------------------- preview & unduh

    /** PRD 15S §36 — tipe di luar daftar hanya boleh diunduh. */
    public function test_preview_hanya_untuk_tipe_yang_diizinkan(): void
    {
        $this->scenario();

        $csv = $this->post('/api/v1/documents', [
            'document_name' => 'Rekap', 'document_type' => 'REPORT',
            'file' => UploadedFile::fake()->createWithContent('rekap.csv', "a,b\n1,2\n"),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');

        $this->assertFalse($csv['previewable']);
        $this->getJson("/api/v1/documents/{$csv['id']}/preview")->assertStatus(409);
    }

    public function test_preview_memakai_content_type_sistem_dan_menolak_sniffing(): void
    {
        $this->scenario();
        $document = $this->upload();

        $response = $this->get("/api/v1/documents/{$document['id']}/preview");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_unduhan_selalu_sebagai_lampiran(): void
    {
        $this->scenario();
        $document = $this->upload();

        $response = $this->get("/api/v1/documents/{$document['id']}/download");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        // PRD 15W — setiap pengaksesan tercatat.
        $this->assertDatabaseHas('document_access_logs', ['document_id' => $document['id'], 'action' => 'downloaded']);
    }

    /** PRD 15F §10 — dokumen privat milik orang lain tidak boleh dibuka. */
    public function test_dokumen_privat_milik_orang_lain_ditolak(): void
    {
        $s = $this->scenario();
        $document = $this->upload(['visibility' => 'PRIVATE']);

        $lain = $this->member($s['organization'], 'AMIL', ['email' => 'amil.doc@example.test']);
        $role = Role::whereNull('organization_id')->where('code', 'AMIL')->firstOrFail();
        $role->permissions()->syncWithoutDetaching(Permission::whereIn('name', ['document.view', 'document.download'])->pluck('id'));

        $this->loginAs($lain, $s['organization']);
        $this->getJson("/api/v1/documents/{$document['id']}/download")->assertStatus(403);
    }

    // -------------------------------------------------------------- siklus

    public function test_penggantian_berkas_menyimpan_versi_lama(): void
    {
        $this->scenario();
        $document = $this->upload();

        $this->post("/api/v1/documents/{$document['id']}/replace", [
            'file' => $this->pdf('revisi.pdf'),
            'change_note' => 'Perbaikan nominal',
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('document_versions', ['document_id' => $document['id'], 'version_number' => 1]);
    }

    public function test_dokumen_arsip_bersifat_hanya_baca(): void
    {
        $this->scenario();
        $document = $this->upload();

        $this->postJson("/api/v1/documents/{$document['id']}/archive")->assertOk();
        $this->patchJson("/api/v1/documents/{$document['id']}", ['document_name' => 'Ubah'])->assertStatus(409);
    }

    public function test_dokumen_terhapus_tidak_dapat_diakses_lalu_dapat_dipulihkan(): void
    {
        $this->scenario();
        $document = $this->upload();

        $this->deleteJson("/api/v1/documents/{$document['id']}")->assertOk();
        $this->getJson("/api/v1/documents/{$document['id']}")->assertNotFound();

        $this->postJson("/api/v1/documents/{$document['id']}/restore")->assertOk();
        $this->getJson("/api/v1/documents/{$document['id']}")->assertOk();
    }

    public function test_verifikasi_dan_penolakan_tercatat(): void
    {
        $this->scenario();
        $document = $this->upload();

        $this->postJson("/api/v1/documents/{$document['id']}/verify", ['note' => 'Sesuai bukti fisik'])
            ->assertOk()->assertJsonPath('data.status', 'VERIFIED');

        $this->assertDatabaseHas('document_verifications', ['document_id' => $document['id'], 'status' => 'VERIFIED']);

        // Penolakan wajib beralasan.
        $this->postJson("/api/v1/documents/{$document['id']}/reject", [])->assertStatus(409);
        $this->postJson("/api/v1/documents/{$document['id']}/reject", ['note' => 'Buram'])
            ->assertOk()->assertJsonPath('data.status', 'REJECTED');
    }

    // ------------------------------------------------------------- keamanan

    public function test_dokumen_organisasi_lain_tidak_dapat_diakses(): void
    {
        $this->scenario();
        $document = $this->upload();

        $organizationB = $this->organization();
        $adminB = $this->member($organizationB, 'ADMIN', ['email' => 'lain.doc@example.test']);

        $this->loginAs($adminB, $organizationB);
        $this->getJson("/api/v1/documents/{$document['id']}")->assertNotFound();
        $this->getJson("/api/v1/documents/{$document['id']}/download")->assertNotFound();
        $this->getJson('/api/v1/documents')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_permission_kurang_ditolak(): void
    {
        $s = $this->scenario();
        $document = $this->upload();

        $viewer = $this->member($s['organization'], 'VIEWER', ['email' => 'viewer.doc@example.test']);
        $this->loginAs($viewer, $s['organization']);

        $this->getJson('/api/v1/documents')->assertForbidden();
        $this->postJson("/api/v1/documents/{$document['id']}/verify", [])->assertForbidden();
    }
}
