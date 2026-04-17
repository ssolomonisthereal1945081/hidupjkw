<?php
/**
 * encrypt_existing.php
 * Script sekali jalan: enkripsi semua data klien lama dengan ROT21
 * HAPUS file ini setelah dijalankan!
 */
require_once 'config.php';

$result = mysqli_query($conn, "SELECT * FROM klien");
$encrypted = 0;
$skipped   = 0;
$log       = [];

while ($k = mysqli_fetch_assoc($result)) {
    // Deteksi apakah field sudah terenkripsi atau belum
    // Cara: decode lalu encode kembali, jika hasilnya sama → sudah terenkripsi
    $sample = $k['nama_klien'];
    $decoded = rot21_dec($sample);
    $re_enc  = rot21_enc($decoded);

    if ($re_enc === $sample) {
        // Sudah terenkripsi, lewati
        $log[] = "⚠ SKIP  [{$k['id']}] {$k['kode_klien']} — kemungkinan sudah terenkripsi";
        $skipped++;
        continue;
    }

    // Enkripsi semua field teks
    $enc = klien_encode($k);

    $stmt = mysqli_prepare($conn,
        "UPDATE klien SET nama_klien=?, perusahaan=?, email=?, telepon=?, alamat=?, kota=?, catatan=? WHERE id=?"
    );
    mysqli_stmt_bind_param($stmt, 'sssssssi',
        $enc['nama_klien'],
        $enc['perusahaan'],
        $enc['email'],
        $enc['telepon'],
        $enc['alamat'],
        $enc['kota'],
        $enc['catatan'],
        $k['id']
    );
    mysqli_stmt_execute($stmt);

    $log[] = "✅ ENC   [{$k['id']}] {$k['kode_klien']} | {$k['nama_klien']} → {$enc['nama_klien']}";
    $encrypted++;
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Enkripsi ROT21</title>
<style>
body{font-family:monospace;background:#0f0f1a;color:#e2e8f0;padding:24px;}
h2{color:#818cf8;margin-bottom:16px;}
.log{background:#1a1a2e;border:1px solid #2d2d50;border-radius:8px;padding:16px;margin-bottom:16px;}
.ok{color:#6ee7b7;} .skip{color:#fcd34d;} .info{color:#94a3b8;}
.summary{background:#1e3a5f;border:1px solid #3b82f6;border-radius:8px;padding:16px;}
</style>
</head>
<body>
<h2>🔐 Enkripsi ROT21 — Data Klien</h2>
<div class="log">
<?php foreach ($log as $line): ?>
    <div class="<?= strpos($line,'✅') !== false ? 'ok' : 'skip' ?>"><?= htmlspecialchars($line) ?></div>
<?php endforeach; ?>
<?php if (empty($log)): ?><div class="info">Tidak ada data klien.</div><?php endif; ?>
</div>
<div class="summary">
    <strong>📊 Ringkasan:</strong><br>
    ✅ Berhasil dienkripsi: <strong><?= $encrypted ?> record</strong><br>
    ⚠ Dilewati (sudah enkripsi): <strong><?= $skipped ?> record</strong><br><br>
    <span style="color:#f87171;font-weight:bold;">⚠ PENTING: Hapus file ini segera → <code>encrypt_existing.php</code></span>
</div>
</body></html>
