# PRD MODULE 01 — AUTHENTICATION & AUTHORIZATION

Project: ZETRA
Module: Authentication & Authorization
Module Code: AUTH
Version: 0.1.0
Status: Implemented (Backend)
Dependencies: PRD 00 — Core & Foundation

---

# PRD 01A — INTRO & AUTHENTICATION

## 1. Purpose

Modul Authentication & Authorization bertanggung jawab untuk:

* Identitas pengguna
* Registrasi pengguna
* Login
* Logout
* Password management
* Session management
* Token authentication
* Role management
* Permission management
* Access control
* Account security
* User activation dan deactivation

Modul ini menjadi gerbang utama akses ke seluruh ZETRA.

---

## 2. Core Principle

Authentication menjawab:

> Siapa pengguna ini?

Authorization menjawab:

> Apa yang boleh dilakukan pengguna ini?

Keduanya harus dipisahkan secara jelas.

---

## 3. Actors

Sistem mengenal actor berikut:

* Guest
* Authenticated User
* System
* Integration

Authenticated user dapat memiliki satu atau lebih role sesuai kebijakan organisasi.

---

## 4. User Identity

Setiap user menggunakan ULID sebagai primary key.

Contoh:

```text
01KABC123XYZ4567890
```

Business number user tidak wajib digunakan pada versi awal.

Email atau username tidak digunakan sebagai primary key.

---

## 5. User Entity

Entity utama:

```text
users
```

Minimum fields:

```text
id
organization_id
name
email
username
password
status
email_verified_at
last_login_at
last_login_ip
created_at
updated_at
deleted_at
```

---

## 6. User Status

Status user:

```text
pending
active
inactive
suspended
locked
```

### pending

Akun telah dibuat tetapi belum aktif.

### active

User dapat login dan menggunakan sistem sesuai permission.

### inactive

Akun tidak dapat digunakan sementara.

### suspended

Akses dihentikan karena keputusan administrator.

### locked

Akun dikunci otomatis atau manual karena alasan keamanan.

---

## 7. Registration Strategy

Versi awal menggunakan:

```text
Admin Created User
```

Public self-registration tidak wajib tersedia pada MVP.

Administrator dapat:

* membuat user;
* mengirim invitation;
* menentukan role awal;
* menentukan organization;
* menonaktifkan user.

Public registration dapat ditambahkan pada fase berikutnya.

---

## 8. User Invitation

Administrator dapat mengundang user.

Flow:

```text
Admin
↓
Create User / Invitation
↓
Invitation Generated
↓
User Receives Invitation
↓
Set Password
↓
Account Activated
```

Invitation memiliki:

```text
id
user_id
token
expires_at
used_at
created_by
created_at
```

Token invitation:

* random;
* tidak dapat ditebak;
* single-use;
* memiliki expiration;
* tidak boleh disimpan plaintext apabila memungkinkan.

---

## 9. Login Identifier

Login dapat menggunakan:

```text
email
```

Username dapat ditambahkan sebagai alternatif apabila diperlukan.

Versi awal:

```text
email + password
```

---

## 10. Login Flow

```text
User
↓
Submit Email + Password
↓
Validate Credentials
↓
Check User Status
↓
Check Organization Context
↓
Create Authenticated Session
↓
Record Login Event
↓
Redirect to Dashboard
```

Jika authentication gagal:

```text
Invalid credentials
```

Sistem tidak boleh memberi informasi apakah:

* email tidak ditemukan;
* password salah.

---

## 11. Logout

Logout harus:

* mengakhiri session/token;
* mencatat logout apabila memungkinkan;
* membersihkan authentication state frontend.

Endpoint:

```text
POST /api/v1/auth/logout
```

---

## 12. Current User

Endpoint:

```text
GET /api/v1/auth/me
```

Response minimal:

```json
{
  "data": {
    "id": "01K...",
    "name": "User Name",
    "email": "user@example.com",
    "organization": {},
    "roles": [],
    "permissions": []
  }
}
```

Password dan security credential tidak boleh dikembalikan.

---

# PRD 01B — PASSWORD MANAGEMENT

## 13. Password Storage

Password wajib:

* di-hash;
* tidak disimpan plaintext;
* tidak dicatat dalam log;
* tidak dimasukkan dalam audit before/after data;
* tidak pernah dikembalikan melalui API.

Menggunakan password hashing mechanism bawaan Laravel yang aman.

---

## 14. Password Policy

Password minimum:

```text
8 characters
```

Rekomendasi production:

* minimum 10–12 karakter;
* kombinasi karakter dapat dikonfigurasi;
* password umum dapat ditolak;
* password tidak boleh sama dengan password sebelumnya apabila password history diaktifkan.

Aturan final harus configurable melalui System Settings.

---

## 15. Change Password

Authenticated user dapat mengubah password.

Flow:

```text
Current Password
↓
Validate
↓
New Password
↓
Confirm Password
↓
Password Updated
↓
Revoke Other Sessions
```

Endpoint:

```text
POST /api/v1/auth/change-password
```

---

## 16. Forgot Password

Flow:

```text
User
↓
Submit Email
↓
Generate Reset Token
↓
Send Reset Link
↓
User Sets New Password
↓
Invalidate Reset Token
```

Endpoint:

```text
POST /api/v1/auth/forgot-password

POST /api/v1/auth/reset-password
```

Reset token:

* single-use;
* memiliki expiration;
* invalid setelah digunakan.

---

# PRD 01C — SESSION & SECURITY

## 17. Authentication Method

Frontend React menggunakan Laravel Sanctum.

Authentication state tidak boleh mengandalkan token yang disimpan secara tidak aman.

Strategi final mengikuti deployment architecture:

```text
Same Domain / Subdomain
```

dengan cookie-based SPA authentication sebagai pilihan utama.

---

## 18. Session Management

Sistem harus dapat:

* melihat session aktif;
* mengakhiri session tertentu;
* mengakhiri seluruh session selain session saat ini;
* mencatat aktivitas login penting.

Entity:

```text
user_sessions
```

Minimum metadata:

```text
id
user_id
ip_address
user_agent
last_activity_at
created_at
```

---

## 19. Session Expiration

Session memiliki masa berlaku configurable.

Session harus dapat berakhir karena:

* expiration;
* manual logout;
* password change;
* administrator revoke;
* account suspension.

---

## 20. Failed Login Protection

Sistem harus menerapkan rate limiting.

Contoh flow:

```text
Failed Login
↓
Counter Increased
↓
Threshold Reached
↓
Temporary Lock
```

Threshold dan lock duration harus configurable.

Sistem tidak boleh langsung melakukan permanent lock tanpa mekanisme recovery.

---

## 21. Account Lock

Account dapat dikunci karena:

* terlalu banyak login gagal;
* aktivitas mencurigakan;
* tindakan administrator;
* security incident.

Lock event harus dicatat.

---

## 22. Two-Factor Authentication

2FA bukan mandatory untuk MVP, tetapi arsitektur harus mendukung implementasi di masa depan.

Target metode:

* TOTP authenticator;
* recovery codes.

2FA direkomendasikan untuk:

* Super Admin;
* Administrator;
* Finance;
* Auditor.

---

# PRD 01D — ROLE MANAGEMENT

## 23. Role

Entity:

```text
roles
```

Fields:

```text
id
organization_id
name
code
description
is_system
is_active
created_at
updated_at
```

Role code harus mengikuti Core Code Convention.

Contoh:

```text
SUPER_ADMIN
ADMIN
AMIL
VERIFIER
APPROVER
FINANCE
AUDITOR
VIEWER
```

---

## 24. Default Roles

### SUPER_ADMIN

Akses penuh terhadap sistem.

Role ini hanya digunakan untuk pengelolaan platform/system-level.

---

### ADMIN

Mengelola konfigurasi organisasi dan user sesuai permission.

---

### AMIL

Melakukan operasional pengelolaan zakat sesuai tugas.

---

### VERIFIER

Memverifikasi data dan transaksi sesuai workflow.

---

### APPROVER

Memberikan approval pada aktivitas yang membutuhkan persetujuan.

---

### FINANCE

Mengelola aktivitas keuangan sesuai permission.

---

### AUDITOR

