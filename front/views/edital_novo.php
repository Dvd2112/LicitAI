<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

layout_header('Novo Edital', 'edital');

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

<div class="card wizard-card">
    <div class="card-header">
        <h2 class="h5 mb-0"><i class="ti ti-file-text me-2 text-primary"></i>Cadastro de edital</h2>
        <span class="badge text-bg-info"><?= count([1, 2, 3, 4, 5, 6, 7]) ?> etapas</span>
    </div>

    <div class="wizard-progress">
        <div class="wizard-progress-label">
            <span>Etapa <strong id="wizardStepCurrent">1</strong> de <strong>7</strong></span>
            <span id="wizardStepName">Identificação do Edital</span>
        </div>
        <div class="wizard-progress-bar">
            <div class="wizard-progress-fill" id="wizardProgressFill" style="width: 14.28%"></div>
        </div>
        <div class="wizard-steps-nav" id="wizardTabs">
            <button type="button" class="wizard-step-pill active" data-step="1"><span class="wp-num">1</span><span class="wp-label">Identificação</span></button>
            <button type="button" class="wizard-step-pill" data-step="2"><span class="wp-num">2</span><span class="wp-label">Itens</span></button>
            <button type="button" class="wizard-step-pill" data-step="3"><span class="wp-num">3</span><span class="wp-label">Locais</span></button>
            <button type="button" class="wizard-step-pill" data-step="4"><span class="wp-num">4</span><span class="wp-label">Faturamento</span></button>
            <button type="button" class="wizard-step-pill" data-step="5"><span class="wp-num">5</span><span class="wp-label">Prazos</span></button>
            <button type="button" class="wizard-step-pill" data-step="6"><span class="wp-num">6</span><span class="wp-label">Documentos</span></button>
            <button type="button" class="wizard-step-pill" data-step="7"><span class="wp-num">7</span><span class="wp-label">Revisão</span></button>
        </div>
    </div>

    <form id="formEdital" method="post" action="/edital" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>
        <div class="wizard-body">

            <!-- ============ Passo 1 — Identificação do Edital ============ -->
            <section class="step-section active" data-step="1">
                <div class="grid-2">
                    <div>
                        <label class="form-label" for="w_numero">Número</label>
                        <input type="text" class="form-control" id="w_numero" name="numero" placeholder="PE-2026/005">
                    </div>
                    <div>
                        <label class="form-label" for="w_ano">Ano</label>
                        <input type="number" class="form-control" id="w_ano" name="ano" placeholder="2026">
                    </div>
                    <div>
                        <label class="form-label" for="w_tipo">Tipo</label>
                        <select class="form-select" id="w_tipo" name="tipo">
                            <option value="">Selecione o tipo</option>
                            <option selected>PNAE</option>
                            <option>Pregão eletrônico</option>
                            <option>Concorrência</option>
                            <option>Tomada de preços</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="w_entidade">Entidade executora</label>
                        <select class="form-select" id="w_entidade" name="entidade">
                            <option value="">Selecione a pessoa</option>
                            <option>Secretaria de Educação</option>
                            <option>Secretaria de Saúde</option>
                            <option>Prefeitura Municipal</option>
                            <option>EMATER</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="w_processo">Processo administrativo</label>
                        <input type="text" class="form-control" id="w_processo" name="processo" placeholder="PA-2026/00123">
                    </div>
                    <div>
                        <label class="form-label" for="w_site">Site / URL</label>
                        <input type="text" class="form-control" id="w_site" name="site" placeholder="https://">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label" for="w_titulo">Título</label>
                    <input type="text" class="form-control" id="w_titulo" name="titulo" placeholder="Ex.: Aquisição de gêneros alimentícios da agricultura familiar">
                </div>

                <div class="wizard-row mt-4">
                    <div class="toggle-field">
                        <span class="form-label mb-0">Licença sanitária</span>
                        <label class="switch">
                            <input type="checkbox" name="licenca" value="1">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div style="min-width: 220px;">
                        <label class="form-label" for="w_entrega">Entrega</label>
                        <select class="form-select" id="w_entrega" name="entrega">
                            <option>Imediata</option>
                            <option>Parcial</option>
                            <option>Programada</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- ============ Passo 2 — Itens ============ -->
            <section class="step-section" data-step="2">
                <div class="card">
                    <div class="card-header">
                        <span class="form-label mb-0"><i class="ti ti-list-check me-1 text-primary"></i>Itens do edital</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover itens-table mb-0">
                                <thead>
                                <tr>
                                    <th style="width:90px">Código</th>
                                    <th>Item</th>
                                    <th style="width:110px">Unidade</th>
                                    <th style="width:110px">Quantidade</th>
                                    <th style="width:130px">Unitário</th>
                                    <th style="width:130px">Total</th>
                                    <th style="width:50px"></th>
                                </tr>
                                </thead>
                                <tbody id="itensBody">
                                <tr class="item-row">
                                    <td>
                                        <input type="text" class="form-control" name="item_codigo[]" placeholder="01">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="item_descricao[]" placeholder="Selecione o item">
                                    </td>
                                    <td>
                                        <select class="form-select" name="item_unidade[]">
                                            <option>kg</option>
                                            <option>g</option>
                                            <option>L</option>
                                            <option>un</option>
                                            <option>cx</option>
                                            <option>pct</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-qtd" name="item_quantidade[]" min="0" step="any" value="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-unit" name="item_unitario[]" min="0" step="any" value="0">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control item-total" disabled value="">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm icon-btn btn-remove-item" title="Remover item">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 flex-between">
                            <button type="button" class="btn btn-outline-info btn-sm" id="btnAddItem">
                                <i class="ti ti-plus me-1"></i>Item
                            </button>
                            <span class="form-label mb-0">Total em itens: <span id="itensTotalBox" class="fw-bold text-primary">R$ 0,00</span></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ Passo 3 — Unidades Executoras ============ -->
            <section class="step-section" data-step="3">
                <div class="grid-2">
                    <div>
                        <label class="form-label" for="w_entrega_max">Entrega máxima a partir da autorização de fornecimento</label>
                        <div class="input-suffix">
                            <input type="number" class="form-control" id="w_entrega_max" name="entrega_max" min="0" value="0">
                            <span class="suffix">dias</span>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header flex-between">
                        <span class="form-label mb-0"><i class="ti ti-map-pin me-1 text-success"></i>Locais de entrega / execução</span>
                        <button type="button" class="btn btn-outline-info btn-sm" id="btnAddLocal">
                            <i class="ti ti-plus me-1"></i>Local
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="locaisBody">
                            <div class="grid-2 local-row">
                                <div>
                                    <label class="form-label"><i class="ti ti-pencil me-1 text-success"></i>Unidade executora</label>
                                    <textarea class="form-control" rows="2" name="unidade_executora[]" placeholder="Ex.: Escola Municipal São José"></textarea>
                                </div>
                                <div>
                                    <label class="form-label"><i class="ti ti-pencil me-1 text-success"></i>Responsável</label>
                                    <textarea class="form-control" rows="2" name="responsavel[]" placeholder="Ex.: ADEMIR MANFREDI"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ Passo 4 — Faturamento ============ -->
            <section class="step-section" data-step="4">
                <div class="grid-2">
                    <div>
                        <label class="form-label" for="w_pagamento_dias">Recebe pagamentos em</label>
                        <div class="input-suffix">
                            <input type="number" class="form-control" id="w_pagamento_dias" name="pagamento_dias" min="0" value="0">
                            <span class="suffix">dias</span>
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="w_forma_pagamento">Forma de pagamento</label>
                        <select class="form-select" id="w_forma_pagamento" name="forma_pagamento">
                            <option>Não definido</option>
                            <option>PIX</option>
                            <option>Transferência bancária</option>
                            <option>Boleto</option>
                            <option>Ordem de pagamento</option>
                        </select>
                    </div>
                </div>

                <div class="section-title mt-4"><i class="ti ti-tag"></i>Regras comerciais</div>
                <div class="grid-2 mt-2">
                    <div>
                        <label class="form-label" for="w_regra_entidade">Entidade executora</label>
                        <select class="form-select" id="w_regra_entidade" name="regra_entidade">
                            <option value="">Selecione a pessoa</option>
                            <option>Secretaria de Educação</option>
                            <option>Secretaria de Saúde</option>
                            <option>Prefeitura Municipal</option>
                            <option>EMATER</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="w_regra_valor">Valor (R$)</label>
                        <input type="number" class="form-control" id="w_regra_valor" name="regra_valor" min="0" step="any" value="0">
                    </div>
                </div>

                <div class="total-box mt-4">
                    <span class="form-label mb-0">Valor total do edital</span>
                    <span class="total-strong" id="valorTotalBox">R$ 0,00</span>
                    <input type="hidden" id="totalHidden" name="total" value="0">
                </div>
            </section>

            <!-- ============ Passo 5 — Prazos e Etapas ============ -->
            <section class="step-section" data-step="5">
                <div class="grid-2">
                    <div style="max-width: 320px;">
                        <label class="form-label" for="w_situacao">Situação</label>
                        <select class="form-select" id="w_situacao" name="situacao">
                            <option>Em licitação</option>
                            <option>Homologado</option>
                            <option>Adjudicado</option>
                            <option>Concluído</option>
                            <option>Deserto</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2 mt-4">
                    <div class="card">
                        <div class="card-header"><span class="form-label mb-0"><i class="ti ti-rocket me-1 text-primary"></i>Publicação</span></div>
                        <div class="card-body">
                            <label class="form-label" for="w_dt_publicacao">Publicação do edital</label>
                            <div class="date-field mb-3">
                                <input type="date" class="form-control" id="w_dt_publicacao" name="dt_publicacao">
                                <i class="ti ti-calendar"></i>
                            </div>
                            <label class="form-label" for="w_dt_impugnacao">Limite para impugnação</label>
                            <div class="date-field">
                                <input type="date" class="form-control" id="w_dt_impugnacao" name="dt_impugnacao">
                                <i class="ti ti-calendar"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><span class="form-label mb-0"><i class="ti ti-inbox me-1 text-primary"></i>Recebimento de propostas</span></div>
                        <div class="card-body">
                            <label class="form-label" for="w_dt_inicio_recebimento">Início do recebimento</label>
                            <div class="date-field mb-3">
                                <input type="date" class="form-control" id="w_dt_inicio_recebimento" name="dt_inicio_recebimento">
                                <i class="ti ti-calendar"></i>
                            </div>
                            <label class="form-label" for="w_prazo_entrega">Prazo de entrega</label>
                            <div class="input-suffix mb-3">
                                <input type="number" class="form-control" id="w_prazo_entrega" name="prazo_entrega" min="0" value="0">
                                <span class="suffix">dias</span>
                            </div>
                            <label class="form-label" for="w_dt_limite_envio">Limite para envio de propostas</label>
                            <div class="date-field">
                                <input type="date" class="form-control" id="w_dt_limite_envio" name="dt_limite_envio">
                                <i class="ti ti-calendar"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><span class="form-label mb-0"><i class="ti ti-search me-1 text-primary"></i>Avaliação</span></div>
                        <div class="card-body">
                            <label class="form-label" for="w_dt_abertura">Abertura de propostas</label>
                            <div class="date-field mb-3">
                                <input type="date" class="form-control" id="w_dt_abertura" name="dt_abertura">
                                <i class="ti ti-calendar"></i>
                            </div>
                            <label class="form-label" for="w_prazo_analise">Prazo para análise</label>
                            <div class="input-suffix mb-3">
                                <input type="number" class="form-control" id="w_prazo_analise" name="prazo_analise" min="0" value="0">
                                <span class="suffix">dias</span>
                            </div>
                            <label class="form-label" for="w_dt_regularizacao">Limite para regularização</label>
                            <div class="date-field mb-3">
                                <input type="date" class="form-control" id="w_dt_regularizacao" name="dt_regularizacao">
                                <i class="ti ti-calendar"></i>
                            </div>
                            <label class="form-label" for="w_dt_recurso">Limite para recorrer</label>
                            <div class="date-field">
                                <input type="date" class="form-control" id="w_dt_recurso" name="dt_recurso">
                                <i class="ti ti-calendar"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><span class="form-label mb-0"><i class="ti ti-handshake me-1 text-primary"></i>Contratação</span></div>
                        <div class="card-body">
                            <label class="form-label" for="w_prazo_convocacao">Prazo para convocação</label>
                            <div class="input-suffix mb-3">
                                <input type="number" class="form-control" id="w_prazo_convocacao" name="prazo_convocacao" min="0" value="0">
                                <span class="suffix">dias</span>
                            </div>
                            <label class="form-label" for="w_dt_contratacao">Limite para contratação</label>
                            <div class="date-field mb-3">
                                <input type="date" class="form-control" id="w_dt_contratacao" name="dt_contratacao">
                                <i class="ti ti-calendar"></i>
                            </div>
                            <label class="form-label" for="w_prazo_assinatura">Prazo para assinatura</label>
                            <div class="input-suffix mb-3">
                                <input type="number" class="form-control" id="w_prazo_assinatura" name="prazo_assinatura" min="0" value="0">
                                <span class="suffix">dias</span>
                            </div>
                            <label class="form-label" for="w_dt_vigencia">Vigência</label>
                            <div class="date-field">
                                <input type="date" class="form-control" id="w_dt_vigencia" name="dt_vigencia">
                                <i class="ti ti-calendar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ Passo 6 — Documentos ============ -->
            <section class="step-section" data-step="6">
                <div class="grid-3">
                    <div>
                        <label class="form-label" for="w_documento_tipo">Documentos obrigatórios</label>
                        <select class="form-select" id="w_documento_tipo" name="documento_tipo">
                            <option value="">Selecione o documento</option>
                            <option>Alvará de funcionamento</option>
                            <option>Certidão negativa de débitos</option>
                            <option>Licença sanitária</option>
                            <option>Proposta comercial</option>
                            <option>Comprovante de inscrição no CNPJ</option>
                            <option>Declaração de MEI</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="w_documento_dias">Emitidos em até</label>
                        <div class="input-suffix">
                            <input type="number" class="form-control" id="w_documento_dias" name="documento_dias" min="0" value="0">
                            <span class="suffix">dias</span>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Anexar arquivos</label>
                        <div class="upload-area" data-target="w_documentos">
                            <i class="ti ti-folder"></i>
                            <p>Arraste os arquivos aqui ou clique para selecionar</p>
                            <small class="upload-files">Nenhum arquivo selecionado</small>
                        </div>
                        <input type="file" class="d-none" id="w_documentos" name="documentos[]" accept="application/pdf" multiple>
                    </div>
                </div>
            </section>

            <!-- ============ Passo 7 — Revisar e Gravar ============ -->
            <section class="step-section" data-step="7">
                <div class="wizard-layout">
                    <div class="card timeline-card">
                        <div class="card-header">
                            <span class="form-label mb-0"><i class="ti ti-calendar-event me-1 text-primary"></i>Cronograma</span>
                        </div>
                        <div class="timeline">
                            <div class="timeline-event">
                                <span class="tl-dot tl-green"></span>
                                <div><span class="tl-date">06/08/2026</span><span class="tl-label">Publicação</span></div>
                            </div>
                            <div class="timeline-event">
                                <span class="tl-dot tl-orange"></span>
                                <div><span class="tl-date">28/08/2026</span><span class="tl-label">Limite para impugnação</span></div>
                            </div>
                            <div class="timeline-event">
                                <span class="tl-dot tl-blue"></span>
                                <div><span class="tl-date">07/08/2026</span><span class="tl-label">Início do recebimento de propostas</span></div>
                            </div>
                            <div class="timeline-event">
                                <span class="tl-dot tl-red"></span>
                                <div><span class="tl-date">08/08/2026</span><span class="tl-label">Limite para envio de propostas</span></div>
                            </div>
                            <div class="timeline-event">
                                <span class="tl-dot tl-red"></span>
                                <div><span class="tl-date">13/08/2026</span><span class="tl-label">Abertura de propostas</span></div>
                            </div>
                            <div class="timeline-event">
                                <span class="tl-dot tl-orange"></span>
                                <div><span class="tl-date">14/08/2026</span><span class="tl-label">Limite para regularização</span></div>
                            </div>
                            <div class="timeline-event">
                                <span class="tl-dot tl-red"></span>
                                <div><span class="tl-date">08/08/2026</span><span class="tl-label">Limite para recorrer</span></div>
                            </div>
                            <div class="timeline-event">
                                <span class="tl-dot tl-orange"></span>
                                <div><span class="tl-date">13/08/2026</span><span class="tl-label">Apresentação de amostras</span></div>
                            </div>
                            <div class="timeline-event">
                                <span class="tl-dot tl-green"></span>
                                <div><span class="tl-date">18/08/2026</span><span class="tl-label">Resultado final</span></div>
                            </div>
                            <div class="timeline-event">
                                <span class="tl-dot tl-orange"></span>
                                <div><span class="tl-date">13/08/2026</span><span class="tl-label">Contratação</span></div>
                            </div>
                            <div class="timeline-event">
                                <span class="tl-dot tl-red"></span>
                                <div><span class="tl-date">14/08/2026</span><span class="tl-label">Vigência</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="review-content">
                        <h4><i class="ti ti-file-text text-primary"></i>Dados do edital</h4>
                        <div class="card p-0">
                            <table class="data-table">
                                <tr><th>Número</th><td id="rv_numero">—</td></tr>
                                <tr><th>Ano</th><td id="rv_ano">—</td></tr>
                                <tr><th>Tipo</th><td id="rv_tipo">—</td></tr>
                                <tr><th>Situação</th><td><span class="badge text-bg-info" id="rv_situacao">—</span></td></tr>
                                <tr><th>Locais</th><td id="rv_locais">—</td></tr>
                                <tr><th>Itens</th><td id="rv_itens">—</td></tr>
                                <tr><th>Licença sanitária</th><td id="rv_licenca">—</td></tr>
                                <tr><th>Título</th><td id="rv_titulo">—</td></tr>
                                <tr><th>Pessoa</th><td id="rv_pessoa">—</td></tr>
                            </table>
                        </div>

                        <h4 class="mt-4"><i class="ti ti-currency-real text-primary"></i>Dados financeiros</h4>
                        <div class="card p-0">
                            <table class="data-table">
                                <tr><th>Site / URL</th><td id="rv_site">Sem link</td></tr>
                                <tr><th>Forma</th><td id="rv_forma">Não definido</td></tr>
                                <tr><th>Pagamento em</th><td id="rv_pagamento">Não definido</td></tr>
                                <tr><th>Limite DAP</th><td id="rv_dap">R$ 0,00</td></tr>
                                <tr><th>R$ Total</th><td class="fw-bold text-success" id="rv_total">R$ 0,00</td></tr>
                            </table>
                        </div>

                        <div class="mt-3">
                            <label class="form-label" for="w_descricao">Descrição</label>
                            <textarea class="form-control" id="w_descricao" name="descricao" rows="3" placeholder="Escreva a descrição do edital..."></textarea>
                        </div>
                    </div>
                </div>
            </section>

        </div>

        <div class="wizard-footer">
            <button type="button" class="btn btn-outline-secondary" id="btnPrev" disabled>
                <i class="ti ti-chevron-left me-1"></i>Voltar
            </button>
            <div class="flex">
                <button type="button" class="btn btn-outline-danger" id="btnEdit" data-bs-toggle="modal" data-bs-target="#editarModal" style="display:none">
                    <i class="ti ti-edit me-1"></i>Editar
                </button>
                <button type="button" class="btn btn-outline-info" id="btnNext">
                    Avançar<i class="ti ti-chevron-right ms-1"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ============ Tela 7 — Modal de edição ============ -->
