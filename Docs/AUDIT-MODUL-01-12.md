# AUDIT TEMUAN — MODUL 01 SAMPAI 12

Tanggal audit: 2026-08-20
Auditor: sesi AI development
Basis: `.CLAUDE.md` + PRD `/Docs`
Cakupan: modul 00 (Core), 01 (Auth), 02 (Organization), 06 (Collection), 07 (Fund),
08 (Accounting), 09 (Mustahik), 11 (Program), 12 (Distribution)

---

## CARA MEMBACA DOKUMEN INI

Setiap temuan punya ID tetap (`F-01`, `F-02`, ...). ID tidak pernah dipakai ulang.

Kolom **Bukti**:

- `TEREPRODUKSI` — sudah dijalankan dan hasilnya dicatat di dokumen ini.
- `PEMBACAAN KODE` — ditemukan dari pembacaan kode, belum dieksekusi.

Kolom **Status**:

- `TERBUKA` — belum diperbaiki.
- `SELESAI` — sudah diperbaiki, ada test yang menjaganya.
- `DITUNDA` — diputuskan tidak dikerjakan sekarang, alasannya dicatat.

Saat memperbaiki, ubah Status dan tambahkan baris `Perbaikan:` berisi file yang
disentuh dan nama test yang menjaga agar tidak kambuh.

---

## RINGKASAN

| ID | Modul | Tingkat | Ringkas | Bukti | Status |
|----|-------|---------|---------|-------|--------|
| F-01 | 07 Fund | KRITIS | Inflow dari collection tidak idempoten, saldo bisa berlipat | TEREPRODUKSI | SELESAI |
| F-02 | 08 Accounting | KRITIS | Jurnal draft bisa langsung diposting, approval terlewati | TEREPRODUKSI | SELESAI |
| F-03 | 08 Accounting | KRITIS | Period locked masih menerima jurnal dan posting | TEREPRODUKSI | SELESAI |
| F-04 | 07 Fund | KRITIS | Tidak ada row lock pada operasi saldo, rawan double spend | PEMBACAAN KODE | SELESAI |
| F-05 | 07 Fund | TINGGI | Saldo minus dipaksa jadi nol, over-distribution tersembunyi | PEMBACAAN KODE | SELESAI |
| F-06 | 06 Collection | TINGGI | Perhitungan uang memakai float | PEMBACAAN KODE | SELESAI |
| F-07 | 06 Collection | TINGGI | Presisi kolom uang 20,8 sedangkan modul lain 20,2 | TEREPRODUKSI | SELESAI |
| F-08 | 09 Mustahik | TINGGI | NIK dapat dienumerasi lintas organisasi | TEREPRODUKSI | SELESAI |
| F-09 | 06 Collection | TINGGI | `GET /collections/summary` selalu 500 | TEREPRODUKSI | SELESAI |
| F-10 | 08 Accounting | SEDANG | `reverse()` tidak atomik, nilai bisa terhitung dua kali | PEMBACAAN KODE | SELESAI |
| F-11 | 08 Accounting | SEDANG | Duplikat accounting event menghasilkan 500, bukan 409 | PEMBACAAN KODE | SELESAI |
| F-12 | 08 Accounting | SEDANG | Ledger dan trial balance tanpa paginasi | PEMBACAAN KODE | SELESAI |
| F-13 | 08 Accounting | SEDANG | Cek balance debit-kredit menjumlah sebagai float | PEMBACAAN KODE | SELESAI |
| F-14 | 06 Collection | SEDANG | Mass update dijalankan di dalam endpoint GET | PEMBACAAN KODE | SELESAI |
| F-15 | Lintas | SEDANG | Rate limiting hanya pada grup `/auth` | PEMBACAAN KODE | SELESAI |
| F-16 | 06 Collection | RENDAH | Cabang mati pada penentuan status pembayaran | PEMBACAAN KODE | SELESAI |
| F-17 | 08 Accounting | KRITIS | Maker-checker jurnal tidak pernah aktif, `created_by` tidak diisi | TEREPRODUKSI | SELESAI |
| F-18 | 08 Accounting | KRITIS | Reversal menghilangkan jurnal asal dari ledger, efeknya terhitung dua kali | TEREPRODUKSI | SELESAI |
| F-19 | Frontend | KRITIS | `noDiscovery` membuat dependensi CJS gagal dimuat, layar putih | TEREPRODUKSI | SELESAI |
| F-20 | Frontend | SEDANG | Path font ikon salah, seluruh ikon tidak tampil | TEREPRODUKSI | SELESAI |
| F-21 | Build | TINGGI | `bun run build` tidak mengompilasi SCSS, produksi tanpa style dan ikon | TEREPRODUKSI | SELESAI |
| F-22 | Frontend | TINGGI | Enam dari delapan role bawaan mendapat layar kosong setelah login | TEREPRODUKSI | SELESAI |
| F-23 | Frontend | SEDANG | Menu navigasi tidak menyaring permission, semua link tampil ke semua role | TEREPRODUKSI | SELESAI |

