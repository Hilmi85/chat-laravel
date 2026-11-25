# TempChat — Tasklist

Progress: 100%

## Keputusan Teknis

- Tabel peserta memakai `participants`; auth scaffold `users` tidak diubah.
- Warna mengikuti `design.md`: aksen cyan `#5FB5D6`; bubble sendiri cyan, lawan bicara surface.
- Countdown < 1 jam memakai status warning semantik sebagai pengecualian.
- Dependency: `axios` dan `mews/purifier`.
- Semua endpoint AJAX lewat `routes/web.php` agar session dan CSRF aktif.
- Tidak perlu `migrate:fresh`; migration baru hanya `rooms`, `participants`, `messages`.

---

## 1. Project Setup & Configuration

- [x] ✅ Task 1.1 - Finalisasi `.agents/tasklist.md` dan progress awal (Selesai)
  - Catatan: checklist aktif tersimpan di `.agents/tasklist.md`; progress diperbarui menjadi 10%.
- [x] ✅ Task 1.2 - Install dependency frontend dan backend (Selesai)
  - `npm install axios`; `composer require mews/purifier`.
  - `npm install -D fontaine` untuk optimized font fallbacks.
  - File: `package.json`, `package-lock.json`, `composer.json`, `composer.lock`.
- [x] ✅ Task 1.3 - Tambah `config/chat.php` (Selesai)
  - TTL room 24 jam, window online 30 detik, window typing 3 detik.
  - Batas nama 30 karakter, pesan 500 karakter, interval polling 2000 ms.
  - File: `config/chat.php`.
- [x] ✅ Task 1.4 - Update font dan theme Tailwind (Selesai)
  - `vite.config.js`: Inter + JetBrains Mono.
  - `resources/css/app.css`: token warna, font, radius, spacing, dan base dark body.
  - File: `vite.config.js`, `resources/css/app.css`.
- [x] ✅ Task 1.5 - Set nama aplikasi dan dokumentasi environment (Selesai)
  - `APP_NAME=TempChat` di `.env` dan `.env.example`.
  - `.env.example` kini memakai MySQL `chat-laravel` sebagai default.
  - File: `.env`, `.env.example`.
- [x] ✅ Task 1.6 - Verifikasi awal (Selesai)
  - `npm run build` sukses; `php artisan about` menunjukkan TempChat + MySQL.
  - `php artisan migrate:status` menunjukkan 3 migration bawaan sudah berjalan.

## 2. Database & Migration

- [x] ✅ Task 2.1 - Migration `rooms` (Selesai)
  - `id`, `code` unik 8 karakter, `expired_at` index, timestamps.
  - File: `database/migrations/2026_11_25_000001_create_rooms_table.php`.
- [x] ✅ Task 2.2 - Migration `participants` (Selesai)
  - `id`, `room_id` FK cascade, `name`, `last_seen` nullable, `typing_at` nullable, timestamps.
  - Unique `(room_id, name)`.
  - File: `database/migrations/2026_11_25_000002_create_participants_table.php`.
- [x] ✅ Task 2.3 - Migration `messages` (Selesai)
  - `id`, `room_id` FK cascade, `participant_id` nullable FK `nullOnDelete`, `message`, timestamps.
  - Index `(room_id, id)`.
  - File: `database/migrations/2026_11_25_000003_create_messages_table.php`.
- [x] ✅ Task 2.4 - Factory (Selesai)
  - `RoomFactory`, `ParticipantFactory`, `MessageFactory` + state expired/offline/typing.
  - File: `database/factories/RoomFactory.php`, `ParticipantFactory.php`, `MessageFactory.php`.
- [x] ✅ Task 2.5 - Jalankan migration dan cek schema (Selesai)
  - `php artisan migrate` sukses; 3 tabel baru terbuat.
  - Foreign key cascade dan `nullOnDelete` terverifikasi via `php artisan db:table`.

## 3. Models & Relationships

- [x] ✅ Task 3.1 - Model `Room` (Selesai)
  - `participants()`, `messages()`, scope `active()`/`expired()`, `isExpired()`, generator kode unik.
  - File: `app/Models/Room.php`.
- [x] ✅ Task 3.2 - Model `Participant` (Selesai)
  - `room()`, `messages()`, `isOnline()`, `isTyping()`, scope `online()`/`typing()`.
  - File: `app/Models/Participant.php`.
- [x] ✅ Task 3.3 - Model `Message` (Selesai)
  - `room()`, `participant()`, `isSystem()`.
  - File: `app/Models/Message.php`.
