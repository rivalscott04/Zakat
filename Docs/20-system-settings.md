# PRD MODULE 21 — CODING STANDARD & BEST PRACTICE

Project: ZETRA
Module: Coding Standard
Module Code: CST
Version: 0.1.0
Status: Draft

Dependencies:

- All Modules

Related Modules:

- 20-system-design.md
- 17-audit-trail.md
- 21-coding-standard.md

---

# PRD 21A — OVERVIEW

## 1. Purpose

Dokumen ini mendefinisikan standar coding untuk seluruh ZETRA.

Tujuan:

1. Maintainable.
2. Readable.
3. Testable.
4. Secure.
5. Scalable.
6. Consistent.
7. Avoid N+1 Query.
8. Clear Separation of Responsibility.
9. Business Logic berada pada Service Layer.
10. Controller hanya menangani Request dan Response.

---

# PRD 21B — ARCHITECTURE PRINCIPLE

## 2. Layer Responsibility

Arsitektur utama:

Request

↓

Controller

↓

Service

↓

Repository atau Model Query bila diperlukan

↓

Database.

Response kembali:

Service Result

↓

Controller

↓

API Resource

↓

JSON Response.

---

# PRD 21C — CONTROLLER RULE

## 3. Controller Responsibility

Controller hanya bertanggung jawab untuk:

1. Menerima Request.
2. Memanggil Form Request.
3. Memanggil Service.
4. Menentukan HTTP Response.
5. Menggunakan API Resource.

Controller tidak boleh berisi:

Business Logic.

Complex Calculation.

Financial Logic.

Complex Query.

Transaction Logic.

Loop Business Process.

---

## 4. Bad Controller

Contoh yang tidak diperbolehkan:

public function store(Request $request)
{
    $data = $request->validate([...]);

    $collection = Collection::create($data);

    foreach ($request->items as $item) {
        ...
    }

    DB::transaction(function () {
        ...
    });

    Notification::send(...);

    return response()->json(...);
}

---

## 5. Expected Controller

Contoh struktur:

public function store(StoreCollectionRequest $request)
{
    $collection = $this->collectionService->create(
        $request->validated()
    );

    return new CollectionResource($collection);
}

Controller harus:

Thin Controller.

---

# PRD 21D — SERVICE LAYER

## 6. Service Responsibility

Business Logic berada pada Service Layer.

Contoh:

CollectionService.

DistributionService.

FundService.

LedgerService.

AssessmentService.

ProgramService.

PaymentService.

ReconciliationService.

---

## 7. Service Responsibilities

Service menangani:

- Business Rule.
- Workflow.
- Database Transaction.
- Calculation.
- State Transition.
- Domain Event.
- Audit Trigger.
- Integration Trigger.

---

## 8. Example Structure

app/Services/

Collection/

CollectionService.php

Collection/

CollectionPaymentService.php

Distribution/

DistributionService.php

Fund/

FundService.php

Accounting/

LedgerService.php

Reporting/

ReportService.php

---

# PRD 21E — FORM REQUEST

## 9. Validation Rule

Semua input utama menggunakan:

Form Request.

Contoh:

StoreMuzakkiRequest.

UpdateMuzakkiRequest.

StoreDistributionRequest.

ApproveDistributionRequest.

Controller tidak membuat validation panjang.

---

## 10. Form Request Responsibility

Form Request bertanggung jawab untuk:

Authorization.

Validation Rules.

Validation Messages.

Data Preparation sederhana.

---

# PRD 21F — API RESOURCE

## 11. API Response

Response entity menggunakan:

Laravel API Resource.

Contoh:

MuzakkiResource.

MustahikResource.

CollectionResource.

DistributionResource.

ProgramResource.

---

## 12. Resource Responsibility

API Resource bertanggung jawab untuk:

- Data transformation.
- Field visibility.
- Nested relationship.
- Consistent JSON structure.

Resource tidak boleh:

Melakukan query tambahan.

Contoh yang harus dihindari:

$this->relation()->first()

di dalam Resource.

Semua relationship harus sudah di-load sebelumnya.

---

# PRD 21G — DATABASE QUERY

## 13. No N+1 Query Rule

N+1 Query dilarang.

Contoh buruk:

$collections = Collection::all();

foreach ($collections as $collection) {
    echo $collection->muzakki->name;
}

Gunakan:

with().

Contoh:

Collection::with([
    'muzakki',
    'fund'
])->get();

---

## 14. Nested Eager Loading

Gunakan eager loading untuk nested relation.

Contoh:

Distribution::with([
    'mustahik',
    'program',
    'fund',
    'items'
])->paginate();

---

## 15. Conditional Eager Loading

Load relationship hanya jika diperlukan.

Gunakan:

withWhen

atau pattern conditional loading yang sesuai.

Jangan:

Selalu load seluruh relationship.

