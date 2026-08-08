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
    <title>Entrar — SimplificaGov</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" rel="stylesheet">
    <link href="/assets/app.css?v=<?= filemtime(__DIR__ . '/../assets/app.css') ?>" rel="stylesheet">
    <link rel="icon" href="/assets/simbolo.png">
</head>
<body class="login-body">
<div class="login-shell">
    <div class="login-hero">
        <div class="login-hero-content">
            <div class="login-hero-brand">
                <img src="/assets/logo.png" alt="SimplificaGov">
            </div>
            <h1 class="login-hero-title">Análise inteligente<span>para decisões melhores.</span></h1>
            <p class="login-hero-text">
                Simplificamos a análise de licitações públicas para promover eficiência,
                transparência e resultados para a gestão pública.
            </p>
        </div>
        <img src="/assets/mascote.png" alt="" class="login-hero-mascot">
    </div>

    <div class="login-panel">
        <div class="login-card">
            <img src="/assets/logo.png" alt="SimplificaGov" class="login-card-logo">
            <p class="login-card-subtitle">Apoio à análise de licitações públicas</p>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                    <i class="ti ti-alert-triangle"></i>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="/login">
                <?= \App\Core\Csrf::field() ?>

                <label class="form-label" for="usuario">Usuário</label>
                <div class="input-icon-group mb-3">
                    <i class="ti ti-user"></i>
                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Digite seu usuário" required autofocus>
                </div>

                <label class="form-label" for="senha">Senha</label>
                <div class="input-icon-group mb-3">
                    <i class="ti ti-lock"></i>
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required>
                    <button type="button" class="input-icon-toggle" id="toggleSenha" title="Mostrar/ocultar senha" tabindex="-1">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>

                <div class="flex-between login-options">
                    <label class="login-check">
                        <input type="checkbox" name="lembrar" value="1"> Lembrar-me
                    </label>
                    <a href="#" class="small">Esqueceu sua senha?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100 login-submit">
                    <i class="ti ti-login-2 me-1"></i>Entrar
                </button>
            </form>

            <div class="login-divider"><span><i class="ti ti-building-bank"></i></span></div>

            <p class="login-demo-hint">
                Acesso de demonstração: <a href="#" id="fillDemo">admin / admin123</a>
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var toggleBtn = document.getElementById('toggleSenha');
    var senhaInput = document.getElementById('senha');
    if (toggleBtn && senhaInput) {
        toggleBtn.addEventListener('click', function () {
            var isHidden = senhaInput.type === 'password';
            senhaInput.type = isHidden ? 'text' : 'password';
            toggleBtn.querySelector('i').className = isHidden ? 'ti ti-eye-off' : 'ti ti-eye';
        });
    }

    var fillDemo = document.getElementById('fillDemo');
    if (fillDemo) {
        fillDemo.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('usuario').value = 'admin';
            document.getElementById('senha').value = 'admin123';
        });
    }
</script>
</body>
</html>
