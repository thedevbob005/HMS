<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class EmployeeRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // --- DEPARTMENTS ---
    public function createDepartment(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO departments (hotel_id, name, code)
            VALUES (:hotel_id, :name, :code)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':name' => $data['name'],
            ':code' => $data['code']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findAllDepartments(int $hotelId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM departments WHERE hotel_id = :hotel_id ORDER BY name ASC');
        $stmt->execute([':hotel_id' => $hotelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- SHIFTS ---
    public function createShift(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO shifts (hotel_id, name, start_time, end_time)
            VALUES (:hotel_id, :name, :start_time, :end_time)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':name' => $data['name'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findAllShifts(int $hotelId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM shifts WHERE hotel_id = :hotel_id ORDER BY id ASC');
        $stmt->execute([':hotel_id' => $hotelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findShiftById(int $hotelId, int $shiftId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM shifts WHERE id = :id AND hotel_id = :hotel_id LIMIT 1');
        $stmt->execute([':id' => $shiftId, ':hotel_id' => $hotelId]);
        $shift = $stmt->fetch();
        return $shift ? $shift : null;
    }

    // --- EMPLOYEES ---
    public function createEmployee(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO employees (hotel_id, user_id, employee_code, first_name, last_name, department_id, shift_id, email, phone, emergency_contact_name, emergency_contact_phone, salary_base, status, created_by)
            VALUES (:hotel_id, :user_id, :employee_code, :first_name, :last_name, :department_id, :shift_id, :email, :phone, :emergency_contact_name, :emergency_contact_phone, :salary_base, :status, :created_by)
        ');
        $stmt->execute([
            ':hotel_id' => $data['hotel_id'],
            ':user_id' => $data['user_id'] ?? null,
            ':employee_code' => $data['employee_code'],
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':department_id' => $data['department_id'] ?? null,
            ':shift_id' => $data['shift_id'] ?? null,
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            ':emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            ':salary_base' => $data['salary_base'] ?? 0.00,
            ':status' => $data['status'] ?? 'Active',
            ':created_by' => $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateEmployee(int $empId, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE employees 
            SET user_id = :user_id, employee_code = :employee_code, first_name = :first_name, last_name = :last_name, department_id = :department_id, shift_id = :shift_id, email = :email, phone = :phone, emergency_contact_name = :emergency_contact_name, emergency_contact_phone = :emergency_contact_phone, salary_base = :salary_base, status = :status, updated_by = :updated_by
            WHERE id = :id
        ');
        $stmt->execute([
            ':user_id' => $data['user_id'] ?? null,
            ':employee_code' => $data['employee_code'],
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':department_id' => $data['department_id'] ?? null,
            ':shift_id' => $data['shift_id'] ?? null,
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            ':emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            ':salary_base' => $data['salary_base'] ?? 0.00,
            ':status' => $data['status'] ?? 'Active',
            ':updated_by' => $data['updated_by'],
            ':id' => $empId
        ]);
    }

    public function deleteEmployee(int $empId): void
    {
        $stmt = $this->pdo->prepare('UPDATE employees SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $empId]);
    }

    public function findEmployeeById(int $hotelId, int $empId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT e.*, d.name as department_name, s.name as shift_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN shifts s ON e.shift_id = s.id
            WHERE e.id = :id AND e.hotel_id = :hotel_id AND e.deleted_at IS NULL 
            LIMIT 1
        ');
        $stmt->execute([':id' => $empId, ':hotel_id' => $hotelId]);
        $emp = $stmt->fetch();
        return $emp ? $emp : null;
    }

    public function findEmployeeByCode(int $hotelId, string $code): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM employees WHERE employee_code = :code AND hotel_id = :hotel_id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':code' => $code, ':hotel_id' => $hotelId]);
        $emp = $stmt->fetch();
        return $emp ? $emp : null;
    }

    public function findAllEmployees(int $hotelId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT e.*, d.name as department_name, s.name as shift_name, s.start_time, s.end_time
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN shifts s ON e.shift_id = s.id
            WHERE e.hotel_id = :hotel_id AND e.deleted_at IS NULL
            ORDER BY e.first_name ASC, e.last_name ASC
        ');
        $stmt->execute([':hotel_id' => $hotelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- ATTENDANCE ---
    public function logAttendance(array $data): int
    {
        // First see if a log already exists for this employee and date
        $stmtFind = $this->pdo->prepare('
            SELECT id FROM employee_attendance 
            WHERE hotel_id = :hotel_id AND employee_id = :employee_id AND work_date = :work_date
            LIMIT 1
        ');
        $stmtFind->execute([
            ':hotel_id' => $data['hotel_id'],
            ':employee_id' => $data['employee_id'],
            ':work_date' => $data['work_date']
        ]);
        $existingId = $stmtFind->fetchColumn();

        if ($existingId) {
            $stmtUpdate = $this->pdo->prepare('
                UPDATE employee_attendance 
                SET clock_in = :clock_in, clock_out = :clock_out, status = :status, notes = :notes, updated_by = :updated_by
                WHERE id = :id
            ');
            $stmtUpdate->execute([
                ':clock_in' => $data['clock_in'] ?? null,
                ':clock_out' => $data['clock_out'] ?? null,
                ':status' => $data['status'],
                ':notes' => $data['notes'] ?? null,
                ':updated_by' => $data['created_by'],
                ':id' => $existingId
            ]);
            return (int)$existingId;
        } else {
            $stmtInsert = $this->pdo->prepare('
                INSERT INTO employee_attendance (hotel_id, employee_id, work_date, clock_in, clock_out, status, notes, created_by)
                VALUES (:hotel_id, :employee_id, :work_date, :clock_in, :clock_out, :status, :notes, :created_by)
            ');
            $stmtInsert->execute([
                ':hotel_id' => $data['hotel_id'],
                ':employee_id' => $data['employee_id'],
                ':work_date' => $data['work_date'],
                ':clock_in' => $data['clock_in'] ?? null,
                ':clock_out' => $data['clock_out'] ?? null,
                ':status' => $data['status'] ?? 'Present',
                ':notes' => $data['notes'] ?? null,
                ':created_by' => $data['created_by']
            ]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function findAttendance(int $hotelId, string $date): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ea.*, e.first_name, e.last_name, e.employee_code, d.name as department_name, s.name as shift_name
            FROM employee_attendance ea
            JOIN employees e ON ea.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN shifts s ON e.shift_id = s.id
            WHERE ea.hotel_id = :hotel_id AND ea.work_date = :work_date
            ORDER BY e.first_name ASC
        ');
        $stmt->execute([':hotel_id' => $hotelId, ':work_date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
