<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class InvoiceRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createInvoice(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO invoices (
                hotel_id, stay_id, invoice_number, guest_id, subtotal, cgst, sgst, igst, discount, total_amount, status, created_by
            ) VALUES (
                :hotel_id, :stay_id, :invoice_number, :guest_id, :subtotal, :cgst, :sgst, :igst, :discount, :total_amount, :status, :created_by
            )
        ');

        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':stay_id' => $data['stay_id'] ?? null,
            ':invoice_number' => $data['invoice_number'],
            ':guest_id' => $data['guest_id'],
            ':subtotal' => $data['subtotal'],
            ':cgst' => $data['cgst'] ?? 0.00,
            ':sgst' => $data['sgst'] ?? 0.00,
            ':igst' => $data['igst'] ?? 0.00,
            ':discount' => $data['discount'] ?? 0.00,
            ':total_amount' => $data['total_amount'],
            ':status' => $data['status'] ?? 'Paid',
            ':created_by' => $data['created_by']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findInvoiceById(int $hotelId, int $invoiceId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT i.*, g.first_name, g.last_name, g.email, g.phone, u.username as creator_name
            FROM invoices i
            JOIN guests g ON i.guest_id = g.id
            JOIN users u ON i.created_by = u.id
            WHERE i.hotel_id = :hotelId AND i.id = :id
        ');
        $stmt->execute([':hotelId' => $hotelId, ':id' => $invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $invoice ?: null;
    }

    public function findInvoiceByStayId(int $hotelId, int $stayId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT i.*, g.first_name, g.last_name, g.email, g.phone
            FROM invoices i
            JOIN guests g ON i.guest_id = g.id
            WHERE i.hotel_id = :hotelId AND i.stay_id = :stayId
        ');
        $stmt->execute([':hotelId' => $hotelId, ':stayId' => $stayId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $invoice ?: null;
    }

    public function findAllInvoices(int $hotelId, array $filters = []): array
    {
        $sql = '
            SELECT i.*, g.first_name, g.last_name, g.phone
            FROM invoices i
            JOIN guests g ON i.guest_id = g.id
            WHERE i.hotel_id = :hotelId
        ';
        $params = [':hotelId' => $hotelId];

        if (!empty($filters['search'])) {
            $sql .= ' AND (i.invoice_number LIKE :search OR g.first_name LIKE :search OR g.last_name LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY i.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNextInvoiceNumber(int $hotelId): string
    {
        $year = date('Y');
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) 
            FROM invoices 
            WHERE hotel_id = :hotelId AND YEAR(created_at) = :year
        ');
        $stmt->execute([':hotelId' => $hotelId, ':year' => $year]);
        $count = (int)$stmt->fetchColumn();

        $nextNum = $count + 1;
        return sprintf('INV-%s-%04d', $year, $nextNum);
    }
}
