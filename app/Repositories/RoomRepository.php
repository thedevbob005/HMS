<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class RoomRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Room Types CRUD
    public function findRoomTypeById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM room_types WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $id]);
        $type = $stmt->fetch();
        return $type ? $type : null;
    }

    public function findAllRoomTypes(int $hotelId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM room_types WHERE hotel_id = :hotel_id AND deleted_at IS NULL');
        $stmt->execute([':hotel_id' => $hotelId]);
        return $stmt->fetchAll();
    }

    public function createRoomType(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO room_types (hotel_id, name, description, base_price, extra_bed_price, max_occupancy)
            VALUES (:hotel_id, :name, :description, :base_price, :extra_bed_price, :max_occupancy)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':base_price' => $data['base_price'],
            ':extra_bed_price' => $data['extra_bed_price'] ?? 0.00,
            ':max_occupancy' => $data['max_occupancy'] ?? 2,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateRoomType(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE room_types 
            SET name = :name, description = :description, base_price = :base_price, 
                extra_bed_price = :extra_bed_price, max_occupancy = :max_occupancy, updated_at = NOW()
            WHERE id = :id
        ');
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':base_price' => $data['base_price'],
            ':extra_bed_price' => $data['extra_bed_price'] ?? 0.00,
            ':max_occupancy' => $data['max_occupancy'] ?? 2,
        ]);
    }

    // Rooms CRUD
    public function findRoomById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rooms WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $id]);
        $room = $stmt->fetch();
        return $room ? $room : null;
    }

    public function findRoomByNumber(int $hotelId, string $roomNumber): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rooms WHERE hotel_id = :hotel_id AND room_number = :room_number AND deleted_at IS NULL');
        $stmt->execute([
            ':hotel_id' => $hotelId,
            ':room_number' => $roomNumber
        ]);
        $room = $stmt->fetch();
        return $room ? $room : null;
    }

    public function findAllRooms(int $hotelId, array $filters = []): array
    {
        $sql = '
            SELECT r.*, rt.name as room_type_name, rt.base_price, rt.extra_bed_price 
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.hotel_id = :hotel_id AND r.deleted_at IS NULL
        ';
        $params = [':hotel_id' => $hotelId];

        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['room_type_id'])) {
            $sql .= ' AND r.room_type_id = :room_type_id';
            $params[':room_type_id'] = (int)$filters['room_type_id'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createRoom(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO rooms (hotel_id, room_type_id, room_number, floor, status)
            VALUES (:hotel_id, :room_type_id, :room_number, :floor, :status)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':room_type_id' => $data['room_type_id'],
            ':room_number' => $data['room_number'],
            ':floor' => $data['floor'] ?? 0,
            ':status' => $data['status'] ?? 'Available',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateRoom(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE rooms 
            SET room_type_id = :room_type_id, room_number = :room_number, 
                floor = :floor, updated_at = NOW()
            WHERE id = :id
        ');
        return $stmt->execute([
            ':id' => $id,
            ':room_type_id' => $data['room_type_id'],
            ':room_number' => $data['room_number'],
            ':floor' => $data['floor'],
        ]);
    }

    public function updateRoomStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE rooms SET status = :status, updated_at = NOW() WHERE id = :id
        ');
        return $stmt->execute([
            ':id' => $id,
            ':status' => $status
        ]);
    }

    public function logStatusChange(int $roomId, string $oldStatus, string $newStatus, ?string $reason, int $userId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO room_status_logs (room_id, old_status, new_status, reason, created_by)
            VALUES (:room_id, :old_status, :new_status, :reason, :created_by)
        ');
        $stmt->execute([
            ':room_id' => $roomId,
            ':old_status' => $oldStatus,
            ':new_status' => $newStatus,
            ':reason' => $reason,
            ':created_by' => $userId,
        ]);
    }

    // Rate Rules persist operations
    public function setWeekendRate(int $roomTypeId, int $dayOfWeek, float $rate): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO room_rates (room_type_id, day_of_week, rate)
            VALUES (:room_type_id, :day_of_week, :rate)
            ON DUPLICATE KEY UPDATE rate = :rate_update
        ');
        $stmt->execute([
            ':room_type_id' => $roomTypeId,
            ':day_of_week' => $dayOfWeek,
            ':rate' => $rate,
            ':rate_update' => $rate,
        ]);
    }

    public function setSeasonalRate(int $roomTypeId, string $startDate, string $endDate, float $rate, ?string $description): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO seasonal_rate_rules (room_type_id, start_date, end_date, rate, description)
            VALUES (:room_type_id, :start_date, :end_date, :rate, :description)
        ');
        $stmt->execute([
            ':room_type_id' => $roomTypeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':rate' => $rate,
            ':description' => $description,
        ]);
    }

    public function setHolidayRate(int $roomTypeId, string $date, float $rate, ?string $description): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO holiday_rate_rules (room_type_id, date, rate, description)
            VALUES (:room_type_id, :date, :rate, :description)
            ON DUPLICATE KEY UPDATE rate = :rate_update, description = :description_update, deleted_at = NULL
        ');
        $stmt->execute([
            ':room_type_id' => $roomTypeId,
            ':date' => $date,
            ':rate' => $rate,
            ':description' => $description,
            ':rate_update' => $rate,
            ':description_update' => $description
        ]);
    }

    // Rate Queries for Calculations
    public function findHolidayRate(int $roomTypeId, string $date): ?float
    {
        $stmt = $this->pdo->prepare('
            SELECT rate FROM holiday_rate_rules 
            WHERE room_type_id = :room_type_id AND date = :date AND deleted_at IS NULL 
            LIMIT 1
        ');
        $stmt->execute([':room_type_id' => $roomTypeId, ':date' => $date]);
        $rate = $stmt->fetchColumn();
        return $rate !== false ? (float)$rate : null;
    }

    public function findSeasonalRate(int $roomTypeId, string $date): ?float
    {
        $stmt = $this->pdo->prepare('
            SELECT rate FROM seasonal_rate_rules 
            WHERE room_type_id = :room_type_id AND :date BETWEEN start_date AND end_date AND deleted_at IS NULL 
            ORDER BY created_at DESC LIMIT 1
        ');
        $stmt->execute([':room_type_id' => $roomTypeId, ':date' => $date]);
        $rate = $stmt->fetchColumn();
        return $rate !== false ? (float)$rate : null;
    }

    public function findWeekendRate(int $roomTypeId, int $dayOfWeek): ?float
    {
        $stmt = $this->pdo->prepare('
            SELECT rate FROM room_rates 
            WHERE room_type_id = :room_type_id AND day_of_week = :day_of_week
            LIMIT 1
        ');
        $stmt->execute([':room_type_id' => $roomTypeId, ':day_of_week' => $dayOfWeek]);
        $rate = $stmt->fetchColumn();
        return $rate !== false ? (float)$rate : null;
    }
}
