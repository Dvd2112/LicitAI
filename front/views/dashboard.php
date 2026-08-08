<?php

declare(strict_types=1);

use App\Services\AuditLogger;
use App\Services\LicitationService;
use App\Services\ProposalService;

require_once __DIR__ . '/layout.php';

layout_header('Dashboard', 'dashboard');

$pendingStates = ['submitted', 'queued', 'extracting', 'analyzing', 'needs_review'];
$namedPendingParams = [];
$namedPendingPlaceholders = [];
foreach ($pendingStates as $i => $state) {
    $namedPendingPlaceholders[] = ":s{$i}";
    $namedPendingParams["s{$i}"] = $state;
}
$namedPlaceholders = implode(',', $namedPendingPlaceholders);

$periodLabels = ['month' => 'Este mês', '7d' => 'Últimos 7 dias', 'year' => 'Este ano', 'all' => 'Tudo'];
$period = (string) ($_GET['period'] ?? 'month');
if (!isset($periodLabels[$period])) {
    $period = 'month';
}
$since = match ($period) {
    'month' => date('Y-m-01 00:00:00'),
    '7d' => date('Y-m-d 00:00:00', strtotime('-7 days')),
    'year' => date('Y-01-01 00:00:00'),
    default => null,
};

if ($isAdmin) {
    $licitacoesAbertas = (int) $pdo->query("SELECT COUNT(*) FROM licitations WHERE status = 'open'")->fetchColumn();
    $totalPropostas = (int) $pdo->query('SELECT COUNT(*) FROM proposals')->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE status IN ($namedPlaceholders)");
    $stmt->execute($namedPendingParams);
    $pendentes = (int) $stmt->fetchColumn();

    $perPage = 5;
    $licPage = max(1, (int) ($_GET['lic_page'] ?? 1));
    $totalLicitacoes = (int) $pdo->query('SELECT COUNT(*) FROM licitations')->fetchColumn();
    $totalLicPages = max(1, (int) ceil($totalLicitacoes / $perPage));
    $licPage = min($licPage, $totalLicPages);
    $offset = ($licPage - 1) * $perPage;

    $stmt = $pdo->prepare("SELECT * FROM licitations ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute();
    $licitacoesRecentes = $stmt->fetchAll();

    $licitacoesAtencao = $pdo->query(
        "SELECT l.*,
                (SELECT COUNT(*) FROM proposals p WHERE p.licitation_id = l.id AND p.status = 'needs_review') AS needs_review,
                (SELECT COUNT(*) FROM proposals p WHERE p.licitation_id = l.id) AS proposal_count
         FROM licitations l
         WHERE l.status = 'open'
         ORDER BY needs_review DESC, l.created_at DESC
         LIMIT 5"
    )->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT * FROM licitations
         WHERE opening_date IS NOT NULL AND opening_date >= CURDATE()
         ORDER BY opening_date ASC
         LIMIT 5"
    );
    $stmt->execute();
    $proximasAberturas = $stmt->fetchAll();

    $atividadesRecentes = AuditLogger::recentActivity($pdo, 10, $since);
} else {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM proposals WHERE company_id = :id');
    $stmt->execute(['id' => $companyId]);
    $minhasPropostas = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE company_id = :id AND status IN ($namedPlaceholders)");
    $stmt->execute(array_merge(['id' => $companyId], $namedPendingParams));
    $pendentes = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE company_id = :id AND status = 'analyzed'");
    $stmt->execute(['id' => $companyId]);
    $concluidas = (int) $stmt->fetchColumn();

    $licitacoesAbertas = (int) $pdo->query("SELECT COUNT(*) FROM licitations WHERE status = 'open'")->fetchColumn();
    $minhasPropostasRecentes = ProposalService::listForCompany($pdo, $companyId);
    $minhasPropostasRecentes = array_slice($minhasPropostasRecentes, 0, 5);
}
?>
<div class="flex-between mb-4">
    <div>
        <h1 class="h4 mb-1">Visão geral das contratações</h1>
        <p class="text-muted mb-0">Acompanhe o andamento das licitações e propostas.</p>
    </div>
    <?php if ($isAdmin): ?>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Período: <i class="ti ti-calendar mx-1"></i><?= $periodLabels[$period] ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach ($periodLabels as $value => $label): ?>
                    <li>
                        <a class="dropdown-item <?= $period === $value ? 'fw-semibold' : '' ?>" href="?period=<?= $value ?>">
                            <?= $label ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
    <div class="stat-grid mb-4">
        <div class="stat-tile">
            <div class="stat-icon"><i class="ti ti-file-text"></i></div>
            <div class="stat-body">
                <div class="stat-label">Licitações em andamento</div>
                <div class="stat-value"><?= $licitacoesAbertas ?></div>
                <a href="/edital" class="stat-link">Ver todas <i class="ti ti-chevron-right"></i></a>
            </div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon stat-icon-success"><i class="ti ti-file-check"></i></div>
            <div class="stat-body">
                <div class="stat-label">Propostas recebidas</div>
                <div class="stat-value"><?= $totalPropostas ?></div>
                <a href="/licitantes" class="stat-link">Ver todas <i class="ti ti-chevron-right"></i></a>
            </div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon stat-icon-warning"><i class="ti ti-clock"></i></div>
            <div class="stat-body">
                <div class="stat-label">Análises pendentes</div>
                <div class="stat-value"><?= $pendentes ?></div>
                <a href="/licitantes" class="stat-link">Ver todas <i class="ti ti-chevron-right"></i></a>
            </div>
        </div>
    </div>

    <div class="grid-2 mb-4">
        <div class="card">
            <div class="card-header flex-between">
                <h2 class="h6 mb-0">Licitações recentes</h2>
                <a href="/edital" class="text-primary small">Ver todas <i class="ti ti-chevron-right"></i></a>
            </div>
            <div class="card-body p-0">
                <?php if (!$licitacoesRecentes): ?>
                    <div class="empty-state"><i class="ti ti-file-text"></i>Nenhuma licitação cadastrada ainda.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Número</th><th>Objeto</th><th>Modalidade</th><th>Abertura</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($licitacoesRecentes as $licitacao): ?>
                                <?php $status = LicitationService::statusMeta($licitacao['status']); ?>
                                <tr>
                                    <td class="fw-semibold"><a href="/edital?id=<?= (int) $licitacao['id'] ?>"><?= htmlspecialchars($licitacao['number'], ENT_QUOTES, 'UTF-8') ?></a></td>
                                    <td><?= htmlspecialchars($licitacao['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($licitacao['type'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-muted small"><?= $licitacao['opening_date'] ? date('d/m/Y', strtotime($licitacao['opening_date'])) : '—' ?></td>
                                    <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex-between p-3 border-top">
                        <span class="text-muted small"><?= $totalLicitacoes ?> registro<?= $totalLicitacoes === 1 ? '' : 's' ?></span>
                        <?php if ($totalLicPages > 1): ?>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= $licPage <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?period=<?= $period ?>&lic_page=<?= $licPage - 1 ?>">Anterior</a>
                                    </li>
                                    <li class="page-item active"><span class="page-link"><?= $licPage ?></span></li>
                                    <li class="page-item <?= $licPage >= $totalLicPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?period=<?= $period ?>&lic_page=<?= $licPage + 1 ?>">Próximo</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex-between">
                <h2 class="h6 mb-0">Licitações que exigem atenção</h2>
                <a href="/edital" class="text-primary small">Ver todas <i class="ti ti-chevron-right"></i></a>
            </div>
            <div class="card-body p-0">
                <?php if (!$licitacoesAtencao): ?>
                    <div class="empty-state"><i class="ti ti-circle-check"></i>Nenhuma licitação aberta requer atenção no momento.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Licitação</th><th>Revisão</th><th>Propostas</th></tr></thead>
                            <tbody>
                            <?php foreach ($licitacoesAtencao as $lic): ?>
                                <tr>
                                    <td class="fw-semibold"><a href="/edital?id=<?= (int) $lic['id'] ?>"><?= htmlspecialchars($lic['number'], ENT_QUOTES, 'UTF-8') ?></a></td>
                                    <td>
                                        <?php if ((int) $lic['needs_review'] > 0): ?>
                                            <span class="badge text-bg-warning"><?= (int) $lic['needs_review'] ?> pendente(s)</span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?= (int) $lic['proposal_count'] ?> proposta(s)</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid-2 mb-4">
        <div class="card">
            <div class="card-header flex-between">
                <h2 class="h6 mb-0">Atividades recentes</h2>
                <a href="/auditoria" class="text-primary small">Ver todas as atividades <i class="ti ti-chevron-right"></i></a>
            </div>
            <div class="card-body p-0">
                <?php if (!$atividadesRecentes): ?>
                    <div class="empty-state"><i class="ti ti-history"></i>Nenhuma atividade no período selecionado.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Data/hora</th><th>Usuário</th><th>Atividade</th><th>Detalhes</th></tr></thead>
                            <tbody>
                            <?php foreach ($atividadesRecentes as $atividade): ?>
                                <tr>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($atividade['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($atividade['user_name'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($atividade['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($atividade['detail_text'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-top">
                        <span class="text-muted small"><?= count($atividadesRecentes) ?> registros</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex-between">
                <h2 class="h6 mb-0">Próximas aberturas de licitação</h2>
                <a href="/edital" class="text-primary small">Ver todas <i class="ti ti-chevron-right"></i></a>
            </div>
            <div class="card-body p-0">
                <?php if (!$proximasAberturas): ?>
                    <div class="empty-state"><i class="ti ti-calendar-check"></i>Nenhuma licitação com abertura futura cadastrada.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Licitação</th><th>Objeto</th><th>Abertura</th></tr></thead>
                            <tbody>
                            <?php foreach ($proximasAberturas as $lic): ?>
                                <tr>
                                    <td class="fw-semibold"><a href="/edital?id=<?= (int) $lic['id'] ?>"><?= htmlspecialchars($lic['number'], ENT_QUOTES, 'UTF-8') ?></a></td>
                                    <td><?= htmlspecialchars($lic['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-muted small"><?= date('d/m/Y', strtotime($lic['opening_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="stat-grid mb-4">
        <div class="stat-tile">
            <div class="stat-icon"><i class="ti ti-file-check"></i></div>
            <div class="stat-body">
                <div class="stat-label">Minhas propostas</div>
                <div class="stat-value"><?= $minhasPropostas ?></div>
            </div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon stat-icon-warning"><i class="ti ti-clock"></i></div>
            <div class="stat-body">
                <div class="stat-label">Em análise</div>
                <div class="stat-value"><?= $pendentes ?></div>
            </div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-label">Concluídas</div>
                <div class="stat-value"><?= $concluidas ?></div>
            </div>
        </div>
        <div class="stat-tile">
            <div class="stat-icon"><i class="ti ti-file-text"></i></div>
            <div class="stat-body">
                <div class="stat-label">Licitações abertas</div>
                <div class="stat-value"><?= $licitacoesAbertas ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex-between">
            <h2 class="h6 mb-0">Minhas propostas recentes</h2>
            <a href="/edital" class="btn btn-outline-info btn-sm"><i class="ti ti-search me-1"></i>Ver licitações abertas</a>
        </div>
        <div class="card-body p-0">
            <?php if (!$minhasPropostasRecentes): ?>
                <div class="empty-state"><i class="ti ti-file-check"></i>Você ainda não enviou nenhuma proposta.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Licitação</th><th>Status</th><th>Envio</th></tr></thead>
                        <tbody>
                        <?php foreach ($minhasPropostasRecentes as $proposta): ?>
                            <?php $status = ProposalService::statusMeta($proposta['status']); ?>
                            <tr>
                                <td class="fw-semibold"><a href="/licitantes?id=<?= (int) $proposta['id'] ?>"><?= htmlspecialchars($proposta['licitation_number'], ENT_QUOTES, 'UTF-8') ?></a></td>
                                <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                                <td class="text-muted small"><?= $proposta['submitted_at'] ? date('d/m/Y', strtotime($proposta['submitted_at'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<?php
layout_footer();
