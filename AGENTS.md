# Bantu Daftarin Engineering Guide

## Source of truth

Implementasi mengikuti urutan prioritas berikut:

1. instruksi eksplisit pengguna pada task aktif;
2. master prompt proyek;
3. AGENTS.md;
4. aturan bisnis, state machine, dan dokumentasi di docs/;
5. implementasi yang sudah ada.

Skill Codex, termasuk antislop, adalah pedoman kualitas implementasi dan bukan
sumber aturan bisnis.

Jika terdapat konflik antara skill generik dan aturan proyek, aturan proyek
lebih tinggi prioritasnya.

Konflik atau ambiguitas bisnis tidak boleh diselesaikan dengan asumsi yang
memperluas scope. Tandai sebagai:

[BUSINESS CONFIRMATION REQUIRED]

Jangan meminta konfirmasi untuk keputusan teknis, layout, atau implementasi
yang dapat ditentukan secara aman dari source dan instruksi task.


## Scope lock

- MVP hanya NPWP Perseorangan dan NPWP Badan Usaha.
- Lapor Pajak tetap COMING_SOON dan harus ditolak backend.
- Tidak ada integrasi DJP/Coretax, OCR, AI, biometric, mobile app, API frontend
  terpisah, atau WebSocket wajib.
- Role MVP hanya CLIENT dan SUPER_ADMIN.
- Dokumen sensitif selalu private.
- Jangan menaruh upload sensitif di web root.
- Jangan membuat URL permanen publik untuk dokumen private.


## Implementation rules

- PostgreSQL pada 127.0.0.1:5432 adalah database aplikasi lokal dan testing.

- Laravel menggunakan:
  - connection / PDO: pgsql
  - schema: public
  - runtime database: bantu_daftarin_mvp
  - testing database: bantu_daftarin_mvp_test

- XAMPP tetap boleh dipakai untuk Apache/PHP, tetapi MariaDB/MySQL bukan runtime
  database aplikasi.

- Perubahan schema hanya melalui Laravel migration.

- Gunakan arsitektur project yang sudah ada:
  - Blade
  - Livewire
  - Alpine bila diperlukan
  - Eloquent
  - Form Request
  - Policy
  - Action / Service
  - Job / Notification

- Jangan memperkenalkan framework frontend, CSS framework, state-management
  library, atau dependency baru kecuali task benar-benar membutuhkannya.

- Entity yang tampil pada URL menggunakan public_id UUID/ULID, tetapi tetap
  wajib melewati policy / authorization yang sesuai.

- Semua state transition harus mengikuti rule/action yang authoritative dan
  mencatat data yang relevan seperti:
  - actor
  - timestamp
  - from state
  - to state
  - reason
  - history
  - audit event jika sensitif

- Jangan membuat transition langsung dari controller / view jika project sudah
  mempunyai workflow service/action yang authoritative.

- Jangan log:
  - password
  - OTP
  - secret
  - API key
  - callback token
  - isi dokumen
  - PII sensitif yang tidak perlu

- Gunakan synthetic data saja untuk testing dan development fixture.


## Security invariants

Security hardening yang sudah ada dianggap authoritative kecuali task secara
eksplisit meminta audit atau perubahan security.

Jangan melemahkan atau menghindari:

- authentication;
- authorization / policies;
- persistent client/admin middleware;
- email verification;
- OTP;
- session regeneration;
- throttling;
- CSRF;
- CSP;
- private-file authorization;
- encrypted PII;
- application/document locking;
- payment concurrency protection;
- Xendit webhook authentication;
- webhook idempotency;
- provider evidence handling;
- payment state authority;
- malware scanning;
- retention safeguards;
- production security checks.

UI atau responsive work tidak boleh mengubah business/security behavior hanya
agar implementasi menjadi lebih mudah.


## Business workflow invariants

Pertahankan state machine yang sudah authoritative.

Secara konseptual flow client adalah:

Data & dokumen
-> Pembayaran
-> Pemeriksaan
-> Proses eksternal
-> Hasil
-> Selesai

Submission/payment flow secara konseptual:

data dan dokumen lengkap
-> Kirim pengajuan
-> siap pembayaran
-> pilih metode pembayaran
-> Xendit
-> payment confirmation
-> admin review
-> proses berikutnya

Jangan mengubah ordering ini hanya karena kebutuhan UI.

Payment truth berasal dari provider/webhook yang authoritative.

Jangan membuat manual mark-paid atau fake production payment path.


## Change discipline

Jaga patch tetap terarah.

Sebelum mengubah source:

1. baca instruksi task;
2. baca file project yang relevan;
3. pahami implementasi existing;
4. cari reusable component/service/action terlebih dahulu;
5. identifikasi area regression-sensitive.

Jangan melakukan:

- destructive reset;
- checkout yang membuang perubahan pengguna;
- broad rewrite tanpa kebutuhan;
- refactor unrelated;
- migration unrelated;
- dependency upgrade unrelated;
- silent fallback yang mengubah behavior.

Pertahankan unrelated working-tree changes.

Perubahan yang belum dapat diverifikasi diberi status:

NOT VERIFIED

atau jika menyangkut bisnis:

[BUSINESS CONFIRMATION REQUIRED]


## Verification

Dari root aplikasi, verification dasar backend:

php artisan optimize:clear
php artisan test --no-coverage

Untuk perubahan schema, hanya terhadap test database:

php artisan migrate:fresh --seed --env=testing

Jangan pernah menjalankan migrate:fresh terhadap runtime / production
database.

optimize:clear diperlukan sebelum test agar override environment PHPUnit
memilih PostgreSQL testing yang terisolasi dan bukan configuration cache
database aplikasi.

Untuk perubahan frontend / Blade / Livewire / Tailwind / JavaScript:

npm run build
php artisan optimize:clear
php artisan test --no-coverage
vendor/bin/pint --test
git diff --check

Jika task hanya audit dan secara eksplisit melarang perubahan source, jangan
menjalankan build/test yang tidak memberikan nilai diagnostik.

Saat PostgreSQL lokal tidak tersedia:

- laporkan dengan jelas;
- jangan menyamarkan SQLite atau DBMS lain sebagai database aplikasi;
- tandai verification yang tidak dapat dilakukan.

Periksa sesuai scope perubahan:

- .env / secret leakage;
- private storage;
- route authorization;
- state transitions;
- webhook idempotency;
- migration;
- seeder;
- frontend asset build;
- desktop/mobile regression bila UI berubah.


<!-- antislop:start -->

## Antislop design and writing skills

Project ini menyediakan skill Codex lokal di:

.codex/skills/

Skill yang tersedia saat ini:


### Core

.codex/skills/antislop/

Files:

- ANTISLOP.md
- SKILL.md

Core antislop adalah filter dasar untuk pekerjaan yang berhubungan dengan:

- UI;
- visual design;
- mobile/responsive layout;
- copy;
- user-facing text;
- people imagery;
- design critique.

Untuk task tersebut, baca core antislop terlebih dahulu.


### UI / visual design

.codex/skills/antislop-ui/SKILL.md

Gunakan untuk:

- UI audit;
- UI redesign;
- visual hierarchy;
- cards;
- buttons;
- navigation;
- forms;
- dashboard;
- modal;
- empty state;
- status presentation;
- component composition;
- frontend visual polish.

Untuk task UI, load:

1. antislop
2. antislop-ui


### Mobile / responsive layout

.codex/skills/antislop-layoutmobile/SKILL.md

Gunakan untuk:

- mobile UI;
- responsive behavior;
- breakpoint strategy;
- mobile navigation;
- mobile information hierarchy;
- mobile forms;
- tables on small screens;
- mobile overflow;
- sticky mobile actions;
- viewport-specific layout.

