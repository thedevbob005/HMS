<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Services\AuditLogService;
use Exception;

class EmployeeService
{
    private EmployeeRepository $employeeRepository;
    private AuditLogService $auditLogService;

    public function __construct(EmployeeRepository $employeeRepository, AuditLogService $auditLogService)
    {
        $this->employeeRepository = $employeeRepository;
        $this->auditLogService = $auditLogService;
    }

    // --- DEPARTMENTS ---
    public function createDepartment(int $hotelId, array $data): array
    {
        if (empty($data['name']) || empty($data['code'])) {
            throw new Exception('Department name and code are required.');
        }

        $deptData = [
            'hotel_id' => $hotelId,
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code']))
        ];

        $deptId = $this->employeeRepository->createDepartment($deptData);
        $deptData['id'] = $deptId;

        return $deptData;
    }

    public function listDepartments(int $hotelId): array
    {
        return $this->employeeRepository->findAllDepartments($hotelId);
    }

    // --- SHIFTS ---
    public function createShift(int $hotelId, array $data): array
    {
        if (empty($data['name']) || empty($data['start_time']) || empty($data['end_time'])) {
            throw new Exception('Shift name, start time, and end time are required.');
        }

        $shiftData = [
            'hotel_id' => $hotelId,
            'name' => trim($data['name']),
            'start_time' => trim($data['start_time']),
            'end_time' => trim($data['end_time'])
        ];

        $shiftId = $this->employeeRepository->createShift($shiftData);
        $shiftData['id'] = $shiftId;

        return $shiftData;
    }

    public function listShifts(int $hotelId): array
    {
        return $this->employeeRepository->findAllShifts($hotelId);
    }

    // --- EMPLOYEES ---
    public function createEmployee(int $hotelId, array $data, int $userId): array
    {
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['employee_code'])) {
            throw new Exception('First name, last name, and employee code are required.');
        }

        $code = strtoupper(trim($data['employee_code']));
        $existing = $this->employeeRepository->findEmployeeByCode($hotelId, $code);
        if ($existing) {
            throw new Exception(sprintf('An employee with code "%s" already exists.', $code));
        }

        $empData = [
            'hotel_id' => $hotelId,
            'user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
            'employee_code' => $code,
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'department_id' => !empty($data['department_id']) ? (int)$data['department_id'] : null,
            'shift_id' => !empty($data['shift_id']) ? (int)$data['shift_id'] : null,
            'email' => !empty($data['email']) ? trim($data['email']) : null,
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'emergency_contact_name' => !empty($data['emergency_contact_name']) ? trim($data['emergency_contact_name']) : null,
            'emergency_contact_phone' => !empty($data['emergency_contact_phone']) ? trim($data['emergency_contact_phone']) : null,
            'salary_base' => isset($data['salary_base']) ? (float)$data['salary_base'] : 0.00,
            'status' => $data['status'] ?? 'Active',
            'created_by' => $userId
        ];

        $empId = $this->employeeRepository->createEmployee($empData);
        $empData['id'] = $empId;

        $this->auditLogService->log(
            'employee',
            $empId,
            $hotelId,
            'create_employee',
            null,
            $empData,
            $userId
        );

        return $empData;
    }

    public function updateEmployee(int $hotelId, int $empId, array $data, int $userId): array
    {
        $existing = $this->employeeRepository->findEmployeeById($hotelId, $empId);
        if (!$existing) {
            throw new Exception('Employee not found or unauthorized.');
        }

        $code = strtoupper(trim($data['employee_code']));
        $duplicate = $this->employeeRepository->findEmployeeByCode($hotelId, $code);
        if ($duplicate && (int)$duplicate['id'] !== $empId) {
            throw new Exception(sprintf('An employee with code "%s" already exists.', $code));
        }

        $empData = [
            'user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
            'employee_code' => $code,
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'department_id' => !empty($data['department_id']) ? (int)$data['department_id'] : null,
            'shift_id' => !empty($data['shift_id']) ? (int)$data['shift_id'] : null,
            'email' => !empty($data['email']) ? trim($data['email']) : null,
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'emergency_contact_name' => !empty($data['emergency_contact_name']) ? trim($data['emergency_contact_name']) : null,
            'emergency_contact_phone' => !empty($data['emergency_contact_phone']) ? trim($data['emergency_contact_phone']) : null,
            'salary_base' => isset($data['salary_base']) ? (float)$data['salary_base'] : (float)$existing['salary_base'],
            'status' => $data['status'] ?? $existing['status'],
            'updated_by' => $userId
        ];

        $this->employeeRepository->updateEmployee($empId, $empData);

        $this->auditLogService->log(
            'employee',
            $empId,
            $hotelId,
            'update_employee',
            $existing,
            $empData,
            $userId
        );

        return $this->employeeRepository->findEmployeeById($hotelId, $empId);
    }

    public function listEmployees(int $hotelId, bool $hasSalaryPermission): array
    {
        $employees = $this->employeeRepository->findAllEmployees($hotelId);
        if (!$hasSalaryPermission) {
            foreach ($employees as &$emp) {
                unset($emp['salary_base']);
            }
        }
        return $employees;
    }

    public function deleteEmployee(int $hotelId, int $empId, int $userId): void
    {
        $existing = $this->employeeRepository->findEmployeeById($hotelId, $empId);
        if (!$existing) {
            throw new Exception('Employee not found or unauthorized.');
        }

        $this->employeeRepository->deleteEmployee($empId);

        $this->auditLogService->log(
            'employee',
            $empId,
            $hotelId,
            'delete_employee',
            $existing,
            null,
            $userId
        );
    }
}
