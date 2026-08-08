<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Services\ContractService;

require_once __DIR__ . '/layout.php';

layout_header('Contratos', 'contratos');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

function percentualBarra(int $percentual): string
{
    $class = $percentual >= 70 ? 'bg-success' : ($percentual >= 30 ? 'bg-warning' : 'bg-danger');

    return sprintf(
        '<div class="progress" role="progressbar" aria-valuenow="%d" aria-valuemin="0" aria-valuemax="100" title="%d%% entregue">'
        . '<div class="progress-bar %s" style="width: %d%%"></div></div>',
        $percentual,
        $percentual,
        $class,
        max(8, min(100, $percentual))
    );
}
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

<div class="flex-between mb-4">
    <div>
        <h1 class="h4 mb-1">Contratos</h1>
        <p class="text-muted mb-0">Acompanhe a execução física e financeira dos contratos vigentes.</p>
    </div>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novoContratoModal">
        <i class="ti ti-plus me-1"></i>Novo Contrato
    </button>
</div>

<div class="card mb-4">
    <div class="card-header flex-between">
        <span class="form-label mb-0">Todos os contratos</span>
        <span class="badge text-bg-info"><?= count($contratos) ?> registro<?= count($contratos) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (!$contratos): ?>
            <div class="empty-state">
                <i class="ti ti-briefcase"></i>
                Nenhum contrato cadastrado ainda.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Número</th>
                        <th>Fornecedor</th>
                        <th>Status</th>
                        <th style="min-width: 200px;">Execução física</th>
                        <th>Valor</th>
                        <th class="action-cell">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($contratos as $contrato): ?>
                        <?php $status = ContractService::statusMeta($contrato['status']); ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($contrato['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($contrato['number'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($contrato['corporate_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                            <td><?= percentualBarra((int) $contrato['physical_execution_percentage']) ?></td>
                            <td><?= 'R$ ' . number_format((float) $contrato['value'], 2, ',', '.') ?></td>
                            <td class="action-cell">
                                <form method="post" action="/contratos/remover" class="d-inline" data-confirm="Excluir permanentemente este contrato?">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $contrato['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm icon-btn" title="Excluir">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="novoContratoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="/contratos">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-briefcase me-2 text-success"></i>Cadastrar contrato</h5>
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
                            <label class="form-label" for="c_empresa">Fornecedor</label>
                            <select class="form-select" id="c_empresa" name="company_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($empresasDisponiveis as $empresa): ?>
                                    <option value="<?= (int) $empresa['id'] ?>">
                                        <?= htmlspecialchars($empresa['corporate_name'], ENT_QUOTES, 'UTF-8') ?>
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
                            <label class="form-label" for="c_percentual">% execução física</label>
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
                    <button type="submit" class="btn btn-success"><i class="ti ti-check me-1"></i>Confirmar cadastro</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
layout_footer();
