<?php

declare(strict_types=1);

namespace App\Services;

use App\Providers\OpenAIProvider;
use PDO;
use RuntimeException;
use Throwable;

final class EvaluationService
{
    private const CLASSIFICATION_LABELS = [
        'atende' => ['label' => 'Atende', 'class' => 'text-bg-success'],
        'atende_parcialmente' => ['label' => 'Atende parcialmente', 'class' => 'text-bg-warning'],
        'nao_atende' => ['label' => 'Não atende', 'class' => 'text-bg-danger'],
        'evidencia_insuficiente' => ['label' => 'Evidência insuficiente', 'class' => 'text-bg-secondary'],
        'nao_identificado' => ['label' => 'Não identificado', 'class' => 'text-bg-secondary'],
        'requer_revisao' => ['label' => 'Requer revisão', 'class' => 'text-bg-warning'],
    ];

    private const VALID_CLASSIFICATIONS = [
        'ATENDE', 'ATENDE_PARCIALMENTE', 'NAO_ATENDE',
        'EVIDENCIA_INSUFICIENTE', 'NAO_IDENTIFICADO', 'REQUER_REVISAO',
    ];

    public static function classificationMeta(string $classification): array
    {
        return self::CLASSIFICATION_LABELS[$classification] ?? ['label' => $classification, 'class' => 'text-bg-secondary'];
    }

    public static function forProposal(PDO $pdo, int $proposalId): array
    {
        $stmt = $pdo->prepare(
            'SELECT e.*, r.description AS requirement_description, r.category AS requirement_category, r.mandatory
             FROM evaluations e
             JOIN licitation_requirements r ON r.id = e.licitation_requirement_id
             WHERE e.proposal_id = :proposal_id
             ORDER BY r.id'
        );
        $stmt->execute(['proposal_id' => $proposalId]);

        return $stmt->fetchAll();
    }

    public static function evidences(PDO $pdo, int $evaluationId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM evaluation_evidences WHERE evaluation_id = :id');
        $stmt->execute(['id' => $evaluationId]);

        return $stmt->fetchAll();
    }

    /**
     * Records a human decision on top of the AI's evaluation. The original AI
     * classification/justification columns are never touched here — only the
     * human_* columns are written, so the AI's original conclusion is always preserved.
     */
    public static function submitHumanReview(PDO $pdo, int $evaluationId, string $classification, string $justification, int $userId): void
    {
        $classification = strtolower(trim($classification));

        if (!in_array(strtoupper($classification), self::VALID_CLASSIFICATIONS, true)) {
            throw new RuntimeException('Classificação inválida.');
        }

        $stmt = $pdo->prepare(
            'UPDATE evaluations SET
                human_reviewed = 1,
                human_classification = :classification,
                human_justification = :justification,
                human_reviewed_by = :user_id,
                human_reviewed_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'classification' => $classification,
            'justification' => $justification !== '' ? $justification : null,
            'user_id' => $userId,
            'id' => $evaluationId,
        ]);

        $stmt = $pdo->prepare('SELECT proposal_id FROM evaluations WHERE id = :id');
        $stmt->execute(['id' => $evaluationId]);
        $proposalId = (int) $stmt->fetchColumn();

