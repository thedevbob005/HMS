<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RoomRepository;
use App\Services\AuditLogService;
use PDO;
use Exception;

class RoomService
{
    private RoomRepository $roomRepository;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(RoomRepository $roomRepository, AuditLogService $auditLogService, PDO $pdo)
    {
        $this->roomRepository = $roomRepository;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    public function listRoomTypes(int $hotelId): array
    {
        return $this->roomRepository->findAllRoomTypes($hotelId);
    }

    public function getRoomType(int $id): ?array
    {
        return $this->roomRepository->findRoomTypeById($id);
    }

    public function createRoomType(int $hotelId, array $data, int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            $data['hotel_id'] = $hotelId;
            $typeId = $this->roomRepository->createRoomType($data);

            $this->auditLogService->log(
                'room_type',
                $typeId,
                $hotelId,
                'create',
                null,
                $data,
                $userId
            );

            $this->pdo->commit();
            return array_merge(['id' => $typeId], $data);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listRooms(int $hotelId, array $filters = []): array
    {
        return $this->roomRepository->findAllRooms($hotelId, $filters);
    }

    public function createRoom(int $hotelId, array $data, int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            // Verify room number uniqueness for this hotel
            $existing = $this->roomRepository->findRoomByNumber($hotelId, $data['room_number']);
            if ($existing) {
                throw new Exception(sprintf('Room number %s already exists in this hotel.', $data['room_number']));
            }

            // Verify room type belongs to this hotel
            $roomType = $this->roomRepository->findRoomTypeById((int)$data['room_type_id']);
            if (!$roomType || (int)$roomType['hotel_id'] !== $hotelId) {
                throw new Exception('Invalid room type selected for this hotel.');
            }

            $data['hotel_id'] = $hotelId;
            $roomId = $this->roomRepository->createRoom($data);

            $this->auditLogService->log(
                'room',
                $roomId,
                $hotelId,
                'create',
                null,
                $data,
                $userId
            );

            $this->pdo->commit();
            return array_merge(['id' => $roomId], $data);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateRoomStatus(int $hotelId, int $roomId, string $newStatus, ?string $reason, int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            $room = $this->roomRepository->findRoomById($roomId);
            if (!$room || (int)$room['hotel_id'] !== $hotelId) {
                throw new Exception('Room not found or unauthorized.');
            }

            // Verify status constraints (Maintenance/Blocked require a reason)
            $newStatus = trim($newStatus);
            $validStatuses = ['Available', 'Reserved', 'Occupied', 'Cleaning', 'Maintenance', 'Blocked'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception(sprintf('Invalid status "%s" requested.', $newStatus));
            }

            if (in_array($newStatus, ['Maintenance', 'Blocked']) && empty($reason)) {
                throw new Exception(sprintf('A reason must be provided when placing a room in %s status.', $newStatus));
            }

            $oldStatus = $room['status'];
            
            // Perform update
            $this->roomRepository->updateRoomStatus($roomId, $newStatus);

            // Log status transition
            $this->roomRepository->logStatusChange($roomId, $oldStatus, $newStatus, $reason, $userId);

            // Log audit
            $this->auditLogService->log(
                'room_status',
                $roomId,
                $hotelId,
                'change_status',
                ['status' => $oldStatus],
                ['status' => $newStatus, 'reason' => $reason],
                $userId
            );

            $this->pdo->commit();
            
            $room['status'] = $newStatus;
            return $room;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function configureRates(int $hotelId, int $roomTypeId, array $data, int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $roomType = $this->roomRepository->findRoomTypeById($roomTypeId);
            if (!$roomType || (int)$roomType['hotel_id'] !== $hotelId) {
                throw new Exception('Room type not found or unauthorized.');
            }

            // Weekend overrides configuration
            if (isset($data['weekend_rates'])) {
                foreach ($data['weekend_rates'] as $day => $rate) {
                    $this->roomRepository->setWeekendRate($roomTypeId, (int)$day, (float)$rate);
                }
            }

            // Seasonal configuration
            if (isset($data['seasonal_rates'])) {
                foreach ($data['seasonal_rates'] as $rule) {
                    $this->roomRepository->setSeasonalRate(
                        $roomTypeId,
                        $rule['start_date'],
                        $rule['end_date'],
                        (float)$rule['rate'],
                        $rule['description'] ?? null
                    );
                }
            }

            // Holiday configuration
            if (isset($data['holiday_rates'])) {
                foreach ($data['holiday_rates'] as $rule) {
                    $this->roomRepository->setHolidayRate(
                        $roomTypeId,
                        $rule['date'],
                        (float)$rule['rate'],
                        $rule['description'] ?? null
                    );
                }
            }

            $this->auditLogService->log(
                'room_type_rates',
                $roomTypeId,
                $hotelId,
                'configure_rates',
                null,
                $data,
                $userId
            );

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function calculateRoomRate(int $roomTypeId, string $date): float
    {
        // 1. Holiday price check
        $holidayRate = $this->roomRepository->findHolidayRate($roomTypeId, $date);
        if ($holidayRate !== null) {
            return $holidayRate;
        }

        // 2. Seasonal price check
        $seasonalRate = $this->roomRepository->findSeasonalRate($roomTypeId, $date);
        if ($seasonalRate !== null) {
            return $seasonalRate;
        }

        // 3. Weekend price check (0 = Sunday, 6 = Saturday)
        $dayOfWeek = (int)date('w', strtotime($date));
        $weekendRate = $this->roomRepository->findWeekendRate($roomTypeId, $dayOfWeek);
        if ($weekendRate !== null) {
            return $weekendRate;
        }

        // 4. Base price fallback
        $roomType = $this->roomRepository->findRoomTypeById($roomTypeId);
        if (!$roomType) {
            throw new Exception('Room type not found.');
        }

        return (float)$roomType['base_price'];
    }
}
