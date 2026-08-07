<?php

declare(strict_types=1);

$editais = [
    ['id' => 1, 'numero' => 'PE-2026/001', 'titulo' => 'Aquisição de Equipamentos de TI', 'orgao' => 'SEINFRA', 'status' => 'aberto', 'data' => '01/08/2026'],
    ['id' => 2, 'numero' => 'PE-2026/002', 'titulo' => 'Prestação de Serviços de Limpeza', 'orgao' => 'SEDUC', 'status' => 'pendente', 'data' => '28/07/2026'],
    ['id' => 3, 'numero' => 'CC-2026/003', 'titulo' => 'Construção de Creche Municipal', 'orgao' => 'SMOB', 'status' => 'aberto', 'data' => '15/07/2026'],
    ['id' => 4, 'numero' => 'PE-2026/004', 'titulo' => 'Material de Escritório', 'orgao' => 'SEPLAG', 'status' => 'encerrado', 'data' => '02/07/2026'],
];

$retornos = [
    ['id' => 1, 'edital_id' => 1, 'licitante' => 'TechSolutions Ltda', 'cnpj' => '12.345.678/0001-90', 'status' => 'concluido', 'arquivos' => ['proposta_techsolutions.pdf'], 'data' => '03/08/2026'],
    ['id' => 2, 'edital_id' => 1, 'licitante' => 'DataMax Comércio', 'cnpj' => '98.765.432/0001-11', 'status' => 'processando', 'arquivos' => ['proposta_datamax.pdf'], 'data' => '04/08/2026'],
    ['id' => 3, 'edital_id' => 2, 'licitante' => 'LimpaServ Prestadora', 'cnpj' => '45.678.912/0001-22', 'status' => 'aguardando', 'arquivos' => [], 'data' => '05/08/2026'],
    ['id' => 4, 'edital_id' => 3, 'licitante' => 'Construtora Horizonte', 'cnpj' => '33.444.555/0001-33', 'status' => 'concluido', 'arquivos' => ['proposta_horizonte.pdf', 'anexo_horizonte.pdf'], 'data' => '29/07/2026'],
    ['id' => 5, 'edital_id' => 3, 'licitante' => 'Engemax Construções', 'cnpj' => '11.222.333/0001-44', 'status' => 'falhou', 'arquivos' => ['proposta_engemax.pdf'], 'data' => '30/07/2026'],
];

$contratos = [
    ['id' => 1, 'nome' => 'Aquisição de Equipamentos de TI', 'numero' => 'CT-2026/001', 'licitante' => 'TechSolutions Ltda', 'status' => 'ativo', 'percentual' => 100, 'valor' => 85000.00, 'vigencia' => '31/12/2026'],
    ['id' => 2, 'nome' => 'Construção de Creche Municipal', 'numero' => 'CT-2026/002', 'licitante' => 'Construtora Horizonte', 'status' => 'ativo', 'percentual' => 45, 'valor' => 1200000.00, 'vigencia' => '30/06/2027'],
    ['id' => 3, 'nome' => 'Material de Escritório', 'numero' => 'CT-2026/003', 'licitante' => 'DataMax Comércio', 'status' => 'suspenso', 'percentual' => 12, 'valor' => 6000.00, 'vigencia' => '31/12/2026'],
    ['id' => 4, 'nome' => 'Serviços de Manutenção Predial', 'numero' => 'CT-2025/014', 'licitante' => 'LimpaServ Prestadora', 'status' => 'vencido', 'percentual' => 80, 'valor' => 45000.00, 'vigencia' => '30/06/2026'],
    ['id' => 5, 'nome' => 'Consultoria Jurídica', 'numero' => 'CT-2026/004', 'licitante' => 'Engemax Construções', 'status' => 'em_elaboracao', 'percentual' => 0, 'valor' => 12000.00, 'vigencia' => '31/12/2026'],
];

$editais = array_merge($editais, $_SESSION['editais'] ?? []);
$retornos = array_merge($retornos, $_SESSION['retornos'] ?? []);
$contratos = array_merge($contratos, $_SESSION['contratos'] ?? []);

$editalStatus = [
    'aberto' => ['label' => 'Aberto', 'class' => 'text-bg-success'],
    'pendente' => ['label' => 'Pendente', 'class' => 'text-bg-warning'],
    'encerrado' => ['label' => 'Encerrado', 'class' => 'text-bg-secondary'],
];

$retornoStatus = [
    'aguardando' => ['label' => 'Aguardando', 'class' => 'text-bg-secondary'],
    'processando' => ['label' => 'Processando', 'class' => 'text-bg-info'],
    'concluido' => ['label' => 'Concluído', 'class' => 'text-bg-success'],
    'falhou' => ['label' => 'Falhou', 'class' => 'text-bg-danger'],
];

$contratoStatus = [
    'em_elaboracao' => ['label' => 'Em elaboração', 'class' => 'text-bg-info'],
    'ativo' => ['label' => 'Ativo', 'class' => 'text-bg-success'],
    'suspenso' => ['label' => 'Suspenso', 'class' => 'text-bg-warning'],
    'encerrado' => ['label' => 'Encerrado', 'class' => 'text-bg-secondary'],
    'vencido' => ['label' => 'Vencido', 'class' => 'text-bg-danger'],
];

function percentualBarra(int $percentual): string
{
    $class = $percentual >= 70 ? 'bg-success' : ($percentual >= 30 ? 'bg-warning' : 'bg-danger');

    return sprintf(
        '<div class="progress" role="progressbar" aria-valuenow="%d" aria-valuemin="0" aria-valuemax="100" title="%d%% entregue">'
        . '<div class="progress-bar %s" style="width: %d%%">%d%%</div></div>',
        $percentual,
        $percentual,
        $class,
        max(8, min(100, $percentual)),
        $percentual
    );
}
