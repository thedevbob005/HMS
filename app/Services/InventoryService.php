<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InventoryRepository;
use App\Services\AuditLogService;
use PDO;
use Exception;

class InventoryService
{
    private InventoryRepository $inventoryRepository;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(
        InventoryRepository $inventoryRepository,
        AuditLogService $auditLogService,
        PDO $pdo
    ) {
        $this->inventoryRepository = $inventoryRepository;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    // --- VENDORS ---
    public function createVendor(int $hotelId, array $data, int $userId): array
    {
        if (empty($data['name'])) {
            throw new Exception('Vendor name is required.');
        }

        $vendorData = [
            'hotel_id' => $hotelId,
            'name' => trim($data['name']),
            'contact_name' => isset($data['contact_name']) ? trim($data['contact_name']) : null,
            'phone' => isset($data['phone']) ? trim($data['phone']) : null,
            'email' => isset($data['email']) ? trim($data['email']) : null,
            'address' => isset($data['address']) ? trim($data['address']) : null,
            'gst_number' => isset($data['gst_number']) ? trim($data['gst_number']) : null,
            'created_by' => $userId
        ];

        $vendorId = $this->inventoryRepository->createVendor($vendorData);
        $vendorData['id'] = $vendorId;

        $this->auditLogService->log(
            'vendor',
            $vendorId,
            $hotelId,
            'create_vendor',
            null,
            $vendorData,
            $userId
        );

        return $vendorData;
    }

    public function listVendors(int $hotelId): array
    {
        return $this->inventoryRepository->findAllVendors($hotelId);
    }

    // --- INVENTORY ITEMS ---
    public function createInventoryItem(int $hotelId, array $data, int $userId): array
    {
        if (empty($data['sku']) || empty($data['name']) || empty($data['unit_of_measure'])) {
            throw new Exception('SKU, name, and unit of measure are required.');
        }

        $sku = strtoupper(trim($data['sku']));
        $existing = $this->inventoryRepository->findInventoryItemBySku($hotelId, $sku);
        if ($existing) {
            throw new Exception(sprintf('An item with SKU "%s" already exists.', $sku));
        }

        $itemData = [
            'hotel_id' => $hotelId,
            'sku' => $sku,
            'name' => trim($data['name']),
            'category' => isset($data['category']) ? trim($data['category']) : null,
            'unit_of_measure' => trim($data['unit_of_measure']),
            'min_stock_level' => isset($data['min_stock_level']) ? (float)$data['min_stock_level'] : 0.0,
            'current_stock' => 0.0,
            'average_unit_cost' => 0.0,
            'created_by' => $userId
        ];

        $itemId = $this->inventoryRepository->createInventoryItem($itemData);
        $itemData['id'] = $itemId;

        $this->auditLogService->log(
            'inventory_item',
            $itemId,
            $hotelId,
            'create_item',
            null,
            $itemData,
            $userId
        );

        return $itemData;
    }

    public function listInventoryItems(int $hotelId): array
    {
        return $this->inventoryRepository->findAllInventoryItems($hotelId);
    }

    public function getLedger(int $itemId): array
    {
        return $this->inventoryRepository->findLedgerByItem($itemId);
    }

    // --- STOCK ADJUSTMENT ---
    public function adjustStock(int $hotelId, int $itemId, float $quantity, float $unitCost, string $reason, int $userId): array
    {
        if ($quantity == 0) {
            throw new Exception('Adjustment quantity cannot be zero.');
        }
        if ($unitCost < 0) {
            throw new Exception('Adjustment unit cost cannot be negative.');
        }
        if (empty(trim($reason))) {
            throw new Exception('Adjustment reason is required.');
        }

        $item = $this->inventoryRepository->findInventoryItemById($hotelId, $itemId);
        if (!$item) {
            throw new Exception('Inventory item not found or unauthorized.');
        }

        $this->pdo->beginTransaction();
        try {
            $qOld = (float)$item['current_stock'];
            $cOld = (float)$item['average_unit_cost'];

            $qNew = $quantity;
            $qNewTotal = $qOld + $qNew;

            if ($qNewTotal < 0) {
                throw new Exception(sprintf('Negative stock is disabled. Cannot adjust below zero (Current stock: %.4f, requested: %.4f).', $qOld, $qNew));
            }

            $cNewAvg = $cOld;

            // Weighted Average Recalculation: Only applies on positive stock additions
            if ($qNew > 0) {
                if ($qNewTotal > 0) {
                    $cNewAvg = (($qOld * $cOld) + ($qNew * $unitCost)) / $qNewTotal;
                } else {
                    $cNewAvg = $cOld;
                }
            }

            // Update item metrics
            $this->inventoryRepository->updateInventoryItemMetrics($itemId, $qNewTotal, $cNewAvg, $userId);

            // Log ledger transaction
            $ledgerId = $this->inventoryRepository->logStockTransaction([
                'hotel_id' => $hotelId,
                'inventory_item_id' => $itemId,
                'transaction_type' => 'adjustment',
                'transaction_id' => null,
                'quantity' => $qNew,
                'unit_cost' => $unitCost,
                'resulting_stock' => $qNewTotal,
                'resulting_avg_cost' => $cNewAvg,
                'notes' => trim($reason),
                'created_by' => $userId
            ]);

            // Audit log
            $this->auditLogService->log(
                'stock_adjustment',
                $ledgerId,
                $hotelId,
                'adjust_stock',
                ['stock' => $qOld, 'avg_cost' => $cOld],
                ['stock' => $qNewTotal, 'avg_cost' => $cNewAvg, 'reason' => $reason],
                $userId
            );

            $this->pdo->commit();
            return [
                'success' => true,
                'item_id' => $itemId,
                'old_stock' => $qOld,
                'new_stock' => $qNewTotal,
                'old_avg_cost' => $cOld,
                'new_avg_cost' => $cNewAvg
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
