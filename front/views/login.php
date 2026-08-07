<?php

declare(strict_types=1);

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — LicitAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" rel="stylesheet">
    <link href="/assets/app.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="card login-card">
        <div class="card-body">
            <div class="login-logo"><i class="ti ti-gavel"></i></div>
            <h1 class="h4 text-center fw-bold mb-1">LicitAI</h1>
            <p class="text-center text-muted small mb-4">Análise de propostas — Lei 14.133/2021</p>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="/login">
                <div class="mb-3">
                    <label class="form-label" for="usuario">Usuário</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="senha">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                <button type="submit" class="btn btn-info w-100">
                    <i class="ti ti-login me-1"></i>Entrar
                </button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">Acesso de demonstração: admin / admin123</p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
