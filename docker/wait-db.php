<?php

/**
 * Menunggu MySQL siap menerima koneksi sebelum container aplikasi lanjut
 * menjalankan migrasi. Tidak butuh mysql client, cukup ekstensi pdo_mysql.
 */
$host = getenv('DB_HOST') ?: 'mysql';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: '';
$username = getenv('DB_USERNAME') ?: '';
$password = getenv('DB_PASSWORD') ?: '';
$timeout = (int) (getenv('DB_WAIT_TIMEOUT') ?: 60);

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

for ($attempt = 1; $attempt <= $timeout; $attempt++) {
    try {
        new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2,
        ]);

        fwrite(STDOUT, "[entrypoint] MySQL siap (percobaan ke-{$attempt}).\n");
        exit(0);
    } catch (PDOException $exception) {
        if ($attempt === $timeout) {
            fwrite(STDERR, "[entrypoint] Gagal terhubung ke MySQL: {$exception->getMessage()}\n");
            exit(1);
        }

        sleep(1);
    }
}
