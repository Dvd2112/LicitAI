<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

layout_header('Edital', 'edital');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<?php if ($flash): ?>
    <div class="alert alert-success alert-flash d-flex align-items-center gap-2" role="alert">
        <i class="ti ti-circle-check me-2"></i>
        <div><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header flex-between">
        <h2 class="h5 mb-0"><i class="ti ti-file-text me-2 text-primary"></i>Editais enviados</h2>
        <div class="flex">
            <span class="badge text-bg-info"><?= count($editais) ?> registros</span>
            <a href="/edital/novo" class="btn btn-success">
                <i class="ti ti-plus me-1"></i>Novo edital
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Número</th>
                    <th>Título</th>
                    <th>Órgão</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th class="action-cell">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($editais as $edital): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($edital['numero'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($edital['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($edital['orgao'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= $editalStatus[$edital['status']]['class'] ?>">
                                <?= $editalStatus[$edital['status']]['label'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($edital['data'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="action-cell">
                            <a href="/edital/novo" class="btn btn-outline-info btn-sm icon-btn" title="Visualizar">
                                <i class="ti ti-eye"></i>
                            </a>
                            <form method="post" action="/edital/remover" class="d-inline" data-confirm="Excluir permanentemente este edital?">
                                <input type="hidden" name="id" value="<?= (int) $edital['id'] ?>">
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
    </div>
</div>

<?php
layout_footer();
