# FinAccChain — Smart Financial Accountability for MSMEs

[![PHP Lint](https://github.com/irrcorps/finaccchain/actions/workflows/lint.yml/badge.svg)](https://github.com/irrcorps/finaccchain/actions/workflows/lint.yml)

**Functional research prototype (TKT 3)** dibangun untuk mendukung penelitian PDP 2026:

> **"Perancangan Model Integrasi Smart Contract Berbasis Fintech untuk Penguatan
> Akuntabilitas Keuangan UMKM dalam Ekosistem Hilirisasi Ekonomi Digital di Kota Medan."**

## ⚠️ Disclaimer Penelitian

- Ini adalah **prototipe pembuktian konsep (proof-of-concept) TKT 3**, BUKAN sistem keuangan produksi.
- Lapisan "smart contract" **disimulasikan** melalui rule engine deterministik (`core/RuleEngine.php`) yang menghasilkan hash SHA-256 berantai dan audit trail append-only (`core/HashChain.php`) — **bukan** jaringan blockchain (mainnet/testnet) sungguhan.
- Seluruh kanal fintech (transfer bank, QRIS, e-wallet, pembiayaan digital) **disimulasikan**; sistem **tidak** terhubung ke API/payment gateway fintech nyata.
- Indeks Akuntabilitas Keuangan adalah **formula riset yang dapat dikonfigurasi peneliti** (`admin/settings.php`), bukan instrumen baku yang sudah tervalidasi secara ilmiah — klaim "tervalidasi" hanya relevan setelah data Expert Judgment (`validator/expert_validation.php`) benar-benar dimasukkan.

## Konteks Riset

| Aspek | Keterangan |
|---|---|
| Peran pengguna | Admin/Peneliti, UMKM, Validator/Auditor |
| Model inti | Transaksi fintech (simulasi) → Rule Engine → Klasifikasi Akuntansi → Hash-Chain Audit Trail → Indeks Akuntabilitas |
| Status | TKT 3 — pembuktian konsep fungsional di lingkungan riset/lab |
| Batasan | Belum diuji operasional nyata; belum terhubung blockchain/API fintech sungguhan |

## Arsitektur

- **Stack**: PHP native (modular, tanpa framework berat), MySQL/MariaDB, Bootstrap 5, FontAwesome, ApexCharts, DomPDF (via Composer).
- **Pola**: front-controller ringan (`core/bootstrap.php`) di-`require` oleh setiap halaman; tidak ada routing tunggal — setiap file PHP adalah satu halaman/aksi (pola yang sama dipakai proyek BIMA lain di repo ini).
- **Lapisan inti** (`/core`):
  - `Database.php` — PDO singleton.
  - `Auth.php` — register/login/logout/reset password, CSRF, session guard.
  - `RuleEngine.php` — pipeline validasi "smart contract" (completeness → duplicate → authorization → evidence → threshold → classification → validator approval → finalize).
  - `HashChain.php` — pembuatan & verifikasi rantai hash SHA-256 (audit trail).
  - `Accounting.php` — chart of accounts, klasifikasi debit/kredit, posting jurnal, buku besar, arus kas.
  - `Accountability.php` — perhitungan 8 indikator akuntabilitas → indeks 0–100 (bobot dikonfigurasi via `research_settings`).
  - `TransactionService.php` — pembuatan transaksi manual/CSV/simulasi fintech + upload bukti.
  - `ReportBuilder.php` — HTML generator untuk 9 jenis laporan, dirender ke PDF oleh DomPDF (`reports/generate.php`).

## Database

Skema lengkap: [`database/schema.sql`](database/schema.sql) (18 tabel utama + `password_resets`, dengan foreign key & index).
Data referensi + akun demo: [`database/seed_master.sql`](database/seed_master.sql).
Seeder transaksi demo (dijalankan **melalui** RuleEngine/Accounting/HashChain/Accountability yang sesungguhnya, bukan INSERT mentah): [`database/seed_demo_transactions.php`](database/seed_demo_transactions.php).

### Instalasi Database

```bash
mysql -u root < database/schema.sql
mysql -u root < database/seed_master.sql
php database/seed_demo_transactions.php
```

Sesuaikan kredensial di `config/database.php` bila MySQL Anda memakai user/password berbeda.

## Instalasi & Menjalankan

1. Pastikan PHP ≥ 7.4 (mendukung 8.x) dan MySQL/MariaDB aktif (mis. XAMPP).
2. `composer install` (mengunduh DomPDF ke `/vendor`).
3. Jalankan tiga perintah instalasi database di atas.
4. Jalankan server:
   - Dev server bawaan PHP: `php -S localhost:8000` dari root proyek, lalu buka `http://localhost:8000/index.php`.
   - Atau taruh folder ini di `htdocs` XAMPP dan akses via Apache — URL dasar dideteksi otomatis (`config/app.php`).
5. Folder `uploads/evidence` harus writable oleh proses PHP (dibuat otomatis saat upload pertama).

## Akun Demo

Password untuk **semua** akun demo: `Demo@12345`

| Peran | Email |
|---|---|
| Admin/Peneliti | admin@finaccchain.demo |
| UMKM — Kedai Kopi Deli | umkm1@finaccchain.demo |
| UMKM — Konveksi Medan Jaya | umkm2@finaccchain.demo |
| UMKM — Toko Kerupuk Belawan | umkm3@finaccchain.demo |
| Validator/Auditor | validator@finaccchain.demo |

Seluruh data demo ditandai `is_demo = 1` pada tabel `users`/`msmes`/`transactions` dan **terpisah secara eksplisit** dari dataset riset sungguhan (kuesioner/wawancara/validasi ahli yang dientri peneliti melalui modul Riset).

## Struktur Folder

```
/admin          Halaman Admin/Peneliti (users, umkm, validator, rules, transactions, settings, research dashboard)
/auth           Login, register (UMKM), logout, forgot/reset password
/config         Konfigurasi aplikasi & database
/core           Lapisan inti (rule engine, hash chain, akuntansi, akuntabilitas, auth, report builder)
/database       Schema SQL, seed data, seeder transaksi demo
/includes       Layout bersama (guest & app shell: header/footer/sidebar)
/msme           Halaman UMKM (dashboard, profil, transaksi, fintech simulation, akuntansi, akuntabilitas, chain verification)
/reports        Generator laporan PDF (9 jenis)
/research       Kuesioner, entri wawancara, ringkasan validasi ahli, ekspor dataset CSV
/validator      Halaman Validator/Auditor (verifikasi, audit trail, chain verification, expert judgment)
/uploads/evidence  Berkas bukti transaksi yang diunggah pengguna
```

## Fitur yang Sudah Selesai

- Landing page (navbar, hero, overview, problem, cara kerja, fitur, manfaat akuntabilitas, workflow peran, info riset/prototipe, FAQ, footer).
- Autentikasi lengkap: register (UMKM), login, logout, forgot/reset password, RBAC 3 peran, CSRF, prepared statements, `password_hash`/`password_verify`.
- Profil UMKM lengkap (12 field sesuai spesifikasi riset).
- Transaksi keuangan multi-jenis (9 tipe), input manual, impor CSV, dan simulasi transaksi fintech (4 kanal) — semuanya melalui rule engine yang sama.
- Rule engine "smart contract" 7 langkah, log per-langkah, status pipeline (Draft/Pending/Validated/Rejected/Recorded/Reversed).
- Hash-chain audit trail (SHA-256 berantai) + halaman verifikasi visual (Integrity Verified/Warning) untuk UMKM & Validator.
- Modul akuntansi: chart of accounts, jurnal otomatis, buku besar per akun, ringkasan arus kas.
- Accountability Engine: 8 indikator, bobot dikonfigurasi peneliti, radar/bar chart, kelemahan & rekomendasi otomatis.
- Dashboard UMKM & Dashboard Peneliti/Riset dengan ApexCharts (income vs expense, channel, status, tren bulanan, radar akuntabilitas, profil metode akuntansi/fintech, gap akuntabilitas).
- Modul Expert Validation (9 kriteria skala 1–5, indeks validasi, matriks revisi, komentar).
- Modul Riset: kuesioner dinamis (buat pertanyaan, isi sebagai UMKM/Validator, entri wawancara oleh admin), ekspor dataset CSV (5 dataset).
- 9 jenis laporan PDF (DomPDF) siap cetak untuk dokumentasi/publikasi.
- Reversal/adjustment entry (transaksi tervalidasi tidak pernah dihapus langsung).
- UI responsif (sidebar desktop + offcanvas mobile), empty state, pagination, konfirmasi aksi, toast/alert, tabel modern, print-friendly report layout.
- Demo data eksplisit (1 admin, 3 UMKM, 1 validator, transaksi realistis mencakup semua status pipeline) — dihasilkan lewat rule engine sungguhan sebagai smoke test integrasi.

## Batasan Riset (Limitations)

1. Tidak ada koneksi ke jaringan blockchain nyata (public/private) — hash-chain tersimpan di MySQL.
2. Tidak ada integrasi API fintech/payment gateway sungguhan — seluruh transaksi fintech adalah data simulasi berlabel jelas.
3. Formula indeks akuntabilitas adalah rancangan awal peneliti; validitas ilmiahnya bergantung pada data Expert Judgment yang benar-benar dientri.
4. Ambang nominal (`evidence_required_amount`, `validator_required_amount`) & bobot indikator dapat diubah admin (`admin/settings.php`) — nilai default bersifat ilustratif, bukan hasil kalibrasi lapangan.
5. Server email belum terpasang; reset password menampilkan tautan langsung di layar untuk keperluan demo (bukan alur produksi).
6. Belum ada pengujian keamanan/penetrasi formal; ditujukan untuk lingkungan riset/lab, bukan produksi.
7. Modul akuntansi sengaja disederhanakan (tanpa multi-currency, sub-ledger, atau fitur ERP lanjutan).

## Tingkat Kesiapterapan Teknologi (TKT)

**TKT 3** — pembuktian konsep fungsional di lingkungan riset/lab. Prototipe ini mendemonstrasikan alur model secara end-to-end (fintech → rule engine → akuntansi → hash-chain → akuntabilitas → laporan) namun belum divalidasi pada lingkungan operasional UMKM sesungguhnya.
