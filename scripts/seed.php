<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\AuthService;

Env::load(__DIR__ . '/../.env');
$pdo = Database::connection();

function ensureUser(PDO $pdo, string $name, string $email, string $password, string $role): int
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $id = $stmt->fetchColumn();

    if ($id) {
        return (int) $id;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)'
    );
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'hash' => AuthService::hash($password),
        'role' => $role,
    ]);

    return (int) $pdo->lastInsertId();
}

function ensureCompany(PDO $pdo, string $corporateName, string $cnpj, string $email): int
{
    $stmt = $pdo->prepare('SELECT id FROM companies WHERE cnpj = :cnpj');
    $stmt->execute(['cnpj' => $cnpj]);
    $id = $stmt->fetchColumn();

    if ($id) {
        return (int) $id;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO companies (corporate_name, cnpj, email, responsible_name)
         VALUES (:name, :cnpj, :email, :responsible)'
    );
    $stmt->execute([
        'name' => $corporateName,
        'cnpj' => $cnpj,
        'email' => $email,
        'responsible' => 'Responsável Legal',
    ]);

    return (int) $pdo->lastInsertId();
}

function ensureCompanyUser(PDO $pdo, int $companyId, int $userId): void
{
    $stmt = $pdo->prepare('SELECT id FROM company_users WHERE company_id = :company_id AND user_id = :user_id');
    $stmt->execute(['company_id' => $companyId, 'user_id' => $userId]);

    if ($stmt->fetchColumn()) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO company_users (company_id, user_id) VALUES (:company_id, :user_id)');
    $stmt->execute(['company_id' => $companyId, 'user_id' => $userId]);
}

$adminId = ensureUser($pdo, 'Administrador', 'admin', 'admin123', 'admin');
fwrite(STDOUT, "Usuário admin pronto (login: admin / admin123) — id {$adminId}\n");

$techCompanyId = ensureCompany($pdo, 'TechSolutions Ltda', '12.345.678/0001-90', 'contato@techsolutions.example');
$techUserId = ensureUser($pdo, 'TechSolutions Ltda', 'techsolutions', 'empresa123', 'company');
ensureCompanyUser($pdo, $techCompanyId, $techUserId);
fwrite(STDOUT, "Empresa TechSolutions pronta (login: techsolutions / empresa123)\n");

$dataMaxCompanyId = ensureCompany($pdo, 'DataMax Comércio', '98.765.432/0001-11', 'contato@datamax.example');
$dataMaxUserId = ensureUser($pdo, 'DataMax Comércio', 'datamax', 'empresa123', 'company');
ensureCompanyUser($pdo, $dataMaxCompanyId, $dataMaxUserId);
fwrite(STDOUT, "Empresa DataMax pronta (login: datamax / empresa123)\n");

fwrite(STDOUT, "Seed concluído.\n");
