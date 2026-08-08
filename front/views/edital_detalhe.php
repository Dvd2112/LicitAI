<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Services\LicitationService;
use App\Services\ProposalService;

require_once __DIR__ . '/layout.php';

layout_header('Licitação ' . $licitacao['number'], 'edital');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$status = LicitationService::statusMeta($licitacao['status']);

$requirementCategories = [
    'documentacao' => 'Documentação obrigatória',
    'especificacao_tecnica' => 'Especificação técnica',
    'quantidade' => 'Quantidade',
    'certificacao' => 'Certificação',
    'experiencia' => 'Experiência',
    'prazo' => 'Prazo',
    'condicao_comercial' => 'Condição comercial',
    'capacidade_tecnica' => 'Capacidade técnica',
    'obrigacao_legal' => 'Obrigação legal',
    'requisito_financeiro' => 'Requisito financeiro',
    'outro' => 'Outro',
];

$myProposal = null;
if (!$isAdmin && $companyId) {
    foreach ($propostas as $p) {
        if ((int) $p['company_id'] === (int) $companyId) {
            $myProposal = $p;
            break;
        }
    }
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

<div class="flex-between mb-3">
    <div>
        <a href="/edital" class="text-muted small"><i class="ti ti-arrow-left me-1"></i>Voltar para licitações</a>
        <h1 class="h4 mb-0 mt-1"><?= htmlspecialchars($licitacao['number'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($licitacao['title'], ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
    <div class="flex">
        <span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span>
        <?php if (!$isAdmin && $licitacao['status'] === 'open' && !$myProposal): ?>
            <a href="/licitantes?enviar=<?= (int) $licitacao['id'] ?>" class="btn btn-success btn-sm">
                <i class="ti ti-send me-1"></i>Enviar proposta
            </a>
        <?php elseif ($myProposal): ?>
            <?php $myStatus = ProposalService::statusMeta($myProposal['status']); ?>
            <span class="text-muted small">Sua proposta: <span class="badge <?= $myStatus['class'] ?>"><?= $myStatus['label'] ?></span></span>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2 mb-4">
    <div class="card">
        <div class="card-header"><span class="form-label mb-0">Identificação</span></div>
        <table class="data-table">
            <tr><th>Entidade executora</th><td><?= htmlspecialchars($licitacao['executing_entity'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Tipo</th><td><?= htmlspecialchars($licitacao['type'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Processo administrativo</th><td><?= htmlspecialchars($licitacao['administrative_process'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Situação</th><td><?= htmlspecialchars($licitacao['situation'], ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Valor total</th><td class="fw-bold text-primary">R$ <?= number_format((float) $licitacao['total_value'], 2, ',', '.') ?></td></tr>
        </table>
    </div>
    <div class="card">
        <div class="card-header"><span class="form-label mb-0">Prazos</span></div>
        <table class="data-table">
            <tr><th>Publicação</th><td><?= $licitacao['publication_date'] ? date('d/m/Y', strtotime($licitacao['publication_date'])) : '—' ?></td></tr>
            <tr><th>Limite para propostas</th><td><?= $licitacao['proposal_deadline'] ? date('d/m/Y', strtotime($licitacao['proposal_deadline'])) : '—' ?></td></tr>
            <tr><th>Abertura de propostas</th><td><?= $licitacao['opening_date'] ? date('d/m/Y', strtotime($licitacao['opening_date'])) : '—' ?></td></tr>
            <tr><th>Vigência</th><td><?= $licitacao['validity_date'] ? date('d/m/Y', strtotime($licitacao['validity_date'])) : '—' ?></td></tr>
        </table>
    </div>
</div>

<div class="grid-2 mb-4">
    <div class="card">
        <div class="card-header"><span class="form-label mb-0"><i class="ti ti-list-check me-1 text-primary"></i>Itens (<?= count($itens) ?>)</span></div>
        <div class="card-body p-0">
            <?php if (!$itens): ?>
                <div class="empty-state"><i class="ti ti-list-check"></i>Nenhum item cadastrado.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Item</th><th>Un.</th><th>Qtd.</th><th>Unitário</th></tr></thead>
                        <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($item['unit'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= rtrim(rtrim(number_format((float) $item['quantity'], 3, ',', '.'), '0'), ',') ?></td>
                                <td>R$ <?= number_format((float) $item['unit_price'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="form-label mb-0"><i class="ti ti-map-pin me-1 text-success"></i>Unidades executoras (<?= count($unidades) ?>)</span></div>
        <div class="card-body p-0">
            <?php if (!$unidades): ?>
                <div class="empty-state"><i class="ti ti-map-pin"></i>Nenhuma unidade cadastrada.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Unidade</th><th>Responsável</th></tr></thead>
                        <tbody>
                        <?php foreach ($unidades as $unidade): ?>
                            <tr>
                                <td><?= htmlspecialchars($unidade['unit_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($unidade['responsible_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header flex-between">
        <span class="form-label mb-0"><i class="ti ti-clipboard-check me-1 text-primary"></i>Requisitos (<?= count($requisitos) ?>)</span>
        <?php if ($isAdmin): ?>
            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#novoRequisitoModal">
                <i class="ti ti-plus me-1"></i>Requisito
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (!$requisitos): ?>
            <div class="empty-state">
                <i class="ti ti-clipboard-check"></i>
                Nenhum requisito cadastrado ainda. Sem requisitos estruturados, a análise automática das propostas não pode ser feita.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Descrição</th>
                        <th>Obrigatório</th>
                        <?php if ($isAdmin): ?><th class="action-cell">Ações</th><?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requisitos as $requisito): ?>
                        <tr>
                            <td class="text-muted small"><?= htmlspecialchars($requirementCategories[$requisito['category']] ?? $requisito['category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($requisito['description'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $requisito['mandatory'] ? '<span class="text-danger">Sim</span>' : '<span class="text-muted">Não</span>' ?></td>
                            <?php if ($isAdmin): ?>
                                <td class="action-cell">
                                    <form method="post" action="/edital/requisito/remover" class="d-inline" data-confirm="Remover este requisito?">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $requisito['id'] ?>">
                                        <input type="hidden" name="licitation_id" value="<?= (int) $licitacao['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm icon-btn" title="Remover">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header flex-between">
        <span class="form-label mb-0"><i class="ti ti-file-check me-1 text-primary"></i>Propostas recebidas (<?= count($propostas) ?>)</span>
        <?php if ($isAdmin && count($propostas) >= 2): ?>
            <a href="/analise?licitation_id=<?= (int) $licitacao['id'] ?>" class="btn btn-outline-info btn-sm">
                <i class="ti ti-chart-bar me-1"></i>Comparar empresas
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (!$propostas): ?>
            <div class="empty-state"><i class="ti ti-file-check"></i>Nenhuma proposta recebida ainda.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Empresa</th><th>CNPJ</th><th>Status</th><th>Envio</th><th class="action-cell">Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($propostas as $proposta): ?>
                        <?php $pStatus = ProposalService::statusMeta($proposta['status']); ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($proposta['corporate_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($proposta['cnpj'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $pStatus['class'] ?>"><?= $pStatus['label'] ?></span></td>
                            <td class="text-muted small"><?= $proposta['submitted_at'] ? date('d/m/Y', strtotime($proposta['submitted_at'])) : '—' ?></td>
                            <td class="action-cell">
                                <a href="/licitantes?id=<?= (int) $proposta['id'] ?>" class="btn btn-outline-info btn-sm icon-btn" title="Visualizar">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="novoRequisitoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="/edital/requisito">
                <?= Csrf::field() ?>
                <input type="hidden" name="licitation_id" value="<?= (int) $licitacao['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-clipboard-plus me-2 text-success"></i>Novo requisito</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="grid-2 mb-3">
                        <div>
                            <label class="form-label" for="req_categoria">Categoria</label>
                            <select class="form-select" id="req_categoria" name="category">
                                <?php foreach ($requirementCategories as $value => $label): ?>
                                    <option value="<?= $value ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="toggle-field mt-4">
                            <span class="form-label mb-0">Obrigatório</span>
                            <label class="switch">
                                <input type="checkbox" name="mandatory" value="1" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <label class="form-label" for="req_descricao">Descrição do requisito</label>
                    <textarea class="form-control" id="req_descricao" name="description" rows="3" placeholder="Ex.: A empresa deve comprovar certificação ISO 9001 vigente." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="ti ti-check me-1"></i>Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
layout_footer();
