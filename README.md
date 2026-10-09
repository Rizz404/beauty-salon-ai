# Beauty Salon AI

Situs salon kecantikan interaktif dengan **AI Beautician**. Tamu menjelajahi salon seperti melangkah ke lobinya (layanan, stylist, fasilitas, informasi, janji temu) sambil mengobrol dengan beautician AI. Beautician menjawab dari data salon yang terkontrol, mencari perawatan sesuai kebutuhan atau anggaran, mengecek jadwal stylist, membuat permintaan janji temu, dan menyerahkan percakapan ke tim bila perlu. Staf mengelola semuanya lewat panel admin.

Satu aplikasi bisa melayani beberapa salon. Setiap salon punya halaman sendiri di `/{salonSlug}`.

## Fitur

### Sisi tamu
- **Halaman bertema "stage"**: lobi, layanan, stylist, fasilitas, info salon, tim, dan janji temu. Latarnya ilustrasi SVG dengan karakter stylist yang berbicara lewat balon di samping wajahnya, navigasi berupa dock ikon bulat di bawah, narrator, dan efek suara (tombol bisa dimatikan).
- **Tiga bahasa**: Indonesia (`id`), Inggris (`en`), dan Jepang (`ja`), dipilih lewat parameter `?lang=`.
- **Layanan**: daftar per kategori (rambut, kuku, perawatan kulit, makeup, spa & pijat) dengan harga, durasi, deposit, tag kebutuhan (halal, vegan, bebas amonia, kulit sensitif, untuk pria, pengantin), dan peringatan bahan.
- **Stylist**: profil, spesialisasi, pengalaman, keahlian, dan portofolio. Spesialisasi stylist sama dengan kategori layanan, itulah yang menentukan layanan apa yang bisa dipesan dengan siapa.
- **Chat dengan AI Beautician**
  - Tahu di halaman mana tamu berada (layanan atau stylist yang sedang dilihat, form janji temu yang sedang diisi), jadi bisa menjawab "perawatan ini".
  - Menjawab dalam bahasa yang ditulis tamu.
  - Menampilkan kartu layanan, stylist dengan jam kosong, dan ketersediaan langsung di dalam chat.
- **Janji temu**: pilih layanan, stylist yang menguasainya, tanggal, lalu jam mulai. Jam yang ditawarkan sudah pasti muat untuk seluruh durasi layanan. Permintaan dikirim dengan status `pending`; tim yang mengonfirmasi. Tidak ada pembayaran di muka.

### Sisi admin (`/admin`)
- Dashboard ringkasan.
- CRUD **layanan** (harga, durasi, deposit, kategori, tag, peringatan bahan, tandai tidak tersedia).
- CRUD **stylist** (spesialisasi, pengalaman, keahlian, portofolio).
- CRUD **knowledge items**, yaitu basis pengetahuan yang menjadi sumber jawaban beautician (kebijakan, fasilitas, acara, lokasi, FAQ).
- Daftar **janji temu** dan ubah statusnya (`pending`, `confirmed`, `cancelled`). Membatalkan janji temu mengembalikan jam stylist ke jadwal.
- **Handover**: percakapan yang diserahkan beautician ke tim. Staf bisa membalas langsung ke tamu dan menandainya selesai.
- Peran pengguna per salon: `owner` dan `staff`.

## Model jadwal

Hari kerja setiap stylist dipecah menjadi blok 30 menit (tabel `stylist_slots`). Sebuah layanan memakai blok berurutan sebanyak durasinya (durasi dibulatkan ke atas ke kelipatan 30 menit), jadi layanan 90 menit memakai tiga blok. Satu jam mulai hanya ditawarkan bila semua blok yang dibutuhkan masih kosong dan belum lewat. Semua perhitungan ini ada di satu tempat, [StylistAvailability](app/Modules/Appointment/Support/StylistAvailability.php), yang dipakai bersama oleh wizard janji temu dan tool AI, sehingga keduanya tidak pernah berbeda pendapat. Membuat janji temu menandai blok-bloknya terpakai di dalam transaksi dengan kunci baris; membatalkannya melepas blok itu sekali saja.

