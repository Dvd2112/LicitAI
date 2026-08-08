<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Services\ProposalService;

require_once __DIR__ . '/layout.php';

layout_header('Proposta — ' . $proposta['licitation_number'], 'licitantes');

$status = ProposalService::statusMeta($proposta['status']);
$canAnalyze = in_array($proposta['status'], ['analyzing', 'analyzed', 'needs_review', 'error'], true);
?>

<a href="/licitantes" class="text-muted small"><i class="ti ti-arrow-left me-1"></i>Voltar para propostas</a>

<div class="flex-between mt-1 mb-3">
    <h1 class="h4 mb-0">
        <?= htmlspecialchars($proposta['corporate_name'], ENT_QUOTES, 'UTF-8') ?>
        <span class="text-muted fw-normal">— <?= htmlspecialchars($proposta['licitation_number'], ENT_QUOTES, 'UTF-8') ?></span>
    </h1>
    <div class="flex">
        <span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span>
        <?php if ($isAdmin && $canAnalyze): ?>
            <form method="post" action="/analisar">
                <?= Csrf::field() ?>
                <input type="hidden" name="proposal_id" value="<?= (int) $proposta['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="ti ti-sparkles me-1"></i><?= in_array($proposta['status'], ['analyzed', 'needs_review'], true) ? 'Reanalisar' : 'Analisar com IA' ?>
                </button>
            </form>
        <?php elseif ($isAdmin): ?>
            <span class="text-muted small" title="Aguarde a extração dos documentos ser concluída">
                <i class="ti ti-clock me-1"></i>Aguardando extração
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if ($proposta['status'] === 'error' && $proposta['error_message']): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
        <i class="ti ti-alert-triangle"></i>
        <div><?= htmlspecialchars($proposta['error_message'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="grid-2 mb-4">
    <div class="card">
        <div class="card-header"><span class="form-label mb-0">Empresa</span></div>
        <table class="data-table">
            <tr><th>Razão social</th><td><?= htmlspecialchars($proposta['corporate_name'], ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>CNPJ</th><td><?= htmlspecialchars($proposta['cnpj'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        </table>
    </div>
    <div class="card">
        <div class="card-header"><span class="form-label mb-0">Licitação</span></div>
        <table class="data-table">
            <tr><th>Número</th><td><a href="/edital?id=<?= (int) $proposta['licitation_id'] ?>"><?= htmlspecialchars($proposta['licitation_number'], ENT_QUOTES, 'UTF-8') ?></a></td></tr>
            <tr><th>Título</th><td><?= htmlspecialchars($proposta['licitation_title'], ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Envio</th><td><?= $proposta['submitted_at'] ? date('d/m/Y H:i', strtotime($proposta['submitted_at'])) : '—' ?></td></tr>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><span class="form-label mb-0"><i class="ti ti-paperclip me-1 text-primary"></i>Documentos (<?= count($documentos) ?>)</span></div>
    <div class="card-body p-0">
        <?php if (!$documentos): ?>
            <div class="empty-state"><i class="ti ti-paperclip"></i>Nenhum documento anexado.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Arquivo</th><th>Tamanho</th><th>Extração</th><th class="action-cell">Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($documentos as $doc): ?>
                        <?php $ext = $extractions[$doc['id']] ?? null; ?>
                        <tr>
                            <td><?= htmlspecialchars($doc['original_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-muted small"><?= number_format($doc['size_bytes'] / 1024, 0) ?> KB</td>
                            <td>
                                <?php if (!$ext): ?>
                                    <span class="text-muted small">—</span>
                                <?php elseif ($ext['status'] === 'done'): ?>
                                    <span class="text-success small"><i class="ti ti-circle-check me-1"></i><?= (int) $ext['page_count'] ?> pág. (<?= $ext['method'] ?>)</span>
                                <?php elseif ($ext['status'] === 'failed'): ?>
                                    <span class="text-danger small"><i class="ti ti-alert-triangle me-1"></i>Falhou</span>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="ti ti-clock me-1"></i><?= $ext['status'] === 'processing' ? 'Processando' : 'Na fila' ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="action-cell">
                                <a href="/documento?tipo=proposta&id=<?= (int) $doc['id'] ?>" target="_blank" class="btn btn-outline-info btn-sm icon-btn" title="Abrir PDF">
                                    <i class="ti ti-file-search"></i>
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

<?php if (!empty($evaluationSummary)): ?>
<div class="card">
    <div class="card-header flex-between">
        <span class="form-label mb-0"><i class="ti ti-clipboard-check me-1 text-primary"></i>Análise de requisitos</span>
        <span class="fw-bold text-primary"><?= $evaluationSummary['adherence_percentage'] ?>% de aderência</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Requisito</th><th>Classificação</th><th>Confiança</th><th>Revisão</th><th class="action-cell"></th></tr></thead>
                <tbody>
                <?php foreach ($evaluations as $ev): ?>
                    <?php $cls = \App\Services\EvaluationService::classificationMeta($ev['human_classification'] ?: $ev['classification']); ?>
                    <tr>
                        <td><?= htmlspecialchars($ev['requirement_description'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge <?= $cls['class'] ?>"><?= $cls['label'] ?></span></td>
                        <td class="text-muted small"><?= $ev['confidence'] !== null ? round(((float) $ev['confidence']) * 100) . '%' : '—' ?></td>
                        <td class="text-muted small"><?= $ev['human_reviewed'] ? 'Revisado' : '—' ?></td>
                        <td class="action-cell">
                            <a href="/avaliacao?id=<?= (int) $ev['id'] ?>" class="btn btn-outline-info btn-sm icon-btn" title="Detalhar">
                                <i class="ti ti-zoom-scan"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
layout_footer();
