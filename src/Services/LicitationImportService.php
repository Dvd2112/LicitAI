<?php

declare(strict_types=1);

namespace App\Services;

use App\Providers\OpenAIProvider;
use PDO;
use RuntimeException;

final class LicitationImportService
{
    private const VALID_CATEGORIES = [
        'documentacao', 'especificacao_tecnica', 'quantidade', 'certificacao',
        'experiencia', 'prazo', 'condicao_comercial', 'capacidade_tecnica',
        'obrigacao_legal', 'requisito_financeiro', 'outro',
    ];

    private const MAX_CHARS = 60000;

    /**
     * Stores the uploaded edital PDF, extracts its text, and asks the AI to structure the
     * licitation data from it. The extracted fields are fed into the same insert path used
     * by the manual wizard (LicitationService::createFromWizard), so both routes stay consistent.
     */
    public static function importFromUpload(PDO $pdo, array $file, int $userId, ?OpenAIProvider $provider = null): array
    {
        $provider ??= new OpenAIProvider();

        $stored = UploadService::storePdf($file, __DIR__ . '/../../storage/uploads/licitations/_import');

        [$pages, $method] = ExtractionService::extractPdfPages($stored['path']);
        $documentText = self::buildDocumentText($pages);

        if ($documentText === '') {
            throw new RuntimeException(
                'O PDF parece ser digitalizado (sem texto extraível) e não pode ser lido automaticamente. Cadastre a licitação manualmente.'
            );
        }

        $systemPrompt = file_get_contents(__DIR__ . '/../../prompts/extract_licitation.txt');
        $userPrompt = json_encode(['documento' => $documentText], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $raw = $provider->completeJson($systemPrompt, $userPrompt);
        $parsed = json_decode($raw, true);

        if (!is_array($parsed) || !is_array($parsed['licitacao'] ?? null)) {
            throw new RuntimeException('A IA retornou uma resposta em formato inesperado. Tente novamente ou cadastre manualmente.');
        }

        $dados = $parsed['licitacao'];
        $numero = trim((string) ($dados['numero'] ?? ''));
        $titulo = trim((string) ($dados['titulo'] ?? ''));

        if ($numero === '' || $titulo === '') {
            throw new RuntimeException(
                'Não foi possível identificar automaticamente o número e o título da licitação neste documento. Cadastre manualmente.'
            );
        }

        $itens = is_array($parsed['itens'] ?? null) ? $parsed['itens'] : [];
        $unidades = is_array($parsed['unidades_executoras'] ?? null) ? $parsed['unidades_executoras'] : [];
        $post = self::toWizardPost($dados, $itens, $unidades);

        $licitationId = LicitationService::createFromWizard($pdo, $post, $userId);

        LicitationService::attachDocuments($pdo, $licitationId, [$stored], 'Edital (upload automático)', $userId);
        $licitationDocumentId = (int) $pdo->lastInsertId();

        $requisitos = is_array($parsed['requisitos'] ?? null) ? $parsed['requisitos'] : [];
        $savedRequirements = 0;

        foreach ($requisitos as $req) {
            if (!is_array($req)) {
                continue;
            }

            $description = trim((string) ($req['descricao'] ?? ''));
            if ($description === '') {
                continue;
            }

            $category = in_array($req['categoria'] ?? null, self::VALID_CATEGORIES, true) ? $req['categoria'] : 'outro';

            LicitationService::addExtractedRequirement($pdo, $licitationId, [
                'category' => $category,
                'description' => $description,
                'mandatory' => !empty($req['obrigatorio']),
                'source_page' => isset($req['pagina']) && is_numeric($req['pagina']) ? (int) $req['pagina'] : null,
                'source_excerpt' => isset($req['trecho']) ? mb_substr((string) $req['trecho'], 0, 2000) : null,
            ], $licitationDocumentId, $userId);

            $savedRequirements++;
        }

        AuditLogger::log($pdo, $userId, 'create', 'licitation', $licitationId, [
            'source' => 'ai_upload',
            'ai_method' => $method,
            'items' => count($post['item_descricao']),
            'requirements' => $savedRequirements,
        ]);

        return [
            'licitation_id' => $licitationId,
            'items' => count($post['item_descricao']),
            'requirements' => $savedRequirements,
        ];
    }

    /** @param array<int, string> $pages */
    private static function buildDocumentText(array $pages): string
    {
        $parts = [];
        $total = 0;

        foreach ($pages as $i => $text) {
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }

            $chunk = '-- Página ' . ($i + 1) . " --\n{$text}\n";
            if ($total + mb_strlen($chunk) > self::MAX_CHARS) {
                $chunk = mb_substr($chunk, 0, max(0, self::MAX_CHARS - $total));
            }

            $parts[] = $chunk;
            $total += mb_strlen($chunk);

            if ($total >= self::MAX_CHARS) {
                break;
            }
        }

        return implode("\n", $parts);
    }