---

# KRITIS

## F-01 — Inflow dari collection tidak idempoten

**Modul** 07 Fund Management
**File** `backend/app/Services/FundService.php:61` (`inflowFromCollection`), `backend/app/Services/FundService.php:196` (`movement`)
**Melanggar** CLAUDE.md §29 (idempotency), Core PRD §33

### Gejala

Memanggil `POST /api/v1/funds/inflow-from-collection` berulang untuk satu
collection yang sama membuat fund movement baru setiap kali. Satu double-click
atau retry jaringan sudah cukup memicunya.

### Bukti

Collection senilai Rp1.000.000, endpoint dipanggil tiga kali:

```
status 3 panggilan                 : [201, 201, 201]
saldo fund                         : "3000000.00"
fund_movements untuk collection ini : 3
```

### Akar masalah

Tidak ada unique constraint pada `fund_movements (organization_id, source_type, source_id)`
dan tidak ada idempotency key di mana pun pada codebase:

```
grep -rn "idempotency" app/ database/   ->  tidak ada hasil
```

### Perbaikan yang disarankan

1. Migration: partial unique index pada `fund_movements (organization_id, source_type, source_id)`
   untuk `source_type` yang memang harus sekali pakai (`collection`, `distribution`, `fund_transfer`).
2. `inflowFromCollection()` mengecek movement yang sudah ada lebih dulu dan
   mengembalikan movement itu, bukan membuat baris baru.
3. Ganti 500 dari pelanggaran unique menjadi `ZakatException::duplicate`.

**Perbaikan**
- `backend/database/migrations/0001_01_01_000027_harden_fund_integrity.php` — partial
  unique index `fund_movements (organization_id, source_type, source_id)` khusus
  `source_type = 'collection'`. Sengaja tidak digeneralisasi: `fund_transfer`
  memang menghasilkan dua movement dengan source_id sama, dan `distribution`
  boleh berkali-kali karena realisasi sebagian.
- `backend/app/Services/FundService.php` — `inflowFromCollection()` mengembalikan
  movement yang sudah ada, bukan membuat baris baru.

Dijaga oleh `FundTest::test_inflow_dari_collection_bersifat_idempoten`.

---

## F-02 — Jurnal draft bisa langsung diposting

**Modul** 08 Accounting & Ledger
**File** `backend/app/Services/AccountingService.php:127`
**Melanggar** CLAUDE.md §33 (state transition), PRD 08 alur approval

### Gejala

Jurnal dengan status `draft` dapat langsung diposting tanpa `submit` dan tanpa
`approve`. Maker-checker pada `approve()` menjadi tidak berarti karena bisa
dilewati sepenuhnya.

### Bukti

Buat jurnal seimbang lalu langsung panggil `POST /journals/{id}/post`:

```
post langsung dari draft : 200
status akhir             : "posted"
```

### Akar masalah

```php
if (! in_array($journal->status, ['approved', 'draft'], true)) {
```

`'draft'` seharusnya tidak ada dalam daftar itu.

### Perbaikan yang disarankan

Hanya izinkan posting dari `approved`. Kalau memang ada kebutuhan sah untuk
posting langsung, itu keputusan produk yang harus masuk PRD 08 dulu, lengkap
dengan permission tersendiri.

---

## F-03 — Period locked tidak mengunci apa pun

**Modul** 08 Accounting & Ledger
**File** `backend/app/Services/AccountingService.php:75` (`createJournal`), `backend/app/Services/AccountingService.php:129` (`post`)
**Melanggar** PRD 08 period lock

### Gejala

Setelah `POST /periods/{id}/lock` berhasil, jurnal baru tetap dapat dibuat dan
diposting ke period tersebut. Status `locked` tidak punya efek apa pun.

### Bukti

```
lock period                     : 200
buat jurnal di period locked    : 201
post di period locked           : 200, status "posted"
```

### Akar masalah

Kedua pengecekan hanya menguji `closed`:

```php
if ($period->status === 'closed') { ... }        // createJournal
if ($journal->period->status === 'closed') { ... } // post
```

### Perbaikan yang disarankan

Perlakukan `locked` sama dengan `closed` untuk penulisan baru. Bedanya hanya
`closed` bersifat final sedangkan `locked` masih bisa dibuka kembali.

**Perbaikan** `backend/app/Services/AccountingService.php` — `post()` kini hanya
menerima jurnal `approved`. Dijaga oleh
`AccountingTest::test_jurnal_draft_tidak_dapat_langsung_diposting`.

---

## F-04 — Tidak ada row lock pada operasi saldo

