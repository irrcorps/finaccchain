# Pedoman Lengkap: Membuat Database Online TiDB Cloud Starter untuk FinAccChain

Panduan ini untuk yang **baru pertama kali** memakai TiDB Cloud. Ditulis selangkah demi
selangkah, termasuk penjelasan istilah yang mungkin membingungkan. Tidak ada kode/skema
FinAccChain yang diubah — file yang dipakai sudah tersedia:

- [`database/production_schema.sql`](../database/production_schema.sql) — struktur 18 tabel + FK/index.
- [`database/demo_seed.sql`](../database/demo_seed.sql) — data referensi + akun demo + contoh transaksi.

> Istilah "cluster" di beberapa dokumentasi lama TiDB sama dengan "**instance**" di
> tampilan TiDB Cloud saat ini — keduanya merujuk pada hal yang sama: satu database
> TiDB yang Anda kelola.

---

## Bagian A — Membuat Akun & Instance

### A1. Daftar Akun

1. Buka **https://tidbcloud.com** di browser.
2. Klik **Start for Free** (atau **Sign In** bila sudah pernah daftar).
3. Daftar pakai salah satu cara:
   - Email + kata sandi (Anda yang atur sendiri), **atau**
   - Sign up with Google / GitHub / Microsoft (lebih cepat, tidak perlu ingat kata sandi baru).
4. Cek email Anda bila diminta verifikasi, klik link verifikasinya.
5. Setelah berhasil, Anda akan diarahkan ke halaman **My TiDB** — ini adalah dashboard utama TiDB Cloud.

> Tier **Starter** yang kita pakai bersifat gratis. Bila di tengah proses diminta info
> pembayaran, isi seadanya sesuai instruksi di layar (umumnya tier Starter tidak
> memerlukan kartu kredit) — jangan lanjut bila diminta memasukkan data kartu, cukup
> kabari saya dan kita cari cara lain.

### A2. Buat Instance Starter

> **Jalan pintas**: akun baru otomatis mendapat 1 instance gratis bernama `Instance0`.
> Kalau Anda melihatnya sudah ada di halaman **My TiDB**, langsung lanjut ke **Bagian B**
> memakai instance itu — tidak perlu membuat instance baru lagi.

Kalau ingin membuat instance sendiri (nama lebih rapi, mis. `finaccchain`):

1. Di halaman **My TiDB**, klik tombol **Create Resource** (biasanya di kanan atas atau tengah halaman).
2. Di halaman **Create Resource**, opsi **Starter** sudah otomatis terpilih (jangan ganti ke Dedicated/Essential — itu berbayar).
3. Isi:
   - **Name**: `finaccchain` (bebas, ini hanya label untuk Anda).
   - **Cloud Provider**: biarkan default (AWS).
   - **Region**: pilih yang terdekat, mis. Singapore (`ap-southeast-1`) untuk latensi terbaik dari Indonesia.
4. Klik **Create**.
5. Tunggu ± 30 detik sampai status instance jadi **Available** (hijau).

---

## Bagian B — Ambil Kredensial Koneksi

1. Di halaman **My TiDB**, klik **nama instance** Anda (`Instance0` atau `finaccchain`) untuk masuk ke halaman overview-nya.
2. Klik tombol **Connect** di pojok kanan atas.
3. Sebuah dialog "Connect" akan muncul. Di dalamnya:
   - Klik **Generate Password**. Sebuah password acak akan muncul.
   - **PENTING: salin & simpan password ini sekarang juga** (misalnya ke Notepad) — password ini **hanya ditampilkan satu kali** dan tidak bisa dilihat ulang (kalau lupa, Anda harus generate password baru).
   - Pada dropdown **Connect With**, pilih **MySQL CLI** (atau General/Public) — akan muncul contoh perintah koneksi yang berisi host, port, dan username lengkap.
4. Dari contoh perintah yang muncul, catat 3 nilai berikut (biasanya terlihat jelas di dalam perintah contohnya):

   | Yang Anda cari di dialog Connect | Contoh bentuknya | Nanti jadi env var Render |
   |---|---|---|
   | Host / Endpoint | `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` | `DB_HOST` |
   | Port | `4000` (selalu 4000 untuk Starter) | `DB_PORT` |
   | User (**ada prefiks + `.root`**, wajib disalin utuh) | `xxxxxxxxxxxx.root` | `DB_USERNAME` |
   | Password | (yang Anda generate & simpan di langkah 3) | `DB_PASSWORD` |

   ⚠️ **Username-nya BUKAN cuma `root`** — TiDB Cloud Starter mewajibkan format `<kode-acak>.root` (dengan titik). Salin persis apa adanya dari dialog Connect, jangan diketik ulang manual.

---

## Bagian C — Buat Database & Import Skema

Ada 2 cara. **Pilih Cara 1 kalau Anda belum pernah pakai `mysql` command-line** (lebih mudah, semua lewat browser).

### Cara 1 (disarankan untuk pemula): SQL Editor di Browser