## Cara kerja AI Beautician

Kode ada di [app/Modules/Assistant/](app/Modules/Assistant) (model, prompt, tool) dan [app/Modules/Conversation/](app/Modules/Conversation) (satu giliran percakapan).

- **[ReplyToGuest](app/Modules/Conversation/Actions/ReplyToGuest.php)** menjalankan satu giliran percakapan dan menyimpan riwayatnya di database. Ia memanggil model lewat [ModelGateway](app/Modules/Assistant/Gateway/ModelGateway.php), yaitu endpoint chat yang kompatibel dengan OpenAI (LM Studio atau Ollama yang di-host sendiri), lalu menjalankan loop tool-calling (maksimal 6 putaran per giliran). Request dikirim dengan `reasoning_effort=none` agar model tidak melakukan fase berpikir panjang. Seluruh giliran dibatasi 85 detik supaya muat dalam batas 100 detik Cloudflare. Semua anggaran ini ada di [config/assistant.php](config/assistant.php). Prompt disusun oleh [PromptComposer](app/Modules/Assistant/Prompt/PromptComposer.php).
- **[ToolRegistry](app/Modules/Assistant/Tools/ToolRegistry.php)** adalah daftar putih alat yang bisa dipanggil model; satu alat satu kelas di [Handlers/](app/Modules/Assistant/Tools/Handlers). Semua fakta salon, layanan, harga, durasi, dan jadwal harus lewat sini, karena model sendiri tidak dipercaya menyimpan data apa pun.

  | Tool                         | Fungsi                                                                              |
  | ---------------------------- | ----------------------------------------------------------------------------------- |
  | `search_knowledge`           | Mencari di knowledge base salon                                                     |
  | `search_services`            | Mencari layanan menurut kata kunci, kategori, kebutuhan, anggaran, atau waktu       |
  | `get_service`                | Detail satu layanan: harga, durasi, deposit, peringatan bahan                       |
  | `search_stylists`            | Mencari stylist yang bisa mengerjakan layanan dan kosong penuh pada satu tanggal    |
  | `check_availability`         | Cek ketersediaan, harga, durasi, dan deposit untuk satu layanan, stylist, dan jam   |
  | `create_appointment_request` | Membuat permintaan janji temu (tidak pernah mengonfirmasi sendiri)                  |
  | `request_human_handover`     | Menyerahkan percakapan ke tim                                                       |

- **[ContentGuard](app/Modules/Conversation/Support/ContentGuard.php)** adalah pengaman deterministik di kode. Pesan yang kasar, bersifat seksual, atau ilegal (Indonesia, Inggris, Jepang) dijawab dengan balasan baku tanpa sampai ke model. Balasan model yang masih memuat kata-kata tersebut juga diganti. Pola dibuat sempit supaya pertanyaan salon yang wajar tidak ikut terblokir.
- **Harga tanpa sumber ditolak**: balasan yang memuat angka harga atau placeholder yang tidak berasal dari tool akan ditantang, supaya model tidak mengarang harga.
- **Alergi dan kulit sensitif**: beautician hanya mengulang peringatan bahan dari data layanan, tidak pernah menyatakan sebuah perawatan "aman", dan mengarahkan tamu dengan alergi atau kondisi medis ke stylist dan tim.
- **Handover**: alasan yang didukung adalah `special_request`, `complaint`, `group_appointment`, `bridal_package`, `skin_concern`, `payment_issue`, dan `low_confidence`.

## Teknologi

- PHP 8.3+ (dikembangkan di 8.4), Laravel 13
- SQLite sebagai database bawaan. Session, cache, dan queue memakai driver `database`
- Vite 8 dan Tailwind CSS 4 untuk frontend, dengan JavaScript vanilla (tanpa framework) di `resources/js/`
- Font Fraunces dan DM Sans (Bunny Fonts), ilustrasi SVG di `public/images/`
- LLM lokal lewat HTTP (endpoint kompatibel OpenAI)
- PHPUnit 12 untuk tes, Laravel Pint untuk format kode