Untuk task mobile/responsive, load:

1. antislop
2. antislop-ui
3. antislop-layoutmobile

Mobile skill tidak memberi izin untuk mengubah desktop jika task dibatasi pada
mobile.


### Copywriting

.codex/skills/antislop-copywriting/SKILL.md

Gunakan ketika task mencakup:

- UI copy;
- headings;
- labels;
- helper text;
- CTA text;
- empty states;
- error copy;
- marketing copy;
- user-facing explanation;
- broad copy audit.

Untuk task yang hanya memindahkan/layout existing copy tanpa mengubah teks,
skill copywriting tidak wajib.

Jika copy ikut diubah secara material, load:

1. antislop
2. skill visual yang relevan jika ada
3. antislop-copywriting


### Human / people representation

.codex/skills/antislop-human/SKILL.md

Folder ini juga dapat berisi utility seperti:

- contrast-check.py
- contrast-mcp.py

Gunakan skill human jika pekerjaan melibatkan:

- representasi manusia;
- portrait/avatar/person imagery;
- people-centric visual generation or selection;
- visual treatment manusia yang relevan dengan guideline skill.

Jangan load antislop-human secara otomatis untuk setiap task UI.

Utility script di dalam skill hanya digunakan jika benar-benar dibutuhkan oleh
task dan sesuai dengan petunjuk skill.


## Skill selection rules

Codex harus menentukan skill relevan dari jenis task dan langsung
menggunakannya.

Jangan menghentikan pekerjaan hanya untuk bertanya apakah antislop harus
diterapkan "selama" atau "setelah" pekerjaan.

Antislop diterapkan selama proses:

audit
-> reasoning
-> design decision
-> implementation
-> review

Gunakan kombinasi minimum yang relevan.


Examples:

### Backend-only task

Tidak perlu load skill visual hanya karena skill tersedia.


### General UI task

Load:

- antislop
- antislop-ui


### Mobile UI / responsive task

Load:

- antislop
- antislop-ui
- antislop-layoutmobile


### UI + copy rewrite

Load:

- antislop
- antislop-ui
- antislop-copywriting


### Mobile UI + copy rewrite

Load:

- antislop
- antislop-ui
- antislop-layoutmobile
- antislop-copywriting


### People-focused visual task

Load:

- antislop
- relevant visual skill
- antislop-human

Do not load every skill indiscriminately.


## Applying antislop

Antislop guidance must improve execution without overriding:

- business rules;
- security;
- authorization;
- state machine;
- project terminology;
- explicit task constraints.

For UI work, avoid generic AI-generated visual patterns unless justified by
the product.

Examples to scrutinize include:

- excessive nested cards;
- gratuitous pills;
- arbitrary gradients;
- glassmorphism;
- generic SaaS dashboard composition;
- decorative icons without information value;
- unnecessary hero sections;
- excessive border radius;
- excessive shadows;
- random accent blocks;
- bottom navigation added merely because the viewport is mobile;
- duplicating desktop UI into a separate mobile frontend.

Prefer:

- existing design language;
- meaningful hierarchy;
- restrained containers;
- clear primary actions;
- appropriate information density;
- responsive reuse of existing components;
- functional design decisions.

Do not redesign from zero unless the task explicitly requires it.


## Mobile-specific skill rule

For any comprehensive mobile audit or mobile redesign of BantuDaftarin, always
read:

- .codex/skills/antislop/ANTISLOP.md
- .codex/skills/antislop/SKILL.md
- .codex/skills/antislop-ui/SKILL.md
- .codex/skills/antislop-layoutmobile/SKILL.md

before making design decisions.

Load:

.codex/skills/antislop-copywriting/SKILL.md

only if user-facing text will be materially changed.

Do not use mobile redesign as justification to alter:

- backend behavior;
- payment/Xendit;
- state transitions;
- document rules;
- security;
- desktop design outside the task scope.

<!-- antislop:end -->