**Modul** 07 Fund Management
**File** `backend/app/Services/FundService.php:188` (`assertAvailable`), `:205` (`refresh`), `:82` (`allocation`), `:139` (`transfer`)
**Melanggar** CLAUDE.md §30 (concurrency), Core PRD §32

### Gejala

Dua request bersamaan yang menarik dana dari fund yang sama sama-sama lolos
pengecekan saldo, lalu keduanya menulis movement. Saldo bisa menjadi minus.

### Akar masalah

Di seluruh `app/` hanya ada satu pemakaian lock, dan itu di modul lain:

```
grep -rn "lockForUpdate" app/
app/Services/CollectionService.php:126
```

`assertAvailable()` melakukan baca (SUM movements) lalu `movement()` menulis,
tanpa lock di antaranya. Pada `allocation()` dan `transfer()` pengecekan saldo
bahkan berada di luar `DB::transaction`.

### Perbaikan yang disarankan

1. `assertAvailable()` mengunci baris fund lebih dulu (`Fund::lockForUpdate()->find()`),
   dan seluruh pemanggilnya wajib berada di dalam transaksi.
2. Pindahkan pengecekan saldo pada `allocation()` dan `transfer()` ke dalam transaksi.
3. Tambahkan check constraint database `current_balance >= 0` sebagai jaring terakhir.

**Perbaikan** `backend/app/Services/AccountingService.php` — `createJournal()` dan
`post()` kini memperlakukan `locked` sama dengan `closed`. Dijaga oleh
`AccountingTest::test_period_locked_menolak_jurnal_baru_dan_posting`.

---

## F-17 — Maker-checker jurnal tidak pernah aktif

**Modul** 08 Accounting & Ledger
**File** `backend/app/Services/AccountingService.php:79` (`createJournal`), `:116` (`approve`)
**Melanggar** CLAUDE.md §33, PRD 08 maker-checker

### Gejala

Pembuat jurnal dapat menyetujui jurnalnya sendiri. Pemisahan maker dan checker
pada modul akuntansi tidak berjalan sama sekali.

### Bukti

`AccountingTest::test_journal_double_entry_dan_posting_immutable` membuat,
menyetujui, dan memposting jurnal dengan **satu user yang sama**, dan lulus.

### Akar masalah

Penjaganya ada:

```php
if ($journal->created_by && $journal->created_by === auth()->id()) {
    throw ZakatException::forbidden('Maker tidak dapat menyetujui journal sendiri.');
}
```

tetapi `createJournal()` tidak pernah mengisi `created_by`, padahal kolomnya ada
pada `journal_entries`. Karena selalu `null`, kondisi pertama selalu gagal dan
penjaga itu tidak pernah berjalan.

### Perbaikan yang disarankan

Isi `created_by` saat pembuatan jurnal, dan ubah test agar memakai dua user
berbeda sehingga penjaganya benar-benar teruji.

**Perbaikan** `backend/app/Services/AccountingService.php` — `createJournal()` mengisi
`created_by`. Dijaga oleh `AccountingTest::test_maker_tidak_dapat_menyetujui_jurnal_sendiri`.

**Perbaikan**
- `backend/app/Services/FundService.php` — `assertAvailable()` mengunci baris fund
  (`SELECT ... FOR UPDATE`) sebelum membaca saldo, dan menolak dipanggil di luar
  transaksi lewat pemeriksaan `DB::transactionLevel()` supaya tidak diam-diam regresi.
- Pengecekan saldo pada `allocation()`, `approveAllocation()`, `transfer()`,
  `approveTransfer()`, dan `adjust()` dipindahkan ke dalam transaksi.
- `backend/database/migrations/0001_01_01_000027_harden_fund_integrity.php` — check
  constraint `funds.current_balance >= 0` sebagai jaring terakhir.

Dijaga oleh `FundTest::test_pengecekan_saldo_mengunci_baris_fund`.

---

# TINGGI

## F-05 — Saldo minus disembunyikan, bukan dicegah

**Modul** 07 Fund Management
**File** `backend/app/Services/FundService.php:205` (`refresh`)

### Gejala

```php
if (bccomp($available, '0', 2) < 0) { $available = '0.00'; }
```

Kalau over-distribution terjadi, `available_balance` dipaksa nol dan gejalanya
hilang dari laporan maupun API. Kesalahan saldo jadi tidak terlihat.

### Perbaikan yang disarankan

Simpan nilai sebenarnya walaupun negatif, lalu jadikan kondisi negatif sebagai
temuan rekonsiliasi yang wajib muncul. Pencegahan dilakukan lewat F-04, bukan
lewat pembulatan ke nol.

**Perbaikan** `backend/app/Services/FundService.php` — `refresh()` menyimpan
`available_balance` apa adanya, termasuk bila negatif. Dijaga oleh
`FundTest::test_available_balance_negatif_tidak_disembunyikan`.

---

## F-06 — Perhitungan uang collection memakai float