## Memulai

### Prasyarat
PHP 8.3+ dengan ekstensi SQLite, Composer, dan Node.js 20.19+ (atau 22.12+) dengan npm. Untuk fitur chat AI, Anda juga perlu server LLM yang kompatibel dengan OpenAI (lihat bagian berikutnya).

### Instalasi

```bash
composer setup
```

Perintah itu menjalankan `composer install`, menyalin `.env.example` ke `.env`, membuat `APP_KEY`, menjalankan migrasi, lalu `npm install` dan `npm run build`.

Isi data demo (hanya jalan di environment `local` dan `testing`):

```bash
php artisan db:seed
```

Seeder membuat salon demo **Beauty Salon AI** (slug `beauty-salon-ai`) dengan 16 layanan, 6 stylist beserta portofolionya, jadwal blok 30 menit untuk 60 hari ke depan, dan knowledge items dalam tiga bahasa.

### Menjalankan

```bash
composer dev
```

Perintah ini menjalankan semua proses development lewat `php artisan dev`. Daftar prosesnya bisa dilihat dengan `php artisan dev:list`.

| Halaman         | URL                                     |
| --------------- | --------------------------------------- |
| Halaman pembuka | `http://localhost:8000/`                |
| Salon demo      | `http://localhost:8000/beauty-salon-ai` |
| Admin           | `http://localhost:8000/admin`           |

Akun admin demo (hanya untuk lokal, jangan dipakai di produksi):

```
email    : owner@beautysalonai.test
password : password
```

### Konfigurasi LLM

Atur di `.env`:

```dotenv
LOCAL_LLM_BASE_URL=   # mis. http://127.0.0.1:1234 (LM Studio) atau http://127.0.0.1:11434 (Ollama)
LOCAL_LLM_API_KEY=    # kosongkan jika server tidak memakai autentikasi
LOCAL_LLM_MODEL=      # nama model yang dimuat di server
```

Model harus mendukung function calling. Tanpa konfigurasi ini, halaman salon tetap jalan tetapi chat beautician tidak bisa menjawab.

Kalau perlu, ubah juga `APP_NAME`, `APP_URL`, dan `APP_TIMEZONE` (bawaan `Asia/Jakarta`).

### Mencoba beautician dari terminal

```bash
php artisan beautician:chat beauty-salon-ai --locale=id
```

Pilihan `--locale` adalah `id`, `en`, atau `ja`. Cara ini berguna untuk menguji loop tool-calling tanpa membuka browser.

## Endpoint utama

| Method | Path                                              | Keterangan                              |
| ------ | ------------------------------------------------- | --------------------------------------- |
| GET    | `/{salonSlug}`                                    | Lobi salon                              |
| GET    | `/{salonSlug}/services`, `/services/{serviceSlug}`  | Daftar dan detail layanan               |
| GET    | `/{salonSlug}/stylists`, `/stylists/{stylistSlug}`  | Daftar dan detail stylist               |
| GET    | `/{salonSlug}/facilities`, `/facilities/{id}`     | Fasilitas                               |
| GET    | `/{salonSlug}/info`, `/staff`, `/appointment`     | Info, tim, janji temu                   |
| POST   | `/{salonSlug}/appointment/slots`                  | Jam mulai yang muat untuk satu layanan  |
| POST   | `/{salonSlug}/appointment/quote`                  | Cek ketersediaan, harga, dan deposit    |
| POST   | `/{salonSlug}/appointment`                        | Kirim permintaan janji temu             |
| POST   | `/{salonSlug}/beautician/start`                   | Mulai percakapan                        |
| POST   | `/{salonSlug}/beautician/message`                 | Kirim pesan ke beautician               |
| GET    | `/{salonSlug}/beautician/history`                 | Riwayat percakapan                      |

Endpoint beautician dan janji temu dibatasi laju (rate limit). Pembatas beautician didefinisikan di [AppServiceProvider](app/Providers/AppServiceProvider.php), dan login admin dibatasi 5 percobaan per menit.

