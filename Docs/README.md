# ZETRA — PRD

Kumpulan Product Requirements Document (PRD) per modul untuk ZETRA.

## Struktur

Setiap modul memiliki dokumen PRD terpisah agar dapat dikembangkan, direview, dan diberikan kepada AI coding agent secara independen.

## Prinsip

- Open source
- Auditability by design
- Sharia rule driven
- Financial integrity
- Public transparency
- Privacy by design
- Modular architecture
- API first
- React + TypeScript frontend
- Laravel backend
- PostgreSQL database

## Status modul

| Kode | Modul | Status |
| --- | --- | --- |
| 00 | [Core & Foundation](./00-core-foundation.md) | Implemented (Backend — verified) |
| 01 | [Authentication & Authorization](./01-authentication-authorization.md) | Implemented (Backend + UI wired/build verified) |
| 02 | [Organization & Amil](./02-organization-amil.md) | Implemented (Backend + UI wired/build verified) |
| 03 | [Muzaki](./03-muzaki.md) | Implemented (Backend + UI build verified) |
| 04 | [Zakat](./04-zakat.md) | In progress (Backend core + UI list verified) |
| 05 | [Zakat Calculator](./05-zakat-calculator.md) | Implemented (Backend + UI wired/build verified) |
| 06 | [Collection](./06-collection.md) | Implemented (Backend + UI wired/build verified) |
| 07 | [Fund Management](./07-fund-management.md) | Implemented (Backend + UI wired/build verified) |
| 08 | [Accounting Ledger](./08-accounting-ledger.md) | Implemented (Backend + UI wired/build verified) |
| 09 | [Mustahik](./09-mustahik.md) | Implemented (Core backend + UI wired/build verified) |
| 10 | [Assessment](./10-assessment.md) | Draft |
| 11 | [Program](./11-program.md) | Draft |
| 12 | [Distribution](./12-distribution.md) | Draft |
| 13 | [Payment Gateway](./13-payment-gateway.md) | Draft |
| 14 | [Bank Reconciliation](./14-bank-reconciliation.md) | Draft |
| 15 | [Document Management](./15-document-management.md) | Draft |
| 16 | [Notification](./16-notification.md) | Draft |
| 17 | [Audit Trail](./17-audit-trail.md) | Draft |
| 18 | [Transparency](./18-transparency.md) | Draft |
| 19 | [Reporting](./19-reporting.md) | Draft |
| 20 | [System Settings](./20-system-settings.md) | Draft |
| 21 | [Security](./21-Security.md) | Draft |

**Implemented (Backend — verified)** artinya API Laravel sudah diimplementasikan, `php artisan test` lulus (52 test, 150 assertion), dan `./vendor/bin/pint --test` lulus pada 20 Agustus 2026.

Frontend aktif sekarang memakai `src/app`, `src/features`, dan `src/shared`. Route aplikasi, AuthProvider, layout baru, dan halaman list Muzaki sudah terhubung. Template lama tetap berada di `src/template` sebagai legacy dan tidak lagi menjadi dependency active graph. `bun x tsc --noEmit` dan `bun run build` sudah lulus.

Module 03 memiliki migrasi, model, service, request, resource, endpoint CRUD/status, serta halaman list Muzaki. Endpoint ringkasan kontribusi sengaja mengembalikan `available: false` sampai Module 06 Collection dan Module 08 Accounting Ledger tersedia; data kontribusi tidak dipalsukan.

Module 01 sekarang memiliki route UI aktif untuk login, session, user, role, permission, serta guard berbasis permission. Module 02 sekarang memiliki route UI aktif untuk organisasi, detail/member, organization switcher, dan amil, semuanya memakai endpoint backend yang sama dengan permission guard.

Module 04 sudah memiliki backend core untuk kategori, jenis zakat, rule version/effective period, lifecycle status, konfigurasi rate/nisab/haul/parameter, reference value, serta endpoint rule resolution. UI baru sudah ter-wire untuk list jenis dan rule. Validasi overlap rule aktif dan detail konfigurasi/UI workflow masih dilanjutkan sebelum modul ditandai selesai.

Module 05 sudah memiliki calculation session, dynamic inputs, backend formula whitelist, rule/rate/nisab/haul/reference resolution, eligibility untuk percentage/nisab/asset/income/harvest/livestock, immutable snapshot, preview, calculate, confirm, cancel, recalculate/versioning, adjustment audit, expiration, API permission, dan halaman UI kalkulator yang wired ke API. Boundary conversion sengaja mengembalikan conflict sampai Module 06 menyediakan entitas Collection; calculation yang confirmed sudah siap dipakai sebagai payload integrasi.

Module 06 sudah memiliki collection number COL, collection manual/from-calculation, item, lifecycle draft/pending/partial/paid/completed/expired/cancelled, payment event pending/verified/settled, allocation, partial payment, overpayment detection, expiration, cancellation, reactivation, summary, audit trail, permission, API, dan UI list/manual collection. Fund Management handoff tetap menjadi boundary modul berikutnya; collection completed menjadi source business transaction untuk handoff tersebut.

Module 07 sudah memiliki fund dan fund type/category metadata, fund code, immutable fund movement, inflow dari Collection completed maupun manual, outflow dengan negative-balance prevention, current/available/reserved/allocated/distributed balance projection, allocation approval, reservation/release, transfer approval, adjustment, reconciliation, availability check, audit trail, permission, API, dan UI list/create fund. Distribution menjadi downstream consumer untuk penggunaan fund dan outflow berikutnya.

Module 08 sudah memiliki Chart of Accounts dengan hierarchy/postable validation, accounting period open/locked/closed, journal entry dan journal line double-entry validation, submit/approve/post/reversal lifecycle, posted journal immutability, accounting event ingestion, general ledger, trial balance, audit trail, permission, API, dan UI COA. Reporting dan financial statement menjadi downstream consumer ledger.

Module 09 sudah memiliki master Mustahik dengan nomor MSH, identitas terenkripsi dan duplicate detection, alamat, profil sosial ekonomi, klasifikasi Asnaf, verification status, permission, API, test feature, serta halaman UI list dan pendaftaran yang ter-wire ke backend. Household, assessment/eligibility lanjutan, dan distribution history tetap menjadi downstream pada modul terkait.

Verifikasi terakhir: `bun x tsc --noEmit`, `bun run build`, `./vendor/bin/pint --test`, dan `php artisan test` lulus. Full backend suite: 61 test dan 227 assertion lulus.
