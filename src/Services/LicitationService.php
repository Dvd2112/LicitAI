<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

final class LicitationService
{
    private const STATUS_LABELS = [
        'draft' => ['label' => 'Rascunho', 'class' => 'text-bg-secondary'],
        'open' => ['label' => 'Aberto', 'class' => 'text-bg-success'],
        'pending' => ['label' => 'Pendente', 'class' => 'text-bg-warning'],
        'closed' => ['label' => 'Encerrado', 'class' => 'text-bg-secondary'],
    ];

    public static function statusMeta(string $status): array
    {
        return self::STATUS_LABELS[$status] ?? ['label' => $status, 'class' => 'text-bg-secondary'];
    }

    public static function list(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT l.*,
                (SELECT COUNT(*) FROM proposals p WHERE p.licitation_id = l.id) AS proposal_count,
                (SELECT COUNT(*) FROM licitation_requirements r WHERE r.licitation_id = l.id) AS requirement_count
             FROM licitations l
             ORDER BY l.created_at DESC"
        )->fetchAll();
    }

    public static function listOpen(PDO $pdo): array
    {
        $stmt = $pdo->prepare(
            "SELECT * FROM licitations WHERE status = 'open' ORDER BY proposal_deadline IS NULL, proposal_deadline"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM licitations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function items(PDO $pdo, int $licitationId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM licitation_items WHERE licitation_id = :id ORDER BY id');
        $stmt->execute(['id' => $licitationId]);

        return $stmt->fetchAll();
    }

    public static function units(PDO $pdo, int $licitationId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM execution_units WHERE licitation_id = :id ORDER BY id');
        $stmt->execute(['id' => $licitationId]);

        return $stmt->fetchAll();
    }

    public static function documents(PDO $pdo, int $licitationId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM licitation_documents WHERE licitation_id = :id ORDER BY id');
        $stmt->execute(['id' => $licitationId]);

        return $stmt->fetchAll();
    }

    public static function requirements(PDO $pdo, int $licitationId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM licitation_requirements WHERE licitation_id = :id ORDER BY id');
        $stmt->execute(['id' => $licitationId]);

        return $stmt->fetchAll();
    }

    private static function toDate(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private static function toInt(?string $value): ?int
    {
        $value = trim((string) $value);

        return $value !== '' ? (int) $value : null;
    }

    public static function createFromWizard(PDO $pdo, array $post, int $userId): int
    {
        $numero = trim((string) ($post['numero'] ?? ''));
        $titulo = trim((string) ($post['titulo'] ?? ''));
        $entidade = trim((string) ($post['entidade'] ?? ''));

        if ($numero === '' || $titulo === '') {
            throw new RuntimeException('Número e título são obrigatórios.');
        }

        $stmt = $pdo->prepare('SELECT id FROM licitations WHERE number = :number');
        $stmt->execute(['number' => $numero]);
        if ($stmt->fetchColumn()) {
            throw new RuntimeException('Já existe uma licitação cadastrada com este número.');
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO licitations (
                    number, year, type, title, executing_entity, administrative_process, site_url,
                    delivery_mode, sanitary_license, situation, status, total_value,
                    payment_days, payment_method, delivery_max_days,
                    publication_date, challenge_deadline, proposal_start_date, delivery_term_days, proposal_deadline,
                    opening_date, analysis_term_days, regularization_deadline, appeal_deadline,
                    contracting_term_days, contracting_deadline, signature_term_days, validity_date,
                    description, created_by
                ) VALUES (
                    :number, :year, :type, :title, :executing_entity, :administrative_process, :site_url,
                    :delivery_mode, :sanitary_license, :situation, :status, :total_value,
                    :payment_days, :payment_method, :delivery_max_days,
                    :publication_date, :challenge_deadline, :proposal_start_date, :delivery_term_days, :proposal_deadline,
                    :opening_date, :analysis_term_days, :regularization_deadline, :appeal_deadline,
                    :contracting_term_days, :contracting_deadline, :signature_term_days, :validity_date,
                    :description, :created_by
                )'
            );

            $stmt->execute([
                'number' => $numero,
                'year' => self::toInt($post['ano'] ?? null),
                'type' => trim((string) ($post['tipo'] ?? '')) ?: null,
                'title' => $titulo,
                'executing_entity' => $entidade ?: null,
                'administrative_process' => trim((string) ($post['processo'] ?? '')) ?: null,
                'site_url' => trim((string) ($post['site'] ?? '')) ?: null,
                'delivery_mode' => trim((string) ($post['entrega'] ?? '')) ?: null,
                'sanitary_license' => !empty($post['licenca']) ? 1 : 0,
                'situation' => trim((string) ($post['situacao'] ?? '')) ?: 'Em licitação',
                'status' => 'open',
                'total_value' => (float) ($post['total'] ?? 0),
                'payment_days' => self::toInt($post['pagamento_dias'] ?? null),
                'payment_method' => trim((string) ($post['forma_pagamento'] ?? '')) ?: null,
                'delivery_max_days' => self::toInt($post['entrega_max'] ?? null),
                'publication_date' => self::toDate($post['dt_publicacao'] ?? null),
                'challenge_deadline' => self::toDate($post['dt_impugnacao'] ?? null),
                'proposal_start_date' => self::toDate($post['dt_inicio_recebimento'] ?? null),
                'delivery_term_days' => self::toInt($post['prazo_entrega'] ?? null),
                'proposal_deadline' => self::toDate($post['dt_limite_envio'] ?? null),
                'opening_date' => self::toDate($post['dt_abertura'] ?? null),
                'analysis_term_days' => self::toInt($post['prazo_analise'] ?? null),
                'regularization_deadline' => self::toDate($post['dt_regularizacao'] ?? null),
                'appeal_deadline' => self::toDate($post['dt_recurso'] ?? null),
                'contracting_term_days' => self::toInt($post['prazo_convocacao'] ?? null),
                'contracting_deadline' => self::toDate($post['dt_contratacao'] ?? null),
                'signature_term_days' => self::toInt($post['prazo_assinatura'] ?? null),
                'validity_date' => self::toDate($post['dt_vigencia'] ?? null),
                'description' => trim((string) ($post['descricao'] ?? '')) ?: null,
                'created_by' => $userId,
            ]);

            $licitationId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO licitation_items (licitation_id, code, description, unit, quantity, unit_price, total_price)
                 VALUES (:licitation_id, :code, :description, :unit, :quantity, :unit_price, :total_price)'
            );

            foreach ((array) ($post['item_codigo'] ?? []) as $i => $codigo) {
                $descricao = trim((string) ($post['item_descricao'][$i] ?? ''));
                if (trim((string) $codigo) === '' && $descricao === '') {
                    continue;
                }
                $quantidade = (float) ($post['item_quantidade'][$i] ?? 0);
                $unitario = (float) ($post['item_unitario'][$i] ?? 0);
                $itemStmt->execute([
                    'licitation_id' => $licitationId,
                    'code' => trim((string) $codigo) ?: null,
                    'description' => $descricao ?: '(sem descrição)',
                    'unit' => trim((string) ($post['item_unidade'][$i] ?? '')) ?: null,
                    'quantity' => $quantidade,
                    'unit_price' => $unitario,
                    'total_price' => $quantidade * $unitario,
                ]);
            }

            $unitStmt = $pdo->prepare(
                'INSERT INTO execution_units (licitation_id, unit_name, responsible_name)
                 VALUES (:licitation_id, :unit_name, :responsible_name)'
            );

            foreach ((array) ($post['unidade_executora'] ?? []) as $i => $unitName) {
                $unitName = trim((string) $unitName);
                if ($unitName === '') {
                    continue;
                }
                $unitStmt->execute([
                    'licitation_id' => $licitationId,
                    'unit_name' => $unitName,
                    'responsible_name' => trim((string) ($post['responsavel'][$i] ?? '')) ?: null,
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $licitationId;
    }

    public static function attachDocuments(PDO $pdo, int $licitationId, array $documents, ?string $docType, int $userId): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO licitation_documents (licitation_id, doc_type, original_name, stored_name, mime_type, size_bytes, path, uploaded_by)
             VALUES (:licitation_id, :doc_type, :original_name, :stored_name, :mime_type, :size_bytes, :path, :uploaded_by)'
        );

        foreach ($documents as $doc) {
            $stmt->execute([
                'licitation_id' => $licitationId,
                'doc_type' => $docType,
                'original_name' => $doc['original_name'],
                'stored_name' => $doc['stored_name'],
                'mime_type' => $doc['mime_type'],
                'size_bytes' => $doc['size_bytes'],
                'path' => $doc['path'],
                'uploaded_by' => $userId,
            ]);
        }
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM proposals WHERE licitation_id = :id');
        $stmt->execute(['id' => $id]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('Não é possível excluir: a licitação já possui propostas vinculadas.');
        }

        $stmt = $pdo->prepare('DELETE FROM licitations WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function addRequirement(PDO $pdo, int $licitationId, array $data, int $userId): int
    {
        $description = trim((string) ($data['description'] ?? ''));
        if ($description === '') {
            throw new RuntimeException('Descreva o requisito.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO licitation_requirements (licitation_id, category, description, mandatory, weight, origin, created_by)
             VALUES (:licitation_id, :category, :description, :mandatory, :weight, "manual", :created_by)'
        );
        $stmt->execute([
            'licitation_id' => $licitationId,
            'category' => $data['category'] ?? 'outro',
            'description' => $description,
            'mandatory' => !empty($data['mandatory']) ? 1 : 0,
            'weight' => isset($data['weight']) && $data['weight'] !== '' ? (float) $data['weight'] : 1.00,
            'created_by' => $userId,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function deleteRequirement(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('DELETE FROM licitation_requirements WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