- [x] ✅ Task 3.4 - Cast dan helper waktu (Selesai)
  - Cast `last_seen`, `typing_at`, `expired_at` ke datetime.
  - File: `app/Models/Room.php`, `Participant.php`.
- [x] ✅ Task 3.5 - Rapikan seeder/factory auth yang tidak dipakai (Selesai)
  - Auth scaffold `users` tidak diubah; `DatabaseSeeder` tidak lagi membuat test user.
  - File: `database/seeders/DatabaseSeeder.php`.

## 4. Backend Logic & API

- [x] ✅ Task 4.1 - Definisikan route web (Selesai)
  - Semua endpoint chat terdaftar di `routes/web.php`.
  - File: `routes/web.php`.
- [x] ✅ Task 4.2 - `RoomController@store` (Selesai)
  - Membuat room + participant pertama + system message, menyimpan session, redirect.
  - File: `app/Http/Controllers/RoomController.php`.
- [x] ✅ Task 4.3 - `RoomController@show` (Selesai)
  - Menampilkan room; `$participant` bernilai null saat visitor belum join.
  - File: `app/Http/Controllers/RoomController.php`.
- [x] ✅ Task 4.4 - `RoomController@join` (Selesai)
  - Nama duplikat diberi suffix `(2)`, `(3)`, dst.; system message join dibuat.
  - File: `app/Http/Controllers/RoomController.php`.
- [x] ✅ Task 4.5 - `RoomController@leave` (Selesai)
  - `last_seen`/`typing_at` null, system message leave, session dihapus.
  - File: `app/Http/Controllers/RoomController.php`.
- [x] ✅ Task 4.6 - `MessageController@index` (Selesai)
  - Polling `after_id`, update `last_seen`, response messages + online count + `expired_at`.
  - File: `app/Http/Controllers/MessageController.php`, `app/Support/MessagePayload.php`.
- [x] ✅ Task 4.7 - `MessageController@store` (Selesai)
  - Validasi via FormRequest, sanitasi, rate limit 1/detik, simpan pesan, update `last_seen`.
  - File: `app/Http/Controllers/MessageController.php`, `app/Http/Requests/StoreMessageRequest.php`.
- [x] ✅ Task 4.8 - `TypingController@store` (Selesai)
  - Update `typing_at` dan `last_seen`.
  - File: `app/Http/Controllers/TypingController.php`.
- [x] ✅ Task 4.9 - `TypingController@status` (Selesai)
  - Read-only; query participant typing >= now - 3s, kecuali diri sendiri.
  - File: `app/Http/Controllers/TypingController.php`.
- [x] ✅ Task 4.10 - Middleware `EnsureRoomIsActive` (Selesai)
  - Alias `room.active` terdaftar di `bootstrap/app.php`; expired dihapus, response 410/redirect.
  - File: `app/Http/Middleware/EnsureRoomIsActive.php`, `bootstrap/app.php`.
- [x] ✅ Task 4.11 - Route model binding berdasarkan `code` (Selesai)
  - Route memakai `{room:code}` dan pattern `[A-Z0-9]{8}`.
  - File: `routes/web.php`, `app/Models/Room.php`.

## 5. Security

- [x] ✅ Task 5.1 - Form Request (Selesai)
  - `CreateRoomRequest`, `JoinRoomRequest`, `StoreMessageRequest`.
  - File: `app/Http/Requests/*.php`.
- [x] ✅ Task 5.2 - Sanitasi input dengan Purifier (Selesai)
  - Nama dan pesan dibersihkan lewat `ChatText` + `mews/purifier`; output tetap di-escape.
  - File: `app/Support/ChatText.php`.
- [x] ✅ Task 5.3 - Validasi batas input (Selesai)
  - Nama maksimal 30 karakter, pesan maksimal 500 karakter dari `config/chat.php`.
  - File: `config/chat.php`, FormRequest.
- [x] ✅ Task 5.4 - Rate limit pesan (Selesai)
  - Maksimal 1 pesan per detik per participant; response 429 JSON.
  - File: `app/Http/Controllers/MessageController.php`.
- [x] ✅ Task 5.5 - CSRF untuk AJAX (Selesai)
  - Meta tag `csrf-token` di layout dan header axios di `app.js`.
  - File: `resources/views/layouts/app.blade.php`, `resources/js/app.js`.
- [x] ✅ Task 5.6 - XSS-safe rendering (Selesai)
  - Blade memakai escaping; JavaScript memakai `textContent`, bukan `innerHTML`.
  - File: `resources/views/**/*.blade.php`, `resources/js/app.js`.
- [x] ✅ Task 5.7 - Otorisasi participant (Selesai)
  - `ChatSession` memastikan participant session berasal dari room yang diakses.
  - File: `app/Support/ChatSession.php`, controller chat.