<div class="modal fade" id="editarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-edit me-2 text-primary"></i>Editar edital</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="flex-between mb-3">
                    <button type="button" class="btn btn-success btn-sm">
                        <i class="ti ti-paperclip me-1"></i>Anexar
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-file me-1"></i>Selecione edital
                    </button>
                </div>
                <div class="grid-2">
                    <div>
                        <label class="form-label" for="ed_numero">Número</label>
                        <input type="text" class="form-control is-invalid" id="ed_numero" placeholder="Informe o número">
                        <span class="info-text"><i class="ti ti-alert-circle me-1"></i>Informe Número</span>
                    </div>
                    <div>
                        <label class="form-label" for="ed_ano">Ano</label>
                        <input type="number" class="form-control" id="ed_ano" value="2026">
                    </div>
                    <div>
                        <label class="form-label" for="ed_tipo">Tipo</label>
                        <select class="form-select" id="ed_tipo">
                            <option selected>PNAE</option>
                            <option>Pregão eletrônico</option>
                            <option>Concorrência</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="ed_entidade">Entidade executora</label>
                        <input type="text" class="form-control is-invalid" id="ed_entidade" placeholder="Escolha a pessoa">
                        <span class="info-text"><i class="ti ti-alert-circle me-1"></i>Escolha a pessoa</span>
                    </div>
                    <div>
                        <label class="form-label" for="ed_processo">Processo</label>
                        <input type="text" class="form-control" id="ed_processo" placeholder="PA-2026/00123">
                    </div>
                    <div>
                        <label class="form-label" for="ed_site">Site / URL</label>
                        <input type="text" class="form-control" id="ed_site" placeholder="https://">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label" for="ed_titulo">Título</label>
                    <input type="text" class="form-control is-invalid" id="ed_titulo" placeholder="Informe o título">
                    <span class="info-text"><i class="ti ti-alert-circle me-1"></i>Informe Título</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success"><i class="ti ti-circle-check me-1"></i>Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Templates para linhas dinâmicas -->
