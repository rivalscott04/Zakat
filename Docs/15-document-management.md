# PRD MODULE 15 — DOCUMENT MANAGEMENT

Project: Zakat OS
Module: Document Management
Module Code: DOC
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md

Related Modules:

- 04-zakat-collection.md
- 05-infaq-sedekah.md
- 06-donation.md
- 09-mustahik.md
- 10-assessment.md
- 11-program-management.md
- 12-distribution.md
- 13-payment-gateway.md
- 14-bank-reconciliation.md

---

# PRD 15A — OVERVIEW

## 1. Purpose

Modul Document Management bertanggung jawab untuk penyimpanan, pengelolaan, pengamanan, dan penghubungan dokumen dengan entity lain di dalam Zakat OS.

Modul ini menjadi centralized document system.

Dokumen dapat digunakan oleh:

Mustahik

Muzakki

Assessment

Program

Distribution

Payment

Bank Reconciliation

Organization

User

Reporting

dan module lainnya.

Contoh dokumen:

- KTP
- KK
- Surat Keterangan
- Dokumen Assessment
- Proposal Program
- Bukti Transfer
- Bukti Penerimaan
- Foto Distribution
- Invoice
- Receipt
- Bank Statement
- Dokumen Pendukung
- File Laporan

---

## 2. Goals

Modul harus mampu:

1. Upload document.
2. Download document.
3. Preview document.
4. Menyimpan metadata.
5. Menghubungkan document dengan berbagai module.
6. Mendukung multiple file.
7. Mendukung document category.
8. Mendukung document type.
9. Mendukung private document.
10. Mendukung organization isolation.
11. Mendukung document version sederhana.
12. Mendukung document verification.
13. Mendukung document expiration.
14. Mendukung document status.
15. Mendukung soft delete.
16. Mendukung audit trail.
17. Mendukung storage abstraction.
18. Mendukung local storage.
19. Mendukung object storage di masa depan.

---

# PRD 15B — CORE PRINCIPLE

## 3. Centralized Document

Setiap file tidak perlu membuat sistem upload sendiri pada masing-masing module.

Semua file dikelola melalui:

Document Management Module.

Module lain hanya menyimpan relasi.

Contoh:

Mustahik

↓

Document

KTP

KK

Surat Keterangan.

Distribution

↓

Document

Proof of Receipt

Photo Evidence

Transfer Proof.

---

## 4. Document Ownership

Document memiliki:

organization_id

Setiap dokumen hanya dapat diakses oleh Organization yang memiliki dokumen tersebut.

Cross Organization Access:

Forbidden.

---

# PRD 15C — DOCUMENT ENTITY

## 5. Entity

documents

Fields:

id

organization_id

document_number

document_name

original_filename

stored_filename

document_type

category

mime_type

extension

file_size

storage_disk

storage_path

checksum

version

visibility

status

expires_at

uploaded_by

created_at

updated_at

deleted_at

---

## 6. Document Number

Format:

DOC{YEAR}{SEQUENCE}

Contoh:

DOC2026000001

DOC2026000002

DOC2026000003

Rules:

- unique;
- immutable;
- uppercase;
- tidak menggunakan dash;
- human readable.

Primary key menggunakan:

ULID.

---

# PRD 15D — DOCUMENT TYPE

## 7. Initial Document Type

IDENTITY

FAMILY

ASSESSMENT

PROGRAM

PAYMENT

BANK

DISTRIBUTION

RECEIPT

REPORT

CONTRACT

LETTER

IMAGE

OTHER

Document Type dapat dikembangkan melalui configuration.

---

# PRD 15E — DOCUMENT CATEGORY

## 8. Purpose

Category digunakan untuk klasifikasi yang lebih spesifik.

Contoh:

Document Type:

IDENTITY

Category:

KTP

PASSPORT

SIM.

Contoh:

Document Type:

DISTRIBUTION

Category:

PROOF_OF_RECEIPT

PHOTO_EVIDENCE

TRANSFER_PROOF.

---

# PRD 15F — DOCUMENT VISIBILITY

## 9. Visibility

PRIVATE

INTERNAL

PUBLIC

---

## 10. PRIVATE

Dokumen hanya dapat diakses oleh:

- uploader apabila diizinkan;
- authorized role;
- user yang memiliki permission khusus.

Contoh:

KTP

KK

Dokumen sensitif.

---

## 11. INTERNAL

Dokumen dapat diakses oleh user dalam Organization sesuai permission.

---

## 12. PUBLIC

Digunakan untuk dokumen yang dapat dibagikan secara publik.

Contoh:

Public Report.

Public Program Document.

---

# PRD 15G — DOCUMENT STATUS

## 13. Status

ACTIVE

PENDING_VERIFICATION

VERIFIED

REJECTED