**Modul** 06 Collection
**File** `backend/app/Services/CollectionService.php:123` sampai `:136` (`settle`)
**Melanggar** Core PRD §12 (dilarang FLOAT/DOUBLE), CLAUDE.md §32

### Gejala

Seluruh aritmetika pada `settle()` memakai cast `(float)`:

```php
$already   = (float) PaymentAllocation::...->sum('allocated_amount');
$available = (float) $payment->amount - $already;
$remaining = max(0, (float) $collection->expected_amount - (float) $collection->paid_amount);
$paid      = (float) $collection->paid_amount + $allocation;
```

Ini satu-satunya jalur uang di sistem yang tidak memakai bcmath. Modul fund,
accounting, dan distribution semuanya sudah memakai `bcadd`/`bcsub`/`bccomp`.

### Perbaikan yang disarankan

Ubah seluruh perhitungan pada `settle()` menjadi bcmath dengan skala 2, sejalan
dengan F-07.

**Perbaikan** `backend/app/Services/CollectionService.php` — `settle()` ditulis ulang
memakai `bcadd`, `bcsub`, dan `bccomp` skala 2. Dijaga oleh
`CollectionTest::test_pembayaran_lebih_terdeteksi_dan_nominal_berskala_dua_desimal`.

---

## F-07 — Presisi kolom uang tidak konsisten antar modul

**Modul** 06 Collection
**File** `backend/database/migrations/0001_01_01_000019_create_collection_tables.php`
**Melanggar** Core PRD §12 (`NUMERIC(20,2)`)

### Gejala

Kolom uang pada collection memakai `NUMERIC(20,8)`, sedangkan fund, accounting,
mustahik, program, dan distribution memakai `NUMERIC(20,2)`. Terlihat pada
respons API yang sudah ada: `remaining_amount` keluar sebagai `"7000000.00000000"`.

Saat dana mengalir dari collection ke fund, enam desimal terakhir dibulatkan
tanpa jejak.

### Catatan

Kolom pada modul zakat (`quantity`, `rate`, `minimum_quantity`) memakai 8 desimal
dan itu wajar karena bukan uang melainkan kuantitas dan tarif. Yang bermasalah
khusus kolom uang: `expected_amount`, `paid_amount`, `remaining_amount`,
`allocated_amount`.

### Perbaikan yang disarankan

Migration yang mengubah kolom uang collection menjadi `NUMERIC(20,2)`.
Butuh keputusan pengguna soal data yang sudah ada bila sudah ada di produksi.

**Perbaikan**
- `backend/database/migrations/0001_01_01_000029_align_collection_money_precision.php`
  — kolom uang `collections`, `collection_items`, `collection_payments`, dan
  `payment_allocations` diubah ke `NUMERIC(20,2)`. `collection_items.quantity`
  dibiarkan 20,8 karena kuantitas, bukan uang.
- Cast pada keempat model diselaraskan ke `decimal:2`.

Peringatan: nilai lama dengan lebih dari dua desimal dibulatkan oleh PostgreSQL
dan tidak dapat dipulihkan lewat `down()`.

---

## F-08 — NIK dapat dienumerasi lintas organisasi

**Modul** 09 Mustahik
**File** `backend/app/Services/MustahikService.php:63` (`identity`), `backend/database/migrations/0001_01_01_000022_create_mustahik_tables.php:49`
**Melanggar** Core PRD §23 (data isolation), Core PRD §27 (privacy)

### Gejala

Organisasi A mendaftarkan mustahik dengan NIK tertentu. Organisasi B mendaftarkan
orang yang sama, dan mendapat:

```
status : 409
body   : {"message":"IDENTITY_DUPLICATE","code":"CONFLICT", ...}
```

Dua akibatnya sekaligus:

1. Organisasi B tahu NIK itu terdaftar di organisasi lain — oracle keberadaan data.
2. Organisasi B tidak bisa mendaftarkan mustahiknya sendiri, padahal satu orang
   wajar menerima bantuan dari lebih dari satu lembaga.

### Akar masalah

Dua hal yang saling menguatkan:

```php
// unscoped, mencari ke seluruh organisasi
MustahikIdentity::where('identity_number_hash', $hash)->where('mustahik_id', '!=', $mustahik->id)->exists()
```

```php
$t->unique('identity_number_hash');   // unique global, tanpa organization_id
```

Tabel `mustahiks` sendiri sudah benar memakai `unique(['organization_id', 'identity_number_hash'])`.

### Perbaikan yang disarankan

1. Migration: ganti unique global menjadi unique per organisasi. Karena
   `mustahik_identities` tidak punya `organization_id`, unique-nya dibuat lewat
   kolom turunan atau index atas join. Cara paling sederhana: tambahkan
   `organization_id` pada `mustahik_identities` dan unique `(organization_id, identity_number_hash)`.
2. Batasi query pada `identity()` ke organisasi aktif.

