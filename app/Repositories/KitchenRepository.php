<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class KitchenRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // --- KITCHEN MENU ITEMS ---
    public function createKitchenItem(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO kitchen_items (hotel_id, name, description, price, is_active, created_by)
            VALUES (:hotel_id, :name, :description, :price, :is_active, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'],
            ':is_active' => $data['is_active'] ?? true,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateKitchenItem(int $itemId, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE kitchen_items 
            SET name = :name, description = :description, price = :price, is_active = :is_active, updated_by = :updated_by
            WHERE id = :id
        ');
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'],
            ':is_active' => $data['is_active'] ? 1 : 0,
            ':updated_by' => $data['updated_by'],
            ':id' => $itemId
        ]);
    }

    public function findKitchenItemById(int $hotelId, int $itemId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM kitchen_items WHERE id = :id AND hotel_id = :hotel_id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':id' => $itemId, ':hotel_id' => $hotelId]);
        $item = $stmt->fetch();
        return $item ? $item : null;
    }

    public function findAllKitchenItems(int $hotelId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM kitchen_items WHERE hotel_id = :hotel_id AND deleted_at IS NULL ORDER BY name ASC');
        $stmt->execute([':hotel_id' => $hotelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- RECIPES ---
    public function saveRecipe(array $data): int
    {
        // First see if a recipe already exists for this kitchen item
        $stmt = $this->pdo->prepare('SELECT id FROM recipes WHERE kitchen_item_id = :kitchen_item_id LIMIT 1');
        $stmt->execute([':kitchen_item_id' => $data['kitchen_item_id']]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $recipeId = (int)$existing;
            $stmtUpdate = $this->pdo->prepare('
                UPDATE recipes 
                SET instructions = :instructions, updated_by = :updated_by 
                WHERE id = :id
            ');
            $stmtUpdate->execute([
                ':instructions' => $data['instructions'] ?? null,
                ':updated_by' => $data['created_by'],
                ':id' => $recipeId
            ]);
            
            // Delete old recipe items so we can recreate them
            $this->pdo->prepare('DELETE FROM recipe_items WHERE recipe_id = :recipe_id')->execute([':recipe_id' => $recipeId]);
            return $recipeId;
        } else {
            $stmtInsert = $this->pdo->prepare('
                INSERT INTO recipes (hotel_id, kitchen_item_id, instructions, created_by)
                VALUES (:hotel_id, :kitchen_item_id, :instructions, :created_by)
            ');
            $stmtInsert->execute([
                ':hotel_id' => $data['hotel_id'],
                ':kitchen_item_id' => $data['kitchen_item_id'],
                ':instructions' => $data['instructions'] ?? null,
                ':created_by' => $data['created_by']
            ]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function createRecipeItem(array $data): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO recipe_items (recipe_id, inventory_item_id, quantity)
            VALUES (:recipe_id, :inventory_item_id, :quantity)
        ');
        $stmt->execute([
            ':recipe_id' => $data['recipe_id'],
            ':inventory_item_id' => $data['inventory_item_id'],
            ':quantity' => $data['quantity']
        ]);
    }

    public function findRecipeByItem(int $itemId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM recipes WHERE kitchen_item_id = :item_id LIMIT 1');
        $stmt->execute([':item_id' => $itemId]);
        $recipe = $stmt->fetch();
        if (!$recipe) return null;

        // Fetch ingredients
        $stmtItems = $this->pdo->prepare('
            SELECT ri.*, ii.name as ingredient_name, ii.sku as ingredient_sku, ii.unit_of_measure as ingredient_uom, ii.average_unit_cost
            FROM recipe_items ri
            JOIN inventory_items ii ON ri.inventory_item_id = ii.id
            WHERE ri.recipe_id = :recipe_id
        ');
        $stmtItems->execute([':recipe_id' => $recipe['id']]);
        $recipe['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return $recipe;
    }

    // --- KITCHEN ORDERS ---
    public function createOrder(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO kitchen_orders (hotel_id, stay_id, room_number, order_number, status, notes, is_meal_plan_included, total_amount, created_by)
            VALUES (:hotel_id, :stay_id, :room_number, :order_number, :status, :notes, :is_meal_plan_included, :total_amount, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':stay_id' => $data['stay_id'],
            ':room_number' => $data['room_number'],
            ':order_number' => $data['order_number'],
            ':status' => $data['status'] ?? 'pending',
            ':notes' => $data['notes'] ?? null,
            ':is_meal_plan_included' => $data['is_meal_plan_included'] ? 1 : 0,
            ':total_amount' => $data['total_amount'] ?? 0.00,
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function createOrderItem(array $data): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO kitchen_order_items (kitchen_order_id, kitchen_item_id, quantity, unit_price, total_price)
            VALUES (:kitchen_order_id, :kitchen_item_id, :quantity, :unit_price, :total_price)
        ');
        $stmt->execute([
            ':kitchen_order_id' => $data['kitchen_order_id'],
            ':kitchen_item_id' => $data['kitchen_item_id'],
            ':quantity' => $data['quantity'],
            ':unit_price' => $data['unit_price'],
            ':total_price' => $data['total_price']
        ]);
    }

    public function updateOrderStatus(int $orderId, string $status, int $userId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE kitchen_orders 
            SET status = :status, updated_by = :userId
            WHERE id = :id
        ');
        $stmt->execute([
            ':status' => $status,
            ':userId' => $userId,
            ':id' => $orderId
        ]);
    }

    public function findOrderById(int $hotelId, int $orderId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT ko.*, u.username as creator_name
            FROM kitchen_orders ko
            LEFT JOIN users u ON ko.created_by = u.id
            WHERE ko.id = :id AND ko.hotel_id = :hotel_id
            LIMIT 1
        ');
        $stmt->execute([':id' => $orderId, ':hotel_id' => $hotelId]);
        $order = $stmt->fetch();
        if (!$order) return null;

        // Fetch order items
        $stmtItems = $this->pdo->prepare('
            SELECT koi.*, ki.name as item_name
            FROM kitchen_order_items koi
            JOIN kitchen_items ki ON koi.kitchen_item_id = ki.id
            WHERE koi.kitchen_order_id = :order_id
        ');
        $stmtItems->execute([':order_id' => $orderId]);
        $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }

    public function findAllOrders(int $hotelId, array $filters = []): array
    {
        $sql = '
            SELECT ko.*, u.username as creator_name
            FROM kitchen_orders ko
            LEFT JOIN users u ON ko.created_by = u.id
            WHERE ko.hotel_id = :hotel_id
        ';
        $params = [':hotel_id' => $hotelId];

        if (!empty($filters['status'])) {
            $sql .= ' AND ko.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['room_number'])) {
            $sql .= ' AND ko.room_number = :room_number';
            $params[':room_number'] = $filters['room_number'];
        }

        $sql .= ' ORDER BY ko.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