---

## 16. Relationship Loading Rule

Untuk setiap endpoint:

Developer harus mengetahui:

- Entity utama.
- Relationship yang dibutuhkan.
- Relationship yang tidak dibutuhkan.

Tidak boleh:

Model::with('*')

atau memuat relationship secara berlebihan.

---

# PRD 21H — QUERY PERFORMANCE

## 17. Query Principle

Gunakan:

select()

ketika tidak membutuhkan seluruh kolom.

Contoh:

Muzakki::query()
    ->select([
        'id',
        'reference_number',
        'name',
        'status'
    ]);

---

## 18. Pagination

List data wajib menggunakan:

paginate().

Atau:

simplePaginate()

untuk data besar.

Tidak menggunakan:

Model::all()

untuk endpoint list produksi.

---

## 19. Chunking

Untuk data besar gunakan:

chunkById().

cursor().

lazyById().

Sesuai kebutuhan.

---

# PRD 21I — DATABASE TRANSACTION

## 20. Transaction

Business process yang mengubah beberapa tabel wajib menggunakan:

DB::transaction().

Contoh:

Distribution Approval:

Update Distribution

+

Update Fund

+

Create Ledger

+

Create Audit Event.

Semua harus atomic.

---

## 21. Transaction Rule

Jika salah satu gagal:

Semua perubahan harus rollback.

Tidak boleh terjadi:

Distribution Approved

tetapi:

Fund tidak berkurang.

---

# PRD 21J — MODEL RULE

## 22. Model Responsibility

Model bertanggung jawab untuk:

- Relationship.
- Cast.
- Scope sederhana.
- Attribute.
- Basic Domain Configuration.

Model tidak boleh menjadi tempat:

Complex Business Workflow.

External API Call.

Large Calculation.

Complex Transaction.

---

# PRD 21K — REPOSITORY

## 23. Repository Usage

Repository tidak wajib digunakan untuk semua Model.

Jangan membuat:

UserRepository.

MuzakkiRepository.

CollectionRepository.

hanya karena mengikuti pattern.

Repository digunakan apabila:

- Query sangat kompleks.
- Multiple data source.
- Query reuse tinggi.
- Infrastructure abstraction diperlukan.

Default:

Eloquent Query Builder dapat digunakan oleh Service.

---

# PRD 21L — SERVICE METHOD DESIGN

## 24. Service Method

Service method harus memiliki tujuan jelas.

Contoh:

create()

update()

submit()

approve()

reject()

cancel()

complete().

Hindari:

processEverything().

handleAll().

doAction().

---

## 25. Service Return

Service dapat mengembalikan:

Model.

DTO.

Value Object.

Result Object.

Untuk flow kompleks disarankan:

Result Object.

---

# PRD 21M — DTO

## 26. DTO Usage

Gunakan DTO apabila:

Input kompleks.

Multiple Layer membutuhkan data structure.

Business Process memiliki data contract.

Contoh:

CreateDistributionData.

ApproveDistributionData.

PaymentConfirmationData.

---

# PRD 21N — ENUM

## 27. Enum Rule

Status dan value tetap menggunakan:

PHP Enum.

Contoh:

CollectionStatus.

DistributionStatus.

PaymentStatus.

FundType.

NotificationPriority.

Jangan menggunakan string berulang:

'pending'

'approved'

'completed'

di berbagai file.

---

# PRD 21O — EVENT & LISTENER

## 28. Event Usage

Gunakan Event untuk side effect.

Contoh:

DistributionCompleted.

↓

Listener:

CreateLedgerEntry.

↓

SendNotification.

↓

CreateAuditLog.

Event tidak digunakan untuk menyembunyikan critical business logic yang sulit ditelusuri.

Core transaction harus tetap jelas di Service.

---

# PRD 21P — QUEUE

## 29. Queue Usage

Gunakan Queue untuk:

Email.

Notification.

Report Generation.

Large Export.

Document Processing.

External API.

Heavy Calculation.

Controller tidak boleh menunggu proses berat.

---

# PRD 21Q — CACHE

## 30. Cache Rule

Cache digunakan untuk:

Dashboard Aggregate.

Master Data.

Frequently Used Configuration.

Public Transparency Data.

Cache Key harus mempertimbangkan:

Organization.

User Scope jika relevan.

Parameter.

Contoh:

organization:{id}:dashboard:summary.

---

# PRD 21R — SECURITY

## 31. Authorization

Authorization tidak hanya dilakukan di frontend.

Backend wajib menggunakan:

Policy.

Gate.

Permission.

Service Layer tetap memvalidasi business authorization jika diperlukan.

---

## 32. Mass Assignment

Gunakan:

$fillable.

atau guarded secara sadar.

Tidak menggunakan:

Model::unguard()

secara global.

---

# PRD 21S — ERROR HANDLING

