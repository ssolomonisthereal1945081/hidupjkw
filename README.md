# JKW CRM — Sistem Manajemen Perusahaan
### Jadi Kaya Wajib

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

## Fitur

- ✅ Halaman login dengan validasi
- ✅ Session management (logout otomatis)
- ✅ Responsive design (mobile-friendly)
- ✅ Dark mode modern dengan glassmorphism
- ✅ Footer Jadi Kaya Wajib