EXPIRED

ARCHIVED

DELETED

---

# PRD 15H — DOCUMENT RELATION

## 14. Purpose

Satu Document dapat dihubungkan dengan entity.

Contoh:

Document:

KTP.

Relation:

Mustahik.

Atau:

Document:

Transfer Proof.

Relation:

Payment.

---

## 15. Entity

document_relations

Fields:

id

document_id

entity_type

entity_id

relation_type

created_by

created_at

---

## 16. Entity Type

MUSTAHIK

MUZAKKI

ASSESSMENT

PROGRAM

PROGRAM_ENROLLMENT

DISTRIBUTION

PAYMENT

BANK_STATEMENT

BANK_TRANSACTION

ORGANIZATION

USER

OTHER

---

## 17. Relation Type

PRIMARY

ATTACHMENT

PROOF

SUPPORTING

IDENTITY

OTHER

---

# PRD 15I — DOCUMENT UPLOAD

## 18. Upload Flow

User Select File

↓

Validate Permission

↓

Validate File

↓

Generate Document Number

↓

Generate Storage Filename

↓

Calculate Checksum

↓

Store File

↓

Create Document Record

↓

Create Relation

↓

Audit Event

---

## 19. File Validation

Minimal validation:

- Allowed MIME Type.
- Allowed Extension.
- Maximum File Size.
- File Exists.
- File Integrity.
- Optional Virus Scan.

---

# PRD 15J — ALLOWED FILE TYPE

## 20. Initial Supported Type

Documents:

PDF

DOC

DOCX

XLS

XLSX

CSV

Images:

JPG

JPEG

PNG

WEBP.

File type dapat dikonfigurasi.

---

# PRD 15K — FILE SIZE

## 21. Default Rule

Default maximum:

10 MB per file.

Organization dapat memiliki policy berbeda.

Contoh:

Image:

5 MB.

Document:

10 MB.

Bank Statement:

20 MB.

---

# PRD 15L — STORAGE ABSTRACTION

## 22. Storage

Document Management tidak boleh bergantung pada satu storage provider.

Gunakan storage abstraction.

Initial Storage:

LOCAL.

Future Storage:

S3

MinIO

Cloudflare R2

Google Cloud Storage

Azure Blob.

---

## 23. Storage Fields

storage_disk

storage_path

Contoh:

storage_disk:

private.

storage_path:

documents/01HZABC/DOC2026000001.pdf.

---

# PRD 15M — STORAGE STRUCTURE

## 24. Recommended Structure

documents/

organization/

{organization_id}/

{year}/

{month}/

{document_id}/

file.

Contoh:

documents/

01HXYZ/

2026/

08/

01HABCXYZ/

document.pdf.

---

# PRD 15N — STORED FILENAME

## 25. Rule

Original filename tidak digunakan langsung sebagai storage filename.

Sistem membuat:

stored_filename.

Contoh:

Original:

ktp-ahmad.pdf.

Stored:

01HXYZABC.pdf.

Tujuan:

- prevent collision;
- prevent malicious filename;
- menjaga konsistensi.

---

# PRD 15O — CHECKSUM

## 26. Purpose

Checksum digunakan untuk:

- file integrity;
- duplicate detection;
- verification.

Initial algorithm:

SHA256.

Field:

checksum.

---

## 27. Duplicate Detection

Jika file dengan:

organization

dan:

checksum

sama,

sistem dapat menampilkan:

Possible Duplicate.

Namun duplicate file tidak otomatis ditolak.

User dapat:

Upload Anyway

atau:

Use Existing Document.

---

# PRD 15P — DOCUMENT VERSION

## 28. Purpose

Dokumen dapat memiliki versi.

Contoh:

Proposal Program.

Version 1

↓

Revision

↓

Version 2.

---

## 29. Entity

document_versions

Fields:

id

document_id

version_number

storage_path

file_size

checksum

change_note

created_by

created_at

---

## 30. Rule

Version sebelumnya tidak dihapus.

Current version menjadi default document version.

---

# PRD 15Q — DOCUMENT VERIFICATION

## 31. Purpose

Dokumen tertentu membutuhkan verifikasi.

Contoh:

KTP Mustahik.

Flow:

Uploaded

↓

PENDING_VERIFICATION

↓

VERIFIED

atau:

REJECTED.

---

## 32. Entity

document_verifications

Fields:

id

document_id

status

verification_note

verified_by

verified_at

created_at

updated_at

---

## 33. Verification Status

PENDING

VERIFIED

REJECTED.

---

# PRD 15R — DOCUMENT EXPIRATION

## 34. Purpose

Beberapa dokumen memiliki masa berlaku.

Contoh:

Identity Document.

Permit.

Contract.

Letter.

---

## 35. Expiration Rule

Jika:

current_date > expires_at

maka:

status:

EXPIRED.

Expired Document tidak otomatis dihapus.

---

# PRD 15S — DOCUMENT PREVIEW

## 36. Preview

Supported preview:

PDF

JPG

JPEG

PNG

WEBP.

Untuk file lain:

Download Only.

Future:

Office Document Preview.

---

# PRD 15T — DOCUMENT DOWNLOAD

## 37. Download Security

Download harus melalui authorization.

Sistem memeriksa:

- User authentication;
- Organization ownership;
- Document visibility;
- Permission.

Private file tidak boleh menggunakan permanent public URL.

Gunakan:

Authenticated Download

atau:

Temporary Signed URL.

---

# PRD 15U — DOCUMENT DELETE

## 38. Soft Delete

Document tidak langsung dihapus secara permanen.

Status:

DELETED.

Field:

deleted_at.

---

## 39. Delete Rule

Document yang masih digunakan oleh entity lain:

Tidak boleh hard delete.

Soft delete dapat dilakukan jika policy mengizinkan.

---

## 40. Restore

Authorized user dapat melakukan:

Restore Document.

Jika file masih tersedia.

---

# PRD 15V — DOCUMENT ARCHIVE

## 41. Archive

Document yang tidak aktif tetapi harus disimpan dapat memiliki status:

ARCHIVED.

Archived Document:

- tetap tersedia;
- read-only;
- tidak dapat dimodifikasi.

---

# PRD 15W — DOCUMENT ACCESS LOG

## 42. Purpose

Akses terhadap dokumen sensitif perlu dicatat.

Entity:

document_access_logs

Fields:

id

document_id

user_id

action

ip_address

user_agent

accessed_at

created_at

---

## 43. Action

VIEW

DOWNLOAD

UPLOAD

UPDATE

DELETE

RESTORE

VERIFY

REJECT.

---

# PRD 15X — API SPECIFICATION

## 44. Documents

GET

/api/v1/documents

POST

/api/v1/documents

GET

/api/v1/documents/{id}

PATCH

/api/v1/documents/{id}

DELETE

/api/v1/documents/{id}

POST

/api/v1/documents/{id}/restore

---

## 45. Document File

GET

/api/v1/documents/{id}/download

GET

/api/v1/documents/{id}/preview

POST

/api/v1/documents/{id}/replace

---

## 46. Document Relations

GET

/api/v1/documents/{id}/relations

POST

/api/v1/documents/{id}/relations

DELETE

/api/v1/documents/{id}/relations/{relationId}

---

## 47. Document Verification

POST

/api/v1/documents/{id}/verify

POST

/api/v1/documents/{id}/reject

---

## 48. Document Version

GET

/api/v1/documents/{id}/versions

POST

/api/v1/documents/{id}/versions

POST

/api/v1/documents/{id}/versions/{versionId}/restore

---

# PRD 15Y — PERMISSIONS

## 49. Permission Codes

document.view

document.create

document.update

document.delete

document.restore

document.download

document.preview

document.replace

document.version.view

document.version.create

document.version.restore

document.verify

document.reject

document.relation.manage

document.archive

document.access_log.view

document.manage

---

# PRD 15Z — AUDIT EVENTS

## 50. Audit Events

Minimal:

document_created

document_uploaded

document_updated

document_downloaded

document_previewed

document_replaced

document_deleted

document_restored

document_archived

document_relation_created

document_relation_deleted

document_version_created

document_version_restored

document_verification_requested

document_verified

document_rejected

document_expired

document_duplicate_detected

---

# PRD 15AA — UI REQUIREMENTS

## 51. Document Dashboard

Cards:

Total Documents

Uploaded Today

Pending Verification

Verified

Expired

Archived

Storage Usage.

---

## 52. Document List

Velzon DataTable.

Columns:

Document Number

Document Name

Type

Category

Related Entity

Uploaded By

Created Date

Version

Status

Actions.

Filters:

Document Type

Category

Status

Organization

Date Range

Related Entity.

---

## 53. Document Detail

Header:

Document Number

Document Name

Document Type

Status

Version.

Tabs:

Overview

Preview

Relations

Versions

Verification

Access History

Audit.

---

## 54. Upload Document

Form:

Document Name

Document Type

Category

Related Entity

Visibility

Expiration Date

File.

System menampilkan:

File Name

File Size

File Type

Validation Result.

---

## 55. Document Verification Queue

List:

Document

Related Entity

Document Type

Uploaded Date

Uploaded By

Status.

Actions:

Preview

Verify

Reject.

---

# PRD 15AB — BUSINESS RULES

## 56. General Rules

