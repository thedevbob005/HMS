<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReservationRepository;
use App\Repositories\RoomRepository;
use App\Services\RoomService;
use App\Services\AuditLogService;
use App\Validators\ReservationValidator;
use PDO;
use Exception;

class ReservationService
{
    private ReservationRepository $reservationRepository;
    private RoomRepository $roomRepository;
    private RoomService $roomService;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(
        ReservationRepository $reservationRepository,
        RoomRepository $roomRepository,
        RoomService $roomService,
        AuditLogService $auditLogService,
        PDO $pdo
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->roomRepository = $roomRepository;
        $this->roomService = $roomService;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    public function listReservations(int $hotelId, array $filters = []): array
    {
        return $this->reservationRepository->findAllReservations($hotelId, $filters);
    }

    public function getReservation(int $hotelId, int $resId): ?array
    {
        return $this->reservationRepository->findReservationById($hotelId, $resId);
    }

    public function createReservation(int $hotelId, array $data, int $userId): array
    {
        ReservationValidator::validateReservation($data);

        $this->pdo->beginTransaction();
        try {
            $checkin = $data['checkin_date'];
            $checkout = $data['checkout_date'];

            // 1. Availability validation & Price calculation for each room
            $calculatedRooms = [];
            foreach ($data['rooms'] as $roomSelection) {
                $roomId = (int)$roomSelection['room_id'];
                
                // Verify room belongs to hotel
                $room = $this->roomRepository->findRoomById($roomId);
                if (!$room || (int)$room['hotel_id'] !== $hotelId) {
                    throw new Exception(sprintf('Room ID %d not found or unauthorized.', $roomId));
                }

                // Check availability
                if (!$this->reservationRepository->isRoomAvailable($roomId, $checkin, $checkout)) {
                    throw new Exception(sprintf('Room number %s is not available for the selected dates.', $room['room_number']));
                }

                // Calculate price per night over the date range
                $totalPrice = 0.0;
                $currentDate = $checkin;
                $daysCount = 0;
                while (strtotime($currentDate) < strtotime($checkout)) {
                    $totalPrice += $this->roomService->calculateRoomRate((int)$room['room_type_id'], $currentDate);
                    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                    $daysCount++;
                }

                if ($daysCount === 0) {
                    throw new Exception('Stay duration must be at least 1 night.');
                }

                $avgPrice = $totalPrice / $daysCount;
                
                $roomType = $this->roomRepository->findRoomTypeById((int)$room['room_type_id']);
                $extraBedPrice = $roomType ? (float)$roomType['extra_bed_price'] : 0.00;

                $calculatedRooms[] = [
                    'room_id' => $roomId,
                    'room_type_id' => (int)$room['room_type_id'],
                    'price_per_night' => $avgPrice,
                    'extra_bed_price' => $extraBedPrice,
                    'has_extra_bed' => (bool)($roomSelection['has_extra_bed'] ?? false),
                    'guests' => $roomSelection['guests'] ?? []
                ];
            }

            // 2. Insert reservation
            $resData = [
                'hotel_id' => $hotelId,
                'guest_id' => (int)$data['guest_id'],
                'booking_source' => $data['booking_source'],
                'booking_source_details' => $data['booking_source_details'] ?? null,
                'status' => $data['status'] ?? 'Confirmed',
                'checkin_date' => $checkin,
                'checkout_date' => $checkout,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId
            ];

            $resId = $this->reservationRepository->createReservation($resData);

            // 3. Save reservation rooms & guests mapping
            foreach ($calculatedRooms as $cRoom) {
                $this->reservationRepository->addRoomToReservation(
                    $resId,
                    $cRoom['room_id'],
                    $cRoom['room_type_id'],
                    $cRoom['price_per_night'],
                    $cRoom['extra_bed_price'],
                    $cRoom['has_extra_bed']
                );

                // Add primary guest and additional guests
                $this->reservationRepository->addGuestToReservationRoom($resId, (int)$data['guest_id'], $cRoom['room_id']);
                foreach ($cRoom['guests'] as $addGuestId) {
                    $this->reservationRepository->addGuestToReservationRoom($resId, (int)$addGuestId, $cRoom['room_id']);
                }
            }

            // 4. Record advance payment if provided
            if (isset($data['advance_payment']) && (float)$data['advance_payment'] > 0.00) {
                $advanceAmt = (float)$data['advance_payment'];
                $payStmt = $this->pdo->prepare('
                    INSERT INTO payments (hotel_id, reservation_id, payment_method, amount, transaction_reference, created_by)
                    VALUES (:hotel_id, :reservation_id, :payment_method, :amount, :transaction_reference, :created_by)
                ');
                $payStmt->execute([
                    ':hotel_id' => $hotelId,
                    ':reservation_id' => $resId,
                    ':payment_method' => $data['payment_method'] ?? 'Cash',
                    ':amount' => $advanceAmt,
                    ':transaction_reference' => $data['transaction_reference'] ?? null,
                    ':created_by' => $userId
                ]);

                $this->auditLogService->log(
                    'payment',
                    (int)$this->pdo->lastInsertId(),
                    $hotelId,
                    'collect_advance',
                    null,
                    ['amount' => $advanceAmt, 'reservation_id' => $resId],
                    $userId
                );
            }

            // 5. Log audit
            $this->auditLogService->log(
                'reservation',
                $resId,
                $hotelId,
                'create',
                null,
                $resData,
                $userId
            );

            $this->pdo->commit();
            return $this->reservationRepository->findReservationById($hotelId, $resId);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function cancelReservation(int $hotelId, int $resId, int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            $res = $this->reservationRepository->findReservationById($hotelId, $resId);
            if (!$res) {
                throw new Exception('Reservation not found or unauthorized.');
            }

            if (in_array($res['status'], ['Cancelled', 'Completed', 'Checked In'])) {
                throw new Exception(sprintf('Cannot cancel reservation with status: %s.', $res['status']));
            }

            $this->reservationRepository->updateReservationStatus($resId, 'Cancelled', $userId);

            $this->auditLogService->log(
                'reservation',
                $resId,
                $hotelId,
                'cancel',
                ['status' => $res['status']],
                ['status' => 'Cancelled'],
                $userId
            );

            $this->pdo->commit();
            $res['status'] = 'Cancelled';
            return $res;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function checkRoomAvailability(int $hotelId, int $roomId, string $checkin, string $checkout): bool
    {
        // Verify room belongs to hotel
        $room = $this->roomRepository->findRoomById($roomId);
        if (!$room || (int)$room['hotel_id'] !== $hotelId) {
            throw new Exception('Room not found or unauthorized.');
        }

        return $this->reservationRepository->isRoomAvailable($roomId, $checkin, $checkout);
    }
}