**Perbaikan**
- `backend/database/migrations/0001_01_01_000028_scope_mustahik_identity_per_organization.php`
  — kolom `organization_id` ditambahkan pada `mustahik_identities`, unique global
  diganti `(organization_id, identity_number_hash)`.
- `backend/app/Models/MustahikIdentity.php` — memakai trait `BelongsToOrganization`.
- `backend/app/Services/MustahikService.php` — pengecekan duplikat dibatasi ke
  organisasi pemilik mustahik.

Dijaga oleh `MustahikTest::test_nik_tidak_dapat_dienumerasi_lintas_organisasi`.

---

## F-09 — `GET /collections/summary` selalu 500

**Modul** 06 Collection
**File** `backend/app/Services/CollectionService.php:154` (`expireDueCollections`)

### Gejala

Endpoint selalu gagal:

```
SQLSTATE[42703]: Undefined column: 7 ERROR: column "0" does not exist
LINE 1: ... and "due_date"::date < $6 and "remaining_amount" > "0" and ...
```

### Akar masalah

```php
->whereColumn('remaining_amount', '>', '0')
```

`whereColumn` membandingkan dua **kolom**, jadi `'0'` diperlakukan sebagai nama
kolom. Seharusnya `where('remaining_amount', '>', 0)`.

Tidak ada test yang menyentuh endpoint ini, jadi lolos sampai sekarang.

**Perbaikan** `backend/app/Services/CollectionService.php` — `whereColumn` diganti
`where('remaining_amount', '>', 0)`. Dijaga oleh
`CollectionTest::test_summary_collection_dapat_diakses`.

---

# SEDANG

## F-10 — `reverse()` tidak atomik

**Modul** 08 Accounting
**File** `backend/app/Services/AccountingService.php:137`

`createJournal()` punya transaksi sendiri, tetapi dua `forceFill` sesudahnya
berada di luar transaksi mana pun. Bila penyimpanan kedua gagal, jurnal reversal
sudah berstatus `posted` sementara jurnal asal belum ditandai `reversed`, sehingga
nilainya terhitung dua kali pada ledger dan trial balance.

**Perbaikan** Bungkus seluruh alur reversal dalam satu `DB::transaction`.

**Perbaikan** `backend/app/Services/AccountingService.php` — seluruh alur reversal
dibungkus satu `DB::transaction`. Dijaga oleh
`AccountingTest::test_reversal_menandai_jurnal_asal_dan_berimbang_terbalik`.

---

## F-11 — Duplikat accounting event menghasilkan 500

**Modul** 08 Accounting
**File** `backend/app/Services/AccountingService.php:150` (`event`)

`accounting_events` punya unique `(organization_id, event_type, source_type, source_id)`.
`event()` langsung `create()` tanpa pengecekan, jadi duplikat memunculkan
`UniqueConstraintViolationException` mentah dan menjadi 500.

`DistributionService::emitAccountingEvent()` sudah melakukan pre-check, tetapi
endpoint `POST /accounting/events` belum.

**Perbaikan** Kembalikan event yang sudah ada, atau lempar `ZakatException::duplicate`.

**Perbaikan** `backend/app/Services/AccountingService.php` — `event()` mengembalikan
event yang sudah ada. Dijaga oleh
`AccountingTest::test_accounting_event_duplikat_tidak_menghasilkan_error_server`.

---

## F-12 — Ledger dan trial balance tanpa paginasi

**Modul** 08 Accounting
**File** `backend/app/Services/AccountingService.php:155` (`ledger`), `:162` (`trialBalance`)
**Melanggar** CLAUDE.md §23

Keduanya memanggil `->get()` atas seluruh journal line berstatus posted lalu
memproses di memori. Akan tumbang seiring bertambahnya data, dan bisa dipakai
untuk menghabiskan memori server.

**Perbaikan** Agregasi di database (`selectRaw` dengan `group by`) untuk trial
balance, dan paginasi untuk ledger.

**Perbaikan** `backend/app/Services/AccountingService.php` — `ledger()` berpaginasi
dengan `LedgerLineResource`, `trialBalance()` menjumlah di database. Penyaringan
organisasi tetap lewat `whereHas('journal', ...)` supaya global scope pada
`JournalEntry` ikut terpakai; join langsung ke `journal_entries` akan melewatinya.
Dijaga oleh `AccountingTest::test_general_ledger_berpaginasi`.

---

## F-13 — Cek balance debit-kredit menjumlah sebagai float

**Modul** 08 Accounting
**File** `backend/app/Services/AccountingService.php:169` (`assertBalanced`), `:162` (`trialBalance`)
**Melanggar** Core PRD §12

```php
$debit = (string) $journal->lines->sum('debit_amount');
```

`Collection::sum()` menjumlahkan sebagai float. Pada `NUMERIC(20,2)` nilai di atas
sekitar 9×10¹⁵ kehilangan presisi, sehingga pengecekan keseimbangan jurnal
justru rawan pada nominal besar.

