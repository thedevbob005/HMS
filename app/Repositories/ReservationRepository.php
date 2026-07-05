<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class ReservationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findReservationById(int $hotelId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT r.*, g.first_name, g.last_name, g.phone, g.email
            FROM reservations r
            JOIN guests g ON r.guest_id = g.id
            WHERE r.id = :id AND r.hotel_id = :hotel_id AND r.deleted_at IS NULL
            LIMIT 1
        ');
        $stmt->execute([
            ':id' => $id,
            ':hotel_id' => $hotelId
        ]);
        $res = $stmt->fetch();
        if (!$res) return null;

        // Fetch associated rooms
        $res['rooms'] = $this->findReservationRooms((int)$res['id']);
        return $res;
    }

    public function findAllReservations(int $hotelId, array $filters = []): array
    {
        $sql = '
            SELECT r.*, g.first_name, g.last_name, g.phone 
            FROM reservations r
            JOIN guests g ON r.guest_id = g.id
            WHERE r.hotel_id = :hotel_id AND r.deleted_at IS NULL
        ';
        $params = [':hotel_id' => $hotelId];

        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= ' AND r.checkin_date >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= ' AND r.checkout_date <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }

        $sql .= ' ORDER BY r.checkin_date ASC, r.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $reservations = $stmt->fetchAll();

        foreach ($reservations as &$res) {
            $res['rooms'] = $this->findReservationRooms((int)$res['id']);
        }

        return $reservations;
    }

    public function createReservation(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO reservations (hotel_id, guest_id, booking_source, booking_source_details, status, checkin_date, checkout_date, notes, created_by)
            VALUES (:hotel_id, :guest_id, :booking_source, :booking_source_details, :status, :checkin_date, :checkout_date, :notes, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':guest_id' => $data['guest_id'],
            ':booking_source' => $data['booking_source'],
            ':booking_source_details' => $data['booking_source_details'] ?? null,
            ':status' => $data['status'] ?? 'Draft',
            ':checkin_date' => $data['checkin_date'],
            ':checkout_date' => $data['checkout_date'],
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateReservationStatus(int $id, string $status, int $userId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE reservations 
            SET status = :status, updated_by = :updated_by
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':updated_by' => $userId
        ]);
    }

    public function addRoomToReservation(int $resId, int $roomId, int $roomTypeId, float $price, float $extraBedPrice, bool $hasExtraBed): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO reservation_rooms (reservation_id, room_id, room_type_id, price_per_night, extra_bed_price, has_extra_bed)
            VALUES (:resId, :roomId, :roomTypeId, :price, :extraBedPrice, :hasExtraBed)
        ');
        $stmt->execute([
            ':resId' => $resId,
            ':roomId' => $roomId,
            ':roomTypeId' => $roomTypeId,
            ':price' => $price,
            ':extraBedPrice' => $extraBedPrice,
            ':hasExtraBed' => $hasExtraBed ? 1 : 0
        ]);
    }

    public function addGuestToReservationRoom(int $resId, int $guestId, int $roomId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO reservation_guests (reservation_id, guest_id, room_id)
            VALUES (:resId, :guestId, :roomId)
        ');
        $stmt->execute([
            ':resId' => $resId,
            ':guestId' => $guestId,
            ':roomId' => $roomId
        ]);
    }

    public function findReservationRooms(int $resId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT rr.*, r.room_number, rt.name as room_type_name
            FROM reservation_rooms rr
            JOIN rooms r ON rr.room_id = r.id
            JOIN room_types rt ON rr.room_type_id = rt.id
            WHERE rr.reservation_id = :resId
        ');
        $stmt->execute([':resId' => $resId]);
        return $stmt->fetchAll();
    }

    public function isRoomAvailable(int $roomId, string $checkinDate, string $checkoutDate, ?int $excludeReservationId = null): bool
    {
        // 1. Check overlapping confirmed reservations
        $sqlRes = '
            SELECT COUNT(*) 
            FROM reservation_rooms rr
            JOIN reservations r ON rr.reservation_id = r.id
            WHERE rr.room_id = :room_id 
              AND r.status IN (\'Confirmed\', \'Draft\')
              AND r.checkin_date < :checkout_date 
              AND r.checkout_date > :checkin_date
              AND r.deleted_at IS NULL
        ';
        $paramsRes = [
            ':room_id' => $roomId,
            ':checkin_date' => $checkinDate,
            ':checkout_date' => $checkoutDate
        ];

        if ($excludeReservationId !== null) {
            $sqlRes .= ' AND r.id != :exclude_id';
            $paramsRes[':exclude_id'] = $excludeReservationId;
        }

        $stmtRes = $this->pdo->prepare($sqlRes);
        $stmtRes->execute($paramsRes);
        if ((int)$stmtRes->fetchColumn() > 0) {
            return false;
        }

        // 2. Check overlapping active stays
        $stmtStay = $this->pdo->prepare('
            SELECT COUNT(*) 
            FROM stay_rooms sr
            JOIN stays s ON sr.stay_id = s.id
            WHERE sr.room_id = :room_id 
              AND s.status = \'Active\'
              AND DATE(sr.checked_in_at) < :checkout_date 
              AND (sr.checked_out_at IS NULL OR DATE(sr.checked_out_at) > :checkin_date)
              AND s.deleted_at IS NULL
        ');
        $stmtStay->execute([
            ':room_id' => $roomId,
            ':checkin_date' => $checkinDate,
            ':checkout_date' => $checkoutDate
        ]);
        if ((int)$stmtStay->fetchColumn() > 0) {
            return false;
        }

        return true;
    }
}
