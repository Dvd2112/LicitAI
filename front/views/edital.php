<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Services\LicitationService;

require_once __DIR__ . '/layout.php';

layout_header('Licitações', 'edital');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);
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
        <h1 class="h4 mb-1">Licitações</h1>
        <p class="text-muted mb-0">Gerencie as licitações cadastradas e acompanhe o andamento de cada uma.</p>
    </div>
    <?php if ($isAdmin): ?>
        <a href="/edital/novo" class="btn btn-success">
            <i class="ti ti-plus me-1"></i>Nova licitação
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header flex-between">
        <span class="form-label mb-0">Todas as licitações</span>
        <span class="badge text-bg-info"><?= count($licitacoes) ?> registro<?= count($licitacoes) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (!$licitacoes): ?>
            <div class="empty-state">
                <i class="ti ti-file-text"></i>
                Nenhuma licitação cadastrada ainda.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Número</th>
                        <th>Título</th>
                        <th>Entidade</th>
                        <th>Status</th>
                        <th>Requisitos</th>
                        <th>Propostas</th>
                        <th>Cadastro</th>
                        <th class="action-cell">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($licitacoes as $licitacao): ?>
                        <?php $status = LicitationService::statusMeta($licitacao['status']); ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($licitacao['number'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($licitacao['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($licitacao['executing_entity'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                            <td class="text-muted small"><?= (int) $licitacao['requirement_count'] ?></td>
                            <td class="text-muted small"><?= (int) $licitacao['proposal_count'] ?></td>
                            <td class="text-muted small"><?= date('d/m/Y', strtotime($licitacao['created_at'])) ?></td>
                            <td class="action-cell">
                                <a href="/edital?id=<?= (int) $licitacao['id'] ?>" class="btn btn-outline-info btn-sm icon-btn" title="Visualizar">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <?php if ($isAdmin): ?>
                                    <form method="post" action="/edital/remover" class="d-inline" data-confirm="Excluir permanentemente esta licitação?">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $licitacao['id'] ?>">
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

<?php
layout_footer();