## 6. Frontend & Blade Views

- [x] ✅ Task 6.1 - Layout utama (Selesai)
  - `layouts/app.blade.php`: dark theme, meta CSRF, `@vite`, font, toast container.
  - File: `resources/views/layouts/app.blade.php`.
- [x] ✅ Task 6.2 - Homepage (Selesai)
  - Form nama + tombol Create Room, card dan spacing sesuai `design.md`.
  - File: `resources/views/home.blade.php`.
- [x] ✅ Task 6.3 - Header room (Selesai)
  - Kode room, online count, countdown, copy link, share, leave.
  - File: `resources/views/rooms/show.blade.php`.
- [x] ✅ Task 6.4 - Daftar pesan (Selesai)
  - Bubble sendiri kanan/cyan, pesan lain kiri/surface, system message terpusat, waktu HH:mm.
  - File: `resources/views/rooms/partials/message.blade.php`.
- [x] ✅ Task 6.5 - Empty state (Selesai)
  - Tampilan empty state saat belum ada pesan.
  - File: `resources/views/rooms/show.blade.php`.
- [x] ✅ Task 6.6 - Composer dan typing indicator (Selesai)
  - Input pesan, tombol kirim, indikator typing dengan animasi titik.
  - File: `resources/views/rooms/show.blade.php`, `resources/css/app.css`.
- [x] ✅ Task 6.7 - Join overlay (Selesai)
  - Form nama muncul jika session participant belum ada.
  - File: `resources/views/rooms/show.blade.php`.
- [x] ✅ Task 6.8 - Toast, sound toggle, share, copy, leave confirmation (Selesai)
  - Terintegrasi di header, layout, dan JavaScript.
  - File: `resources/views/rooms/show.blade.php`, `resources/js/app.js`.
- [x] ✅ Task 6.9 - Responsive mobile (Selesai)
  - Layout full-height `h-dvh`, composer menempel, header wrap di layar kecil.
  - File: `resources/views/rooms/show.blade.php`, `resources/views/home.blade.php`.

## 7. JavaScript & Realtime Features

- [x] ✅ Task 7.1 - Setup axios dan CSRF (Selesai)
  - File: `resources/js/app.js`.
- [x] ✅ Task 7.2 - Polling pesan setiap 2 detik dengan `after_id` (Selesai)
  - Deduplikasi DOM + guard `isPollingMessages` mencegah polling tumpang tindih.
  - File: `resources/js/app.js`.
- [x] ✅ Task 7.3 - Debounce typing 300 ms (Selesai)
  - POST typing hanya setelah user berhenti mengetik.
  - File: `resources/js/app.js`.
- [x] ✅ Task 7.4 - Polling typing status setiap 2 detik (Selesai)
  - Menampilkan nama selain diri sendiri; otomatis hilang.
  - File: `resources/js/app.js`.
- [x] ✅ Task 7.5 - Smart auto-scroll (Selesai)
  - Scroll hanya jika dekat bawah atau baru mengirim pesan.
  - File: `resources/js/app.js`.
- [x] ✅ Task 7.6 - Countdown realtime (Selesai)
  - Update tiap detik; warning < 1 jam; overlay expired saat habis.
  - File: `resources/js/app.js`, `resources/css/app.css`.
- [x] ✅ Task 7.7 - Toast notification (Selesai)
  - Success, error, info; auto-dismiss.
  - File: `resources/js/app.js`, `resources/views/layouts/app.blade.php`.
- [x] ✅ Task 7.8 - Sound notification (Selesai)
  - Web Audio API, toggle tersimpan di `localStorage`.
  - File: `resources/js/app.js`.
- [x] ✅ Task 7.9 - Copy link, share WhatsApp/Telegram, leave confirmation (Selesai)
  - File: `resources/js/app.js`, `resources/views/rooms/show.blade.php`.
- [x] ✅ Task 7.10 - Handling error (Selesai)
  - 419/429/410/403 dan room expired; polling berhenti saat expired.
  - File: `resources/js/app.js`.

## 8. Additional Features

- [x] ✅ Task 8.1 - System message join/leave (Selesai)
  - Dibuat saat create/join/leave.
  - File: `app/Http/Controllers/RoomController.php`.
- [x] ✅ Task 8.2 - Online user count (Selesai)
  - Berdasarkan `last_seen` dalam 30 detik; diperbarui lewat polling.
  - File: `app/Models/Participant.php`, `MessageController.php`, `app.js`.
- [x] ✅ Task 8.3 - Share room via WhatsApp dan Telegram (Selesai)
  - File: `resources/views/rooms/show.blade.php`.
