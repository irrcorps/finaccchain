# Pedoman: Membuat TiDB Cloud Starter untuk FinAccChain

Panduan ini hanya untuk **membuat database online (TiDB Cloud Starter)** yang dipakai
FinAccChain di Render. Tidak ada perubahan kode/skema — file yang dipakai sudah
tersedia di [`database/production_schema.sql`](../database/production_schema.sql)
dan [`database/demo_seed.sql`](../database/demo_seed.sql).

> Catatan: pembuatan akun harus dilakukan sendiri oleh pemilik proyek (login/OAuth
> pihak ketiga tidak bisa diwakilkan). Panduan ini memandu langkah demi langkah.

## 1. Daftar/Masuk TiDB Cloud

1. Buka **https://tidbcloud.com** → **Sign up** (bisa pakai akun Google/GitHub) atau **Log in** bila sudah punya akun.
2. Verifikasi email bila diminta.

## 2. Buat Cluster Starter (gratis)

1. Di dashboard, klik **Create Cluster**.
2. Pilih tier **Starter** (free tier — cukup untuk prototipe riset ini).
3. Pilih **Cloud Provider** & **Region** yang terdekat/tersedia (mis. AWS Singapore `ap-southeast-1`).
4. Beri nama cluster, mis. `finaccchain-cluster`.
5. Klik **Create** dan tunggu 1–3 menit sampai status cluster **Available**.

## 3. Buat Password & Ambil Connection Info

1. Masuk ke cluster → tab **Connect**.
2. Klik **Generate Password** (atau **Reset Password**) — **catat/simpan password ini**, hanya ditampilkan sekali.
3. Pilih connection type **General** / **Public** dan catat nilai berikut (nanti dipakai sebagai environment variable di Render):

   | Ambil dari halaman Connect | → Environment Variable Render |
   |---|---|
   | Host | `DB_HOST` |
   | Port (biasanya `4000`) | `DB_PORT` |
   | User (format `xxxxxxx.root`) | `DB_USERNAME` |
   | Password yang tadi digenerate | `DB_PASSWORD` |

4. TiDB Cloud Serverless/Starter **mewajibkan TLS** — biarkan `DB_SSL_MODE=require` (sudah default di `render.yaml`), tidak perlu upload file CA sendiri (`DB_SSL_CA` dikosongkan).

## 4. Izinkan Akses dari Render (Network Access)

1. Masih di tab **Connect** (atau menu **Networking**), cari bagian **Traffic Filter / IP Access List**.
2. Karena Render Free tidak memberi IP statis, tambahkan aturan **Allow Access from Anywhere** (`0.0.0.0/0`).
   - Ini standar untuk PaaS gratis seperti Render/Vercel yang IP keluarnya dinamis. Keamanan tetap terjaga karena akses tetap wajib user+password+TLS.

## 5. Buat Database

Pilih salah satu cara:

**A. Lewat TiDB Cloud SQL Editor (di browser, tanpa install apa pun)**
1. Buka cluster → **SQL Editor** (atau **Chat2Query**).
2. Jalankan:
   ```sql
   CREATE DATABASE finaccchain CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Pilih database `finaccchain` sebagai context, lalu **copy-paste isi** `database/production_schema.sql`, jalankan (Run).
4. Ulangi untuk isi `database/demo_seed.sql`, jalankan.

**B. Lewat `mysql` client di komputer Anda** (perlu `mysql` CLI terpasang)
```bash
mysql --comments -h <HOST> -P 4000 -u <USERNAME> -p --ssl-mode=REQUIRED -e "CREATE DATABASE finaccchain CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

mysql --comments -h <HOST> -P 4000 -u <USERNAME> -p --ssl-mode=REQUIRED -D finaccchain < database/production_schema.sql

mysql --comments -h <HOST> -P 4000 -u <USERNAME> -p --ssl-mode=REQUIRED -D finaccchain < database/demo_seed.sql
```
Ganti `<HOST>` dan `<USERNAME>` sesuai langkah 3. Masukkan password saat diminta.

## 6. Verifikasi Import

Jalankan di SQL Editor / `mysql` client yang sama:
```sql
SELECT COUNT(*) FROM users;         -- harus 5
SELECT COUNT(*) FROM msmes;         -- harus 3
SELECT COUNT(*) FROM transactions;  -- harus 14
SELECT COUNT(*) FROM audit_trails;  -- harus 18
SELECT COUNT(*) FROM expert_validations; -- harus 2
```
Jika kelima angka di atas cocok, migrasi database **berhasil**.

## 7. Kredensial Siap Dipakai di Render

Simpan 4 nilai berikut untuk diisi sebagai Environment Variable di Render (lihat [README bagian "Deployment Cloud"](../README.md#deployment-cloud-render--tidb-cloud)):

```
DB_HOST=<host dari langkah 3>
DB_PORT=4000
DB_DATABASE=finaccchain
DB_USERNAME=<user dari langkah 3>
DB_PASSWORD=<password dari langkah 3>
```

Selesai — database online siap dipakai. Lanjut ke pembuatan Web Service di Render.
