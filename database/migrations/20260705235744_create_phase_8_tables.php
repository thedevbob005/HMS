<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePhase8Tables extends AbstractMigration
{
    public function change(): void
    {
        // 1. Kitchen Items (menu items)
        $tableKitchenItems = $this->table('kitchen_items', ['id' => true, 'signed' => false]);
        $tableKitchenItems->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('price', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0.00])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id'])
            ->create();

        // 2. Recipes
        $tableRecipes = $this->table('recipes', ['id' => true, 'signed' => false]);
        $tableRecipes->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('kitchen_item_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('instructions', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('kitchen_item_id', 'kitchen_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['kitchen_item_id'], ['unique' => true])
            ->create();

        // 3. Recipe Items (ingredients details)
        $tableRecipeItems = $this->table('recipe_items', ['id' => true, 'signed' => false]);
        $tableRecipeItems->addColumn('recipe_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('inventory_item_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('quantity', 'decimal', ['precision' => 12, 'scale' => 4])
            
            ->addForeignKey('recipe_id', 'recipes', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('inventory_item_id', 'inventory_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 4. Kitchen Orders
        $tableKitchenOrders = $this->table('kitchen_orders', ['id' => true, 'signed' => false]);
        $tableKitchenOrders->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('stay_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('room_number', 'string', ['limit' => 50])
            ->addColumn('order_number', 'string', ['limit' => 100])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'pending']) // pending, preparing, served, cancelled
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('is_meal_plan_included', 'boolean', ['default' => false])
            ->addColumn('total_amount', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0.00])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('stay_id', 'stays', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'order_number'])
            ->create();

        // 5. Kitchen Order Items
        $tableKitchenOrderItems = $this->table('kitchen_order_items', ['id' => true, 'signed' => false]);
        $tableKitchenOrderItems->addColumn('kitchen_order_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('kitchen_item_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('quantity', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('unit_price', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('total_price', 'decimal', ['precision' => 12, 'scale' => 2])
            
            ->addForeignKey('kitchen_order_id', 'kitchen_orders', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('kitchen_item_id', 'kitchen_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