- [x] ✅ Task 8.4 - Sound notification toggle (Selesai)
  - File: `resources/js/app.js`.
- [x] ✅ Task 8.5 - Empty state dan expired state (Selesai)
  - File: `resources/views/rooms/show.blade.php`, `resources/js/app.js`.
- [x] ✅ Task 8.6 - Copy link dengan toast (Selesai)
  - File: `resources/js/app.js`.

## 9. Cleanup Command & Scheduler

- [x] ✅ Task 9.1 - Command `rooms:cleanup` (Selesai)
  - Menghapus room dengan `expired_at <= now()`.
  - File: `app/Console/Commands/CleanupExpiredRooms.php`.
- [x] ✅ Task 9.2 - Cascade delete (Selesai)
  - Participants dan messages terhapus lewat foreign key cascade.
  - File: migration `participants`, `messages`; test cleanup.
- [x] ✅ Task 9.3 - Daftarkan scheduler (Selesai)
  - `Schedule::command('rooms:cleanup')->everyMinute()->withoutOverlapping()`.
  - File: `routes/console.php`.
- [x] ✅ Task 9.4 - Verifikasi (Selesai)
  - `php artisan schedule:list` menampilkan jadwal; command berjalan sukses.
  - Test: `tests/Feature/CleanupExpiredRoomsTest.php`.

## 10. Final Polish & Bug Fixes

- [x] ✅ Task 10.1 - Feature test (Selesai)
  - Create room, join + duplicate name, kirim pesan, validasi, rate limit.
  - Polling, typing read-only, room expired, cleanup command, XSS.
  - File: `tests/Feature/RoomTest.php`, `MessageTest.php`, `TypingTest.php`, `CleanupExpiredRoomsTest.php`.
- [x] ✅ Task 10.2 - Jalankan test (Selesai)
  - `php artisan test`: 25 test lulus, 83 assertion.
- [x] ✅ Task 10.3 - Format code (Selesai)
  - `vendor/bin/pint --test`: passed.
- [x] ✅ Task 10.4 - Build frontend (Selesai)
  - `npm run build`: sukses, termasuk font Inter/JetBrains Mono.
- [x] ✅ Task 10.5 - QA dua sesi (Selesai)
  - Smoke test HTTP dua sesi: create → join Bob → kirim pesan → typing Alice → polling → leave Bob.
  - Verifikasi visual browser (sound/copy/share) belum dijalankan; build JS/CSS sudah sukses.
- [x] ✅ Task 10.6 - Update README (Selesai)
  - Instruksi setup, MySQL, migrate, npm, scheduler, test, dan catatan arsitektur.
  - File: `README.md`.
- [x] ✅ Task 10.7 - Update tasklist ke `Progress: 100%` (Selesai)
  - Semua task ditandai `[x] ✅` dan file terkait dicatat.

- [x] ✅ Task 10.8 - Poles UI/UX agar lebih elegan (Selesai)
  - Catatan: Homepage, toolbar dan ruang chat, empty state, halaman 404, serta aksesibilitas gerak dikurangi dirapikan mengikuti design.md.
  - File: `resources/views/home.blade.php`, `resources/views/rooms/show.blade.php`, `resources/views/errors/404.blade.php`, `resources/css/app.css`.
- [x] ✅ Task 10.9 - Redesign UI/UX dan palet warna (Selesai)
  - Catatan: Palet diperbarui ke charcoal/slate dengan kontras teks lebih jelas, aksen cyan lebih tegas, dan kanvas chat terpisah.
  - File: `.agents/design.md`, `resources/css/app.css`, `resources/views/home.blade.php`, `resources/views/rooms/show.blade.php`, `resources/views/errors/404.blade.php`.
- [x] ✅ Task 10.10 - Rapikan komposisi copy homepage (Selesai)
  - Catatan: Kalimat hero dipisahkan menjadi headline singkat dan keterangan privasi dengan hierarki teks serta aksen sisi yang jelas.
  - File: `resources/views/home.blade.php`.
- [x] ✅ Task 10.11 - Halaman join tamu mandiri (Selesai)
  - Catatan: Tamu memakai layar join penuh dengan info room dan countdown; chat terkunci di belakang modal dihapus dan autofocus dinonaktifkan.
  - File: `resources/views/rooms/show.blade.php`.
- [x] ✅ Task 10.12 - Satukan layar tamu dengan layout chat (Selesai)
  - Catatan: Tamu kini melihat header dan pratinjau room yang sama; form username mengisi area composer tanpa overlay atau halaman terpisah.
  - File: `resources/views/rooms/show.blade.php`.