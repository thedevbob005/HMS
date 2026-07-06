<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePhase9Tables extends AbstractMigration
{
    public function change(): void
    {
        // 1. Departments table
        $tableDepts = $this->table('departments', ['id' => true, 'signed' => false]);
        $tableDepts->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('code', 'string', ['limit' => 50])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'code'])
            ->create();

        // 2. Shifts table
        $tableShifts = $this->table('shifts', ['id' => true, 'signed' => false]);
        $tableShifts->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('start_time', 'time')
            ->addColumn('end_time', 'time')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id'])
            ->create();

        // 3. Employees table
        $tableEmps = $this->table('employees', ['id' => true, 'signed' => false]);
        $tableEmps->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true]) // optional link to users credentials
            ->addColumn('employee_code', 'string', ['limit' => 100])
            ->addColumn('first_name', 'string', ['limit' => 255])
            ->addColumn('last_name', 'string', ['limit' => 255])
            ->addColumn('department_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('shift_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('emergency_contact_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('emergency_contact_phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('salary_base', 'decimal', ['precision' => 14, 'scale' => 2, 'default' => 0.00])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'Active']) // Active, Inactive
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('department_id', 'departments', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('shift_id', 'shifts', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'employee_code'])
            ->create();

        // 4. Employee Attendance table
        $tableAttendance = $this->table('employee_attendance', ['id' => true, 'signed' => false]);
        $tableAttendance->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('employee_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('work_date', 'date')
            ->addColumn('clock_in', 'datetime', ['null' => true])
            ->addColumn('clock_out', 'datetime', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'Present']) // Present, Absent, Leave, Late
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('employee_id', 'employees', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['hotel_id', 'employee_id', 'work_date'])
            ->create();

        // 5. Employee Documents table
        $tableDocs = $this->table('employee_documents', ['id' => true, 'signed' => false]);
        $tableDocs->addColumn('employee_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('document_name', 'string', ['limit' => 255])
            ->addColumn('file_path', 'string', ['limit' => 255])
            ->addColumn('uploaded_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            
            ->addForeignKey('employee_id', 'employees', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
