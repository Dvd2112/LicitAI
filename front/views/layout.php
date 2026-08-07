<?php

declare(strict_types=1);

function layout_header(string $title, string $active = ''): void
{
    $navItems = [
        'edital' => ['/edital', 'Edital', 'ti-file-text'],
        'licitantes' => ['/licitantes', 'Retornos dos Licitantes', 'ti-users'],
        'contratos' => ['/contratos', 'Contratos', 'ti-briefcase'],
    ];
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> — LicitAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" rel="stylesheet">
    <link href="/assets/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/edital">
            <span class="icon-btn bg-primary bg-opacity-10 text-primary"><i class="ti ti-gavel"></i></span>
            LicitAI
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($navItems as $key => [$href, $label, $icon]): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $active === $key ? 'active fw-semibold' : '' ?>" href="<?= $href ?>">
                            <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn-outline-danger btn-sm" href="/logout">
                <i class="ti ti-logout me-1"></i>Sair
            </a>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php
}

function layout_footer(): void
{
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/app.js"></script>
</body>
</html>
    <?php
}
