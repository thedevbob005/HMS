<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\KitchenRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\StayRepository;
use App\Services\AuditLogService;
use PDO;
use Exception;

class KitchenService
{
    private KitchenRepository $kitchenRepository;
    private InventoryRepository $inventoryRepository;
    private StayRepository $stayRepository;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(
        KitchenRepository $kitchenRepository,
        InventoryRepository $inventoryRepository,
        StayRepository $stayRepository,
        AuditLogService $auditLogService,
        PDO $pdo
    ) {
        $this->kitchenRepository = $kitchenRepository;
        $this->inventoryRepository = $inventoryRepository;
        $this->stayRepository = $stayRepository;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    // --- KITCHEN ITEMS ---
    public function createKitchenItem(int $hotelId, array $data, int $userId): array
    {
        if (empty($data['name']) || !isset($data['price'])) {
            throw new Exception('Name and selling price are required.');
        }

        $itemData = [
            'hotel_id' => $hotelId,
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'price' => (float)$data['price'],
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'created_by' => $userId
        ];

        $itemId = $this->kitchenRepository->createKitchenItem($itemData);
        $itemData['id'] = $itemId;

        $this->auditLogService->log(
            'kitchen_item',
            $itemId,
            $hotelId,
            'create_item',
            null,
            $itemData,
            $userId
        );

        return $itemData;
    }

    public function listKitchenItems(int $hotelId): array
    {
        return $this->kitchenRepository->findAllKitchenItems($hotelId);
    }

    // --- RECIPES ---
    public function saveRecipe(int $hotelId, int $itemId, array $data, int $userId): array
    {
        $item = $this->kitchenRepository->findKitchenItemById($hotelId, $itemId);
        if (!$item) {
            throw new Exception('Kitchen item not found or unauthorized.');
        }

        $this->pdo->beginTransaction();
        try {
            $recipeId = $this->kitchenRepository->saveRecipe([
                'hotel_id' => $hotelId,
                'kitchen_item_id' => $itemId,
                'instructions' => $data['instructions'] ?? null,
                'created_by' => $userId
            ]);

            if (!empty($data['ingredients']) && is_array($data['ingredients'])) {
                foreach ($data['ingredients'] as $ing) {
                    if (empty($ing['inventory_item_id']) || empty($ing['quantity'])) {
                        throw new Exception('Each ingredient must specify inventory_item_id and quantity.');
                    }
                    
                    $qty = (float)$ing['quantity'];
                    if ($qty <= 0) {
                        throw new Exception('Ingredient quantity must be positive.');
                    }

                    $this->kitchenRepository->createRecipeItem([
                        'recipe_id' => $recipeId,
                        'inventory_item_id' => (int)$ing['inventory_item_id'],
                        'quantity' => $qty
                    ]);
                }
            }

            $this->auditLogService->log(
                'recipe',
                $recipeId,
                $hotelId,
                'save_recipe',
                null,
                ['kitchen_item_id' => $itemId, 'ingredients' => $data['ingredients'] ?? []],
                $userId
            );

            $this->pdo->commit();
            return $this->kitchenRepository->findRecipeByItem($itemId);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getRecipe(int $hotelId, int $itemId): ?array
    {
        return $this->kitchenRepository->findRecipeByItem($itemId);
    }

    public function getRecipeCostingSheet(int $hotelId): array
    {
        $menuItems = $this->kitchenRepository->findAllKitchenItems($hotelId);
        $costingSheet = [];

        foreach ($menuItems as $item) {
            $recipe = $this->kitchenRepository->findRecipeByItem((int)$item['id']);
            $cost = 0.00;

            if ($recipe && !empty($recipe['items'])) {
                foreach ($recipe['items'] as $ri) {
                    $cost += (float)$ri['quantity'] * (float)$ri['average_unit_cost'];
                }
            }

            $price = (float)$item['price'];
            $profit = $price - $cost;
            $marginPct = $price > 0 ? ($profit / $price) * 100 : 0.00;

            $costingSheet[] = [
                'item_id' => $item['id'],
                'name' => $item['name'],
                'price' => $price,
                'recipe_cost' => $cost,
                'gross_profit' => $profit,
                'margin_percentage' => $marginPct,
                'has_recipe' => $recipe !== null
            ];
        }

        return $costingSheet;
    }

    // --- KITCHEN ORDERS ---
    public function createOrder(int $hotelId, array $data, int $userId): array
    {
        if (empty($data['stay_id']) || empty($data['items']) || !is_array($data['items'])) {
            throw new Exception('Stay ID and ordered items are required.');
        }

        $stayId = (int)$data['stay_id'];
        $stay = $this->stayRepository->findStayById($hotelId, $stayId);
        if (!$stay || $stay['status'] !== 'Active') {
            throw new Exception('Stay is not active or does not exist.');
        }

        $this->pdo->beginTransaction();
        try {
            $totalAmount = 0.00;
            $itemsToCreate = [];

            foreach ($data['items'] as $line) {
                if (empty($line['kitchen_item_id']) || empty($line['quantity'])) {
                    throw new Exception('Each line must specify kitchen_item_id and quantity.');
                }
                
                $qty = (float)$line['quantity'];
                if ($qty <= 0) {
                    throw new Exception('Quantity ordered must be positive.');
                }

                $kItem = $this->kitchenRepository->findKitchenItemById($hotelId, (int)$line['kitchen_item_id']);
                if (!$kItem || !$kItem['is_active']) {
                    throw new Exception('Menu item not active or not found.');
                }

                $lineTotal = round($qty * (float)$kItem['price'], 2);
                $totalAmount += $lineTotal;

                $itemsToCreate[] = [
                    'kitchen_item_id' => (int)$line['kitchen_item_id'],
                    'quantity' => $qty,
                    'unit_price' => (float)$kItem['price'],
                    'total_price' => $lineTotal
                ];
            }

            // Find guest room numbers associated with stay
            $roomNumber = 'Room';
            if (!empty($stay['rooms'])) {
                $roomNumber = $stay['rooms'][0]['room_number'];
            }

            $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

            $orderData = [
                'hotel_id' => $hotelId,
                'stay_id' => $stayId,
                'room_number' => $roomNumber,
                'order_number' => $orderNumber,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'is_meal_plan_included' => !empty($data['is_meal_plan_included']),
                'total_amount' => $totalAmount,
                'created_by' => $userId
            ];

            $orderId = $this->kitchenRepository->createOrder($orderData);

            foreach ($itemsToCreate as $item) {
                $item['kitchen_order_id'] = $orderId;
                $this->kitchenRepository->createOrderItem($item);
            }

            $this->auditLogService->log(
                'kitchen_order',
                $orderId,
                $hotelId,
                'create_order',
                null,
                array_merge(['id' => $orderId], $orderData),
                $userId
            );

            $this->pdo->commit();
            return $this->kitchenRepository->findOrderById($hotelId, $orderId);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listOrders(int $hotelId, array $filters = []): array
    {
        return $this->kitchenRepository->findAllOrders($hotelId, $filters);
    }

    public function updateOrderStatus(int $hotelId, int $orderId, string $status, int $userId): array
    {
        $order = $this->kitchenRepository->findOrderById($hotelId, $orderId);
        if (!$order) {
            throw new Exception('Kitchen order not found or unauthorized.');
        }

        $allowed = ['pending', 'preparing', 'served', 'cancelled'];
        if (!in_array($status, $allowed)) {
            throw new Exception(sprintf('Invalid status transition: %s.', $status));
        }

        if ($order['status'] === 'served' || $order['status'] === 'cancelled') {
            throw new Exception(sprintf('Cannot update status of a %s order.', $order['status']));
        }

        $dbAlreadyInTransaction = $this->pdo->inTransaction();
        if (!$dbAlreadyInTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $oldStatus = $order['status'];

            // Trigger stock deduction and folio charges only when status transitions to served
            if ($status === 'served') {
                
                // 1. Deduct ingredients based on recipe
                foreach ($order['items'] as $item) {
                    $recipe = $this->kitchenRepository->findRecipeByItem((int)$item['kitchen_item_id']);
                    if ($recipe && !empty($recipe['items'])) {
                        foreach ($recipe['items'] as $ri) {
                            $itemId = (int)$ri['inventory_item_id'];
                            $qtyToDeduct = (float)$item['quantity'] * (float)$ri['quantity'];

                            $invItem = $this->inventoryRepository->findInventoryItemById($hotelId, $itemId);
                            if (!$invItem) {
                                throw new Exception(sprintf('Ingredient stock item ID %d not found.', $itemId));
                            }

                            $qOld = (float)$invItem['current_stock'];
                            $cOld = (float)$invItem['average_unit_cost'];
                            $qNew = $qOld - $qtyToDeduct;

                            if ($qNew < 0) {
                                throw new Exception(sprintf(
                                    'Deduction exceeds available stock for %s (Current: %.4f, required: %.4f).',
                                    $invItem['name'], $qOld, $qtyToDeduct
                                ));
                            }

                            // Update stock
                            $this->inventoryRepository->updateInventoryItemMetrics($itemId, $qNew, $cOld, $userId);

                            // Log stock transaction
                            $this->inventoryRepository->logStockTransaction([
                                'hotel_id' => $hotelId,
                                'inventory_item_id' => $itemId,
                                'transaction_type' => 'kitchen_deduction',
                                'transaction_id' => $orderId,
                                'quantity' => -$qtyToDeduct,
                                'unit_cost' => $cOld,
                                'resulting_stock' => $qNew,
                                'resulting_avg_cost' => $cOld,
                                'notes' => sprintf('Ingredient deduction for served Kitchen Order #%s line', $order['order_number']),
                                'created_by' => $userId
                            ]);
                        }
                    }
                }

                // 2. Post charge to folio if NOT included in meal plan
                if ((int)$order['is_meal_plan_included'] === 0) {
                    $this->stayRepository->createFolioItem([
                        'hotel_id' => $hotelId,
                        'stay_id' => (int)$order['stay_id'],
                        'item_type' => 'kitchen_order',
                        'description' => 'Room Service Kitchen Order #' . $order['order_number'],
                        'amount' => (float)$order['total_amount'],
                        'tax_amount' => 0.00,
                        'reference_type' => 'kitchen_orders',
                        'reference_id' => $orderId,
                        'created_by' => $userId
                    ]);
                }
            }

            // Update status
            $this->kitchenRepository->updateOrderStatus($orderId, $status, $userId);

            $this->auditLogService->log(
                'kitchen_order',
                $orderId,
                $hotelId,
                'update_status',
                ['status' => $oldStatus],
                ['status' => $status],
                $userId
            );

            if (!$dbAlreadyInTransaction) {
                $this->pdo->commit();
            }

            return $this->kitchenRepository->findOrderById($hotelId, $orderId);
        } catch (Exception $e) {
            if (!$dbAlreadyInTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
