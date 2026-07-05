<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class GuestRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findGuestById(int $hotelId, int $guestId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM guests 
            WHERE id = :id AND hotel_id = :hotel_id AND deleted_at IS NULL
            LIMIT 1
        ');
        $stmt->execute([
            ':id' => $guestId,
            ':hotel_id' => $hotelId
        ]);
        $guest = $stmt->fetch();
        return $guest ? $guest : null;
    }

    public function findAllGuests(int $hotelId, array $filters = []): array
    {
        $sql = 'SELECT * FROM guests WHERE hotel_id = :hotel_id AND deleted_at IS NULL';
        $params = [':hotel_id' => $hotelId];

        if (!empty($filters['search'])) {
            $sql .= ' AND (first_name LIKE :search OR last_name LIKE :search OR phone LIKE :search OR email LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createGuest(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO guests (hotel_id, first_name, last_name, email, phone, created_by)
            VALUES (:hotel_id, :first_name, :last_name, :email, :phone, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateGuest(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE guests 
            SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, updated_by = :updated_by
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':updated_by' => $data['updated_by']
        ]);
    }

    public function findDocuments(int $guestId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM guest_identity_documents WHERE guest_id = :guest_id
        ');
        $stmt->execute([':guest_id' => $guestId]);
        return $stmt->fetchAll();
    }

    public function createDocument(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO guest_identity_documents (guest_id, document_type, document_number_masked, document_number_encrypted, file_path, is_verified, verification_metadata, created_by)
            VALUES (:guest_id, :document_type, :document_number_masked, :document_number_encrypted, :file_path, :is_verified, :verification_metadata, :created_by)
        ');
        $stmt->execute([
            ':guest_id' => $data['guest_id'],
            ':document_type' => $data['document_type'],
            ':document_number_masked' => $data['document_number_masked'],
            ':document_number_encrypted' => $data['document_number_encrypted'],
            ':file_path' => $data['file_path'] ?? null,
            ':is_verified' => $data['is_verified'] ?? 0,
            ':verification_metadata' => isset($data['verification_metadata']) ? json_encode($data['verification_metadata']) : null,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}
