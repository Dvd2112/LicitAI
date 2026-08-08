<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use PDO;
use RuntimeException;
use Throwable;

final class ExtractionService
{
    /** @return array<int, array> keyed by proposal_document_id */
    public static function forProposal(PDO $pdo, int $proposalId): array
    {
        $stmt = $pdo->prepare(
            'SELECT de.* FROM document_extractions de
             JOIN proposal_documents pd ON pd.id = de.proposal_document_id
             WHERE pd.proposal_id = :proposal_id'
        );
        $stmt->execute(['proposal_id' => $proposalId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['proposal_document_id']] = $row;
        }

        return $result;
    }

    public static function chunksForExtraction(PDO $pdo, int $extractionId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM document_chunks WHERE document_extraction_id = :id ORDER BY page_number, position');
        $stmt->execute(['id' => $extractionId]);

        return $stmt->fetchAll();
    }

    /** Processes pending extraction jobs synchronously. Returns a summary per job. */
    public static function runPendingJobs(PDO $pdo, int $limit = 10): array
    {
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE type = 'extract_document' AND status = 'pending' ORDER BY id LIMIT " . (int) $limit);
        $stmt->execute();
        $jobs = $stmt->fetchAll();

        $results = [];
        foreach ($jobs as $job) {
            $results[] = self::runJob($pdo, $job);
        }

        return $results;
    }

    private static function runJob(PDO $pdo, array $job): array
    {
        $pdo->prepare("UPDATE jobs SET status = 'running', attempts = attempts + 1 WHERE id = :id")
            ->execute(['id' => $job['id']]);

        $payload = json_decode($job['payload'], true) ?: [];
        $documentId = (int) ($payload['proposal_document_id'] ?? 0);
        $extractionId = (int) ($payload['document_extraction_id'] ?? 0);

        try {
            self::extractProposalDocument($pdo, $extractionId, $documentId);
            $pdo->prepare("UPDATE jobs SET status = 'done' WHERE id = :id")->execute(['id' => $job['id']]);

            return ['job_id' => (int) $job['id'], 'status' => 'done'];
        } catch (Throwable $e) {
            $pdo->prepare("UPDATE jobs SET status = 'failed', error_message = :msg WHERE id = :id")
                ->execute(['msg' => $e->getMessage(), 'id' => $job['id']]);
            $pdo->prepare("UPDATE document_extractions SET status = 'failed', error_message = :msg WHERE id = :id")
                ->execute(['msg' => $e->getMessage(), 'id' => $extractionId]);

            self::syncProposalStatus($pdo, $documentId, forceError: true, errorMessage: $e->getMessage());

            return ['job_id' => (int) $job['id'], 'status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    private static function extractProposalDocument(PDO $pdo, int $extractionId, int $documentId): void
    {
        $stmt = $pdo->prepare('SELECT * FROM proposal_documents WHERE id = :id');
        $stmt->execute(['id' => $documentId]);
        $doc = $stmt->fetch();

        if (!$doc) {
            throw new RuntimeException('Documento não encontrado.');
        }

        $pdo->prepare("UPDATE document_extractions SET status = 'processing' WHERE id = :id")->execute(['id' => $extractionId]);
        $pdo->prepare("UPDATE proposals SET status = 'extracting' WHERE id = :id")->execute(['id' => $doc['proposal_id']]);

        [$pages, $method] = self::extractPdfPages($doc['path']);

        $chunkStmt = $pdo->prepare(
            'INSERT INTO document_chunks (document_extraction_id, page_number, position, content)
             VALUES (:extraction_id, :page, :position, :content)'
        );

        foreach ($pages as $i => $text) {
            $chunkStmt->execute([
                'extraction_id' => $extractionId,
                'page' => $i + 1,
                'position' => 0,
                'content' => $text,
            ]);
        }

        $pdo->prepare("UPDATE document_extractions SET status = 'done', method = :method, page_count = :pages WHERE id = :id")
            ->execute(['method' => $method, 'pages' => count($pages), 'id' => $extractionId]);

        self::syncProposalStatus($pdo, $documentId);
    }

    /** Advances the parent proposal to 'queued' for analysis once every document of that proposal finished extracting. */
    private static function syncProposalStatus(PDO $pdo, int $documentId, bool $forceError = false, ?string $errorMessage = null): void
    {
        $stmt = $pdo->prepare('SELECT proposal_id FROM proposal_documents WHERE id = :id');
        $stmt->execute(['id' => $documentId]);
        $proposalId = $stmt->fetchColumn();

        if (!$proposalId) {
            return;
        }

        if ($forceError) {
            $pdo->prepare("UPDATE proposals SET status = 'error', error_message = :msg WHERE id = :id")
                ->execute(['msg' => $errorMessage, 'id' => $proposalId]);

            return;
        }

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM document_extractions de
             JOIN proposal_documents pd ON pd.id = de.proposal_document_id
             WHERE pd.proposal_id = :proposal_id AND de.status NOT IN ('done', 'failed')"
        );
        $stmt->execute(['proposal_id' => $proposalId]);

        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->prepare("UPDATE proposals SET status = 'analyzing' WHERE id = :id")->execute(['id' => $proposalId]);
        }
    }

    /**
     * Extracts text per page. Tries pdftotext first (native PDF text layer);
     * falls back to Tesseract OCR when the page has no usable text (scanned PDF).
     *
     * @return array{0: array<int, string>, 1: string} [pages, method]
     */
    private static function extractPdfPages(string $path): array
    {
        $pdftotext = Env::get('PDFTOTEXT_BIN', 'pdftotext');
        $output = self::run([$pdftotext, '-layout', $path, '-']);

        $pages = $output !== '' ? preg_split('/\f/', $output) : [''];
        $pages = array_map('trim', $pages);

        $hasText = count(array_filter($pages, static fn(string $p): bool => mb_strlen($p) > 20)) > 0;

        if ($hasText) {
            return [$pages, 'pdftotext'];
        }

        $tesseract = Env::get('TESSERACT_BIN', '');
        if ($tesseract === '' || $tesseract === null) {
            throw new RuntimeException(
                'O PDF parece ser digitalizado (sem texto extraível) e o OCR (Tesseract) não está configurado neste ambiente.'
            );
        }

        throw new RuntimeException('Fallback de OCR ainda não implementado nesta instalação.');
    }

    private static function run(array $command): string
    {
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = @proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);

        if (!is_resource($process)) {
            throw new RuntimeException('Não foi possível executar o extrator de PDF (' . $command[0] . ').');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException('Falha ao extrair texto do PDF: ' . trim($stderr) ?: "código {$exitCode}");
        }

        return (string) $stdout;
    }
}
