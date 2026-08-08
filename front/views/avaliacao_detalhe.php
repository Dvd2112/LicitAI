<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Services\EvaluationService;

require_once __DIR__ . '/layout.php';

layout_header('Avaliação de requisito', 'licitantes');

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$aiClass = EvaluationService::classificationMeta($evaluation['classification']);
$humanClass = $evaluation['human_classification'] ? EvaluationService::classificationMeta($evaluation['human_classification']) : null;

$classificationOptions = [
    'atende' => 'Atende',
    'atende_parcialmente' => 'Atende parcialmente',
    'nao_atende' => 'Não atende',
    'evidencia_insuficiente' => 'Evidência insuficiente',
    'nao_identificado' => 'Não identificado',
    'requer_revisao' => 'Requer revisão',
];
?>

<a href="/licitantes?id=<?= (int) $evaluation['proposal_id'] ?>" class="text-muted small">
    <i class="ti ti-arrow-left me-1"></i>Voltar para a proposta
</a>

<?php if ($flashError): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mt-3" role="alert">
        <i class="ti ti-alert-triangle me-2"></i>
        <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="mt-1 mb-3">
    <span class="text-muted small text-uppercase"><?= htmlspecialchars($evaluation['requirement_category'], ENT_QUOTES, 'UTF-8') ?><?= $evaluation['mandatory'] ? ' · obrigatório' : '' ?></span>
    <h1 class="h5 mb-0"><?= htmlspecialchars($evaluation['requirement_description'], ENT_QUOTES, 'UTF-8') ?></h1>
</div>

<div class="grid-2 mb-4">
    <div class="card">
        <div class="card-header flex-between">
            <span class="form-label mb-0"><i class="ti ti-robot me-1 text-primary"></i>Classificação da IA</span>
            <span class="badge <?= $aiClass['class'] ?>"><?= $aiClass['label'] ?></span>
        </div>
        <div class="card-body">
            <p class="mb-2"><?= nl2br(htmlspecialchars($evaluation['justification'], ENT_QUOTES, 'UTF-8')) ?></p>
            <div class="text-muted small">
                Confiança: <?= $evaluation['confidence'] !== null ? round(((float) $evaluation['confidence']) * 100) . '%' : 'não informada' ?>
                · Modelo: <?= htmlspecialchars($evaluation['ai_model'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                · <?= date('d/m/Y H:i', strtotime($evaluation['created_at'])) ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex-between">
            <span class="form-label mb-0"><i class="ti ti-user-check me-1 text-success"></i>Revisão humana</span>
            <?php if ($humanClass): ?><span class="badge <?= $humanClass['class'] ?>"><?= $humanClass['label'] ?></span><?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!$evaluation['human_reviewed']): ?>
                <p class="text-muted mb-0">Ainda não revisado por um responsável humano.</p>
            <?php else: ?>
                <p class="mb-2"><?= $evaluation['human_justification'] ? nl2br(htmlspecialchars($evaluation['human_justification'], ENT_QUOTES, 'UTF-8')) : '<span class="text-muted">Sem observações adicionais.</span>' ?></p>
                <div class="text-muted small">
                    Revisado em <?= date('d/m/Y H:i', strtotime($evaluation['human_reviewed_at'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><span class="form-label mb-0"><i class="ti ti-quote me-1 text-primary"></i>Evidências (<?= count($evidencias) ?>)</span></div>
    <div class="card-body p-0">
        <?php if (!$evidencias): ?>
            <div class="empty-state"><i class="ti ti-quote"></i>Nenhuma evidência textual foi vinculada a esta avaliação.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Trecho</th><th>Página</th><th class="action-cell"></th></tr></thead>
                    <tbody>
                    <?php foreach ($evidencias as $evd): ?>
                        <tr>
                            <td><em>"<?= htmlspecialchars($evd['excerpt'], ENT_QUOTES, 'UTF-8') ?>"</em></td>
                            <td class="text-muted small"><?= $evd['page_number'] ?? '—' ?></td>
                            <td class="action-cell">
                                <?php if ($evd['proposal_document_id']): ?>
                                    <a href="/documento?tipo=proposta&id=<?= (int) $evd['proposal_document_id'] ?>" target="_blank" class="btn btn-outline-info btn-sm icon-btn" title="Abrir documento">
                                        <i class="ti ti-file-search"></i>
                                    </a>
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

<?php if ($isAdmin): ?>
<div class="card">
    <div class="card-header"><span class="form-label mb-0"><i class="ti ti-edit me-1 text-primary"></i>Validar / alterar classificação</span></div>
    <div class="card-body">
        <form method="post" action="/avaliacao/revisar">
            <?= Csrf::field() ?>
            <input type="hidden" name="evaluation_id" value="<?= (int) $evaluation['id'] ?>">
            <div class="grid-2 mb-3">
                <div>
                    <label class="form-label" for="rv_classificacao">Classificação</label>
                    <select class="form-select" id="rv_classificacao" name="classification">
                        <?php $current = $evaluation['human_classification'] ?: $evaluation['classification']; ?>
                        <?php foreach ($classificationOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $current === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <label class="form-label" for="rv_justificativa">Observação da revisão</label>
            <textarea class="form-control mb-3" id="rv_justificativa" name="justification" rows="3" placeholder="Explique sua decisão (opcional se estiver apenas confirmando a análise da IA)."><?= htmlspecialchars($evaluation['human_justification'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <button type="submit" class="btn btn-success">
                <i class="ti ti-check me-1"></i>Salvar revisão
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
<?php
layout_footer();
