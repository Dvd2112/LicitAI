<?php

declare(strict_types=1);

use App\Core\Csrf;

require_once __DIR__ . '/layout.php';

layout_header('Empresas', 'empresas');

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
        <h1 class="h4 mb-1">Empresas</h1>
        <p class="text-muted mb-0">Cadastre e gerencie as empresas participantes das licitações.</p>
    </div>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novaEmpresaModal">
        <i class="ti ti-plus me-1"></i>Nova empresa
    </button>
</div>

<div class="card">
    <div class="card-header flex-between">
        <span class="form-label mb-0">Empresas cadastradas</span>
        <span class="badge text-bg-info"><?= count($empresas) ?> registro<?= count($empresas) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (!$empresas): ?>
            <div class="empty-state">
                <i class="ti ti-building"></i>
                Nenhuma empresa cadastrada ainda.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Razão social</th>
                        <th>Nome fantasia</th>
                        <th>CNPJ</th>
                        <th>Responsável</th>
                        <th>Contato</th>
                        <th>Acesso</th>
                        <th>Situação</th>
                        <th class="action-cell">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($empresas as $empresa): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($empresa['corporate_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($empresa['trade_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($empresa['cnpj'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($empresa['responsible_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($empresa['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($empresa['login_email']): ?>
                                    <span class="text-success small"><i class="ti ti-circle-check me-1"></i><?= htmlspecialchars($empresa['login_email'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">Sem acesso</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $empresa['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $empresa['status'] === 'active' ? 'Ativa' : 'Inativa' ?>
                                </span>
                            </td>
                            <td class="action-cell">
                                <form method="post" action="/empresas/remover" class="d-inline" data-confirm="Excluir permanentemente esta empresa?">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $empresa['id'] ?>">
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

<div class="modal fade" id="novaEmpresaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="/empresas">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-building-plus me-2 text-success"></i>Nova empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="grid-2">
                        <div>
                            <label class="form-label" for="e_razao">Razão social</label>
                            <input type="text" class="form-control" id="e_razao" name="corporate_name" required>
                        </div>
                        <div>
                            <label class="form-label" for="e_fantasia">Nome fantasia</label>
                            <input type="text" class="form-control" id="e_fantasia" name="trade_name">
                        </div>
                        <div>
                            <label class="form-label" for="e_cnpj">CNPJ</label>
                            <input type="text" class="form-control" id="e_cnpj" name="cnpj" data-cnpj placeholder="00.000.000/0000-00" required>
                        </div>
                        <div>
                            <label class="form-label" for="e_responsavel">Responsável</label>
                            <input type="text" class="form-control" id="e_responsavel" name="responsible_name">
                        </div>
                        <div>
                            <label class="form-label" for="e_email">E-mail de contato</label>
                            <input type="email" class="form-control" id="e_email" name="email">
                        </div>
                        <div>
                            <label class="form-label" for="e_telefone">Telefone</label>
                            <input type="text" class="form-control" id="e_telefone" name="phone">
                        </div>
                    </div>

                    <div class="section-title mt-4 mb-2"><i class="ti ti-key"></i>Acesso ao sistema (opcional)</div>
                    <p class="text-muted small mb-3">Preencha para permitir que a empresa entre e envie propostas. Deixe em branco para cadastrar só o registro.</p>
                    <div class="grid-2">
                        <div>
                            <label class="form-label" for="e_login_email">Usuário de acesso</label>
                            <input type="text" class="form-control" id="e_login_email" name="login_email" placeholder="ex.: techsolutions">
                        </div>
                        <div>
                            <label class="form-label" for="e_login_senha">Senha de acesso</label>
                            <input type="password" class="form-control" id="e_login_senha" name="login_password" minlength="6" placeholder="mínimo 6 caracteres">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="ti ti-check me-1"></i>Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
layout_footer();
