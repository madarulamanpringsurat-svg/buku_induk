# Panduan Login Google untuk Guru

## 1. Buat OAuth Client Google

1. Buka Google Cloud Console: https://console.cloud.google.com/
2. Buat project baru atau pilih project aplikasi madrasah.
3. Buka `APIs & Services` -> `OAuth consent screen`.
4. Pilih `External` jika akun Gmail berasal dari berbagai domain, lalu lengkapi nama aplikasi dan email dukungan.
5. Tambahkan scope standar: `email`, `profile`, `openid`.
6. Buka `APIs & Services` -> `Credentials`.
7. Pilih `Create Credentials` -> `OAuth client ID` -> `Web application`.
8. Isi `Authorized JavaScript origins`:
   - Untuk testing lokal: `http://localhost`
   - Untuk server madrasah: `https://domain-madrasah.sch.id`
9. Isi `Authorized redirect URIs` jika diperlukan:
   - Untuk alur saat ini Google Identity Services dapat memakai origin utama, tanpa redirect URI khusus.
10. Salin `Client ID`.

## 2. Konfigurasi XAMPP

Tambahkan konfigurasi berikut ke `C:\xampp\apache\conf\extra\httpd-vhosts.conf` atau `C:\xampp\apache\conf\httpd.conf`:

```apache
SetEnv GOOGLE_CLIENT_ID "ISI_CLIENT_ID_APLIKASI_GOOGLE"
```

Restart Apache melalui XAMPP Control Panel.

## 3. Aktifkan Google Drive untuk SK

Tambahkan environment berikut pada konfigurasi Apache XAMPP:

```apache
SetEnv GOOGLE_CLIENT_SECRET "CLIENT_SECRET_OAUTH"
SetEnv GOOGLE_REDIRECT_URI "http://localhost/buku_induk/login_google.php"
```

Pada Google Cloud Console, buka OAuth client web application lalu tambahkan URI redirect:

```text
http://localhost/buku_induk/login_google.php
```

Pada OAuth consent screen, tambahkan scope Google Drive:

```text
https://www.googleapis.com/auth/drive.file
```

Saat guru menekan tombol `Masuk dengan Google dan Drive`, aplikasi meminta izin Gmail, profil, dan Google Drive sekaligus. Setelah login berhasil, akun guru langsung terhubung ke Drive dan setiap SK Keaktifan dikirim ke folder `SK Keaktifan - Nama Guru` pada Drive akun guru.

## 4. Daftarkan Guru

Di aplikasi sebagai admin:

1. Buka `Data Guru`.
2. Tambah guru baru atau edit guru lama.
3. Isi `Email Gmail untuk Login Guru`.
4. Centang `Aktifkan login Google`.
5. Simpan.

Guru yang belum didaftarkan atau belum diaktifkan akan ditolak saat login.

## 5. Uji Login

1. Buka `http://localhost/buku_induk/login.php`.
2. Klik tombol Login Google.
3. Pilih Gmail yang sama persis dengan email yang didaftarkan admin.
4. Guru akan diarahkan ke profilnya sendiri dan hanya dapat mengakses data miliknya.

## 6. Setelah Dipasang di Hosting

1. Gunakan domain HTTPS.
2. Tambahkan domain HTTPS tersebut ke `Authorized JavaScript origins` Google Cloud.
3. Set environment `GOOGLE_CLIENT_ID` pada Apache/PHP hosting.
4. Pastikan PHP `cURL` aktif.
5. Pastikan sesi PHP aktif dan pengguna memakai browser normal, bukan mode private yang memblokir Google.

## Catatan Keamanan

- Akses guru dibatasi berdasarkan `guru_id` dari session setelah verifikasi Google.
- Guru tidak dapat mengubah ID guru orang lain melalui URL atau POST pada halaman profil.
- Admin tetap menggunakan login password lama.
- Jangan membagikan `Google Client Secret`; implementasi ini memakai Google Identity Services dengan verifikasi ID token, sehingga Secret tidak diperlukan.
