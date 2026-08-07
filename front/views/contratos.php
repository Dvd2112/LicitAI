<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

layout_header('Contratos', 'contratos');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$licitantes = array_unique(array_column($retornos, 'licitante'));
?>
<?php if ($flash): ?>
    <div class="alert alert-success alert-flash d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <div><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0"><i class="bi bi-briefcase me-2 text-info"></i>Contratos</h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novoContratoModal">
            <i class="bi bi-plus-lg me-1"></i>Novo Contrato
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Número</th>
                    <th>Licitante</th>
                    <th>Status</th>
                    <th style="min-width: 200px;">% Entregue</th>
                    <th>Valor</th>
                    <th class="action-cell">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contratos as $contrato): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($contrato['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($contrato['numero'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($contrato['licitante'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= $contratoStatus[$contrato['status']]['class'] ?>">
                                <?= $contratoStatus[$contrato['status']]['label'] ?>
                            </span>
                        </td>
                        <td>
                            <?= percentualBarra((int) $contrato['percentual']) ?>
                        </td>
                        <td><?= 'R$ ' . number_format((float) $contrato['valor'], 2, ',', '.') ?></td>
                        <td class="action-cell">
                            <a href="#" class="btn btn-outline-info btn-sm icon-btn" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form method="post" action="/contratos/remover" class="d-inline" data-confirm="Excluir permanentemente este contrato?">
                                <input type="hidden" name="id" value="<?= (int) $contrato['id'] ?>">
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

<div class="modal fade" id="novoContratoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="/contratos">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-success"></i>Cadastrar contrato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="c_nome">Nome do contrato</label>
                            <input type="text" class="form-control" id="c_nome" name="nome" placeholder="Ex.: Aquisição de mobiliário" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c_numero">Número do contrato</label>
                            <input type="text" class="form-control" id="c_numero" name="numero" placeholder="CT-2026/005" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c_licitante">Licitante</label>
                            <select class="form-select" id="c_licitante" name="licitante" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($licitantes as $licitante): ?>
                                    <option value="<?= htmlspecialchars($licitante, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($licitante, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c_status">Status</label>
                            <select class="form-select" id="c_status" name="status" required>
                                <option value="em_elaboracao">Em elaboração</option>
                                <option value="ativo" selected>Ativo</option>
                                <option value="suspenso">Suspenso</option>
                                <option value="encerrado">Encerrado</option>
                                <option value="vencido">Vencido</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="c_valor">Valor (R$)</label>
                            <input type="number" class="form-control" id="c_valor" name="valor" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="c_percentual">% entregue</label>
                            <input type="number" class="form-control" id="c_percentual" name="percentual" min="0" max="100" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="c_vigencia">Vigência até</label>
                            <input type="date" class="form-control" id="c_vigencia" name="vigencia" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Confirmar cadastro</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
layout_footer();
