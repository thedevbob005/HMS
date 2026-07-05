<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class CreatePhase3Tables extends AbstractMigration
{
    public function change(): void
    {
        // 1. Guests
        $tableGuests = $this->table('guests', ['id' => true, 'signed' => false]);
        $tableGuests->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('first_name', 'string', ['limit' => 100])
            ->addColumn('last_name', 'string', ['limit' => 100])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'phone'])
            ->addIndex(['hotel_id', 'email'])
            ->create();

        // 2. Guest Identity Documents
        $tableDocs = $this->table('guest_identity_documents', ['id' => true, 'signed' => false]);
        $tableDocs->addColumn('guest_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('document_type', 'string', ['limit' => 50]) // Aadhaar, Passport, Driving License, Voter ID
            ->addColumn('document_number_masked', 'string', ['limit' => 100])
            ->addColumn('document_number_encrypted', 'text', ['null' => false])
            ->addColumn('file_path', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('is_verified', 'boolean', ['default' => false])
            ->addColumn('verification_metadata', 'json', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('guest_id', 'guests', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['guest_id'])
            ->create();

        // 3. Reservations
        $tableReservations = $this->table('reservations', ['id' => true, 'signed' => false]);
        $tableReservations->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('guest_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('booking_source', 'string', ['limit' => 50]) // Walk-in, Phone, Other
            ->addColumn('booking_source_details', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'Draft']) // Draft, Confirmed, Checked In, Completed, Cancelled, No Show
            ->addColumn('checkin_date', 'date')
            ->addColumn('checkout_date', 'date')
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('guest_id', 'guests', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'status'])
            ->addIndex(['hotel_id', 'checkin_date'])
            ->create();

        // 4. Reservation Rooms
        $tableResRooms = $this->table('reservation_rooms', ['id' => false, 'primary_key' => ['reservation_id', 'room_id']]);
        $tableResRooms->addColumn('reservation_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('room_type_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('price_per_night', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('extra_bed_price', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0.00])
            ->addColumn('has_extra_bed', 'boolean', ['default' => false])
            ->addForeignKey('reservation_id', 'reservations', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('room_id', 'rooms', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('room_type_id', 'room_types', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 5. Reservation Guests
        $tableResGuests = $this->table('reservation_guests', ['id' => false, 'primary_key' => ['reservation_id', 'guest_id', 'room_id']]);
        $tableResGuests->addColumn('reservation_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('guest_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false])
            ->addForeignKey('reservation_id', 'reservations', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('guest_id', 'guests', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('room_id', 'rooms', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 6. Stays
        $tableStays = $this->table('stays', ['id' => true, 'signed' => false]);
        $tableStays->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('reservation_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'Active']) // Active, Completed, Cancelled
            ->addColumn('checkin_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('checkout_at', 'timestamp', ['null' => true])
            ->addColumn('expected_checkout_at', 'timestamp')
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('reservation_id', 'reservations', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'status'])
            ->create();

        // 7. Stay Rooms
        $tableStayRooms = $this->table('stay_rooms', ['id' => true, 'signed' => false]);
        $tableStayRooms->addColumn('stay_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('room_type_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('price_per_night', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('extra_bed_price', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0.00])
            ->addColumn('has_extra_bed', 'boolean', ['default' => false])
            ->addColumn('checked_in_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('checked_out_at', 'timestamp', ['null' => true])
            ->addForeignKey('stay_id', 'stays', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('room_id', 'rooms', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('room_type_id', 'room_types', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 8. Stay Guests
        $tableStayGuests = $this->table('stay_guests', ['id' => false, 'primary_key' => ['stay_id', 'guest_id', 'room_id']]);
        $tableStayGuests->addColumn('stay_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('guest_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false])
            ->addForeignKey('stay_id', 'stays', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('guest_id', 'guests', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('room_id', 'rooms', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 9. Room Shift Logs
        $tableShifts = $this->table('room_shift_logs', ['id' => true, 'signed' => false]);
        $tableShifts->addColumn('stay_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('old_room_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('new_room_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('reason', 'string', ['limit' => 255])
            ->addColumn('shifted_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('shifted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('stay_id', 'stays', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('old_room_id', 'rooms', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('new_room_id', 'rooms', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('shifted_by', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['stay_id'])
            ->create();

        // 10. Payments
        $tablePayments = $this->table('payments', ['id' => true, 'signed' => false]);
        $tablePayments->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('stay_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('reservation_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('payment_method', 'string', ['limit' => 50])
            ->addColumn('amount', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('transaction_reference', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('stay_id', 'stays', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('reservation_id', 'reservations', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'stay_id'])
            ->addIndex(['hotel_id', 'reservation_id'])
            ->create();

        // 11. Folio Items
        $tableFolios = $this->table('folio_items', ['id' => true, 'signed' => false]);
        $tableFolios->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('stay_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('item_type', 'string', ['limit' => 50]) // room_charge, extra_bed, kitchen_order, late_checkout, adjustment, payment_credit
            ->addColumn('description', 'string', ['limit' => 255])
            ->addColumn('amount', 'decimal', ['precision' => 12, 'scale' => 2])
            ->addColumn('tax_amount', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0.00])
            ->addColumn('reference_type', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('reference_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('stay_id', 'stays', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['stay_id'])
            ->create();
    }
}
