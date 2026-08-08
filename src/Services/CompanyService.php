<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class CompanyService
{
    public static function list(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT c.*,
                (SELECT u.email FROM company_users cu
                    JOIN users u ON u.id = cu.user_id
                    WHERE cu.company_id = c.id LIMIT 1) AS login_email
             FROM companies c
             ORDER BY c.corporate_name"
        )->fetchAll();
    }

    public static function create(PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare(
            'SELECT id FROM companies WHERE cnpj = :cnpj'
        );
        $stmt->execute(['cnpj' => $data['cnpj']]);
        if ($stmt->fetchColumn()) {
            throw new RuntimeException('Já existe uma empresa cadastrada com este CNPJ.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO companies (corporate_name, trade_name, cnpj, email, phone, responsible_name)
             VALUES (:corporate_name, :trade_name, :cnpj, :email, :phone, :responsible_name)'
        );
        $stmt->execute([
            'corporate_name' => $data['corporate_name'],
            'trade_name' => $data['trade_name'] ?: null,
            'cnpj' => $data['cnpj'],
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'responsible_name' => $data['responsible_name'] ?: null,
        ]);

        $companyId = (int) $pdo->lastInsertId();

        if (!empty($data['login_email']) && !empty($data['login_password'])) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute(['email' => $data['login_email']]);
            if ($stmt->fetchColumn()) {
                throw new RuntimeException('Empresa cadastrada, mas o usuário de acesso informado já existe.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, "company")'
            );
            $stmt->execute([
                'name' => $data['corporate_name'],
                'email' => $data['login_email'],
                'hash' => AuthService::hash($data['login_password']),
            ]);
            $userId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO company_users (company_id, user_id) VALUES (:company_id, :user_id)');
            $stmt->execute(['company_id' => $companyId, 'user_id' => $userId]);
        }

        return $companyId;
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM proposals WHERE company_id = :id');
        $stmt->execute(['id' => $id]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('Não é possível excluir: a empresa possui propostas vinculadas.');
        }

        $stmt = $pdo->prepare('DELETE FROM companies WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
