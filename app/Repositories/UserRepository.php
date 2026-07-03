<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, phone, is_active FROM users WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ? $user : null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username AND deleted_at IS NULL');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        return $user ? $user : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('
            SELECT u.id, u.username, u.email, u.phone, u.is_active,
                   GROUP_CONCAT(DISTINCT r.name) as roles,
                   GROUP_CONCAT(DISTINCT h.name) as hotels
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            LEFT JOIN user_hotel_access uha ON u.id = uha.user_id
            LEFT JOIN hotels h ON uha.hotel_id = h.id
            WHERE u.deleted_at IS NULL
            GROUP BY u.id
        ');
        return $stmt->fetchAll();
    }

    public function findAllRoles(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM roles WHERE deleted_at IS NULL');
        return $stmt->fetchAll();
    }

    public function findRoleByName(string $name): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM roles WHERE name = :name AND deleted_at IS NULL');
        $stmt->execute([':name' => $name]);
        $role = $stmt->fetch();
        return $role ? $role : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (username, password_hash, email, phone, is_active) 
            VALUES (:username, :password_hash, :email, :phone, :is_active)
        ');
        $stmt->execute([
            ':username' => $data['username'],
            ':password_hash' => $data['password_hash'],
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function assignRole(int $userId, int $roleId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)
        ');
        $stmt->execute([
            ':user_id' => $userId,
            ':role_id' => $roleId
        ]);
    }

    public function clearRoles(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
    }

    public function grantHotelAccess(int $userId, int $hotelId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO user_hotel_access (user_id, hotel_id) VALUES (:user_id, :hotel_id)
        ');
        $stmt->execute([
            ':user_id' => $userId,
            ':hotel_id' => $hotelId
        ]);
    }

    public function clearHotelAccess(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM user_hotel_access WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
    }
}
