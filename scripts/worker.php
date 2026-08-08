<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\ExtractionService;

Env::load(__DIR__ . '/../.env');
$pdo = Database::connection();

$results = ExtractionService::runPendingJobs($pdo, 20);

if (!$results) {
    fwrite(STDOUT, "Nenhum job de extração pendente.\n");
    exit(0);
}

foreach ($results as $r) {
    $line = "job #{$r['job_id']}: {$r['status']}";
    if (isset($r['error'])) {
        $line .= ' — ' . $r['error'];
    }
    fwrite(STDOUT, $line . "\n");
}
