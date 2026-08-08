<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(__DIR__ . '/../.env');

$dbName = Env::get('DB_NAME', 'simplificagov');

$server = Database::serverConnection();
$server->exec(sprintf(
    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    str_replace('`', '', $dbName)
));

$pdo = Database::connection();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(190) NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_schema_migrations_migration (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$appliedSet = array_flip($applied);

$files = glob(__DIR__ . '/../database/migrations/*.sql');
sort($files, SORT_STRING);

if (!$files) {
    fwrite(STDOUT, "Nenhuma migration encontrada em database/migrations.\n");
    exit(0);
}

$ran = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (isset($appliedSet[$name])) {
        continue;
    }

    $sql = file_get_contents($file);
    $statements = array_filter(array_map('trim', explode(";\n", $sql)));
    // Statements split on ";\n" may still carry a trailing ";" on the last line of each block — strip it.
    $statements = array_filter(array_map(static fn(string $s): string => rtrim(trim($s), ';'), $statements));

    // MySQL/MariaDB auto-commits DDL, so transactions provide no rollback safety here — apply directly.
    try {
        foreach ($statements as $statement) {
            if ($statement === '') {
                continue;
            }
            $pdo->exec($statement);
        }

        $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
        $stmt->execute(['migration' => $name]);

        fwrite(STDOUT, "Aplicada: {$name}\n");
        $ran++;
    } catch (Throwable $e) {
        fwrite(STDERR, "Falha ao aplicar {$name}: {$e->getMessage()}\n");
        exit(1);
    }
}

fwrite(STDOUT, $ran > 0 ? "Concluído. {$ran} migration(s) aplicada(s).\n" : "Nada a fazer. Banco já está atualizado.\n");
