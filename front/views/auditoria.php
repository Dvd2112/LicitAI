<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

layout_header('Auditoria', 'auditoria');

$actionLabels = [
    'login' => 'Login',
    'login_failed' => 'Tentativa de login falhou',
    'logout' => 'Logout',
    'create' => 'Criação',
    'delete' => 'Exclusão',
    'process_queue' => 'Processamento da fila',
    'analyze' => 'Análise por IA',
    'analyze_failed' => 'Falha na análise por IA',
    'human_review' => 'Revisão humana',
    'view_document' => 'Visualização de documento',
];

$entityLabels = [
    'user' => 'Usuário',
    'company' => 'Empresa',
    'licitation' => 'Licitação',
    'licitation_requirement' => 'Requisito',
    'proposal' => 'Proposta',
    'proposal_document' => 'Documento da proposta',
    'licitation_document' => 'Documento da licitação',
    'contract' => 'Contrato',
    'evaluation' => 'Avaliação',
    'job' => 'Fila de processamento',
];
?>

<div class="flex-between mb-4">
    <div>
        <h1 class="h4 mb-1">Auditoria</h1>
        <p class="text-muted mb-0">Histórico de ações críticas realizadas no sistema.</p>
    </div>
    <form method="get" action="/auditoria" class="flex">
        <select class="form-select" name="entity_type" onchange="this.form.submit()" style="min-width:200px;">
            <option value="">Todas as entidades</option>
            <?php foreach ($entityTypes as $type): ?>
                <option value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedType === $type ? 'selected' : '' ?>>
                    <?= htmlspecialchars($entityLabels[$type] ?? $type, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="card">
    <div class="card-header flex-between">
        <span class="form-label mb-0">Registros de auditoria</span>
        <span class="badge text-bg-info"><?= count($logs) ?> registro<?= count($logs) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (!$logs): ?>
            <div class="empty-state">
                <i class="ti ti-list-search"></i>
                Nenhum registro de auditoria encontrado.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Data/hora</th>
                        <th>Usuário</th>
                        <th>Ação</th>
                        <th>Entidade</th>
                        <th>ID</th>
                        <th>Detalhes</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-muted small"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                            <td><?= htmlspecialchars($log['user_name'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($actionLabels[$log['action']] ?? $log['action'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($entityLabels[$log['entity_type']] ?? $log['entity_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-muted small">#<?= $log['entity_id'] ?? '—' ?></td>
                            <td class="text-muted small">
                                <?php if ($log['details']): ?>
                                    <?php $d = json_decode($log['details'], true); ?>
                                    <?= htmlspecialchars(is_array($d) ? implode(', ', array_map(fn($k, $v) => "{$k}: {$v}", array_keys($d), $d)) : '', ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    —
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
<?php
layout_footer();
