<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class InventoryRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // --- VENDORS ---
    public function createVendor(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO vendors (hotel_id, name, contact_name, phone, email, address, gst_number, created_by)
            VALUES (:hotel_id, :name, :contact_name, :phone, :email, :address, :gst_number, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':name' => $data['name'],
            ':contact_name' => $data['contact_name'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':email' => $data['email'] ?? null,
            ':address' => $data['address'] ?? null,
            ':gst_number' => $data['gst_number'] ?? null,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findVendorById(int $hotelId, int $vendorId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vendors WHERE id = :id AND hotel_id = :hotel_id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':id' => $vendorId, ':hotel_id' => $hotelId]);
        $vendor = $stmt->fetch();
        return $vendor ? $vendor : null;
    }

    public function findAllVendors(int $hotelId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vendors WHERE hotel_id = :hotel_id AND deleted_at IS NULL ORDER BY name ASC');
        $stmt->execute([':hotel_id' => $hotelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- INVENTORY ITEMS ---
    public function createInventoryItem(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO inventory_items (hotel_id, sku, name, category, unit_of_measure, min_stock_level, current_stock, average_unit_cost, created_by)
            VALUES (:hotel_id, :sku, :name, :category, :unit_of_measure, :min_stock_level, :current_stock, :average_unit_cost, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':sku' => $data['sku'],
            ':name' => $data['name'],
            ':category' => $data['category'] ?? null,
            ':unit_of_measure' => $data['unit_of_measure'],
            ':min_stock_level' => $data['min_stock_level'] ?? 0.00,
            ':current_stock' => $data['current_stock'] ?? 0.00,
            ':average_unit_cost' => $data['average_unit_cost'] ?? 0.00,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findInventoryItemById(int $hotelId, int $itemId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM inventory_items WHERE id = :id AND hotel_id = :hotel_id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':id' => $itemId, ':hotel_id' => $hotelId]);
        $item = $stmt->fetch();
        return $item ? $item : null;
    }

    public function findInventoryItemBySku(int $hotelId, string $sku): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM inventory_items WHERE sku = :sku AND hotel_id = :hotel_id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':sku' => $sku, ':hotel_id' => $hotelId]);
        $item = $stmt->fetch();
        return $item ? $item : null;
    }

    public function findAllInventoryItems(int $hotelId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM inventory_items WHERE hotel_id = :hotel_id AND deleted_at IS NULL ORDER BY name ASC');
        $stmt->execute([':hotel_id' => $hotelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateInventoryItemMetrics(int $itemId, float $stock, float $avgCost, int $userId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE inventory_items 
            SET current_stock = :current_stock, average_unit_cost = :average_unit_cost, updated_by = :userId
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $itemId,
            ':current_stock' => $stock,
            ':average_unit_cost' => $avgCost,
            ':userId' => $userId
        ]);
    }

    // --- LEDGER ---
    public function logStockTransaction(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO stock_ledger 
                (hotel_id, inventory_item_id, transaction_type, transaction_id, quantity, unit_cost, resulting_stock, resulting_avg_cost, notes, created_by)
            VALUES 
                (:hotel_id, :inventory_item_id, :transaction_type, :transaction_id, :quantity, :unit_cost, :resulting_stock, :resulting_avg_cost, :notes, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':inventory_item_id' => $data['inventory_item_id'],
            ':transaction_type' => $data['transaction_type'],
            ':transaction_id' => $data['transaction_id'] ?? null,
            ':quantity' => $data['quantity'],
            ':unit_cost' => $data['unit_cost'],
            ':resulting_stock' => $data['resulting_stock'],
            ':resulting_avg_cost' => $data['resulting_avg_cost'],
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findLedgerByItem(int $itemId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT sl.*, u.username as creator_name
            FROM stock_ledger sl
            LEFT JOIN users u ON sl.created_by = u.id
            WHERE sl.inventory_item_id = :itemId
            ORDER BY sl.id DESC
        ');
        $stmt->execute([':itemId' => $itemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
