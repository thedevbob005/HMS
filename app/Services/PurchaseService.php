<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PurchaseRepository;
use App\Repositories\InventoryRepository;
use App\Services\AuditLogService;
use PDO;
use Exception;

class PurchaseService
{
    private PurchaseRepository $purchaseRepository;
    private InventoryRepository $inventoryRepository;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(
        PurchaseRepository $purchaseRepository,
        InventoryRepository $inventoryRepository,
        AuditLogService $auditLogService,
        PDO $pdo
    ) {
        $this->purchaseRepository = $purchaseRepository;
        $this->inventoryRepository = $inventoryRepository;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    // --- PURCHASE ORDERS ---
    public function createPurchaseOrder(int $hotelId, array $data, int $userId): array
    {
        if (empty($data['vendor_id']) || empty($data['items']) || !is_array($data['items'])) {
            throw new Exception('Vendor and items list are required.');
        }

        $this->pdo->beginTransaction();
        try {
            $totalAmount = 0.00;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                if (empty($item['inventory_item_id']) || empty($item['quantity']) || empty($item['unit_price'])) {
                    throw new Exception('Each item must have inventory_item_id, quantity, and unit_price.');
                }
                
                $qty = (float)$item['quantity'];
                $price = (float)$item['unit_price'];
                if ($qty <= 0 || $price <= 0) {
                    throw new Exception('Quantity and unit price must be positive.');
                }

                $totalPrice = round($qty * $price, 2);
                $totalAmount += $totalPrice;

                $itemsData[] = [
                    'inventory_item_id' => (int)$item['inventory_item_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_price' => $totalPrice
                ];
            }

            $poNumber = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);

            $poHeader = [
                'hotel_id' => $hotelId,
                'vendor_id' => (int)$data['vendor_id'],
                'po_number' => $poNumber,
                'status' => 'Pending Approval', // Default directly to Pending Approval for manager action
                'total_amount' => $totalAmount,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId
            ];

            $poId = $this->purchaseRepository->createPurchaseOrder($poHeader);

            foreach ($itemsData as $item) {
                $item['purchase_order_id'] = $poId;
                $this->purchaseRepository->createPurchaseOrderItem($item);
            }

            $this->auditLogService->log(
                'purchase_order',
                $poId,
                $hotelId,
                'create_po',
                null,
                array_merge(['id' => $poId], $poHeader),
                $userId
            );

            $this->pdo->commit();
            return $this->purchaseRepository->findPurchaseOrderById($hotelId, $poId);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listPurchaseOrders(int $hotelId): array
    {
        return $this->purchaseRepository->findAllPurchaseOrders($hotelId);
    }

    public function getPurchaseOrder(int $hotelId, int $poId): ?array
    {
        return $this->purchaseRepository->findPurchaseOrderById($hotelId, $poId);
    }

    public function approvePurchaseOrder(int $hotelId, int $poId, string $status, int $userId): array
    {
        $po = $this->purchaseRepository->findPurchaseOrderById($hotelId, $poId);
        if (!$po) {
            throw new Exception('Purchase order not found or unauthorized.');
        }

        if ($po['status'] !== 'Pending Approval') {
            throw new Exception(sprintf('Purchase order cannot be approved from status "%s".', $po['status']));
        }

        if (!in_array($status, ['Approved', 'Rejected'])) {
            throw new Exception('Invalid approval status target.');
        }

        $nowStr = date('Y-m-d H:i:s');
        $this->purchaseRepository->updatePurchaseOrderStatus($poId, $status, $userId, $nowStr);

        $this->auditLogService->log(
            'purchase_order',
            $poId,
            $hotelId,
            'approve_po',
            ['status' => 'Pending Approval'],
            ['status' => $status, 'approved_by' => $userId, 'approved_at' => $nowStr],
            $userId
        );

        return $this->purchaseRepository->findPurchaseOrderById($hotelId, $poId);
    }

    // --- GOODS RECEIPTS ---
    public function logGoodsReceipt(int $hotelId, int $poId, array $data, int $userId): array
    {
        $po = $this->purchaseRepository->findPurchaseOrderById($hotelId, $poId);
        if (!$po) {
            throw new Exception('Purchase order not found or unauthorized.');
        }

        if ($po['status'] !== 'Approved') {
            throw new Exception('Goods Receipt can only be logged for Approved purchase orders.');
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            throw new Exception('Received items list is required.');
        }

        $this->pdo->beginTransaction();
        try {
            $grnNumber = 'GRN-' . date('Ymd') . '-' . rand(1000, 9999);
            $receivedDate = $data['received_date'] ?? date('Y-m-d');

            $grnHeader = [
                'hotel_id' => $hotelId,
                'purchase_order_id' => $poId,
                'grn_number' => $grnNumber,
                'received_date' => $receivedDate,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId
            ];

            $grnId = $this->purchaseRepository->createGoodsReceipt($grnHeader);

            foreach ($data['items'] as $item) {
                if (empty($item['inventory_item_id']) || empty($item['quantity']) || empty($item['unit_cost'])) {
                    throw new Exception('Each received item must specify inventory_item_id, quantity, and unit_cost.');
                }

                $itemId = (int)$item['inventory_item_id'];
                $qty = (float)$item['quantity'];
                $cost = (float)$item['unit_cost'];
                $batchNumber = isset($item['batch_number']) && trim($item['batch_number']) !== '' ? trim($item['batch_number']) : 'BATCH-' . date('Ymd') . '-' . rand(100, 999);
                $expiryDate = !empty($item['expiry_date']) ? $item['expiry_date'] : null;

                if ($qty <= 0 || $cost <= 0) {
                    throw new Exception('Received quantity and unit cost must be positive.');
                }

                // 1. Create Goods Receipt Item
                $this->purchaseRepository->createGoodsReceiptItem([
                    'goods_receipt_id' => $grnId,
                    'inventory_item_id' => $itemId,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate
                ]);

                // 2. Upsert Stock Batch
                $this->purchaseRepository->upsertStockBatch([
                    'hotel_id' => $hotelId,
                    'inventory_item_id' => $itemId,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                    'quantity' => $qty,
                    'unit_cost' => $cost
                ]);

                // 3. Recalculate Weighted Average Cost on Item
                $invItem = $this->inventoryRepository->findInventoryItemById($hotelId, $itemId);
                if (!$invItem) {
                    throw new Exception('Inventory item linked to GRN not found.');
                }

                $qOld = (float)$invItem['current_stock'];
                $cOld = (float)$invItem['average_unit_cost'];

                $qNewTotal = $qOld + $qty;
                
                $cNewAvg = $cOld;
                if ($qNewTotal > 0) {
                    $cNewAvg = (($qOld * $cOld) + ($qty * $cost)) / $qNewTotal;
                }

                // Update Item Stock metrics
                $this->inventoryRepository->updateInventoryItemMetrics($itemId, $qNewTotal, $cNewAvg, $userId);

                // 4. Log Ledger Transaction
                $this->inventoryRepository->logStockTransaction([
                    'hotel_id' => $hotelId,
                    'inventory_item_id' => $itemId,
                    'transaction_type' => 'goods_receipt',
                    'transaction_id' => $grnId,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'resulting_stock' => $qNewTotal,
                    'resulting_avg_cost' => $cNewAvg,
                    'notes' => sprintf('Received via GRN %s on PO #%s (Batch: %s)', $grnNumber, $po['po_number'], $batchNumber),
                    'created_by' => $userId
                ]);
            }

            // 5. Update PO status to Received
            $this->purchaseRepository->updatePurchaseOrderStatus($poId, 'Received');

            $this->auditLogService->log(
                'goods_receipt',
                $grnId,
                $hotelId,
                'log_grn',
                ['po_status' => 'Approved'],
                ['po_status' => 'Received', 'grn_number' => $grnNumber],
                $userId
            );

            $this->pdo->commit();
            return $this->purchaseRepository->findGoodsReceiptById($hotelId, $grnId);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listGoodsReceipts(int $hotelId): array
    {
        return $this->purchaseRepository->findAllGoodsReceipts($hotelId);
    }

    public function getGoodsReceipt(int $hotelId, int $grnId): ?array
    {
        return $this->purchaseRepository->findGoodsReceiptById($hotelId, $grnId);
    }
}
