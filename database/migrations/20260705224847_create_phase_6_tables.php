<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePhase6Tables extends AbstractMigration
{
    public function change(): void
    {
        $tableTasks = $this->table('housekeeping_tasks', ['id' => true, 'signed' => false]);
        $tableTasks->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('room_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('task_type', 'string', ['limit' => 50]) // cleaning, maintenance, laundry
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'pending']) // pending, in_progress, completed, cancelled
            ->addColumn('priority', 'string', ['limit' => 50, 'default' => 'medium']) // low, medium, high
            ->addColumn('assigned_to', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('completed_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('room_id', 'rooms', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('assigned_to', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            
            ->addIndex(['hotel_id'])
            ->addIndex(['room_id'])
            ->addIndex(['assigned_to'])
            ->create();
    }
}