1. Document Number harus unik.
2. Setiap Document harus memiliki Organization.
3. Cross Organization Access tidak diperbolehkan.
4. File harus melewati validation.
5. Original filename tidak digunakan sebagai storage filename.
6. Storage filename harus aman dan unik.
7. Checksum harus dihitung.
8. Duplicate file dapat dideteksi.
9. Document dapat memiliki satu atau lebih Relation.
10. Document dapat memiliki version.
11. Version sebelumnya tidak boleh dihapus otomatis.
12. Private Document harus melalui authorization.
13. Private file tidak boleh memiliki permanent public URL.
14. Download harus diperiksa permission.
15. Sensitive document access harus dicatat.
16. Verification membutuhkan permission.
17. Rejected Document harus memiliki note.
18. Expired Document tidak boleh otomatis dihapus.
19. Delete menggunakan soft delete.
20. Hard delete hanya dilakukan melalui retention policy atau admin process.
21. Archived Document bersifat read-only.
22. Storage abstraction wajib digunakan.
23. Organization isolation wajib diterapkan.
24. Permission diperiksa di backend.
25. Semua aktivitas material harus diaudit.

---

# PRD 15AC — TESTING REQUIREMENTS

## 57. Unit Test

Minimal:

- Document Number Generation
- File Upload
- MIME Validation
- Extension Validation
- File Size Validation
- Stored Filename Generation
- Checksum Generation
- Duplicate Detection
- Organization Isolation
- Document Relation
- Document Version
- Document Verification
- Document Rejection
- Document Expiration
- Soft Delete
- Restore
- Download Authorization
- Private Access
- Access Logging

---

## 58. Integration Test

Flow:

Upload KTP

↓

File Validation

↓

Store File

↓

Generate Checksum

↓

Create Document

↓

Create Mustahik Relation

↓

Pending Verification

↓

Document Preview

↓

Verify

↓

Document Available.

---

## 59. Security Test

Test:

- Cross organization document access;
- Direct storage path access;
- Malicious filename;
- Invalid MIME upload;
- Oversized file;
- Unauthorized download;
- Unauthorized document verification;
- Deleted document access;
- Sensitive document exposure;
- Version manipulation;
- Audit bypass.

---

# PRD 15AD — ACCEPTANCE CRITERIA

- [ ] Document dapat diupload.
- [ ] Document Number otomatis dibuat.
- [ ] Metadata disimpan.
- [ ] File validation tersedia.
- [ ] MIME validation tersedia.
- [ ] File size validation tersedia.
- [ ] Storage abstraction tersedia.
- [ ] Local storage tersedia.
- [ ] Document Relation tersedia.
- [ ] Multiple Relation tersedia.
- [ ] Checksum tersedia.
- [ ] Duplicate Detection tersedia.
- [ ] Document Version tersedia.
- [ ] Document Verification tersedia.
- [ ] Document Rejection tersedia.
- [ ] Document Expiration tersedia.
- [ ] Preview tersedia.
- [ ] Secure Download tersedia.
- [ ] Private Document tersedia.
- [ ] Soft Delete tersedia.
- [ ] Restore tersedia.
- [ ] Archive tersedia.
- [ ] Access Log tersedia.
- [ ] Audit Trail tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 15AE — DEFINITION OF DONE

Modul Document Management dianggap selesai apabila:

1. Document dapat diupload.
2. Document Number dibuat otomatis.
3. File Metadata tersimpan.
4. File Validation berjalan.
5. File disimpan menggunakan storage abstraction.
6. Original filename tidak digunakan sebagai storage filename.
7. Checksum file tersedia.
8. Duplicate Document dapat dideteksi.
9. Document dapat dihubungkan dengan entity lain.
10. Multiple Relation didukung.
11. Document Version tersedia.
12. Document dapat diverifikasi.
13. Document dapat direject.
14. Document Expiration berjalan.
15. Preview file yang didukung tersedia.
16. Download aman melalui authorization.
17. Private Document terlindungi.
18. Soft Delete tersedia.
19. Restore tersedia.
20. Archive tersedia.
21. Access Log tersedia.
22. Audit Trail tersedia.
23. Organization isolation berjalan.
24. Permission berjalan.
25. Automated Test berhasil.

---

# FUTURE DEVELOPMENT

Fitur berikut dapat dikembangkan pada versi selanjutnya:

- OCR Document
- Automatic Document Classification
- AI Document Extraction
- Full Text Search
- Virus Scanning Integration
- Digital Signature
- Electronic Signature
- Document Retention Policy
- Automatic Archive
- Object Storage Integration
- S3 Integration
- MinIO Integration
- Cloudflare R2 Integration
- Office Document Preview
- PDF Annotation
- Document Watermark
- Bulk Upload
- Bulk Download
- Document Sharing Link
- Temporary Public Link
- Advanced Document Workflow

---

# END OF PRD MODULE 15 — DOCUMENT MANAGEMENT