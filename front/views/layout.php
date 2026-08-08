<?php

declare(strict_types=1);

use App\Services\AuthService;

function layout_header(string $title, string $active = ''): void
{
    global $pdo;

    $user = AuthService::currentUser();
    $isAdmin = ($user['role'] ?? null) === 'admin';

    $pendingReviewCount = 0;
    if ($isAdmin && $pdo instanceof PDO) {
        $pendingReviewCount = (int) $pdo->query("SELECT COUNT(*) FROM proposals WHERE status = 'needs_review'")->fetchColumn();
    }

    $navItems = [
        'dashboard' => ['/dashboard', 'Dashboard', 'ti-layout-dashboard', true],
        'edital' => ['/edital', 'Licitações', 'ti-file-text', true],
        'licitantes' => ['/licitantes', 'Propostas', 'ti-file-check', true],
        // 'empresas' => ['/empresas', 'Empresas', 'ti-building', false],
        // 'contratos' => ['/contratos', 'Contratos', 'ti-briefcase', false],
        'auditoria' => ['/auditoria', 'Auditoria', 'ti-list-search', false],
    ];
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> — SimplificaGov</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" rel="stylesheet">
    <link href="/assets/app.css?v=<?= filemtime(__DIR__ . '/../assets/app.css') ?>" rel="stylesheet">
    <link rel="icon" href="/assets/simbolo.png">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/dashboard">
            <img src="/assets/logo.png" alt="SimplificaGov" class="navbar-logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav navbar-nav-center">
                <?php foreach ($navItems as $key => [$href, $label, $icon, $companyVisible]): ?>
                    <?php if (!$isAdmin && !$companyVisible) continue; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $active === $key ? 'active fw-semibold' : '' ?>" href="<?= $href ?>">
                            <i class="ti <?= $icon ?> me-1"></i><?= $label ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if ($isAdmin): ?>
                    <div class="dropdown">
                        <button class="nav-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notificações">
                            <i class="ti ti-bell"></i>
                            <?php if ($pendingReviewCount > 0): ?>
                                <span class="nav-badge"><?= $pendingReviewCount > 9 ? '9+' : $pendingReviewCount ?></span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notificações</h6></li>
                            <?php if ($pendingReviewCount > 0): ?>
                                <li>
                                    <a class="dropdown-item" href="/licitantes">
                                        <?= $pendingReviewCount ?> proposta<?= $pendingReviewCount > 1 ? 's' : '' ?> requer<?= $pendingReviewCount > 1 ? 'em' : '' ?> revisão humana
                                    </a>
                                </li>
                            <?php else: ?>
                                <li><span class="dropdown-item-text text-muted small">Nenhuma pendência no momento.</span></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="user-menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="user-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr((string) ($user['name'] ?? '?'), 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= $isAdmin ? 'Perfil: Gestor' : 'Perfil: Empresa' ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="/logout">
                                <i class="ti ti-logout me-2"></i>Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
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
<footer class="app-footer">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>&copy; <?= date('Y') ?> SimplificaGov. Todos os direitos reservados.</span>
        <span class="d-flex gap-3">
            <span>Versão 1.0.0</span>
            <a href="#">Política de Privacidade</a>
        </span>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/app.js"></script>
</body>
</html>
    <?php
}
