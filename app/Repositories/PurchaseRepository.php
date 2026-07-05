<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class PurchaseRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // --- PURCHASE ORDERS ---
    public function createPurchaseOrder(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO purchase_orders (hotel_id, vendor_id, po_number, status, total_amount, notes, created_by)
            VALUES (:hotel_id, :vendor_id, :po_number, :status, :total_amount, :notes, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':vendor_id' => $data['vendor_id'],
            ':po_number' => $data['po_number'],
            ':status' => $data['status'] ?? 'Draft',
            ':total_amount' => $data['total_amount'] ?? 0.00,
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function createPurchaseOrderItem(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO purchase_order_items (purchase_order_id, inventory_item_id, quantity, unit_price, total_price)
            VALUES (:purchase_order_id, :inventory_item_id, :quantity, :unit_price, :total_price)
        ');
        $stmt->execute([
            ':purchase_order_id' => $data['purchase_order_id'],
            ':inventory_item_id' => $data['inventory_item_id'],
            ':quantity' => $data['quantity'],
            ':unit_price' => $data['unit_price'],
            ':total_price' => $data['total_price']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updatePurchaseOrderStatus(int $poId, string $status, ?int $approvedBy = null, ?string $approvedAt = null): void
    {
        $sql = 'UPDATE purchase_orders SET status = :status';
        $params = [':status' => $status, ':id' => $poId];

        if ($approvedBy !== null) {
            $sql .= ', approved_by = :approved_by, approved_at = :approved_at';
            $params[':approved_by'] = $approvedBy;
            $params[':approved_at'] = $approvedAt;
        }

        $sql .= ' WHERE id = :id';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function findPurchaseOrderById(int $hotelId, int $poId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT po.*, v.name as vendor_name, u.username as creator_name, app.username as approver_name
            FROM purchase_orders po
            JOIN vendors v ON po.vendor_id = v.id
            LEFT JOIN users u ON po.created_by = u.id
            LEFT JOIN users app ON po.approved_by = app.id
            WHERE po.id = :id AND po.hotel_id = :hotel_id
            LIMIT 1
        ');
        $stmt->execute([':id' => $poId, ':hotel_id' => $hotelId]);
        $po = $stmt->fetch();
        if (!$po) return null;

        // Fetch items
        $stmtItems = $this->pdo->prepare('
            SELECT poi.*, ii.name as item_name, ii.sku as item_sku, ii.unit_of_measure as item_uom
            FROM purchase_order_items poi
            JOIN inventory_items ii ON poi.inventory_item_id = ii.id
            WHERE poi.purchase_order_id = :po_id
        ');
        $stmtItems->execute([':po_id' => $poId]);
        $po['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return $po;
    }

    public function findAllPurchaseOrders(int $hotelId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT po.*, v.name as vendor_name, u.username as creator_name
            FROM purchase_orders po
            JOIN vendors v ON po.vendor_id = v.id
            LEFT JOIN users u ON po.created_by = u.id
            WHERE po.hotel_id = :hotel_id
            ORDER BY po.id DESC
        ');
        $stmt->execute([':hotel_id' => $hotelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- GOODS RECEIPTS ---
    public function createGoodsReceipt(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO goods_receipts (hotel_id, purchase_order_id, grn_number, received_date, notes, created_by)
            VALUES (:hotel_id, :purchase_order_id, :grn_number, :received_date, :notes, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':purchase_order_id' => $data['purchase_order_id'] ?? null,
            ':grn_number' => $data['grn_number'],
            ':received_date' => $data['received_date'],
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function createGoodsReceiptItem(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO goods_receipt_items (goods_receipt_id, inventory_item_id, quantity, unit_cost, batch_number, expiry_date)
            VALUES (:goods_receipt_id, :inventory_item_id, :quantity, :unit_cost, :batch_number, :expiry_date)
        ');
        $stmt->execute([
            ':goods_receipt_id' => $data['goods_receipt_id'],
            ':inventory_item_id' => $data['inventory_item_id'],
            ':quantity' => $data['quantity'],
            ':unit_cost' => $data['unit_cost'],
            ':batch_number' => $data['batch_number'] ?? null,
            ':expiry_date' => $data['expiry_date'] ?? null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findGoodsReceiptById(int $hotelId, int $grnId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT gr.*, po.po_number, u.username as creator_name
            FROM goods_receipts gr
            LEFT JOIN purchase_orders po ON gr.purchase_order_id = po.id
            LEFT JOIN users u ON gr.created_by = u.id
            WHERE gr.id = :id AND gr.hotel_id = :hotel_id
            LIMIT 1
        ');
        $stmt->execute([':id' => $grnId, ':hotel_id' => $hotelId]);
        $grn = $stmt->fetch();
        if (!$grn) return null;

        // Fetch items
        $stmtItems = $this->pdo->prepare('
            SELECT gri.*, ii.name as item_name, ii.sku as item_sku, ii.unit_of_measure as item_uom
            FROM goods_receipt_items gri
            JOIN inventory_items ii ON gri.inventory_item_id = ii.id
            WHERE gri.goods_receipt_id = :grn_id
        ');
        $stmtItems->execute([':grn_id' => $grnId]);
        $grn['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return $grn;
    }

    public function findAllGoodsReceipts(int $hotelId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT gr.*, po.po_number, u.username as creator_name
            FROM goods_receipts gr
            LEFT JOIN purchase_orders po ON gr.purchase_order_id = po.id
            LEFT JOIN users u ON gr.created_by = u.id
            WHERE gr.hotel_id = :hotel_id
            ORDER BY gr.id DESC
        ');
        $stmt->execute([':hotel_id' => $hotelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- STOCK BATCHES ---
    public function upsertStockBatch(array $data): void
    {
        // First see if a batch with same hotel_id, inventory_item_id and batch_number exists
        $stmtFind = $this->pdo->prepare('
            SELECT id, quantity_remaining 
            FROM stock_batches 
            WHERE hotel_id = :hotel_id AND inventory_item_id = :inventory_item_id AND batch_number = :batch_number
            LIMIT 1
        ');
        $stmtFind->execute([
            ':hotel_id' => $data['hotel_id'],
            ':inventory_item_id' => $data['inventory_item_id'],
            ':batch_number' => $data['batch_number']
        ]);
        $existing = $stmtFind->fetch();

        if ($existing) {
            $newRemaining = (float)$existing['quantity_remaining'] + (float)$data['quantity'];
            $stmtUpdate = $this->pdo->prepare('
                UPDATE stock_batches 
                SET quantity_remaining = :qty, unit_cost = :cost 
                WHERE id = :id
            ');
            $stmtUpdate->execute([
                ':qty' => $newRemaining,
                ':cost' => $data['unit_cost'],
                ':id' => $existing['id']
            ]);
        } else {
            $stmtInsert = $this->pdo->prepare('
                INSERT INTO stock_batches (hotel_id, inventory_item_id, batch_number, expiry_date, quantity_received, quantity_remaining, unit_cost)
                VALUES (:hotel_id, :inventory_item_id, :batch_number, :expiry_date, :quantity_received, :quantity_remaining, :unit_cost)
            ');
            $stmtInsert->execute([
                ':hotel_id' => $data['hotel_id'],
                ':inventory_item_id' => $data['inventory_item_id'],
                ':batch_number' => $data['batch_number'],
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':quantity_received' => $data['quantity'],
                ':quantity_remaining' => $data['quantity'],
                ':unit_cost' => $data['unit_cost']
            ]);
        }
    }
}
