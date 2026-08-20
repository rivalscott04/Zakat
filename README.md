# ZETRA

ZETRA adalah singkatan dari **Zakat Ecosystem for Transparency, Reporting & Accountability**.

Open source zakat management platform yang transparan, akuntabel, modular, dan siap diaudit.

Dari pengumpulan, penyaluran, sampai laporan dan transparansi. Cocok buat lembaga amil, organisasi zakat, atau komunitas yang mau sistemnya rapi, bisa diaudit, dan tetap sesuai aturan syariah.

## Kenapa dibuat?

Pengelolaan zakat sering berantakan di Excel, chat grup, atau aplikasi yang susah dilacak. ZETRA dibuat biar semuanya lebih jelas:

- Publik bisa lihat data yang memang boleh dipublikasikan
- Setiap aksi bisa dilacak: siapa melakukan apa, kapan, dan apa yang berubah
- Data keuangan yang sudah final nggak bisa diutak-atik sembarangan
- Aturan zakat diatur lewat konfigurasi, bukan ditulis kaku di kode
- Tiap modul bisa dikembangin terpisah
- Logika bisnis ada di backend. Frontend cuma tampilan dan input

Ini proyek **open source**. Bebas dipakai, dipelajari, difork, dan dikembangkan bareng.

## Amal jariyah

Kontribusi di sini bisa jadi amal jariyah.

Setiap baris kode, perbaikan bug, dokumentasi, atau ide yang bantu lembaga zakat bekerja lebih baik bisa terus bermanfaat selama sistem ini dipakai orang. Kalau ada yang lebih mudah bayar zakat, lebih akurat penyalurannya, atau lebih transparan laporannya karena kontribusimu, pahalanya insyaAllah ngalir terus.

Nggak harus jago banget. Yang penting niatnya bantu:

- Nulis atau perbaiki kode
- Perjelas dokumentasi
- Laporkan bug
- Usulkan perbaikan
- Bantu orang lain pakai dan kembangkan ZETRA

Sedikit tapi konsisten, itu juga amal jariyah.

Kalau mau berdonasi juga boleh. Bisa lewat rekening atau e-wallet. Atas nama **Rival Biasrori**.

| Metode | Nomor |
| --- | --- |
| <img src="./public/donasi/bca.svg" alt="BCA" height="20" /> BCA | `0561876265` |
| DANA | `087772666911` |
| GoPay | `087772666911` |

## Teknologi

| Bagian | Stack |
| --- | --- |
| Frontend | React + TypeScript (Vite) |
| Backend | Laravel |
| Database | PostgreSQL |
| Lisensi | AGPL-3.0 |

Spesifikasi produk ada di [`Docs/`](./Docs/). Mulai dari sini: [Core & Foundation](./Docs/00-core-foundation.md).

## Mau ikut ngembangin?

Silakan. Alurnya biasa aja:

### 1. Fork

Fork repo ini ke akun kamu.

### 2. Clone

```bash
git clone https://github.com/<username>/Zakat.git
cd Zakat
```

### 3. Bikin branch baru

Jangan langsung kerja di `main`:

```bash
git checkout -b fitur/nama-fitur
# atau
git checkout -b perbaikan/nama-bug
```

Contoh:

- `fitur/kalkulator-zakat`
- `perbaikan/validasi-muzaki`
- `docs/lengkapi-prd-collection`

### 4. Coding, lalu commit

```bash
git add .
git commit -m "Jelaskan kenapa perubahan ini dibutuhkan"
```

### 5. Push dan buka Pull Request

```bash
git push -u origin fitur/nama-fitur
```

Buka PR ke repo utama. Kasih ringkasan singkat sama cara ngetesnya.

## Jalankan frontend

Butuh Node.js, plus `bun` / `npm` / `yarn`.

```bash
bun install
bun run dev
```

Lainnya:

```bash
bun run build    # build produksi
bun run preview  # cek hasil build
```

Backend Laravel ada di [`backend/`](./backend/). Setup API dan databasenya ikuti README di folder itu.

## Struktur folder

```text
Zakat/
├── Docs/          # PRD per modul
├── backend/       # API Laravel
├── src/           # Frontend React + TypeScript
├── public/        # Aset statis
└── README.md
```

## Catatan sebelum ngoding

1. Baca dulu `Docs/00-core-foundation.md`
2. Baca PRD modul yang mau dikerjakan di `Docs/`
3. Jangan nebak aturan bisnis di luar dokumen
4. Jaga jejak audit, keamanan data keuangan, dan privasi

## Lisensi

Pakai **AGPL-3.0**.

Boleh dipakai, diubah, dan dibagikan lagi. Yang penting, kode turunannya tetap open source sesuai aturan lisensi itu.

## Yuk ikut

Issue, ide, atau Pull Request semua welcome. Anggap aja ini jalan amal jariyah yang praktis: bantu orang kelola zakat lebih baik lewat teknologi.

Kalau lembaga kamu mau pakai dan ngembangin sendiri, tinggal fork, bikin branch, terus lanjut dari sini.
