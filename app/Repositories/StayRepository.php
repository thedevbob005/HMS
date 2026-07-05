<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class StayRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findStayById(int $hotelId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT s.*, g.id as guest_id, g.first_name as booker_first_name, g.last_name as booker_last_name, g.phone as booker_phone
            FROM stays s
            LEFT JOIN reservations r ON s.reservation_id = r.id
            LEFT JOIN guests g ON r.guest_id = g.id OR (r.id IS NULL AND g.id = (
                SELECT guest_id FROM stay_guests sg WHERE sg.stay_id = s.id LIMIT 1
            ))
            WHERE s.id = :id AND s.hotel_id = :hotel_id AND s.deleted_at IS NULL
            LIMIT 1
        ');
        $stmt->execute([
            ':id' => $id,
            ':hotel_id' => $hotelId
        ]);
        $stay = $stmt->fetch();
        if (!$stay) return null;

        // Load rooms
        $stay['rooms'] = $this->findStayRooms((int)$stay['id']);
        
        // Load guests
        $stay['guests'] = $this->findStayGuests((int)$stay['id']);

        // Load shift logs
        $stay['shifts'] = $this->findStayShiftLogs((int)$stay['id']);

        // Load folio items
        $stay['folio'] = $this->findFolioItems((int)$stay['id']);

        // Load payments
        $stay['payments'] = $this->findPaymentsByStay((int)$stay['id']);

        return $stay;
    }

    public function findAllStays(int $hotelId, array $filters = []): array
    {
        $sql = '
            SELECT s.*, r.checkin_date as res_checkin, r.checkout_date as res_checkout
            FROM stays s
            LEFT JOIN reservations r ON s.reservation_id = r.id
            WHERE s.hotel_id = :hotel_id AND s.deleted_at IS NULL
        ';
        $params = [':hotel_id' => $hotelId];

        if (!empty($filters['status'])) {
            $sql .= ' AND s.status = :status';
            $params[':status'] = $filters['status'];
        }

        $sql .= ' ORDER BY s.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $stays = $stmt->fetchAll();

        foreach ($stays as &$stay) {
            $stay['rooms'] = $this->findStayRooms((int)$stay['id']);
            $stay['guests'] = $this->findStayGuests((int)$stay['id']);
        }

        return $stays;
    }

    public function createStay(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO stays (hotel_id, reservation_id, status, checkin_at, expected_checkout_at, notes, created_by)
            VALUES (:hotel_id, :reservation_id, :status, :checkin_at, :expected_checkout_at, :notes, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':reservation_id' => $data['reservation_id'] ?? null,
            ':status' => $data['status'] ?? 'Active',
            ':checkin_at' => $data['checkin_at'],
            ':expected_checkout_at' => $data['expected_checkout_at'],
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStayStatus(int $id, string $status, ?string $checkoutAt, int $userId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE stays 
            SET status = :status, checkout_at = :checkout_at, updated_by = :updated_by
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':checkout_at' => $checkoutAt,
            ':updated_by' => $userId
        ]);
    }

    public function addRoomToStay(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO stay_rooms (stay_id, room_id, room_type_id, price_per_night, extra_bed_price, has_extra_bed, checked_in_at)
            VALUES (:stay_id, :room_id, :room_type_id, :price_per_night, :extra_bed_price, :has_extra_bed, :checked_in_at)
        ');
        $stmt->execute([
            ':stay_id' => $data['stay_id'],
            ':room_id' => $data['room_id'],
            ':room_type_id' => $data['room_type_id'],
            ':price_per_night' => $data['price_per_night'],
            ':extra_bed_price' => $data['extra_bed_price'],
            ':has_extra_bed' => $data['has_extra_bed'] ? 1 : 0,
            ':checked_in_at' => $data['checked_in_at']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStayRoomCheckout(int $stayRoomId, string $checkoutAt): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE stay_rooms 
            SET checked_out_at = :checked_out_at 
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $stayRoomId,
            ':checked_out_at' => $checkoutAt
        ]);
    }

    public function addGuestToStayRoom(int $stayId, int $guestId, int $roomId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO stay_guests (stay_id, guest_id, room_id)
            VALUES (:stay_id, :guest_id, :roomId)
        ');
        $stmt->execute([
            ':stay_id' => $stayId,
            ':guest_id' => $guestId,
            ':roomId' => $roomId
        ]);
    }

    public function logRoomShift(array $data): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO room_shift_logs (stay_id, old_room_id, new_room_id, reason, shifted_by)
            VALUES (:stay_id, :old_room_id, :new_room_id, :reason, :shifted_by)
        ');
        $stmt->execute([
            ':stay_id' => $data['stay_id'],
            ':old_room_id' => $data['old_room_id'],
            ':new_room_id' => $data['new_room_id'],
            ':reason' => $data['reason'],
            ':shifted_by' => $data['shifted_by']
        ]);
    }

    public function findStayRooms(int $stayId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT sr.*, r.room_number, rt.name as room_type_name
            FROM stay_rooms sr
            JOIN rooms r ON sr.room_id = r.id
            JOIN room_types rt ON sr.room_type_id = rt.id
            WHERE sr.stay_id = :stay_id
            ORDER BY sr.id ASC
        ');
        $stmt->execute([':stay_id' => $stayId]);
        return $stmt->fetchAll();
    }

    public function findStayGuests(int $stayId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT sg.*, g.first_name, g.last_name, g.phone, r.room_number
            FROM stay_guests sg
            JOIN guests g ON sg.guest_id = g.id
            JOIN rooms r ON sg.room_id = r.id
            WHERE sg.stay_id = :stay_id
        ');
        $stmt->execute([':stay_id' => $stayId]);
        return $stmt->fetchAll();
    }

    public function findStayShiftLogs(int $stayId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT sl.*, u.username as shifted_by_username, r1.room_number as old_room_number, r2.room_number as new_room_number
            FROM room_shift_logs sl
            JOIN users u ON sl.shifted_by = u.id
            JOIN rooms r1 ON sl.old_room_id = r1.id
            JOIN rooms r2 ON sl.new_room_id = r2.id
            WHERE sl.stay_id = :stay_id
            ORDER BY sl.id ASC
        ');
        $stmt->execute([':stay_id' => $stayId]);
        return $stmt->fetchAll();
    }

    public function createFolioItem(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO folio_items (hotel_id, stay_id, item_type, description, amount, tax_amount, reference_type, reference_id, created_by)
            VALUES (:hotel_id, :stay_id, :item_type, :description, :amount, :tax_amount, :reference_type, :reference_id, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':stay_id' => $data['stay_id'],
            ':item_type' => $data['item_type'],
            ':description' => $data['description'],
            ':amount' => $data['amount'],
            ':tax_amount' => $data['tax_amount'] ?? 0.00,
            ':reference_type' => $data['reference_type'] ?? null,
            ':reference_id' => $data['reference_id'] ?? null,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findFolioItems(int $stayId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM folio_items WHERE stay_id = :stay_id ORDER BY id ASC
        ');
        $stmt->execute([':stay_id' => $stayId]);
        return $stmt->fetchAll();
    }

    public function createPayment(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO payments (hotel_id, stay_id, reservation_id, payment_method, amount, transaction_reference, created_by)
            VALUES (:hotel_id, :stay_id, :reservation_id, :payment_method, :amount, :transaction_reference, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':stay_id' => $data['stay_id'] ?? null,
            ':reservation_id' => $data['reservation_id'] ?? null,
            ':payment_method' => $data['payment_method'],
            ':amount' => $data['amount'],
            ':transaction_reference' => $data['transaction_reference'] ?? null,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findPaymentsByStay(int $stayId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM payments WHERE stay_id = :stay_id ORDER BY id ASC
        ');
        $stmt->execute([':stay_id' => $stayId]);
        return $stmt->fetchAll();
    }
}
