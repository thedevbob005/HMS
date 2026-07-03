<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class CreateRoomTables extends AbstractMigration
{
    public function change(): void
    {
        // 1. Room Types
        $tableRoomTypes = $this->table('room_types', ['id' => true, 'signed' => false]);
        $tableRoomTypes->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('base_price', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('extra_bed_price', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0.00])
            ->addColumn('max_occupancy', 'integer', ['default' => 2])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 2. Rooms
        $tableRooms = $this->table('rooms', ['id' => true, 'signed' => false]);
        $tableRooms->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('room_type_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('room_number', 'string', ['limit' => 50])
            ->addColumn('floor', 'integer', ['default' => 0])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'Available'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('room_type_id', 'room_types', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'room_number'], ['unique' => true])
            ->addIndex(['hotel_id', 'status'])
            ->create();

        // 3. Room Rates (Weekend overrides)
        $tableRoomRates = $this->table('room_rates', ['id' => true, 'signed' => false]);
        $tableRoomRates->addColumn('room_type_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('day_of_week', 'integer') // 0 (Sunday) to 6 (Saturday)
            ->addColumn('rate', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('room_type_id', 'room_types', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['room_type_id', 'day_of_week'], ['unique' => true])
            ->create();

        // 4. Seasonal Rate Rules
        $tableSeasonalRates = $this->table('seasonal_rate_rules', ['id' => true, 'signed' => false]);
        $tableSeasonalRates->addColumn('room_type_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('start_date', 'date')
            ->addColumn('end_date', 'date')
            ->addColumn('rate', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('room_type_id', 'room_types', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 5. Holiday Rate Rules
        $tableHolidayRates = $this->table('holiday_rate_rules', ['id' => true, 'signed' => false]);
        $tableHolidayRates->addColumn('room_type_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('date', 'date')
            ->addColumn('rate', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('room_type_id', 'room_types', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['room_type_id', 'date'], ['unique' => true])
            ->create();

        // 6. Room Status Logs
        $tableRoomStatusLogs = $this->table('room_status_logs', ['id' => false, 'primary_key' => ['id']]);
        $tableRoomStatusLogs->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('old_status', 'string', ['limit' => 50])
            ->addColumn('new_status', 'string', ['limit' => 50])
            ->addColumn('reason', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('room_id', 'rooms', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
