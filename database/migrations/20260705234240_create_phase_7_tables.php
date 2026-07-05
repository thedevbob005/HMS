<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePhase7Tables extends AbstractMigration
{
    public function change(): void
    {
        // 1. Vendors table
        $tableVendors = $this->table('vendors', ['id' => true, 'signed' => false]);
        $tableVendors->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('contact_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('address', 'text', ['null' => true])
            ->addColumn('gst_number', 'string', ['limit' => 50, 'null' => true])
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

        // 2. Inventory Items table
        $tableInvItems = $this->table('inventory_items', ['id' => true, 'signed' => false]);
        $tableInvItems->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('sku', 'string', ['limit' => 100])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('category', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('unit_of_measure', 'string', ['limit' => 50]) // Kg, Litre, Pcs, Box
            ->addColumn('min_stock_level', 'decimal', ['precision' => 12, 'scale' => 4, 'default' => 0.0000])
            ->addColumn('current_stock', 'decimal', ['precision' => 12, 'scale' => 4, 'default' => 0.0000])
            ->addColumn('average_unit_cost', 'decimal', ['precision' => 14, 'scale' => 4, 'default' => 0.0000])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'sku'])
            ->create();

        // 3. Purchase Orders table
        $tablePO = $this->table('purchase_orders', ['id' => true, 'signed' => false]);
        $tablePO->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('vendor_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('po_number', 'string', ['limit' => 100])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'Draft']) // Draft, Pending Approval, Approved, Rejected, Received
            ->addColumn('total_amount', 'decimal', ['precision' => 14, 'scale' => 2, 'default' => 0.00])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('approved_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('approved_at', 'timestamp', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('vendor_id', 'vendors', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('approved_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'po_number'])
            ->create();

        // 4. Purchase Order Items table
        $tablePOItems = $this->table('purchase_order_items', ['id' => true, 'signed' => false]);
        $tablePOItems->addColumn('purchase_order_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('inventory_item_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('quantity', 'decimal', ['precision' => 12, 'scale' => 4])
            ->addColumn('unit_price', 'decimal', ['precision' => 14, 'scale' => 4])
            ->addColumn('total_price', 'decimal', ['precision' => 14, 'scale' => 2])
            
            ->addForeignKey('purchase_order_id', 'purchase_orders', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('inventory_item_id', 'inventory_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 5. Goods Receipts table
        $tableGRN = $this->table('goods_receipts', ['id' => true, 'signed' => false]);
        $tableGRN->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('purchase_order_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('grn_number', 'string', ['limit' => 100])
            ->addColumn('received_date', 'date')
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('purchase_order_id', 'purchase_orders', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'grn_number'])
            ->create();

        // 6. Goods Receipt Items table
        $tableGRNItems = $this->table('goods_receipt_items', ['id' => true, 'signed' => false]);
        $tableGRNItems->addColumn('goods_receipt_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('inventory_item_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('quantity', 'decimal', ['precision' => 12, 'scale' => 4])
            ->addColumn('unit_cost', 'decimal', ['precision' => 14, 'scale' => 4])
            ->addColumn('batch_number', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('expiry_date', 'date', ['null' => true])
            
            ->addForeignKey('goods_receipt_id', 'goods_receipts', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('inventory_item_id', 'inventory_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 7. Stock Batches table
        $tableStockBatches = $this->table('stock_batches', ['id' => true, 'signed' => false]);
        $tableStockBatches->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('inventory_item_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('batch_number', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('expiry_date', 'date', ['null' => true])
            ->addColumn('quantity_received', 'decimal', ['precision' => 12, 'scale' => 4])
            ->addColumn('quantity_remaining', 'decimal', ['precision' => 12, 'scale' => 4])
            ->addColumn('unit_cost', 'decimal', ['precision' => 14, 'scale' => 4])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('inventory_item_id', 'inventory_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id'])
            ->addIndex(['inventory_item_id'])
            ->create();

        // 8. Stock Ledger table
        $tableStockLedger = $this->table('stock_ledger', ['id' => true, 'signed' => false]);
        $tableStockLedger->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('inventory_item_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('transaction_type', 'string', ['limit' => 50]) // goods_receipt, adjustment, kitchen_deduction
            ->addColumn('transaction_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('quantity', 'decimal', ['precision' => 12, 'scale' => 4])
            ->addColumn('unit_cost', 'decimal', ['precision' => 14, 'scale' => 4])
            ->addColumn('resulting_stock', 'decimal', ['precision' => 12, 'scale' => 4])
            ->addColumn('resulting_avg_cost', 'decimal', ['precision' => 14, 'scale' => 4])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('inventory_item_id', 'inventory_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id'])
            ->addIndex(['inventory_item_id'])
            ->create();
    }
}
