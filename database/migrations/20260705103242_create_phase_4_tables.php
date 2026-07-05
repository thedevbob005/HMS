<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class CreatePhase4Tables extends AbstractMigration
{
    public function change(): void
    {
        // 1. Invoices Table
        $tableInvoices = $this->table('invoices', ['id' => true, 'signed' => false]);
        $tableInvoices->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('stay_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('invoice_number', 'string', ['limit' => 100])
            ->addColumn('guest_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('subtotal', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('cgst', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0.00])
            ->addColumn('sgst', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0.00])
            ->addColumn('igst', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0.00])
            ->addColumn('discount', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0.00])
            ->addColumn('total_amount', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'Paid']) // Paid, Refunded, Cancelled
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('stay_id', 'stays', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('guest_id', 'guests', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'invoice_number'], ['unique' => true])
            ->addIndex(['stay_id'])
            ->create();

        // 2. Message Queue Table
        $tableQueue = $this->table('message_queue', ['id' => true, 'signed' => false]);
        $tableQueue->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('channel', 'string', ['limit' => 50]) // SMS, WhatsApp
            ->addColumn('recipient', 'string', ['limit' => 50])
            ->addColumn('message_type', 'string', ['limit' => 50]) // booking_confirm, checkin_confirm, checkout_receipt, etc.
            ->addColumn('template_id', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('variables', 'text', ['null' => true]) // JSON array
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'queued']) // queued, processing, sent, failed, cancelled
            ->addColumn('retry_count', 'integer', ['default' => 0])
            ->addColumn('error_message', 'text', ['null' => true])
            ->addColumn('provider_response', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('sent_at', 'timestamp', ['null' => true])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id'])
            ->addIndex(['status'])
            ->create();
    }
}
