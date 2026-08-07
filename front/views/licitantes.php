<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

layout_header('Retornos dos Licitantes', 'licitantes');

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
        <h2 class="h5 mb-0"><i class="bi bi-people me-2 text-info"></i>Inserir retorno dos licitantes</h2>
    </div>
    <div class="card-body">
        <form method="post" action="/licitantes" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="edital_id">Edital</label>
                    <select class="form-select" id="edital_id" name="edital_id" required>
                        <option value="">Selecione o edital...</option>
                        <?php foreach ($editais as $edital): ?>
                            <option value="<?= (int) $edital['id'] ?>">
                                <?= htmlspecialchars($edital['numero'] . ' — ' . $edital['titulo'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="licitante">Licitante (razão social)</label>
                    <input type="text" class="form-control" id="licitante" name="licitante" placeholder="Ex.: Empresa Exemplo Ltda" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="cnpj">CNPJ</label>
                    <input type="text" class="form-control" id="cnpj" name="cnpj" data-cnpj placeholder="00.000.000/0000-00" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="propostas">Proposta e anexos (PDF)</label>
                    <div class="drop-zone" data-target="propostas">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <p class="mb-0 mt-2">Arraste os arquivos aqui ou clique para selecionar</p>
                        <small class="text-muted drop-zone-files">Nenhum arquivo selecionado</small>
                    </div>
                    <input type="file" class="d-none" id="propostas" name="propostas[]" accept="application/pdf" multiple>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Salvar retorno</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0"><i class="bi bi-list-ul me-2 text-info"></i>Retornos recebidos</h2>
        <span class="badge text-bg-info"><?= count($retornos) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Edital</th>
                    <th>Licitante</th>
                    <th>CNPJ</th>
                    <th>Processamento</th>
                    <th>Arquivos</th>
                    <th>Data</th>
                    <th class="action-cell">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($retornos as $retorno): ?>
                    <?php $edital = array_values(array_filter($editais, fn($e) => (int) $e['id'] === (int) $retorno['edital_id']))[0] ?? null; ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= $edital ? htmlspecialchars($edital['numero'], ENT_QUOTES, 'UTF-8') : '—' ?>
                        </td>
                        <td><?= htmlspecialchars($retorno['licitante'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($retorno['cnpj'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= $retornoStatus[$retorno['status']]['class'] ?>">
                                <?= $retornoStatus[$retorno['status']]['label'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($retorno['status'] === 'aguardando'): ?>
                                <span class="text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sem arquivos</span>
                            <?php else: ?>
                                <span class="text-muted small"><?= count($retorno['arquivos']) ?> arquivo(s)</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($retorno['data'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="action-cell">
                            <a href="#" class="btn btn-outline-info btn-sm icon-btn" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form method="post" action="/licitantes/remover" class="d-inline" data-confirm="Excluir permanentemente este retorno?">
                                <input type="hidden" name="id" value="<?= (int) $retorno['id'] ?>">
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