**Perbaikan** Jumlahkan dengan `bcadd` skala 2, atau lewat agregasi database.

**Perbaikan** `backend/app/Services/AccountingService.php` — `assertBalanced()`
menjumlah dengan `bcadd` skala 2.

---

## F-14 — Mass update dijalankan di dalam endpoint GET

**Modul** 06 Collection
**File** `backend/app/Services/CollectionService.php:25`, `:117`

`expireDueCollections()` menjalankan `UPDATE` massal dan dipanggil dari `list()`
dan `summary()`. Endpoint baca menimbulkan efek samping tulis, dan biayanya
bertambah seiring data.

**Perbaikan** Pindahkan ke scheduled command, sejalan dengan Core PRD §29 soal queue.

**Perbaikan**
- `backend/app/Console/Commands/ExpireDueCollections.php` — perintah baru
  `zakat:expire-due-collections`.
- `backend/routes/console.php` — dijadwalkan harian pukul 00:15.
- `backend/app/Services/CollectionService.php` — pemanggilan dari `list()` dan
  `summary()` dihapus.

---

## F-15 — Rate limiting hanya pada grup `/auth`

**Modul** Lintas modul
**File** `backend/routes/api.php:38`
**Melanggar** CLAUDE.md §36

Hanya grup `/auth` yang memakai `throttle:10,1`. CLAUDE.md §36 juga menyebut
payment, export, dan pencarian mahal.

**Perbaikan** Terapkan throttle pada endpoint transaksional dan pencarian.

**Perbaikan**
- `backend/app/Providers/AppServiceProvider.php` — limiter `api` (120 per menit)
  dan `financial` (30 per menit), dikunci per user agar beberapa amil dalam satu
  kantor tidak saling menghabiskan jatah lewat satu IP.
- `backend/routes/api.php` — `throttle:api` pada seluruh grup terautentikasi, dan
  `throttle:financial` pada `collections`, `funds`, `accounting`, `distributions`,
  serta `distribution-batches`.

---

# RENDAH

## F-16 — Cabang mati pada penentuan status pembayaran

**Modul** 06 Collection
**File** `backend/app/Services/CollectionService.php:134`

```php
$status = $paid > $expected ? Paid : ($paid >= $expected ? Paid : ...);
```

Dua cabang pertama menghasilkan nilai yang sama, jadi cabang pertama mati.
Akibat sampingannya, pembayaran lebih tidak dibedakan dari pembayaran pas pada
level status.

**Perbaikan** Ikut tertangani saat `settle()` ditulis ulang untuk F-06: kondisi
diganti `match (true)` dan kelebihan bayar tercermin pada `overpayment_status`.
Dijaga oleh
`CollectionTest::test_pembayaran_lebih_terdeteksi_dan_nominal_berskala_dua_desimal`.

---

## F-18 — Reversal menghilangkan jurnal asal dari ledger

**Modul** 08 Accounting & Ledger
**File** `backend/app/Services/AccountingService.php` (`ledger`, `trialBalance`, `reverse`)
**Melanggar** Core PRD §39 (ledger sebagai kebenaran finansial)

### Gejala

Setelah sebuah jurnal direversal, ledger dan trial balance menjadi salah. Efek
jurnal asal hilang dua kali: sekali karena statusnya berubah menjadi `reversed`
sehingga tidak lagi dihitung, sekali lagi karena jurnal reversal yang membalikkan
nilainya tetap dihitung.

### Bukti

Jurnal debit Kas 1.000.000 dan kredit Pendapatan 1.000.000, diposting lalu
direversal. Trial balance seharusnya kembali nol untuk kedua akun, tetapi akun
Kas menyisakan kredit 1.000.000 tanpa pasangan debit.

Temuan ini muncul saat menulis regression test untuk F-10, bukan dari pembacaan
kode awal.

### Akar masalah

`ledger()` dan `trialBalance()` menyaring `status = 'posted'`, sedangkan
`reverse()` mengubah jurnal asal menjadi `reversed`. Padahal dalam pembukuan
berpasangan, jurnal yang sudah diposting tidak pernah ditarik kembali; koreksinya
diwakili jurnal reversal tersendiri.

**Perbaikan** `backend/app/Services/AccountingService.php` — konstanta
`LEDGER_STATUSES = ['posted', 'reversed']` dipakai kedua query. Status `reversed`
tetap dipertahankan untuk keperluan penelusuran. Dijaga oleh
`AccountingTest::test_reversal_menandai_jurnal_asal_dan_berimbang_terbalik`.

---

# CATATAN MODUL 12

Temuan berikut ditemukan dan **sudah diperbaiki** pada sesi yang sama, dicatat di
sini hanya sebagai riwayat:

