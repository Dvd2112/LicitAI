<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class AuthService
{
    public static function attempt(PDO $pdo, string $email, string $password): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email AND status = "active" LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    public static function login(PDO $pdo, array $user): void
    {
        session_regenerate_id(true);

        $companyId = null;
        if ($user['role'] === 'company') {
            $stmt = $pdo->prepare('SELECT company_id FROM company_users WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $user['id']]);
            $companyId = $stmt->fetchColumn() ?: null;
        }

        $_SESSION['logged'] = true;
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'company_id' => $companyId !== null ? (int) $companyId : null,
        ];
    }

    public static function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return (self::currentUser()['role'] ?? null) === 'admin';
    }

    public static function requireRole(string $role): void
    {
        $user = self::currentUser();

        if (!$user || $user['role'] !== $role) {
            http_response_code(403);
            echo '<h1>403</h1><p>Acesso não permitido para este perfil.</p>';
            exit;
        }
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
