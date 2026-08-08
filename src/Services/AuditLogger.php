<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class AuditLogger
{
    public static function log(
        PDO $pdo,
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $details = []
    ): void {
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address)
             VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public static function list(PDO $pdo, int $limit = 200, string $entityType = ''): array
    {
        $sql = 'SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id';
        $params = [];

        if ($entityType !== '') {
            $sql .= ' WHERE a.entity_type = :entity_type';
            $params['entity_type'] = $entityType;
        }

        $sql .= ' ORDER BY a.id DESC LIMIT ' . max(1, min(500, $limit));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function entityTypes(PDO $pdo): array
    {
        return $pdo->query('SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type')->fetchAll(PDO::FETCH_COLUMN);
    }

    private const ACTIVITY_LABELS = [
        'create.licitation' => 'Licitação criada',
        'delete.licitation' => 'Licitação excluída',
        'create.licitation_requirement' => 'Requisito adicionado',
        'delete.licitation_requirement' => 'Requisito removido',
        'create.proposal' => 'Proposta enviada',
        'delete.proposal' => 'Proposta excluída',
        'create.company' => 'Empresa cadastrada',
        'delete.company' => 'Empresa excluída',
        'create.contract' => 'Contrato cadastrado',
        'delete.contract' => 'Contrato excluído',
        'analyze.proposal' => 'Análise por IA concluída',
        'analyze_failed.proposal' => 'Falha na análise por IA',
        'human_review.evaluation' => 'Revisão humana registrada',
        'process_queue.job' => 'Fila de extração processada',
        'view_document.proposal_document' => 'Documento de proposta visualizado',
        'view_document.licitation_document' => 'Documento da licitação visualizado',
        'login.user' => 'Login realizado',
        'login_failed.user' => 'Tentativa de login falhou',
        'logout.user' => 'Logout realizado',
    ];

    /** Recent activity enriched with human-readable labels, for the dashboard feed. */
    public static function recentActivity(PDO $pdo, int $limit = 10, ?string $since = null): array
    {
        $sql = 'SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id';
        $params = [];

        if ($since !== null) {
            $sql .= ' WHERE a.created_at >= :since';
            $params['since'] = $since;
        }

        $sql .= ' ORDER BY a.id DESC LIMIT ' . max(1, min(100, $limit));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $key = $row['action'] . '.' . $row['entity_type'];
            $row['label'] = self::ACTIVITY_LABELS[$key] ?? ($row['action'] . ' — ' . $row['entity_type']);
            $row['detail_text'] = self::entityLabel($pdo, $row['entity_type'], $row['entity_id'] !== null ? (int) $row['entity_id'] : null);
        }

        return $rows;
    }

    private static function entityLabel(PDO $pdo, string $entityType, ?int $entityId): string
    {
        if (!$entityId) {
            return '—';
        }

        switch ($entityType) {
            case 'licitation':
                $stmt = $pdo->prepare('SELECT number, title FROM licitations WHERE id = :id');
                $stmt->execute(['id' => $entityId]);
                $r = $stmt->fetch();

                return $r ? "{$r['number']} — {$r['title']}" : "Licitação #{$entityId}";

            case 'proposal':
                $stmt = $pdo->prepare(
                    'SELECT l.number, c.corporate_name FROM proposals p
                     JOIN licitations l ON l.id = p.licitation_id
                     JOIN companies c ON c.id = p.company_id
                     WHERE p.id = :id'
                );
                $stmt->execute(['id' => $entityId]);
                $r = $stmt->fetch();

                return $r ? "{$r['corporate_name']} — {$r['number']}" : "Proposta #{$entityId}";

            case 'company':
                $stmt = $pdo->prepare('SELECT corporate_name FROM companies WHERE id = :id');
                $stmt->execute(['id' => $entityId]);

                return (string) ($stmt->fetchColumn() ?: "Empresa #{$entityId}");

            case 'contract':
                $stmt = $pdo->prepare('SELECT name, number FROM contracts WHERE id = :id');
                $stmt->execute(['id' => $entityId]);
                $r = $stmt->fetch();

                return $r ? "{$r['name']} ({$r['number']})" : "Contrato #{$entityId}";

            case 'licitation_requirement':
                $stmt = $pdo->prepare('SELECT description FROM licitation_requirements WHERE id = :id');
                $stmt->execute(['id' => $entityId]);
                $desc = $stmt->fetchColumn();

                return $desc ? mb_substr((string) $desc, 0, 60) : "Requisito #{$entityId}";

            default:
                return "#{$entityId}";
        }
    }
}