        if ($proposalId) {
            ScoreService::recalculate($pdo, $proposalId);
        }
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT e.*, r.description AS requirement_description, r.category AS requirement_category,
                r.mandatory, p.licitation_id, p.company_id
             FROM evaluations e
             JOIN licitation_requirements r ON r.id = e.licitation_requirement_id
             JOIN proposals p ON p.id = e.proposal_id
             WHERE e.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Sends the proposal's extracted text and the licitation's requirements to OpenAI,
     * validates the JSON response, and persists structured evaluations.
     * Never lets the model's output through unvalidated — malformed items are dropped, not guessed at.
     */
    public static function evaluateProposal(PDO $pdo, int $proposalId, ?OpenAIProvider $provider = null): array
    {
        $provider ??= new OpenAIProvider();

        $stmt = $pdo->prepare('SELECT * FROM proposals WHERE id = :id');
        $stmt->execute(['id' => $proposalId]);
        $proposal = $stmt->fetch();

        if (!$proposal) {
            throw new RuntimeException('Proposta não encontrada.');
        }

        $requirements = LicitationService::requirements($pdo, (int) $proposal['licitation_id']);
        if (!$requirements) {
            throw new RuntimeException('A licitação não possui requisitos cadastrados para análise.');
        }

        $documentsText = self::buildDocumentsText($pdo, $proposalId);
        if ($documentsText === '') {
            throw new RuntimeException('Nenhum texto extraído disponível para análise. Processe a fila de extração primeiro.');
        }

        $pdo->prepare("UPDATE proposals SET status = 'analyzing' WHERE id = :id")->execute(['id' => $proposalId]);

        $systemPrompt = file_get_contents(__DIR__ . '/../../prompts/evaluate_requirement.txt');

        $requirementsPayload = array_map(static fn(array $r): array => [
            'requirement_id' => (int) $r['id'],
            'categoria' => $r['category'],
            'descricao' => $r['description'],
            'obrigatorio' => (bool) $r['mandatory'],
        ], $requirements);

        $userPrompt = json_encode([
            'requisitos' => $requirementsPayload,
            'documentos_da_proposta' => $documentsText,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        try {
            $raw = $provider->completeJson($systemPrompt, $userPrompt);
            $parsed = json_decode($raw, true);
        } catch (Throwable $e) {
            $pdo->prepare("UPDATE proposals SET status = 'error', error_message = :msg WHERE id = :id")
                ->execute(['msg' => $e->getMessage(), 'id' => $proposalId]);
            throw $e;
        }

        if (!is_array($parsed) || !isset($parsed['avaliacoes']) || !is_array($parsed['avaliacoes'])) {
            $msg = 'A IA retornou uma resposta em formato inesperado.';
            $pdo->prepare("UPDATE proposals SET status = 'error', error_message = :msg WHERE id = :id")
                ->execute(['msg' => $msg, 'id' => $proposalId]);
            throw new RuntimeException($msg);
        }

        $validRequirementIds = array_column($requirements, 'id');
        $validRequirementIds = array_map('intval', $validRequirementIds);

        $documentsByName = self::documentNameMap($pdo, $proposalId);

        $saved = 0;
        $flagged = 0;

        foreach ($parsed['avaliacoes'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $requirementId = (int) ($item['requirement_id'] ?? 0);
            $classification = strtoupper(trim((string) ($item['classificacao'] ?? '')));
            $justification = trim((string) ($item['justificativa'] ?? ''));

            if (!in_array($requirementId, $validRequirementIds, true)) {
                continue;
            }
            if (!in_array($classification, self::VALID_CLASSIFICATIONS, true)) {
                continue;
            }
            if ($justification === '') {
                continue;
            }

            $confidence = isset($item['confianca']) && is_numeric($item['confianca'])
                ? max(0, min(1, (float) $item['confianca']))
                : null;

            $dbClassification = strtolower($classification);

            if (in_array($dbClassification, ['evidencia_insuficiente', 'nao_identificado', 'requer_revisao'], true)) {
                $flagged++;
            }

            $evaluationId = self::upsertEvaluation($pdo, $proposalId, $requirementId, $dbClassification, $justification, $confidence);

            $evidences = is_array($item['evidencias'] ?? null) ? $item['evidencias'] : [];
            self::replaceEvidences($pdo, $evaluationId, $evidences, $documentsByName);

            $saved++;
        }

        if ($saved === 0) {
            $msg = 'Nenhuma avaliação válida foi retornada pela IA.';
            $pdo->prepare("UPDATE proposals SET status = 'error', error_message = :msg WHERE id = :id")
                ->execute(['msg' => $msg, 'id' => $proposalId]);
            throw new RuntimeException($msg);
        }

        ScoreService::recalculate($pdo, $proposalId);

        $finalStatus = $flagged > 0 ? 'needs_review' : 'analyzed';
        $pdo->prepare("UPDATE proposals SET status = :status, error_message = NULL WHERE id = :id")
            ->execute(['status' => $finalStatus, 'id' => $proposalId]);

        return ['saved' => $saved, 'flagged' => $flagged, 'status' => $finalStatus];
    }

    private static function upsertEvaluation(
        PDO $pdo,
        int $proposalId,
        int $requirementId,
        string $classification,
        string $justification,
        ?float $confidence
    ): int {
        $stmt = $pdo->prepare(
            'SELECT id FROM evaluations WHERE proposal_id = :p AND licitation_requirement_id = :r'
        );
        $stmt->execute(['p' => $proposalId, 'r' => $requirementId]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $stmt = $pdo->prepare(
                'UPDATE evaluations SET classification = :c, justification = :j, confidence = :conf, ai_model = :model
                 WHERE id = :id'
            );
            $stmt->execute([
                'c' => $classification,
                'j' => $justification,
                'conf' => $confidence,
                'model' => \App\Core\Env::get('OPENAI_MODEL', 'gpt-4o-mini'),
                'id' => $existingId,
            ]);

            return (int) $existingId;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO evaluations (proposal_id, licitation_requirement_id, classification, justification, confidence, ai_model)
             VALUES (:p, :r, :c, :j, :conf, :model)'
        );
        $stmt->execute([
            'p' => $proposalId,
            'r' => $requirementId,
            'c' => $classification,
            'j' => $justification,
            'conf' => $confidence,
            'model' => \App\Core\Env::get('OPENAI_MODEL', 'gpt-4o-mini'),
        ]);

        return (int) $pdo->lastInsertId();
    }

    private static function replaceEvidences(PDO $pdo, int $evaluationId, array $evidences, array $documentsByName): void
    {
        $pdo->prepare('DELETE FROM evaluation_evidences WHERE evaluation_id = :id')->execute(['id' => $evaluationId]);

        if (!$evidences) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO evaluation_evidences (evaluation_id, proposal_document_id, page_number, excerpt)
             VALUES (:evaluation_id, :document_id, :page, :excerpt)'
        );

        foreach ($evidences as $ev) {
            if (!is_array($ev)) {
                continue;
            }
            $excerpt = trim((string) ($ev['trecho'] ?? ''));
            if ($excerpt === '') {
                continue;
            }
            $docName = trim((string) ($ev['documento'] ?? ''));
            $page = isset($ev['pagina']) && is_numeric($ev['pagina']) ? (int) $ev['pagina'] : null;

            $stmt->execute([
                'evaluation_id' => $evaluationId,
                'document_id' => $documentsByName[$docName] ?? null,
                'page' => $page,
                'excerpt' => mb_substr($excerpt, 0, 2000),
            ]);
        }
    }

    /** @return array<string, string> keyed by proposal_document_id => original_name */
    private static function documentNameMap(PDO $pdo, int $proposalId): array
    {
        $stmt = $pdo->prepare('SELECT id, original_name FROM proposal_documents WHERE proposal_id = :id');
        $stmt->execute(['id' => $proposalId]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['original_name']] = (int) $row['id'];
        }

        return $map;
    }

    private static function buildDocumentsText(PDO $pdo, int $proposalId): string
    {
        $stmt = $pdo->prepare(
            'SELECT pd.original_name, de.id AS extraction_id
             FROM proposal_documents pd
             JOIN document_extractions de ON de.proposal_document_id = pd.id
             WHERE pd.proposal_id = :proposal_id AND de.status = "done"'
        );
        $stmt->execute(['proposal_id' => $proposalId]);
        $docs = $stmt->fetchAll();

        $parts = [];
        $totalChars = 0;
        $maxChars = 60000;

        foreach ($docs as $doc) {
            $chunks = ExtractionService::chunksForExtraction($pdo, (int) $doc['extraction_id']);
            $docText = "### Documento: {$doc['original_name']}\n";

            foreach ($chunks as $chunk) {
                $docText .= "-- Página {$chunk['page_number']} --\n" . $chunk['content'] . "\n";
            }

            if ($totalChars + mb_strlen($docText) > $maxChars) {
                $docText = mb_substr($docText, 0, max(0, $maxChars - $totalChars));
            }

            $parts[] = $docText;
            $totalChars += mb_strlen($docText);

            if ($totalChars >= $maxChars) {
                break;
            }
        }

        return implode("\n\n", $parts);
    }
}
