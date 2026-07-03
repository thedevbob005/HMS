<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class HotelRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotels WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $id]);
        $hotel = $stmt->fetch();
        return $hotel ? $hotel : null;
    }

    public function findGroupById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotel_groups WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $id]);
        $group = $stmt->fetch();
        return $group ? $group : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM hotels WHERE deleted_at IS NULL');
        return $stmt->fetchAll();
    }

    public function findAllGroups(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM hotel_groups WHERE deleted_at IS NULL');
        return $stmt->fetchAll();
    }

    public function findAccessibleHotels(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT h.* 
            FROM hotels h
            JOIN user_hotel_access uha ON h.id = uha.hotel_id
            WHERE uha.user_id = :user_id AND h.deleted_at IS NULL
        ');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function createGroup(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO hotel_groups (name) VALUES (:name)
        ');
        $stmt->execute([
            ':name' => $data['name']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO hotels (hotel_group_id, name, address) 
            VALUES (:hotel_group_id, :name, :address)
        ');
        $stmt->execute([
            ':hotel_group_id' => $data['hotel_group_id'] ?? null,
            ':name' => $data['name'],
            ':address' => $data['address'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE hotels 
            SET hotel_group_id = :hotel_group_id, name = :name, address = :address, updated_at = NOW() 
            WHERE id = :id
        ');
        return $stmt->execute([
            ':id' => $id,
            ':hotel_group_id' => $data['hotel_group_id'] ?? null,
            ':name' => $data['name'],
            ':address' => $data['address'] ?? null,
        ]);
    }
}
