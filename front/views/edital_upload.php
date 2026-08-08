<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

layout_header('Upload de licitação', 'edital');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);
?>
<?php if ($flash): ?>
    <div class="alert alert-success alert-flash d-flex align-items-center gap-2" role="alert">
        <i class="ti ti-circle-check me-2"></i>
        <div><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-flash d-flex align-items-center gap-2" role="alert">
        <i class="ti ti-alert-triangle me-2"></i>
        <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="flex-between mb-3">
    <div>
        <a href="/edital" class="text-muted small"><i class="ti ti-arrow-left me-1"></i>Voltar para licitações</a>
        <h1 class="h4 mb-0 mt-1">Upload de licitação</h1>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="form-label mb-0"><i class="ti ti-sparkles me-1 text-primary"></i>Cadastro automático via IA</span>
    </div>
    <div class="card-body">
        <p class="text-muted">
            Envie o PDF do edital (ou aviso de licitação) e a IA vai ler o documento e extrair automaticamente
            número, título, itens, prazos e requisitos, cadastrando a licitação para você.
            Como a extração é feita por IA, revise os dados na página da licitação antes de considerá-la válida.
        </p>

        <form method="post" action="/edital/upload" enctype="multipart/form-data" id="formUploadEdital">
            <?= \App\Core\Csrf::field() ?>

            <div class="upload-area" data-target="up_documento">
                <i class="ti ti-file-upload"></i>
                <p>Arraste o PDF do edital aqui ou clique para selecionar</p>
                <small class="upload-files">Nenhum arquivo selecionado</small>
            </div>
            <input type="file" class="d-none" id="up_documento" name="documento" accept="application/pdf" required>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="/edital" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success" id="btnUploadEdital">
                    <i class="ti ti-sparkles me-1"></i>Importar com IA
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('formUploadEdital').addEventListener('submit', function () {
        var btn = document.getElementById('btnUploadEdital');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Lendo documento e extraindo dados...';
    });
</script>
<?php
layout_footer();
