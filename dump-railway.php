<?php
/**
 * HeavyTrack WMS - Pure PHP MySQL Database Dumper (No mysqldump / No Laragon required)
 */
$url = $argv[1] ?? '';
$outFile = $argv[2] ?? (__DIR__ . '/heavytrack_backup.sql');

$url = trim($url, " \t\n\r\0\x0B\"'");
if (!preg_match('#^mysql://([^:]+):([^@]+)@([^:]+):(\d+)/(.+)$#i', $url, $m)) {
    fwrite(STDERR, "[ERROR] Format MYSQL_PUBLIC_URL tidak sesuai! Pastikan diawali mysql://\n");
    exit(1);
}

[, $user, $pass, $host, $port, $db] = $m;
echo "[INFO] Menghubungkan ke Host: {$host} | Port: {$port} | Database: {$db} ...\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);
} catch (Exception $e) {
    fwrite(STDERR, "[ERROR] Gagal konek ke MySQL Railway: " . $e->getMessage() . "\n");
    exit(1);
}

$fp = fopen($outFile, 'w');
if (!$fp) {
    fwrite(STDERR, "[ERROR] Tidak bisa membuat file {$outFile}\n");
    exit(1);
}

fwrite($fp, "-- ============================================================================\n");
fwrite($fp, "-- HeavyTrack WMS (Mitra Abadi Nusantara) - Full Production Database Backup\n");
fwrite($fp, "-- Source Host : {$host}:{$port} ({$db})\n");
fwrite($fp, "-- Generated At: " . date('Y-m-d H:i:s') . "\n");
fwrite($fp, "-- ============================================================================\n\n");
fwrite($fp, "SET NAMES utf8mb4;\n");
fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
fwrite($fp, "SET time_zone = '+00:00';\n\n");

$tables = [];
$stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

echo "[INFO] Ditemukan " . count($tables) . " tabel di database Railway.\n";

foreach ($tables as $table) {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
    $rowCount = (int) $countStmt->fetchColumn();
    echo "  -> Mengunduh tabel `{$table}` ({$rowCount} baris)...\n";

    fwrite($fp, "-- --------------------------------------------------------\n");
    fwrite($fp, "-- Struktur & Data untuk tabel `{$table}` ({$rowCount} baris)\n");
    fwrite($fp, "-- --------------------------------------------------------\n");
    fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");

    $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
    $createSql = $createStmt[1];
    // Pastikan kompatibel dengan semua versi MySQL 8.0+ / 8.4 / MariaDB di Windows
    $createSql = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_unicode_ci', $createSql);
    fwrite($fp, $createSql . ";\n\n");

    if ($rowCount > 0) {
        $rows = $pdo->query("SELECT * FROM `{$table}`");
        $batch = [];
        foreach ($rows as $row) {
            $vals = [];
            foreach ($row as $val) {
                if ($val === null) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = $pdo->quote((string) $val);
                }
            }
            $batch[] = '(' . implode(', ', $vals) . ')';

            if (count($batch) >= 200) {
                $cols = '`' . implode('`, `', array_keys($row)) . '`';
                fwrite($fp, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        if (!empty($batch)) {
            $cols = '`' . implode('`, `', array_keys($row)) . '`';
            fwrite($fp, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $batch) . ";\n");
        }
        fwrite($fp, "\n");
    }
}

fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
fwrite($fp, "-- Dump completed on " . date('Y-m-d H:i:s') . "\n");
fclose($fp);

echo "\n[BERHASIL] Seluruh database (" . count($tables) . " tabel) berhasil disimpan ke:\n";
echo "           {$outFile} (" . number_format(filesize($outFile)) . " bytes)\n";