<template id="itemTemplate">
    <tr class="item-row">
        <td>
            <input type="text" class="form-control" name="item_codigo[]" placeholder="01">
        </td>
        <td>
            <input type="text" class="form-control" name="item_descricao[]" placeholder="Selecione o item">
        </td>
        <td>
            <select class="form-select" name="item_unidade[]">
                <option>kg</option>
                <option>g</option>
                <option>L</option>
                <option>un</option>
                <option>cx</option>
                <option>pct</option>
            </select>
        </td>
        <td>
            <input type="number" class="form-control item-qtd" name="item_quantidade[]" min="0" step="any" value="0">
        </td>
        <td>
            <input type="number" class="form-control item-unit" name="item_unitario[]" min="0" step="any" value="0">
        </td>
        <td>
            <input type="text" class="form-control item-total" disabled value="">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm icon-btn btn-remove-item" title="Remover item">
                <i class="ti ti-trash"></i>
            </button>
        </td>
    </tr>
</template>

<template id="localTemplate">
    <div class="grid-2 local-row">
        <div>
            <label class="form-label"><i class="ti ti-pencil me-1 text-success"></i>Unidade executora</label>
            <textarea class="form-control" rows="2" name="unidade_executora[]" placeholder="Ex.: Escola Municipal São José"></textarea>
        </div>
        <div>
            <label class="form-label"><i class="ti ti-pencil me-1 text-success"></i>Responsável</label>
            <textarea class="form-control" rows="2" name="responsavel[]" placeholder="Ex.: ADEMIR MANFREDI"></textarea>
        </div>
    </div>
</template>
<?php
layout_footer();
