<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class ContractService
{
    private const STATUS_LABELS = [
        'em_elaboracao' => ['label' => 'Em elaboração', 'class' => 'text-bg-info'],
        'ativo' => ['label' => 'Ativo', 'class' => 'text-bg-success'],
        'suspenso' => ['label' => 'Suspenso', 'class' => 'text-bg-warning'],
        'encerrado' => ['label' => 'Encerrado', 'class' => 'text-bg-secondary'],
        'vencido' => ['label' => 'Vencido', 'class' => 'text-bg-danger'],
    ];

    public static function statusMeta(string $status): array
    {
        return self::STATUS_LABELS[$status] ?? ['label' => $status, 'class' => 'text-bg-secondary'];
    }

    public static function list(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT ct.*, c.corporate_name
             FROM contracts ct
             LEFT JOIN companies c ON c.id = ct.company_id
             ORDER BY ct.created_at DESC"
        )->fetchAll();
    }

    public static function create(PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO contracts (company_id, name, number, status, value, physical_execution_percentage, end_date)
             VALUES (:company_id, :name, :number, :status, :value, :percentage, :end_date)'
        );
        $stmt->execute([
            'company_id' => $data['company_id'] ?: null,
            'name' => $data['name'],
            'number' => $data['number'],
            'status' => $data['status'],
            'value' => $data['value'],
            'percentage' => $data['percentage'],
            'end_date' => $data['end_date'] ?: null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('DELETE FROM contracts WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
