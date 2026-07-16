# KareL 📸

Platform berbagi momen harian antar teman, terinspirasi dari **Locket**.

## Tech Stack
- **Backend**: Native PHP
- **Database**: MySQL
- **Styling**: Tailwind CSS (CDN)
- **Kamera**: JavaScript getUserMedia API

## Fitur
- 🔐 Register & Login (session-based)
- 📸 Foto langsung via kamera (tidak bisa upload dari galeri)
- 🏠 Feed momen dari teman
- 👥 Sistem pertemanan (add, accept, reject)
- ✏️ Edit caption post
- 🗑️ Hapus post
- 👤 Profil user dengan grid foto

## Setup Lokal

### 1. Import Database
```sql
source karel.sql
```
Atau import `karel.sql` via phpMyAdmin.

### 2. Konfigurasi Database
Edit `config/database.php`:
```php
$host     = 'localhost';
$dbname   = 'karel_db';
$username = 'root';
$password = '';
```

### 3. Jalankan di XAMPP/Laragon
Pindahkan folder ke `htdocs/` lalu akses:
```
http://localhost/karel/
```

> **Note**: Fitur kamera membutuhkan `localhost` atau HTTPS.