- `DistributionService::complete()` melakukan fund outflow sebelum melepas
  reservation, sehingga dana yang ditahan memblokir penyalurannya sendiri saat
  nominal menghabiskan saldo. Dijaga oleh
  `DistributionTest::test_distribution_dapat_menghabiskan_seluruh_saldo_fund`.
- `DistributionService::cancel()` menimpa kolom `description` dengan alasan
  pembatalan, dan tidak mencatat `cancelled_by` maupun `cancelled_at`.

# TEMUAN FRONTEND DAN BUILD

## F-19 — Layar putih karena dependensi CommonJS tidak di-prebundle

**File** `vite.config.mjs`

`optimizeDeps: { noDiscovery: true }` ditambahkan pada commit `be8a9b9`
("percepat startup dev vite"). Efek sampingnya, Vite berhenti mengubah paket
CommonJS menjadi ESM. `react-router` mengimpor `cookie` dan `set-cookie-parser`
dengan named import, keduanya CJS murni, sehingga seluruh aplikasi gagal dimuat:

```
Uncaught SyntaxError: The requested module '/node_modules/cookie/dist/index.js'
does not provide an export named 'parse' (at chunk-ZA36QIGN.mjs:248:10)
```

Menambahkan `cookie` ke `optimizeDeps.include` **tidak cukup**, karena
`cookie/dist/index.js` menandai dirinya `__esModule: true` sehingga esbuild hanya
menghasilkan `export default` tanpa named export.

**Perbaikan** `noDiscovery` dihapus, discovery bawaan Vite dikembalikan.
Pengukuran: cold start 6,8 detik sekali saat cache `node_modules/.vite` kosong,
warm start 443 ms. Alasannya ditulis sebagai komentar di `vite.config.mjs`.

---

## F-20 — Seluruh ikon tidak tampil

**File** `src/assets/scss/plugins/icons/_remixicon.scss`, `_boxicons.scss`,
`_line-awesome.scss`, `_materialdesignicons.scss`

Keempat file memakai `url('../../../fonts/...')`. Sass meratakan partial ke
entry `src/assets/scss/themes.scss`, dan URL relatifnya diselesaikan terhadap
entry, bukan terhadap partial. Akibatnya path menunjuk ke root proyek dan
browser menerima `index.html` dari SPA fallback:

```
Failed to decode downloaded font: /fonts/remixicon.woff2
OTS parsing error: invalid sfntVersion: 1008813135
```

`1008813135` heksadesimalnya `0x3C21444F`, yaitu teks `<!DO` — awal dari
`<!DOCTYPE html>`.

**Perbaikan** `../../../fonts/` diganti `../fonts/` pada keempat file. Verifikasi:
keempat keluarga font kini mengembalikan magic bytes `wOF2`, dan console bersih
dari warning font.

---

## F-21 — `bun run build` menghasilkan produksi tanpa style dan ikon

**File** `package.json` script `build`

Script `build` memakai `bun build index.html`, yang tidak mengompilasi SCSS.
`themes.scss` hanya disalin mentah sebagai aset dan masih berisi
`@import "../scss/icons.scss";`. CSS yang dihasilkan 17 KB dengan **nol**
`@font-face` dan nol referensi ikon.

Sebagai pembanding, `bun run build:vite` menghasilkan CSS 1,14 MB dengan 10
`@font-face` dan 14 file font ter-emit dengan nama ber-hash, selesai dalam 4,55
detik. Jadi alasan kecepatan tidak lagi berlaku.

**Perbaikan**
- `package.json` — script disederhanakan menjadi `dev: vite`, `build: vite build`,
  `preview: vite preview`. Script `build:vite` dihapus karena tidak lagi perlu,
  dan `NODE_OPTIONS=--max-old-space-size=8192` dibuang karena build selesai 5
  detik tanpa itu.
- `vite.config.ts` dihapus. File itu duplikat basi dari `vite.config.mjs` dan
  masih membawa `noDiscovery: true`, yaitu bug F-19 yang baru diperbaiki. Karena
  Vite memilih `.mjs` sebelum `.ts`, file itu tidak aktif tetapi menjadi jebakan
  bagi siapa pun yang menyuntingnya dan mengira sedang mengubah konfigurasi asli.
  Sekarang hanya ada satu file konfigurasi, dan flag `--config` tidak lagi perlu.
- `tsconfig.json` — `vite.config.ts` dikeluarkan dari `include`.

Verifikasi: build menghasilkan CSS 1,14 MB dengan 10 `@font-face` dan 14 file
font, dan hasilnya dibuka lewat `vite preview` di browser dengan styling utuh
serta ikon tampil.

---

## F-22 — Layar kosong setelah login untuk sebagian besar role

**File** `src/features/auth/guards.tsx`, `src/app/routes/allRoutes.tsx`,
`src/features/auth/pages/Login.tsx`

### Gejala

