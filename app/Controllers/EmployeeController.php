<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EmployeeService;
use App\Services\AttendanceService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use PDO;
use Exception;

class EmployeeController
{
    private EmployeeService $employeeService;
    private AttendanceService $attendanceService;
    private PDO $pdo;

    public function __construct(EmployeeService $employeeService, AttendanceService $attendanceService, PDO $pdo)
    {
        $this->employeeService = $employeeService;
        $this->attendanceService = $attendanceService;
        $this->pdo = $pdo;
    }

    private function hasPermission(int $userId, string $permission): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT 1 
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = :user_id AND p.name = :permission
            LIMIT 1
        ');
        $stmt->execute([
            ':user_id' => $userId,
            ':permission' => $permission
        ]);
        return (bool)$stmt->fetchColumn();
    }

    private function checkPermission(int $userId, string $permission, Request $request): void
    {
        if (!$this->hasPermission($userId, $permission)) {
            throw new HttpForbiddenException($request, sprintf('You do not have the required permission (%s).', $permission));
        }
    }

    // --- EMPLOYEES ---
    public function listEmployees(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'employees.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $hasSalaryView = $this->hasPermission((int)$currentUser['user_id'], 'employees.view_salary');

        try {
            $employees = $this->employeeService->listEmployees($hotelId, $hasSalaryView);
            return ApiResponse::success($employees, 'Employee records loaded.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function createEmployee(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'employees.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        // Enforce salary management permissions
        if (isset($body['salary_base'])) {
            if (!$this->hasPermission((int)$currentUser['user_id'], 'employees.manage_salary')) {
                return ApiResponse::error('Unauthorized to configure salary figures.', 403);
            }
        }

        try {
            $emp = $this->employeeService->createEmployee($hotelId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($emp, 'Employee profile registered successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function updateEmployee(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'employees.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $empId = $route ? (int)$route->getArgument('employeeId') : 0;

        $body = (array)$request->getParsedBody();

        // Enforce salary management permissions on updates
        if (isset($body['salary_base'])) {
            if (!$this->hasPermission((int)$currentUser['user_id'], 'employees.manage_salary')) {
                return ApiResponse::error('Unauthorized to update salary figures.', 403);
            }
        }

        try {
            $emp = $this->employeeService->updateEmployee($hotelId, $empId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($emp, 'Employee profile updated successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function deleteEmployee(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'employees.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;
        $empId = $route ? (int)$route->getArgument('employeeId') : 0;

        try {
            $this->employeeService->deleteEmployee($hotelId, $empId, (int)$currentUser['user_id']);
            return ApiResponse::success(null, 'Employee profile deleted successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    // --- DEPARTMENTS & SHIFTS ---
    public function listDepartments(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'employees.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        try {
            $depts = $this->employeeService->listDepartments($hotelId);
            return ApiResponse::success($depts, 'Departments retrieved.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function createDepartment(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'employees.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $dept = $this->employeeService->createDepartment($hotelId, $body);
            return ApiResponse::success($dept, 'Department registered.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function listShifts(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'employees.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        try {
            $shifts = $this->employeeService->listShifts($hotelId);
            return ApiResponse::success($shifts, 'Shifts retrieved.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function createShift(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'employees.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $shift = $this->employeeService->createShift($hotelId, $body);
            return ApiResponse::success($shift, 'Shift registered.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    // --- ATTENDANCE ---
    public function listAttendance(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'attendance.view', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $queryParams = $request->getQueryParams();
        $date = trim($queryParams['date'] ?? date('Y-m-d'));

        try {
            $attendance = $this->attendanceService->listAttendance($hotelId, $date);
            return ApiResponse::success($attendance, 'Attendance records loaded.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function clockEmployee(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'attendance.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $log = $this->attendanceService->clockEmployee($hotelId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success($log, 'Attendance clock action registered successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function bulkAttendance(Request $request, Response $response): Response
    {
        $currentUser = $request->getAttribute('user');
        $this->checkPermission((int)$currentUser['user_id'], 'attendance.manage', $request);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $hotelId = $route ? (int)$route->getArgument('hotelId') : 0;

        $body = (array)$request->getParsedBody();

        try {
            $this->attendanceService->bulkLogAttendance($hotelId, $body, (int)$currentUser['user_id']);
            return ApiResponse::success(null, 'Daily attendance sheet posted successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
