<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Services\AuditLogService;
use Exception;

class AttendanceService
{
    private EmployeeRepository $employeeRepository;
    private AuditLogService $auditLogService;

    public function __construct(EmployeeRepository $employeeRepository, AuditLogService $auditLogService)
    {
        $this->employeeRepository = $employeeRepository;
        $this->auditLogService = $auditLogService;
    }

    public function clockEmployee(int $hotelId, array $data, int $userId): array
    {
        if (empty($data['employee_id']) || empty($data['action'])) {
            throw new Exception('Employee ID and action (in/out) are required.');
        }

        $empId = (int)$data['employee_id'];
        $emp = $this->employeeRepository->findEmployeeById($hotelId, $empId);
        if (!$emp) {
            throw new Exception('Employee not found or unauthorized.');
        }

        if ($emp['status'] !== 'Active') {
            throw new Exception('Cannot log attendance for inactive employees.');
        }

        $action = strtolower(trim($data['action']));
        $workDate = date('Y-m-d');
        $nowStr = date('Y-m-d H:i:s');

        // Fetch existing log for today
        $attendanceLogs = $this->employeeRepository->findAttendance($hotelId, $workDate);
        $todayLog = null;
        foreach ($attendanceLogs as $log) {
            if ((int)$log['employee_id'] === $empId) {
                $todayLog = $log;
                break;
            }
        }

        $logData = [
            'hotel_id' => $hotelId,
            'employee_id' => $empId,
            'work_date' => $workDate,
            'created_by' => $userId
        ];

        if ($action === 'in') {
            if ($todayLog && !empty($todayLog['clock_in'])) {
                throw new Exception('Employee has already clocked in for today.');
            }

            $logData['clock_in'] = $nowStr;
            $logData['clock_out'] = $todayLog['clock_out'] ?? null;
            
            // Check if late based on shift start time
            $status = 'Present';
            if (!empty($emp['start_time'])) {
                $shiftStart = strtotime($workDate . ' ' . $emp['start_time']);
                $actualIn = strtotime($nowStr);
                // If more than 15 minutes late, mark status as 'Late'
                if ($actualIn > ($shiftStart + 900)) {
                    $status = 'Late';
                }
            }
            $logData['status'] = $status;
            $logData['notes'] = $data['notes'] ?? ($todayLog['notes'] ?? null);

        } else if ($action === 'out') {
            if (!$todayLog || empty($todayLog['clock_in'])) {
                throw new Exception('Employee must clock in before clocking out.');
            }
            if (!empty($todayLog['clock_out'])) {
                throw new Exception('Employee has already clocked out for today.');
            }

            $logData['clock_in'] = $todayLog['clock_in'];
            $logData['clock_out'] = $nowStr;
            $logData['status'] = $todayLog['status'];
            $logData['notes'] = $data['notes'] ?? $todayLog['notes'];
        } else {
            throw new Exception('Invalid clock action. Supported: in, out.');
        }

        $logId = $this->employeeRepository->logAttendance($logData);
        $logData['id'] = $logId;

        $this->auditLogService->log(
            'employee_attendance',
            $logId,
            $hotelId,
            'attendance_clock_' . $action,
            $todayLog,
            $logData,
            $userId
        );

        return $logData;
    }

    public function bulkLogAttendance(int $hotelId, array $data, int $userId): void
    {
        if (empty($data['work_date']) || empty($data['roster']) || !is_array($data['roster'])) {
            throw new Exception('Work date and roster lines are required.');
        }

        $workDate = trim($data['work_date']);

        foreach ($data['roster'] as $line) {
            if (empty($line['employee_id']) || empty($line['status'])) {
                throw new Exception('Each roster item must contain employee_id and status.');
            }

            $empId = (int)$line['employee_id'];
            $emp = $this->employeeRepository->findEmployeeById($hotelId, $empId);
            if (!$emp) {
                throw new Exception(sprintf('Employee ID %d does not exist or belongs to another hotel.', $empId));
            }

            if ($emp['status'] !== 'Active') {
                throw new Exception(sprintf('Cannot update attendance for inactive employee: %s.', $emp['first_name']));
            }

            // Find existing log
            $existingLogs = $this->employeeRepository->findAttendance($hotelId, $workDate);
            $oldLog = null;
            foreach ($existingLogs as $log) {
                if ((int)$log['employee_id'] === $empId) {
                    $oldLog = $log;
                    break;
                }
            }

            $logData = [
                'hotel_id' => $hotelId,
                'employee_id' => $empId,
                'work_date' => $workDate,
                'status' => trim($line['status']),
                'notes' => $line['notes'] ?? null,
                'clock_in' => $oldLog['clock_in'] ?? null,
                'clock_out' => $oldLog['clock_out'] ?? null,
                'created_by' => $userId
            ];

            $logId = $this->employeeRepository->logAttendance($logData);

            $this->auditLogService->log(
                'employee_attendance',
                $logId,
                $hotelId,
                'attendance_bulk_update',
                $oldLog,
                $logData,
                $userId
            );
        }
    }

    public function listAttendance(int $hotelId, string $date): array
    {
        return $this->employeeRepository->findAttendance($hotelId, $date);
    }
}