Login berhasil, lalu halaman menjadi kosong. Terjadi pada user yang tidak
memiliki permission `muzaki.view`, yaitu enam dari delapan role bawaan: AMIL,
VERIFIER, APPROVER, FINANCE, AUDITOR, dan VIEWER.

### Bukti

Login sebagai user ber-role VIEWER menghasilkan halaman putih di `/dashboard`,
tanpa pesan error apa pun di console.

### Akar masalah

Redirect berputar. Route `/dashboard` mensyaratkan `muzaki.view`, sedangkan
`RequirePermission` mengarahkan user yang kekurangan permission ke `/dashboard`
juga. User tanpa `muzaki.view` dilempar ke halaman yang menolaknya, berulang
tanpa henti, sehingga tidak ada yang pernah dirender.

**Perbaikan**
- `src/features/layout/menu.ts` (baru) — satu sumber kebenaran daftar menu,
  dengan `landingPath()` yang menentukan halaman pertama berdasarkan permission.
- `guards.tsx` — `RequirePermission` mengarah ke `landingPath()`, bukan
  `/dashboard`. Bila user tidak punya satu pun halaman yang boleh dibuka,
  ditampilkan halaman penjelasan, bukan redirect yang berputar.
- `Login.tsx` — tujuan setelah login mengikuti `landingPath()`.

Terverifikasi: VIEWER kini mendarat di `/organizations`, dan membuka
`/distributions` secara paksa lewat URL diarahkan kembali dengan bersih.

---

## F-23 — Menu navigasi tidak menyaring permission

**File** `src/features/layout/AppLayout.tsx`
**Melanggar** PRD 01 §27

Daftar menu ditulis hardcoded dalam satu baris JSX tanpa pemeriksaan permission
sama sekali, sehingga semua role melihat seluruh link. User yang mengkliknya
langsung ditolak `RequirePermission`.

PRD 01 §27 menyebut frontend memakai permission justru untuk menyembunyikan menu.

**Perbaikan** Menu disaring dengan `visibleMenu(can)` dari `menu.ts`. Link
"Batch Penyaluran" yang sebelumnya tidak pernah muncul juga ditambahkan.
Terverifikasi: Super Admin melihat 15 menu, VIEWER hanya 3 (Organisasi, Amil, User).

---

# KONFLIK PRD YANG SUDAH DISELESAIKAN

## PRD 02 bagian 27 versus bagian 28 — syarat membership bagi role platform

Bagian 27 menuntut membership aktif untuk semua akses dan menutupnya dengan
"Semua kondisi wajib dipenuhi", sedangkan bagian 28 menyatakan administrator
platform tetap dapat mengakses organisasi suspended untuk investigasi. Keduanya
tidak dapat berlaku bersamaan.

Implementasi awal mengikuti bagian 27 secara harfiah, sehingga SUPER_ADMIN
terpaksa didaftarkan sebagai anggota organisasi agar sistem berjalan. Akibatnya
super admin hanya melihat organisasi tempat ia terdaftar, dan menerima 403 saat
mencoba masuk ke organisasi lain. Kode melanggar PRD-nya sendiri.

Keputusan pemilik produk 2026-08-21: bagian 28 yang dimenangkan. SUPER_ADMIN
adalah role platform, tidak memerlukan membership. Role organisasi tetap tunduk
pada kelima syarat bagian 27.

PRD sudah disamakan di tujuh tempat: PRD 01 bagian 24 dan 29, serta PRD 02
bagian 13, 15, 26, 27, dan 36.

Perbaikan kode: `OrganizationService::availableFor()` dan `switchTo()`,
`ResolveOrganizationContext`, serta `BootstrapSeeder` yang tidak lagi
mendaftarkan super admin sebagai member dan mengosongkan `organization_id`.
Dijaga oleh `OrganizationTest::test_platform_admin_tidak_perlu_membership_untuk_masuk_organisasi`
dan `OrganizationTest::test_admin_organisasi_tetap_butuh_membership`.

---

# PERTANYAAN TERBUKA UNTUK PEMILIK PRODUK

Tiga hal ini butuh keputusan pemilik produk, bukan bug:

1. Accounting event untuk realisasi sebagian. PRD 12X §56 hanya menyebut event
   saat `Completed`, sehingga distribution `partially_completed` sudah
   mengeluarkan uang tetapi belum berjurnal.
2. Kode permission untuk melihat nomor rekening penuh. PRD 12D §9 menyebut
   permission khusus, tetapi PRD 12AB §65 tidak mendefinisikan kodenya. Sementara
   ini nomor penuh tidak pernah keluar lewat API.
3. Presisi kolom uang collection sudah diubah ke `NUMERIC(20,2)` pada F-07.
   Yang masih perlu dipastikan: apakah sudah ada data produksi yang nilainya
   memiliki lebih dari dua desimal, karena migrasi membulatkannya tanpa jalan
   kembali.
