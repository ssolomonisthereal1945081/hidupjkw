# JKW CRM — Sistem Manajemen Klien
### PT Jawa Kerja Wiraswasta

---

## Struktur File

```
jkw/
├── index.php       → Halaman Login
├── dashboard.php   → Dashboard Data Klien
├── logout.php      → Handler Logout
├── config.php      → Konfigurasi Database
├── style.css       → Stylesheet
└── setup.sql       → Setup Database & Data Sample
```

---

## Cara Setup

### 1. Setup Database (WAMP/XAMPP)
Buka **phpMyAdmin** lalu jalankan isi file `setup.sql`:
```sql
-- Atau via command line:
mysql -u root -p < setup.sql
```

### 2. Konfigurasi Database
Edit file `config.php` jika perlu ganti pengaturan:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Isi password MySQL jika ada
define('DB_NAME', 'jkw_db');
```

### 3. Jalankan Server
- **XAMPP**: Taruh folder `jkw/` di `C:\xampp\htdocs\`
- **WAMP**: Taruh folder `jkw/` di `C:\wamp64\www\`
- Buka browser: `http://localhost/jkw/`

---

## Akun Login Default

| Role     | Username   | Password  |
|----------|------------|-----------|
| Admin    | `admin`    | `password` |
| Operator | `operator` | `password` |

> **Admin** dapat tambah, edit, hapus klien.
> **Operator** hanya dapat melihat data.

---

## Fitur

- ✅ Halaman login dengan validasi
- ✅ Session management (logout otomatis)
- ✅ Dashboard statistik (total klien, aktif, prospek, nilai kontrak)
- ✅ Tabel data klien dengan pagination (8 per halaman)
- ✅ Pencarian & filter berdasarkan status
- ✅ CRUD data klien (khusus Admin)
- ✅ Responsive design (mobile-friendly)
- ✅ Dark mode modern dengan glassmorphism
- ✅ Footer PT Jawa Kerja Wiraswasta
- ✅ 10 data klien sample