## 33. Exception

Gunakan custom exception untuk domain error.

Contoh:

InsufficientFundException.

InvalidDistributionStateException.

PaymentAlreadyConfirmedException.

Jangan menggunakan:

throw new Exception('Something went wrong');

untuk domain error penting.

---

# PRD 21T — NAMING CONVENTION

## 34. PHP Naming

Class:

PascalCase.

Method:

camelCase.

Variable:

camelCase.

Database:

snake_case.

Table:

plural snake_case.

Foreign Key:

entity_id.

---

## 35. Code Naming

Contoh:

DistributionService.

StoreDistributionRequest.

DistributionResource.

DistributionStatus.

DistributionCompleted.

---

# PRD 21U — NO DUPLICATION

## 36. DRY Principle

Jangan copy-paste business logic.

Jika logic digunakan oleh beberapa module:

Pertimbangkan:

Shared Service.

Action.

Helper terbatas.

Domain Service.

Namun jangan melakukan abstraction terlalu cepat.

---

# PRD 21V — TESTING

## 37. Test Requirement

Setiap critical business logic wajib memiliki test.

Minimal:

Feature Test.

Unit Test untuk calculation dan business rule kompleks.

---

## 38. Critical Flow Test

Wajib untuk:

Collection.

Fund Movement.

Distribution.

Payment Confirmation.

Ledger Posting.

Bank Reconciliation.

Permission.

Organization Isolation.

Audit.

---

# PRD 21W — STATIC ANALYSIS & FORMAT

## 39. Code Quality

Gunakan:

Laravel Pint.

PHPStan atau Larastan.

CI harus menjalankan:

Test.

Code Formatting.

Static Analysis.

---

# PRD 21X — PERFORMANCE CHECKLIST

## 40. Developer Checklist

Sebelum Pull Request:

- [ ] Tidak ada N+1 Query.
- [ ] Relationship menggunakan eager loading jika diperlukan.
- [ ] Tidak over-fetch relationship.
- [ ] List menggunakan pagination.
- [ ] Query besar menggunakan select.
- [ ] Index database dipertimbangkan.
- [ ] Heavy task menggunakan Queue.
- [ ] Cache dipertimbangkan.
- [ ] Tidak ada Model::all() untuk data besar.
- [ ] Tidak ada query dalam loop.
- [ ] Tidak ada query tambahan dalam API Resource.
- [ ] Database transaction digunakan untuk proses atomic.

---

# PRD 21Y — CODE REVIEW CHECKLIST

## 41. Pull Request Checklist

Architecture:

- [ ] Controller thin.
- [ ] Business logic berada di Service.
- [ ] Validation menggunakan Form Request.
- [ ] API menggunakan Resource.
- [ ] Complex input menggunakan DTO jika diperlukan.
- [ ] Enum digunakan untuk status.

Database:

- [ ] No N+1 Query.
- [ ] Eager loading digunakan dengan tepat.
- [ ] Pagination digunakan.
- [ ] Query tidak berlebihan.
- [ ] Database index dipertimbangkan.

Security:

- [ ] Authorization diterapkan.
- [ ] Organization isolation diterapkan.
- [ ] Sensitive data tidak terekspos.

Quality:

- [ ] Test tersedia.
- [ ] Laravel Pint passed.
- [ ] Static Analysis passed.
- [ ] Tidak ada dead code.
- [ ] Tidak ada debug code.
- [ ] Tidak ada dd().
- [ ] Tidak ada dump().

---

# PRD 21Z — DEFINITION OF DONE

Coding Standard dianggap diterapkan apabila:

1. Controller hanya menangani Request dan Response.
2. Business Logic berada pada Service Layer.
3. Validation menggunakan Form Request.
4. API Response menggunakan API Resource.
5. Resource tidak melakukan database query.
6. Tidak ada N+1 Query.
7. Relationship menggunakan eager loading sesuai kebutuhan.
8. Tidak terjadi over-fetching.
9. List menggunakan pagination.
10. Query data besar dioptimalkan.
11. Query dalam loop tidak diperbolehkan.
12. Business Transaction menggunakan DB Transaction.
13. Model tidak menjadi tempat complex business logic.
14. Repository hanya digunakan apabila benar-benar diperlukan.
15. Status menggunakan Enum.
16. Heavy process menggunakan Queue.
17. Cache digunakan secara aman.
18. Authorization dilakukan di backend.
19. Organization isolation diterapkan.
20. Sensitive data tidak terekspos.
21. Domain Exception digunakan untuk business error.
22. Naming convention konsisten.
23. Critical Business Logic memiliki Automated Test.
24. Laravel Pint digunakan.
25. Static Analysis digunakan.
26. Code Review mengikuti checklist.

---

# END OF PRD MODULE 21 — CODING STANDARD & BEST PRACTICE