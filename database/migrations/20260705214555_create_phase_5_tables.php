<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePhase5Tables extends AbstractMigration
{
    public function change(): void
    {
        // 1. Update guest_identity_documents table to include tracking columns
        $tableDocs = $this->table('guest_identity_documents');
        $tableDocs->addColumn('verification_status', 'string', ['limit' => 50, 'default' => 'not_started'])
            ->addColumn('verification_timestamp', 'timestamp', ['null' => true])
            ->addColumn('provider_reference_id', 'string', ['limit' => 100, 'null' => true])
            ->update();

        // 2. Create identity_verification_logs table
        $tableLogs = $this->table('identity_verification_logs', ['id' => true, 'signed' => false]);
        $tableLogs->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('guest_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('document_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('action', 'string', ['limit' => 50]) // otp_requested, otp_verified, verification_failed, manual_fallback
            ->addColumn('status', 'string', ['limit' => 50]) // success, failure
            ->addColumn('provider', 'string', ['limit' => 50]) // sandbox.co.in, manual
            ->addColumn('reference_id', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('details_json', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('guest_id', 'guests', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('document_id', 'guest_identity_documents', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            
            ->addIndex(['hotel_id'])
            ->addIndex(['guest_id'])
            ->addIndex(['document_id'])
            ->create();
    }
}
