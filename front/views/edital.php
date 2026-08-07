<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

layout_header('Edital', 'edital');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<?php if ($flash): ?>
    <div class="alert alert-success alert-flash d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <div><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="h5 mb-0"><i class="bi bi-file-earmark-text me-2 text-info"></i>Inserir documentação do edital</h2>
    </div>
    <div class="card-body">
        <form method="post" action="/edital" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="numero">Número do edital</label>
                    <input type="text" class="form-control" id="numero" name="numero" placeholder="PE-2026/005" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="titulo">Título / objeto</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Ex.: Aquisição de mobiliário escolar" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="orgao">Órgão</label>
                    <input type="text" class="form-control" id="orgao" name="orgao" placeholder="SEINFRA" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="documentos">Documentos (TR, anexos — PDF)</label>
                    <div class="drop-zone" data-target="documentos">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <p class="mb-0 mt-2">Arraste os arquivos aqui ou clique para selecionar</p>
                        <small class="text-muted drop-zone-files">Nenhum arquivo selecionado</small>
                    </div>
                    <input type="file" class="d-none" id="documentos" name="documentos[]" accept="application/pdf" multiple>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Salvar edital</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0"><i class="bi bi-list-ul me-2 text-info"></i>Editais enviados</h2>
        <span class="badge text-bg-info"><?= count($editais) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Número</th>
                    <th>Título</th>
                    <th>Órgão</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th class="action-cell">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($editais as $edital): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($edital['numero'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($edital['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($edital['orgao'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= $editalStatus[$edital['status']]['class'] ?>">
                                <?= $editalStatus[$edital['status']]['label'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($edital['data'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="action-cell">
                            <a href="#" class="btn btn-outline-info btn-sm icon-btn" title="Visualizar" data-bs-toggle="tooltip">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form method="post" action="/edital/remover" class="d-inline" data-confirm="Excluir permanentemente este edital?">
                                <input type="hidden" name="id" value="<?= (int) $edital['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm icon-btn" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
layout_footer();
