# Studio Ruang — Graphic Design Service

Fondasi aplikasi PHP native untuk studio desain grafis. Antarmuka dan konten berbahasa Indonesia, responsif, dengan arah visual editorial: warm white `#F7F7F4`, charcoal `#202020`, dan aksen kuning `#F2C94C`.

## Preview Vercel tanpa database

Deployment Vercel menggunakan `api/index.php` → `public/demo.php`, yaitu mode pratinjau tanpa koneksi database. Data pesanan disimpan sementara di session browser dan tidak perlu mengatur MySQL. Folder `database/schema.sql` dan aplikasi normal tetap tersedia untuk tahap sesudah demo disetujui.

1. Impor folder proyek ini ke GitHub, lalu impor repository tersebut dari Vercel.
2. Root Directory: `outputs/creative-studio` (atau folder proyek jika repository berisi isi folder ini langsung).
3. Deploy. Runtime PHP ditetapkan di `vercel.json`.
4. Akun demo: admin `a` / `a`; customer `customer` / `customer`.

> Data demo bersifat sementara dan spesifik sesi. Fitur daftar akun menampilkan keterangan bahwa database belum diaktifkan. Akun demo admin bersifat publik, jangan gunakan untuk data nyata atau produksi.

## Isi proyek

- Landing page, layanan, portfolio, pendaftaran dan login.
- Dashboard customer beserta daftar dan detail pesanan miliknya.
- Dashboard admin dengan seluruh pesanan dan perubahan status.
- Form brief, harga awal tersimpan pada pesanan, dan tautan WhatsApp.
- PDO dengan prepared statements, hashing password, session cookie HttpOnly/SameSite, regenerasi ID saat autentikasi, CSRF token, role checks, dan pembatasan query pesanan customer.

## Kebutuhan

- PHP 8.1+ dengan ekstensi `pdo_mysql` dan `mbstring`.
- MySQL 8.0+ atau MariaDB 10.5+.
- Browser dengan koneksi internet untuk Tailwind Play CDN (hanya nyaman untuk demo lokal).

## Setup lokal

1. Buat database dan tabel dengan menjalankan `database/schema.sql` pada MySQL. Seed hanya memasukkan layanan serta portfolio contoh; ia sengaja tidak membuat pengguna atau kata sandi.
2. Salin `.env.example` menjadi `.env`; isi koneksi database lokal dan nomor WhatsApp studio. Tidak ada kredensial nyata dalam contoh.
3. Untuk aplikasi lokal dengan MySQL, jalankan `php -S 127.0.0.1:8000 -t public public/router.php`. Untuk melihat mode demo tanpa database yang sama dengan Vercel, set `APP_DEMO_MODE=true` pada `.env` lalu jalankan server yang sama.
4. Buka `http://127.0.0.1:8000`, lalu buat akun customer melalui halaman Daftar.
5. Login admin demo memakai username `a` dan kata sandi `a`; admin demo tidak disimpan di database. Untuk mencoba customer, daftar akun seperti biasa.

Untuk menjalankan pemeriksaan logika ringan: `php tests/smoke.php`. Untuk lint file PHP: `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }` (PowerShell).

## Rute utama

| Rute | Akses |
|---|---|
| `/`, `/services`, `/portfolio` | Publik |
| `/register`, `/login` | Tamu |
| `/dashboard`, `/orders`, `/orders/new` | Customer dan admin yang login |
| `/orders/{id}` | Pemilik pesanan saja; admin dapat melihat lewat daftar admin |
| `/admin/orders/{id}` | Admin |

## Keamanan dan batas demo

- Peran tidak dapat dipilih di form pendaftaran; akun baru selalu `customer`. Admin `a` / `a` adalah login hardcoded khusus demo lokal, tidak disimpan di database.
- Semua mutasi memakai token CSRF. Semua teks pengguna ditampilkan dengan escaping HTML. Query yang membawa input menggunakan PDO prepared statements.
- Customer hanya memperoleh order melalui query dengan `user_id` sesi. Ketidakcocokan ownership menghasilkan 404.
- `APP_DEBUG` tersedia sebagai konfigurasi, namun detail exception tidak ditampilkan kepada browser.
- **Batas preview:** kredensial admin `a` / `a` dan customer `customer` / `customer` adalah akun demo publik, jangan dipakai untuk data nyata. Pada Vercel demo ini tanpa database dan data pesanan hanya sementara di sesi; pendaftaran ditampilkan nonaktif. Sebelum beralih ke produksi, matikan mode demo, pilih database, gunakan akun admin yang dikelola dengan hash password, dan tambahkan rate limiting/brute-force protection. Tailwind Play CDN juga perlu diganti build Tailwind lokal dengan Content Security Policy. Tambahkan verifikasi email, reset password, audit log, backup, monitoring, migrasi terkelola, dan kebijakan privasi/retensi. Cookie secure aktif bila server melaporkan HTTPS.
- Nomor WhatsApp contoh hanyalah placeholder format, bukan nomor kontak yang diklaim valid. Ganti di `.env`.

## Struktur

```text
public/       Front controller, router lokal, aset publik
app/          Bootstrap, akses data, helper, dan template server-rendered
database/     Schema dan data katalog demo
tests/        Pemeriksaan smoke ringan
```
