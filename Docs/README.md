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
| 01 | [Authentication & Authorization](./01-authentication-authorization.md) | Implemented (Backend — verified); UI belum terintegrasi |
| 02 | [Organization & Amil](./02-organization-amil.md) | Implemented (Backend — verified); UI belum terintegrasi |
| 03 | [Muzaki](./03-muzaki.md) | Implemented (Backend + UI build verified) |
| 04 | [Zakat](./04-zakat.md) | In progress (Backend core + UI list verified) |
| 05 | [Zakat Calculator](./05-zakat-calculator.md) | Draft |
| 06 | [Collection](./06-collection.md) | Draft |
| 07 | [Fund Management](./07-fund-management.md) | Draft |
| 08 | [Accounting Ledger](./08-accounting-ledger.md) | Draft |
| 09 | [Mustahik](./09-mustahik.md) | Draft |
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

Module 04 sudah memiliki backend core untuk kategori, jenis zakat, rule version/effective period, lifecycle status, serta UI list jenis dan rule. Detail rate/nisab/haul management dan rule resolution masih dilanjutkan sebelum modul ditandai selesai.
