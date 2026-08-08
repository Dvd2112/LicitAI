<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Services\ProposalService;

require_once __DIR__ . '/layout.php';

layout_header('Propostas', 'licitantes');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$preselect = (int) ($_GET['enviar'] ?? 0);
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
        <h1 class="h4 mb-1">Propostas</h1>
        <p class="text-muted mb-0">Acompanhe o status de processamento e análise das propostas enviadas.</p>
    </div>
    <div class="flex">
        <?php if ($isAdmin): ?>
            <form method="post" action="/processar" class="d-inline">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-refresh me-1"></i>Processar fila
                </button>
            </form>
        <?php endif; ?>
        <?php if ($isAdmin || $licitacoesAbertas): ?>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registrarPropostaModal">
                <i class="ti ti-plus me-1"></i>Registrar proposta
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header flex-between">
        <span class="form-label mb-0">Todas as propostas</span>
        <span class="badge text-bg-info"><?= count($propostas) ?> registro<?= count($propostas) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (!$propostas): ?>
            <div class="empty-state">
                <i class="ti ti-file-check"></i>
                Nenhuma proposta registrada ainda.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Licitação</th>
                        <th>Documentos</th>
                        <th>Processamento</th>
                        <th>Aderência</th>
                        <th>Envio</th>
                        <th class="action-cell">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($propostas as $proposta): ?>
                        <?php $status = ProposalService::statusMeta($proposta['status']); ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($proposta['licitation_number'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-muted small"><?= (int) $proposta['document_count'] ?> arquivo(s)</td>
                            <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                            <td class="text-muted small">
                                <?= $proposta['adherence_percentage'] !== null ? round((float) $proposta['adherence_percentage']) . '%' : '—' ?>
                            </td>
                            <td class="text-muted small"><?= $proposta['submitted_at'] ? date('d/m/Y', strtotime($proposta['submitted_at'])) : '—' ?></td>
                            <td class="action-cell">
                                <a href="/licitantes?id=<?= (int) $proposta['id'] ?>" class="btn btn-outline-info btn-sm icon-btn" title="Visualizar">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <?php if ($isAdmin): ?>
                                    <form method="post" action="/licitantes/remover" class="d-inline" data-confirm="Excluir permanentemente esta proposta?">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $proposta['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm icon-btn" title="Excluir">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="registrarPropostaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="/licitantes" enctype="multipart/form-data">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-send me-2 text-success"></i>Registrar proposta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="p_licitacao">Licitação *</label>
                            <select class="form-select" id="p_licitacao" name="licitation_id" required>
                                <option value="">Selecione a licitação...</option>
                                <?php foreach ($licitacoesAbertas as $lic): ?>
                                    <option value="<?= (int) $lic['id'] ?>" <?= $preselect === (int) $lic['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($lic['number'] . ' — ' . $lic['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php /* Empresa (oculto do popup): 
                        <?php if ($isAdmin): ?>
                            <div class="col-md-6">
                                <label class="form-label" for="p_empresa">Empresa *</label>
                                <select class="form-select" id="p_empresa" name="company_id" required>
                                    <option value="">Selecione a empresa...</option>
                                    <?php foreach ($empresasDisponiveis as $empresa): ?>
                                        <option value="<?= (int) $empresa['id'] ?>">
                                            <?= htmlspecialchars($empresa['corporate_name'] . ' — ' . $empresa['cnpj'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        */ ?>
                        <?php if ($isAdmin && $empresasDisponiveis): ?>
                            <input type="hidden" name="company_id" value="<?= (int) $empresasDisponiveis[0]['id'] ?>">
                        <?php endif; ?>
                        <div class="col-12">
                            <label class="form-label" for="p_documentos">Documentos da proposta (PDF) *</label>
                            <div class="drop-zone" data-target="p_documentos">
                                <i class="ti ti-cloud-upload"></i>
                                <p class="mb-0 mt-2">Arraste os arquivos aqui ou clique para selecionar</p>
                                <small class="text-muted drop-zone-files">Nenhum arquivo selecionado</small>
                            </div>
                            <input type="file" class="d-none" id="p_documentos" name="documentos[]" accept="application/pdf" multiple required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="ti ti-check me-1"></i>Enviar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($preselect): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = new bootstrap.Modal(document.getElementById('registrarPropostaModal'));
    modal.show();
});
</script>
<?php endif; ?>
<?php
layout_footer();