1. Di halaman overview instance, cari menu **SQL Editor** di panel navigasi kiri, lalu klik.
2. Jika ada pop-up soal AI/Chat2Query meminta izin, boleh di-skip/tutup — kita tidak perlu fitur AI-nya.
3. Di kotak editor SQL, ketik lalu jalankan (tombol **Run**, atau tekan `Ctrl+Enter`):
   ```sql
   CREATE DATABASE finaccchain CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Pastikan database aktif Anda sudah `finaccchain` (biasanya ada dropdown pemilih database di bagian atas SQL Editor — pilih `finaccchain`).
5. Buka file [`database/production_schema.sql`](../database/production_schema.sql) di komputer Anda (pakai Notepad/VS Code), **copy semua isinya**, **paste** ke SQL Editor, lalu klik **Run**.
   - Ini akan membuat 18 tabel + relasi (foreign key) + index. Proses ini beberapa detik.
6. Ulangi langkah yang sama untuk [`database/demo_seed.sql`](../database/demo_seed.sql): copy semua isi file → paste ke SQL Editor (kosongkan dulu editor sebelumnya) → **Run**.
   - Ini mengisi akun demo (1 admin, 3 UMKM, 1 validator), data referensi, dan contoh transaksi.

### Cara 2 (alternatif): `mysql` command-line client

Hanya jika Anda sudah punya `mysql` CLI terpasang di komputer. Ganti `<HOST>` dan `<USERNAME>` sesuai Bagian B (username **harus** memakai tanda kutip karena mengandung titik):

```bash
mysql --comments -h <HOST> -P 4000 -u '<USERNAME>' -p --ssl-mode=REQUIRED \
  -e "CREATE DATABASE finaccchain CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

mysql --comments -h <HOST> -P 4000 -u '<USERNAME>' -p --ssl-mode=REQUIRED \
  -D finaccchain < database/production_schema.sql

mysql --comments -h <HOST> -P 4000 -u '<USERNAME>' -p --ssl-mode=REQUIRED \
  -D finaccchain < database/demo_seed.sql
```
Saat diminta `Enter password:`, tempel password dari Bagian B.

---

## Bagian D — Verifikasi Import Berhasil

Jalankan query berikut (di SQL Editor atau `mysql` client yang sama) satu per satu, lalu cocokkan hasilnya:

```sql
SELECT COUNT(*) FROM users;              -- harus: 5
SELECT COUNT(*) FROM msmes;              -- harus: 3
SELECT COUNT(*) FROM transactions;       -- harus: 14
SELECT COUNT(*) FROM audit_trails;       -- harus: 18
SELECT COUNT(*) FROM expert_validations; -- harus: 2
```

Kalau kelima angka di atas cocok semua → **import database berhasil**, lanjut ke Bagian E.

Kalau ada error saat Bagian C (mis. "table already exists"): jalankan `DROP DATABASE finaccchain;` lalu ulangi dari langkah CREATE DATABASE — aman dilakukan karena ini masih instance baru/kosong.

---

## Bagian E — Soal Akses Jaringan (Networking)

Berbeda dari sebagian tutorial lama: **instance Starter baru secara default SUDAH mengizinkan semua alamat IP** (ada rule otomatis bernama `Allow_all_public_connections`), jadi Render biasanya bisa langsung connect **tanpa** Anda perlu mengatur apa pun tambahan.

Cek untuk memastikan (opsional tapi disarankan):
1. Di halaman overview instance → panel kiri → **Settings** → **Networking**.
2. Pastikan **Public Endpoint** berstatus **Enabled**, dan di bagian **Authorized Networks** ada rule yang mengizinkan semua IP (`0.0.0.0/0`, biasanya bernama `Allow_all_public_connections`).
3. Kalau rule itu ternyata sudah dihapus/tidak ada, klik **Add rule**, lalu masukkan `0.0.0.0/0` (izinkan semua) — ini wajar untuk PaaS gratis seperti Render yang IP keluarnya berubah-ubah; keamanan tetap terjaga lewat kombinasi username+password+TLS.

---

## Bagian F — Kredensial Final untuk Render

Kumpulkan 4 nilai ini (dari Bagian B) untuk nanti diisi sebagai **Environment Variable** di Render — lihat [README bagian "Deployment Cloud"](../README.md#deployment-cloud-render--tidb-cloud):

```
DB_HOST=<host dari dialog Connect>
DB_PORT=4000
DB_DATABASE=finaccchain
DB_USERNAME=<username lengkap dengan prefiks + .root>
DB_PASSWORD=<password yang di-generate di Bagian B>
```

Simpan 4 baris ini di tempat aman (mis. Notepad sementara) — **jangan** commit/upload ke GitHub.

---

## Troubleshooting Umum

| Gejala | Penyebab & Solusi |
|---|---|
| `Access denied for user` | Username salah — pastikan pakai format lengkap `<prefiks>.root`, bukan cuma `root`. |
| Password lupa/hilang | Buka dialog **Connect** lagi → klik **Generate Password** untuk membuat password baru (yang lama otomatis tidak berlaku). |
| Koneksi timeout dari Render | Cek Bagian E — pastikan Public Endpoint aktif dan ada rule `0.0.0.0/0`. |
| `SSL connection error` | TiDB Cloud mewajibkan TLS. Untuk `mysql` CLI tambahkan `--ssl-mode=REQUIRED`; aplikasi FinAccChain sudah otomatis mengaktifkan ini lewat env var `DB_SSL_MODE=require`. |
| Query `production_schema.sql` gagal di tengah jalan | Pastikan Anda menjalankan `CREATE DATABASE` dan memilih database `finaccchain` terlebih dahulu sebelum paste isi file. |

Selesai — database online siap. Lanjut ke pembuatan Web Service di Render (lihat README).
