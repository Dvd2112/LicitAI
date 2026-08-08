<?php

declare(strict_types=1);

use App\Services\EvaluationService;
use App\Services\LicitationService;

require_once __DIR__ . '/layout.php';

layout_header('Análise da licitação', 'edital');

$symbols = [
    'atende' => ['s' => '✓', 'class' => 'text-success'],
    'atende_parcialmente' => ['s' => '!', 'class' => 'text-warning'],
    'nao_atende' => ['s' => '✕', 'class' => 'text-danger'],
    'evidencia_insuficiente' => ['s' => '—', 'class' => 'text-muted'],
    'nao_identificado' => ['s' => '—', 'class' => 'text-muted'],
    'requer_revisao' => ['s' => '?', 'class' => 'text-warning'],
];

$lastUpdate = null;
foreach ($matrix['scores'] as $s) {
    if ($s && (!$lastUpdate || $s['calculated_at'] > $lastUpdate)) {
        $lastUpdate = $s['calculated_at'];
    }
}
?>

<a href="/edital?id=<?= (int) $licitacao['id'] ?>" class="text-muted small">
    <i class="ti ti-arrow-left me-1"></i>Voltar para a licitação
</a>

<div class="mt-1 mb-4">
    <h1 class="h4 mb-1">Análise — <?= htmlspecialchars($licitacao['number'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="text-muted mb-0"><?= htmlspecialchars($licitacao['title'], ENT_QUOTES, 'UTF-8') ?></p>
</div>

<div class="stat-grid mb-4">
    <div class="stat-tile">
        <div class="stat-label">Propostas recebidas</div>
        <div class="stat-value"><?= count($matrix['proposals']) ?></div>
    </div>
    <div class="stat-tile">
        <div class="stat-label">Requisitos analisados</div>
        <div class="stat-value"><?= count($matrix['requirements']) ?></div>
    </div>
    <div class="stat-tile">
        <div class="stat-label">Status</div>
        <div class="stat-value" style="font-size:1.1rem;"><?= LicitationService::statusMeta($licitacao['status'])['label'] ?></div>
    </div>
    <div class="stat-tile">
        <div class="stat-label">Última atualização</div>
        <div class="stat-value" style="font-size:1.1rem;"><?= $lastUpdate ? date('d/m/Y H:i', strtotime($lastUpdate)) : '—' ?></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><span class="form-label mb-0"><i class="ti ti-chart-bar me-1 text-primary"></i>Visão geral — aderência documental</span></div>
    <div class="card-body p-0">
        <?php if (!$matrix['proposals']): ?>
            <div class="empty-state"><i class="ti ti-chart-bar"></i>Nenhuma proposta recebida ainda.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Proposta</th><th>Aderência</th><th>Requisitos atendidos</th></tr></thead>
                    <tbody>
                    <?php foreach ($matrix['proposals'] as $p): ?>
                        <?php $score = $matrix['scores'][(int) $p['id']] ?? null; ?>
                        <tr>
                            <td class="fw-semibold"><a href="/licitantes?id=<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['corporate_name'], ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td>
                                <?php if ($score): ?>
                                    <div class="progress" style="min-width:160px;">
                                        <div class="progress-bar" style="width: <?= max(4, (float) $score['adherence_percentage']) ?>%"></div>
                                    </div>
                                    <span class="fw-semibold"><?= $score['adherence_percentage'] ?>%</span>
                                <?php else: ?>
                                    <span class="text-muted small">Análise pendente</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <?= $score ? "{$score['attended']}/{$score['total_requirements']}" : '—' ?>
                            </td>
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
        <span class="form-label mb-0"><i class="ti ti-table me-1 text-primary"></i>Matriz comparativa</span>
        <div class="flex text-muted small">
            <span><span class="text-success">✓</span> Atende</span>
            <span><span class="text-warning">!</span> Parcial</span>
            <span><span class="text-danger">✕</span> Não atende</span>
            <span><span class="text-muted">—</span> Sem evidência</span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!$matrix['requirements'] || !$matrix['proposals']): ?>
            <div class="empty-state"><i class="ti ti-table"></i>É preciso ter requisitos cadastrados e propostas recebidas para montar a matriz.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-center">
                    <thead>
                    <tr>
                        <th class="text-start">Requisito</th>
                        <?php foreach ($matrix['proposals'] as $p): ?>
                            <th><?= htmlspecialchars($p['corporate_name'], ENT_QUOTES, 'UTF-8') ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($matrix['requirements'] as $req): ?>
                        <tr>
                            <td class="text-start"><?= htmlspecialchars($req['description'], ENT_QUOTES, 'UTF-8') ?></td>
                            <?php foreach ($matrix['proposals'] as $p): ?>
                                <?php $cell = $matrix['cells'][(int) $p['id']][(int) $req['id']] ?? null; ?>
                                <td>
                                    <?php if (!$cell): ?>
                                        <span class="text-muted">—</span>
                                    <?php else: ?>
                                        <?php
                                        $effective = $cell['human_classification'] ?: $cell['classification'];
                                        $sym = $symbols[$effective] ?? ['s' => '—', 'class' => 'text-muted'];
                                        ?>
                                        <a href="/avaliacao?id=<?= (int) $cell['id'] ?>" class="fw-bold <?= $sym['class'] ?>" style="text-decoration:none;" title="Ver detalhe">
                                            <?= $sym['s'] ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
layout_footer();
