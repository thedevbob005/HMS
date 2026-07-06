<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class ReportRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getDailyCollections(int $hotelId, string $startDate, string $endDate): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                DATE(created_at) as collection_date,
                payment_method,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            FROM payments
            WHERE hotel_id = :hotelId
              AND DATE(created_at) >= :startDate
              AND DATE(created_at) <= :endDate
            GROUP BY DATE(created_at), payment_method
            ORDER BY collection_date DESC, payment_method ASC
        ');
        $stmt->execute([
            ':hotelId' => $hotelId,
            ':startDate' => $startDate,
            ':endDate' => $endDate
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveStayRoomsForRange(int $hotelId, string $startDate, string $endDate): array
    {
        $stmt = $this->pdo->prepare('
            SELECT sr.room_id, DATE(s.checkin_at) as checkin_date, DATE(s.checkout_at) as checkout_date, DATE(s.expected_checkout_at) as expected_checkout_date
            FROM stay_rooms sr
            JOIN stays s ON sr.stay_id = s.id
            WHERE s.hotel_id = :hotelId
              AND s.status IN (\'Active\', \'CheckedOut\')
              AND DATE(s.checkin_at) <= :endDate
              AND (s.checkout_at IS NULL OR DATE(s.checkout_at) >= :startDate)
        ');
        $stmt->execute([
            ':hotelId' => $hotelId,
            ':startDate' => $startDate,
            ':endDate' => $endDate
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHotelRoomsCount(int $hotelId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM rooms WHERE hotel_id = :hotelId AND deleted_at IS NULL');
        $stmt->execute([':hotelId' => $hotelId]);
        return (int)$stmt->fetchColumn();
    }

    public function getGSTBreakdown(int $hotelId, string $startDate, string $endDate): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                fi.item_type,
                SUM(fi.amount) as taxable_amount,
                SUM(fi.tax_amount) as tax_amount
            FROM folio_items fi
            JOIN stays s ON fi.stay_id = s.id
            WHERE s.hotel_id = :hotelId
              AND DATE(fi.created_at) >= :startDate
              AND DATE(fi.created_at) <= :endDate
            GROUP BY fi.item_type
        ');
        $stmt->execute([
            ':hotelId' => $hotelId,
            ':startDate' => $startDate,
            ':endDate' => $endDate
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRevenueByCenter(int $hotelId, string $startDate, string $endDate): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                DATE(fi.created_at) as revenue_date,
                fi.item_type,
                SUM(fi.amount) as total_amount
            FROM folio_items fi
            JOIN stays s ON fi.stay_id = s.id
            WHERE s.hotel_id = :hotelId
              AND DATE(fi.created_at) >= :startDate
              AND DATE(fi.created_at) <= :endDate
            GROUP BY DATE(fi.created_at), fi.item_type
            ORDER BY revenue_date DESC, fi.item_type ASC
        ');
        $stmt->execute([
            ':hotelId' => $hotelId,
            ':startDate' => $startDate,
            ':endDate' => $endDate
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
