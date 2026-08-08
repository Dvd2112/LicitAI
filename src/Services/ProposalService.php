<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

final class ProposalService
{
    private const STATUS_LABELS = [
        'draft' => ['label' => 'Rascunho', 'class' => 'text-bg-secondary'],
        'submitted' => ['label' => 'Enviada', 'class' => 'text-bg-info'],
        'queued' => ['label' => 'Na fila', 'class' => 'text-bg-secondary'],
        'extracting' => ['label' => 'Extraindo documentos', 'class' => 'text-bg-info'],
        'analyzing' => ['label' => 'Analisando', 'class' => 'text-bg-info'],
        'analyzed' => ['label' => 'Análise concluída', 'class' => 'text-bg-success'],
        'needs_review' => ['label' => 'Requer revisão', 'class' => 'text-bg-warning'],
        'error' => ['label' => 'Erro no processamento', 'class' => 'text-bg-danger'],
    ];

    public static function statusMeta(string $status): array
    {
        return self::STATUS_LABELS[$status] ?? ['label' => $status, 'class' => 'text-bg-secondary'];
    }

    public static function listForAdmin(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT p.*, c.corporate_name, c.cnpj, l.number AS licitation_number, l.title AS licitation_title,
                s.adherence_percentage,
                (SELECT COUNT(*) FROM proposal_documents d WHERE d.proposal_id = p.id) AS document_count
             FROM proposals p
             JOIN companies c ON c.id = p.company_id
             JOIN licitations l ON l.id = p.licitation_id
             LEFT JOIN proposal_scores s ON s.proposal_id = p.id
             ORDER BY p.created_at DESC"
        )->fetchAll();
    }

    public static function listForCompany(PDO $pdo, int $companyId): array
    {
        $stmt = $pdo->prepare(
            "SELECT p.*, l.number AS licitation_number, l.title AS licitation_title,
                s.adherence_percentage,
                (SELECT COUNT(*) FROM proposal_documents d WHERE d.proposal_id = p.id) AS document_count
             FROM proposals p
             JOIN licitations l ON l.id = p.licitation_id
             LEFT JOIN proposal_scores s ON s.proposal_id = p.id
             WHERE p.company_id = :company_id
             ORDER BY p.created_at DESC"
        );
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll();
    }

    public static function listForLicitation(PDO $pdo, int $licitationId): array
    {
        $stmt = $pdo->prepare(
            "SELECT p.*, c.corporate_name, c.cnpj, s.adherence_percentage,
                (SELECT COUNT(*) FROM proposal_documents d WHERE d.proposal_id = p.id) AS document_count
             FROM proposals p
             JOIN companies c ON c.id = p.company_id
             LEFT JOIN proposal_scores s ON s.proposal_id = p.id
             WHERE p.licitation_id = :licitation_id
             ORDER BY p.created_at DESC"
        );
        $stmt->execute(['licitation_id' => $licitationId]);

        return $stmt->fetchAll();
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT p.*, c.corporate_name, c.cnpj, l.number AS licitation_number, l.title AS licitation_title
             FROM proposals p
             JOIN companies c ON c.id = p.company_id
             JOIN licitations l ON l.id = p.licitation_id
             WHERE p.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function documents(PDO $pdo, int $proposalId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM proposal_documents WHERE proposal_id = :id ORDER BY id');
        $stmt->execute(['id' => $proposalId]);

        return $stmt->fetchAll();
    }

    public static function create(PDO $pdo, int $licitationId, int $companyId, int $userId, array $filesInput): int
    {
        $licitation = LicitationService::find($pdo, $licitationId);
        if (!$licitation) {
            throw new RuntimeException('Licitação não encontrada.');
        }
        if ($licitation['status'] !== 'open') {
            throw new RuntimeException('Esta licitação não está aberta para recebimento de propostas.');
        }

        $stmt = $pdo->prepare('SELECT id FROM proposals WHERE licitation_id = :l AND company_id = :c');
        $stmt->execute(['l' => $licitationId, 'c' => $companyId]);
        if ($stmt->fetchColumn()) {
            throw new RuntimeException('Esta empresa já possui uma proposta enviada para esta licitação.');
        }

        $documents = UploadService::storeManyPdfs(
            $filesInput,
            __DIR__ . '/../../storage/uploads/proposals/' . $licitationId . '_' . $companyId
        );

        if (!$documents) {
            throw new RuntimeException('Envie ao menos um documento em PDF.');
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO proposals (licitation_id, company_id, status, submitted_at, created_by)
                 VALUES (:licitation_id, :company_id, "queued", NOW(), :created_by)'
            );
            $stmt->execute([
                'licitation_id' => $licitationId,
                'company_id' => $companyId,
                'created_by' => $userId,
            ]);
            $proposalId = (int) $pdo->lastInsertId();

            $docStmt = $pdo->prepare(
                'INSERT INTO proposal_documents (proposal_id, original_name, stored_name, mime_type, size_bytes, path)
                 VALUES (:proposal_id, :original_name, :stored_name, :mime_type, :size_bytes, :path)'
            );
            $extractionStmt = $pdo->prepare(
                'INSERT INTO document_extractions (proposal_document_id, status) VALUES (:document_id, "pending")'
            );
            $jobStmt = $pdo->prepare(
                'INSERT INTO jobs (type, payload, status) VALUES ("extract_document", :payload, "pending")'
            );

            foreach ($documents as $doc) {
                $docStmt->execute([
                    'proposal_id' => $proposalId,
                    'original_name' => $doc['original_name'],
                    'stored_name' => $doc['stored_name'],
                    'mime_type' => $doc['mime_type'],
                    'size_bytes' => $doc['size_bytes'],
                    'path' => $doc['path'],
                ]);
                $proposalDocumentId = (int) $pdo->lastInsertId();

                $extractionStmt->execute(['document_id' => $proposalDocumentId]);

                $jobStmt->execute([
                    'payload' => json_encode([
                        'proposal_document_id' => $proposalDocumentId,
                        'document_extraction_id' => (int) $pdo->lastInsertId(),
                    ], JSON_UNESCAPED_UNICODE),
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $proposalId;
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('DELETE FROM proposals WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
