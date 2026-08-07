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

<div class="card">
    <div class="card-header flex-between">
        <h2 class="h5 mb-0"><i class="bi bi-people me-2 text-primary"></i>Retornos recebidos</h2>
        <div class="flex">
            <span class="badge text-bg-info"><?= count($retornos) ?> registros</span>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novoRetornoModal">
                <i class="bi bi-plus-lg me-1"></i>Inserir retorno
            </button>
        </div>
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
                            <?php if ($retorno['status'] === 'aguardando' && empty($retorno['arquivos'])): ?>
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

<div class="modal fade" id="novoRetornoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="/licitantes" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2 text-success"></i>Inserir retorno do licitante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="r_edital_id">Edital</label>
                            <select class="form-select" id="r_edital_id" name="edital_id" required>
                                <option value="">Selecione o edital...</option>
                                <?php foreach ($editais as $edital): ?>
                                    <option value="<?= (int) $edital['id'] ?>">
                                        <?= htmlspecialchars($edital['numero'] . ' — ' . $edital['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="r_licitante">Licitante (razão social)</label>
                            <input type="text" class="form-control" id="r_licitante" name="licitante" placeholder="Ex.: Empresa Exemplo Ltda" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="r_cnpj">CNPJ</label>
                            <input type="text" class="form-control" id="r_cnpj" name="cnpj" data-cnpj placeholder="00.000.000/0000-00" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="r_propostas">Proposta e anexos (PDF)</label>
                            <div class="drop-zone" data-target="r_propostas">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <p class="mb-0 mt-2">Arraste os arquivos aqui ou clique para selecionar</p>
                                <small class="text-muted drop-zone-files">Nenhum arquivo selecionado</small>
                            </div>
                            <input type="file" class="d-none" id="r_propostas" name="propostas[]" accept="application/pdf" multiple>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
layout_footer();