Memiliki akses baca terhadap data audit dan laporan.

Auditor tidak boleh mengubah transaksi operasional.

---

### VIEWER

Read-only access terhadap resource tertentu.

---

# PRD 01E — PERMISSION MANAGEMENT

## 25. Permission

Entity:

```text
permissions
```

Fields:

```text
id
module
resource
action
name
description
created_at
updated_at
```

Format permission:

```text
module.resource.action
```

Contoh:

```text
users.view
users.create
users.update
users.delete

muzaki.view
muzaki.create
muzaki.update

payment.view
payment.create
payment.verify

distribution.view
distribution.create
distribution.approve

audit.view
report.export
```

---

## 26. Permission Assignment

Relationship:

```text
User
↓
Role
↓
Permission
```

User-specific permission override dapat ditambahkan pada fase berikutnya apabila diperlukan.

Versi awal menggunakan:

```text
Role Based Permission
```

---

## 27. Permission Enforcement

Permission wajib diverifikasi di backend.

Frontend hanya menggunakan permission untuk:

* menyembunyikan menu;
* menyembunyikan tombol;
* mengatur UX.

Frontend tidak boleh menjadi security boundary.

---

## 28. Authorization Flow

```text
Authenticated User
↓
Resolve Organization
↓
Resolve Roles
↓
Resolve Permissions
↓
Check Resource Access
↓
Allow / Deny
```

---

## 29. Resource Ownership

Selain permission, beberapa resource dapat menggunakan ownership rule.

Contoh:

```text
User A
↓
Organization A
↓
Data Organization A
```

User tidak boleh mengakses data organisasi lain walaupun mengetahui ULID resource tersebut.

---

# PRD 01F — USER & ACCESS MANAGEMENT

## 30. User Management

Administrator dapat:

* melihat user;
* membuat user;
* mengubah profil;
* mengubah role;
* menonaktifkan user;
* mengaktifkan kembali user;
* suspend user;
* revoke session.

Administrator tidak boleh melihat password user.

---

## 31. User Profile

User dapat mengubah:

```text
name
avatar
email
phone
```

Perubahan email dapat membutuhkan verifikasi ulang.

---

## 32. Role Assignment

Perubahan role harus:

* membutuhkan permission;
* dicatat pada audit log;
* berlaku sesuai organization scope.

Flow:

```text
Admin
↓
Select User
↓
Assign Role
↓
Validate Authorization
↓
Save
↓
Audit Log
```

---

## 33. User Deactivation

User tidak langsung dihapus.

Status:

```text
inactive
```

atau:

```text
suspended
```

Session aktif harus dapat dicabut.

Historical data tetap mempertahankan actor reference.

---

## 34. User Deletion

User yang memiliki historical transaction tidak boleh dihapus secara permanen.

Gunakan:

```text
soft delete
```

atau:

```text
deactivation
```

sesuai kebijakan final.

---

# PRD 01G — API SPECIFICATION

## 35. Authentication Endpoints

```text
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/me

POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
POST   /api/v1/auth/change-password
```

---

## 36. Session Endpoints

```text
GET    /api/v1/auth/sessions
DELETE /api/v1/auth/sessions/{id}
DELETE /api/v1/auth/sessions
```

Delete all sessions endpoint tidak boleh menghapus session aktif kecuali user secara eksplisit meminta logout all.

---

## 37. User Endpoints

```text
GET    /api/v1/users
POST   /api/v1/users
GET    /api/v1/users/{id}
PATCH  /api/v1/users/{id}
```

Status action:

```text
POST /api/v1/users/{id}/activate
POST /api/v1/users/{id}/deactivate
POST /api/v1/users/{id}/suspend
POST /api/v1/users/{id}/unlock
```

---

## 38. Role Endpoints

```text
GET    /api/v1/roles
POST   /api/v1/roles
GET    /api/v1/roles/{id}
PATCH  /api/v1/roles/{id}
```

---

## 39. Permission Endpoints

```text
GET /api/v1/permissions
```

Permission system-level tidak boleh dapat diubah sembarang user.

---

# PRD 01H — AUDIT & SECURITY

## 40. Audit Events

Minimal event:

```text
login
login_failed
logout
password_changed
password_reset_requested
password_reset_completed

user_created
user_updated
user_activated
user_deactivated
user_suspended

role_assigned
role_removed

session_revoked
account_locked
account_unlocked
```

---

## 41. Audit Metadata

Authentication audit minimal menyimpan:

```text
actor_id
target_user_id
action
ip_address
user_agent
request_id
created_at
```

Password, token, secret, OTP, dan credential tidak boleh dicatat.

---

## 42. Security Notification

Sistem dapat mengirim notifikasi untuk:

* password changed;
* account locked;
* login from new device;
* administrator revoked session;
* role changed.

Channel notification mengikuti konfigurasi sistem.

---

# PRD 01I — UI REQUIREMENTS

## 43. Login Page

Frontend menggunakan branding ZETRA di atas komponen ZETRA yang sudah ada.

Halaman login minimal:

* Email
* Password
* Remember me
* Login
* Forgot password

Tidak menampilkan informasi internal sistem.

---

## 44. Forgot Password Page

Fields:

```text
email
```

Response selalu generik.

Contoh:

```text
Jika alamat email terdaftar, instruksi reset password akan dikirim.
```

---

## 45. Reset Password Page

Fields:

```text
new_password
confirm_password
```

---

## 46. User Management Page

Admin dapat melihat:

* nama;
* email;
* organization;
* role;
* status;
* last login.

Action:

* create;
* edit;
* activate;
* deactivate;
* suspend;
* revoke session.

---

## 47. Role Management Page

Minimal:

* Role list
* Role detail
* Permission assignment
* Active status

---

# PRD 01J — VALIDATION & BUSINESS RULES

## 48. Validation Rules

Email:

* required;
* valid format;
* unique sesuai scope yang ditentukan.

Password:

* minimum sesuai policy;
* confirmation required.

Role:

* harus aktif;
* harus berada pada organization/system scope yang valid.

---

## 49. Business Rules

1. User suspended tidak dapat login.
2. User inactive tidak dapat login.
3. User locked tidak dapat login sampai unlock.
4. User tidak dapat menghapus dirinya sendiri.
5. User tidak boleh menghapus role terakhir Super Admin apabila sistem mensyaratkan minimal satu Super Admin.
6. Role system tidak boleh dihapus sembarangan.
7. Permission enforcement wajib dilakukan backend.
8. Perubahan role wajib dicatat dalam audit.
9. Password tidak boleh masuk log atau audit.
10. Session dapat dicabut ketika account dinonaktifkan.

---

# PRD 01K — ACCEPTANCE CRITERIA

* [x] User dapat login.
* [x] User dapat logout.
* [x] User dapat meminta reset password.
* [x] User dapat mengganti password.
* [x] Password tersimpan dalam hash.
* [x] Login memiliki rate limiting.
* [x] Account lock berfungsi.
* [x] User status diterapkan.
* [x] RBAC berfungsi.
* [x] Permission diverifikasi backend.
* [x] Organization isolation diterapkan.
* [x] Role assignment tercatat.
* [x] Security event tercatat.
* [x] Session dapat direvoke.
* [x] API `/auth/me` berfungsi.
* [x] User management tersedia.
* [x] Role management tersedia.
* [x] Audit trail tersedia.
* [x] Sensitive credential tidak masuk log.
* [x] Automated test tersedia.

---

# PRD 01L — DEFINITION OF DONE

Modul Authentication & Authorization dianggap selesai apabila:

1. Authentication berhasil digunakan React frontend.
2. Laravel Sanctum terintegrasi.
3. Login dan logout berfungsi.
4. Password reset berfungsi.
5. User status berfungsi.
6. RBAC berfungsi.
7. Permission enforcement berjalan di backend.
8. Organization isolation telah diuji.
9. Session management tersedia.
10. Failed login protection aktif.
11. Audit security event tersedia.
12. Automated test berhasil.
13. Tidak ada credential sensitif yang terekspos melalui API, log, atau audit.
14. Modul siap digunakan oleh seluruh modul berikutnya.

---

# END OF PRD MODULE 01 — AUTHENTICATION & AUTHORIZATION