## Struktur proyek

```
app/
  Console/Commands/     beautician:chat
  Http/
    Controllers/        halaman tamu, Api/ (chat dan janji temu JSON), Admin/, Auth/
    Middleware/         ResolveSalon (slug ke salon dan bahasa), AssignRequestId
    Requests/           Form Request: Admin/, Api/, Auth/
  Models/               Salon, Service, Stylist, StylistSlot, Appointment, Conversation, HandoverRequest, ...
  Modules/              satu folder per konteks: Actions/, Queries/, Support/, Enums/, Presenters/
    Assistant/          Gateway/ (model), Prompt/, Tools/ (daftar putih alat + Handlers/)
    Conversation/       balasan tamu, handover, ContentGuard
    Experience/         data panggung tamu (teks, narasi, latar)
    Services/ Stylists/ Knowledge/ Appointment/ Operations/
  Policies/             satu salon hanya mengelola datanya sendiri
  Support/              SearchText, Text, Ui/ (kelas gaya komponen)
config/                 salon.php (bahasa, scene), assistant.php (anggaran AI)
database/
  factories/            satu factory per model
  migrations/           skema (salon, layanan, stylist dan jadwal, knowledge, percakapan, janji temu)
  seeders/              satu seeder per model, dipanggil berurutan oleh DatabaseSeeder
deploy/                 contoh nginx, PHP, supervisor, dan env produksi
lang/{id,en,ja}/        semua teks panggung tamu, validasi janji temu, dan pesan handover
public/images/          scenes/, character/, beautician/, services/, stylists/ (ilustrasi SVG)
resources/
  css/                  theme.css (token admin), base.css, presentation.css + stage/ (panggung, token di stage/tokens.css)
  js/                   app.js (satu Alpine.start), stage, narrator, beautician, appointment, sound, shared/, ui/
  views/                halaman tamu (salon/), admin (admin/), components/{layouts,ui,navigation}
scripts/                check-ui-conventions.mjs (npm run lint:ui)
tests/
  Feature/, Unit/, Support/, js/
```

## Mengganti ilustrasi

Semua gambar adalah SVG di `public/images/`. Latar scene ada di `scenes/`, karakter stylist (SVG transparan 800×1200, terpotong rata di bawah) di `character/`, avatar bulat di `beautician/avatar.svg`. Gambar layanan dan portofolio stylist diacu dari kolom `image_url` (path di bawah `public/` atau URL penuh), jadi foto asli bisa dipakai langsung dari panel admin. Pemetaan scene ke gambar ada di [config/salon.php](config/salon.php) (`scenes`).

## Pengujian

```bash
composer test
```

Atau jalankan satu berkas:

```bash
php artisan test --compact tests/Feature/BeauticianChatTest.php
```

Cakupan tes: akses admin, chat beautician (termasuk giliran dengan `FakeModelGateway`), daftar putih tool, tool layanan, stylist, dan janji temu, permintaan janji temu beserta jadwal blok 30 menit, `ResolveSalon`, halaman stage, dan `ContentGuard`.

Sisi frontend punya dua pemeriksaan Node tanpa dependensi tambahan:

```bash
npm run lint:ui   # warna admin dan UI kit hanya boleh lewat token di theme.css
npm run test:ui   # klien API dan aturan lint
```

## Gaya kode

```bash
vendor/bin/pint --dirty
```

## Deployment

- Jalankan `npm run build` dan `php artisan migrate --force`.
- Set `APP_ENV=production` dan `APP_DEBUG=false`.
- Seeder demo tidak jalan di produksi. Buat akun owner dan salon Anda sendiri.
- Pastikan server aplikasi bisa menjangkau `LOCAL_LLM_BASE_URL`. Pada setup saat ini endpoint LLM diakses lewat Tailscale.
- Karena ada batas 100 detik dari Cloudflare, jangan menaikkan batas waktu respons beautician melebihi 85 detik.

## Lisensi

Project ini dibangun di atas [Laravel](https://laravel.com), yang berlisensi [MIT](https://opensource.org/licenses/MIT).