    private static function toDate(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private static function toDaysString(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '';
    }

    /** Maps the AI's JSON output into the same POST field shape LicitationService::createFromWizard() expects. */
    private static function toWizardPost(array $dados, array $itens, array $unidades): array
    {
        $post = [
            'numero' => mb_substr(trim((string) ($dados['numero'] ?? '')), 0, 40),
            'ano' => isset($dados['ano']) ? (string) $dados['ano'] : '',
            'tipo' => mb_substr(trim((string) ($dados['tipo'] ?? '')), 0, 60),
            'titulo' => mb_substr(trim((string) ($dados['titulo'] ?? '')), 0, 255),
            'entidade' => mb_substr(trim((string) ($dados['entidade'] ?? '')), 0, 150),
            'processo' => mb_substr(trim((string) ($dados['processo'] ?? '')), 0, 60),
            'site' => mb_substr(trim((string) ($dados['site'] ?? '')), 0, 255),
            'entrega' => mb_substr(trim((string) ($dados['entrega'] ?? '')), 0, 40),
            'licenca' => !empty($dados['licenca_sanitaria']) ? '1' : '',
            'situacao' => mb_substr(trim((string) ($dados['situacao'] ?? '')), 0, 40),
            'total' => is_numeric($dados['valor_total'] ?? null) ? (string) $dados['valor_total'] : '0',
            'pagamento_dias' => self::toDaysString($dados['pagamento_dias'] ?? null),
            'forma_pagamento' => mb_substr(trim((string) ($dados['forma_pagamento'] ?? '')), 0, 60),
            'entrega_max' => self::toDaysString($dados['entrega_max_dias'] ?? null),
            'dt_publicacao' => self::toDate($dados['dt_publicacao'] ?? null),
            'dt_impugnacao' => self::toDate($dados['dt_impugnacao'] ?? null),
            'dt_inicio_recebimento' => self::toDate($dados['dt_inicio_recebimento'] ?? null),
            'prazo_entrega' => self::toDaysString($dados['prazo_entrega_dias'] ?? null),
            'dt_limite_envio' => self::toDate($dados['dt_limite_envio'] ?? null),
            'dt_abertura' => self::toDate($dados['dt_abertura'] ?? null),
            'prazo_analise' => self::toDaysString($dados['prazo_analise_dias'] ?? null),
            'dt_regularizacao' => self::toDate($dados['dt_regularizacao'] ?? null),
            'dt_recurso' => self::toDate($dados['dt_recurso'] ?? null),
            'prazo_convocacao' => self::toDaysString($dados['prazo_convocacao_dias'] ?? null),
            'dt_contratacao' => self::toDate($dados['dt_contratacao'] ?? null),
            'prazo_assinatura' => self::toDaysString($dados['prazo_assinatura_dias'] ?? null),
            'dt_vigencia' => self::toDate($dados['dt_vigencia'] ?? null),
            'descricao' => trim((string) ($dados['descricao'] ?? '')),
            'item_codigo' => [],
            'item_descricao' => [],
            'item_unidade' => [],
            'item_quantidade' => [],
            'item_unitario' => [],
            'unidade_executora' => [],
            'responsavel' => [],
        ];

        foreach ($itens as $item) {
            if (!is_array($item)) {
                continue;
            }
            $descricao = trim((string) ($item['descricao'] ?? ''));
            if ($descricao === '') {
                continue;
            }
            $post['item_codigo'][] = trim((string) ($item['codigo'] ?? ''));
            $post['item_descricao'][] = $descricao;
            $post['item_unidade'][] = trim((string) ($item['unidade'] ?? ''));
            $post['item_quantidade'][] = is_numeric($item['quantidade'] ?? null) ? $item['quantidade'] : 0;
            $post['item_unitario'][] = is_numeric($item['valor_unitario'] ?? null) ? $item['valor_unitario'] : 0;
        }

        foreach ($unidades as $unidade) {
            if (!is_array($unidade)) {
                continue;
            }
            $nome = trim((string) ($unidade['unidade'] ?? ''));
            if ($nome === '') {
                continue;
            }
            $post['unidade_executora'][] = $nome;
            $post['responsavel'][] = trim((string) ($unidade['responsavel'] ?? ''));
        }

        return $post;
    }
}
