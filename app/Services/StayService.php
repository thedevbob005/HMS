<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\StayRepository;
use App\Repositories\RoomRepository;
use App\Repositories\ReservationRepository;
use App\Services\RoomService;
use App\Services\AuditLogService;
use App\Validators\StayValidator;
use PDO;
use Exception;

class StayService
{
    private StayRepository $stayRepository;
    private RoomRepository $roomRepository;
    private ReservationRepository $reservationRepository;
    private RoomService $roomService;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(
        StayRepository $stayRepository,
        RoomRepository $roomRepository,
        ReservationRepository $reservationRepository,
        RoomService $roomService,
        AuditLogService $auditLogService,
        PDO $pdo
    ) {
        $this->stayRepository = $stayRepository;
        $this->roomRepository = $roomRepository;
        $this->reservationRepository = $reservationRepository;
        $this->roomService = $roomService;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    public function listStays(int $hotelId, array $filters = []): array
    {
        return $this->stayRepository->findAllStays($hotelId, $filters);
    }

    public function getStay(int $hotelId, int $stayId): ?array
    {
        return $this->stayRepository->findStayById($hotelId, $stayId);
    }

    public function performCheckin(int $hotelId, array $data, int $userId): array
    {
        StayValidator::validateCheckin($data);

        $this->pdo->beginTransaction();
        try {
            $nowStr = date('Y-m-d H:i:s');
            $expectedCheckoutStr = date('Y-m-d H:i:s', strtotime($data['expected_checkout_at']));

            $stayId = 0;
            $resId = !empty($data['reservation_id']) ? (int)$data['reservation_id'] : null;

            if ($resId !== null) {
                // 1. Check-in from existing reservation
                $res = $this->reservationRepository->findReservationById($hotelId, $resId);
                if (!$res) {
                    throw new Exception('Reservation not found or unauthorized.');
                }

                if ($res['status'] !== 'Confirmed' && $res['status'] !== 'Draft') {
                    throw new Exception(sprintf('Reservation cannot be checked in with status: %s.', $res['status']));
                }

                // Update reservation status
                $this->reservationRepository->updateReservationStatus($resId, 'Checked In', $userId);

                // Create stay
                $stayData = [
                    'hotel_id' => $hotelId,
                    'reservation_id' => $resId,
                    'status' => 'Active',
                    'checkin_at' => $nowStr,
                    'expected_checkout_at' => $expectedCheckoutStr,
                    'notes' => $data['notes'] ?? $res['notes'],
                    'created_by' => $userId
                ];
                $stayId = $this->stayRepository->createStay($stayData);

                // Add rooms & copy guests
                foreach ($res['rooms'] as $resRoom) {
                    $roomId = (int)$resRoom['room_id'];
                    
                    // Add stay room
                    $this->stayRepository->addRoomToStay([
                        'stay_id' => $stayId,
                        'room_id' => $roomId,
                        'room_type_id' => (int)$resRoom['room_type_id'],
                        'price_per_night' => (float)$resRoom['price_per_night'],
                        'extra_bed_price' => (float)$resRoom['extra_bed_price'],
                        'has_extra_bed' => (bool)$resRoom['has_extra_bed'],
                        'checked_in_at' => $nowStr
                    ]);

                    // Set room status to occupied
                    $this->roomRepository->updateRoomStatus($roomId, 'Occupied');
                    $this->roomRepository->logStatusChange($roomId, 'Reserved', 'Occupied', 'Reservation Check-in', $userId);

                    // Copy reservation guests to stay guests
                    $gStmt = $this->pdo->prepare('SELECT guest_id FROM reservation_guests WHERE reservation_id = :res_id AND room_id = :room_id');
                    $gStmt->execute([':res_id' => $resId, ':room_id' => $roomId]);
                    $resGuests = $gStmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($resGuests as $guestId) {
                        $this->stayRepository->addGuestToStayRoom($stayId, (int)$guestId, $roomId);
                    }
                }

                // Link existing reservation payments to the stay, and create folio credit entries
                $pStmt = $this->pdo->prepare('SELECT * FROM payments WHERE reservation_id = :res_id');
                $pStmt->execute([':res_id' => $resId]);
                $payments = $pStmt->fetchAll();

                foreach ($payments as $payment) {
                    // Update payment record to link to stay
                    $upPay = $this->pdo->prepare('UPDATE payments SET stay_id = :stay_id WHERE id = :id');
                    $upPay->execute([':stay_id' => $stayId, ':id' => $payment['id']]);

                    // Post to folio
                    $this->stayRepository->createFolioItem([
                        'hotel_id' => $hotelId,
                        'stay_id' => $stayId,
                        'item_type' => 'payment_credit',
                        'description' => sprintf('Advance Payment (%s)', $payment['payment_method']),
                        'amount' => -((float)$payment['amount']),
                        'reference_type' => 'payments',
                        'reference_id' => (int)$payment['id'],
                        'created_by' => $userId
                    ]);
                }

            } else {
                // 2. Walk-in Check-in
                $stayData = [
                    'hotel_id' => $hotelId,
                    'reservation_id' => null,
                    'status' => 'Active',
                    'checkin_at' => $nowStr,
                    'expected_checkout_at' => $expectedCheckoutStr,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $userId
                ];
                $stayId = $this->stayRepository->createStay($stayData);

                foreach ($data['rooms'] as $roomSelection) {
                    $roomId = (int)$roomSelection['room_id'];
                    $room = $this->roomRepository->findRoomById($roomId);
                    if (!$room || (int)$room['hotel_id'] !== $hotelId) {
                        throw new Exception(sprintf('Room ID %d not found or unauthorized.', $roomId));
                    }

                    // Check availability for requested range
                    $checkinDateOnly = date('Y-m-d', strtotime($nowStr));
                    $checkoutDateOnly = date('Y-m-d', strtotime($expectedCheckoutStr));
                    if (!$this->reservationRepository->isRoomAvailable($roomId, $checkinDateOnly, $checkoutDateOnly)) {
                        throw new Exception(sprintf('Room number %s is not available for the selected dates.', $room['room_number']));
                    }

                    // Calculate price per night
                    $totalPrice = 0.0;
                    $currentDate = $checkinDateOnly;
                    $daysCount = 0;
                    while (strtotime($currentDate) < strtotime($checkoutDateOnly)) {
                        $totalPrice += $this->roomService->calculateRoomRate((int)$room['room_type_id'], $currentDate);
                        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                        $daysCount++;
                    }
                    if ($daysCount === 0) $daysCount = 1; // Minimum 1 night
                    $avgPrice = $totalPrice / $daysCount;

                    $roomType = $this->roomRepository->findRoomTypeById((int)$room['room_type_id']);
                    $extraBedPrice = $roomType ? (float)$roomType['extra_bed_price'] : 0.00;

                    $this->stayRepository->addRoomToStay([
                        'stay_id' => $stayId,
                        'room_id' => $roomId,
                        'room_type_id' => (int)$room['room_type_id'],
                        'price_per_night' => $avgPrice,
                        'extra_bed_price' => $extraBedPrice,
                        'has_extra_bed' => (bool)($roomSelection['has_extra_bed'] ?? false),
                        'checked_in_at' => $nowStr
                    ]);

                    // Set room status to occupied
                    $oldStatus = $room['status'];
                    $this->roomRepository->updateRoomStatus($roomId, 'Occupied');
                    $this->roomRepository->logStatusChange($roomId, $oldStatus, 'Occupied', 'Walk-in Check-in', $userId);

                    // Add guests
                    $this->stayRepository->addGuestToStayRoom($stayId, (int)$data['guest_id'], $roomId);
                    if (isset($roomSelection['guests'])) {
                        foreach ($roomSelection['guests'] as $addGuestId) {
                            $this->stayRepository->addGuestToStayRoom($stayId, (int)$addGuestId, $roomId);
                        }
                    }
                }

                // If immediate advance payment collected
                if (isset($data['advance_payment']) && (float)$data['advance_payment'] > 0.00) {
                    $advanceAmt = (float)$data['advance_payment'];
                    $payId = $this->stayRepository->createPayment([
                        'hotel_id' => $hotelId,
                        'stay_id' => $stayId,
                        'payment_method' => $data['payment_method'] ?? 'Cash',
                        'amount' => $advanceAmt,
                        'transaction_reference' => $data['transaction_reference'] ?? null,
                        'created_by' => $userId
                    ]);

                    $this->stayRepository->createFolioItem([
                        'hotel_id' => $hotelId,
                        'stay_id' => $stayId,
                        'item_type' => 'payment_credit',
                        'description' => sprintf('Advance Payment (%s)', $data['payment_method'] ?? 'Cash'),
                        'amount' => -$advanceAmt,
                        'reference_type' => 'payments',
                        'reference_id' => $payId,
                        'created_by' => $userId
                    ]);
                }
            }

            // Log audit
            $this->auditLogService->log(
                'stay',
                $stayId,
                $hotelId,
                'checkin',
                null,
                ['reservation_id' => $resId, 'status' => 'Active'],
                $userId
            );

            $this->pdo->commit();
            return $this->stayRepository->findStayById($hotelId, $stayId);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function performRoomShift(int $hotelId, int $stayId, array $data, int $userId): void
    {
        StayValidator::validateRoomShift($data);

        $this->pdo->beginTransaction();
        try {
            $stay = $this->stayRepository->findStayById($hotelId, $stayId);
            if (!$stay || $stay['status'] !== 'Active') {
                throw new Exception('Active stay not found or unauthorized.');
            }

            $newRoomId = (int)$data['new_room_id'];
            $reason = trim($data['reason']);

            // Find current active room for this guest stay (assuming 1 room for simplicity, or select room shifting from room)
            $oldRoomId = null;
            $oldStayRoom = null;
            foreach ($stay['rooms'] as $sRoom) {
                if ($sRoom['checked_out_at'] === null) {
                    $oldRoomId = (int)$sRoom['room_id'];
                    $oldStayRoom = $sRoom;
                    break;
                }
            }

            if ($oldRoomId === null) {
                throw new Exception('No active room found in this stay to shift.');
            }

            if ($oldRoomId === $newRoomId) {
                throw new Exception('Source and target rooms must be different.');
            }

            // Check availability for target room
            $checkinDateOnly = date('Y-m-d');
            $expectedCheckoutDateOnly = date('Y-m-d', strtotime($stay['expected_checkout_at']));
            if (!$this->reservationRepository->isRoomAvailable($newRoomId, $checkinDateOnly, $expectedCheckoutDateOnly)) {
                throw new Exception('Target room is not available for the remaining stay dates.');
            }

            // Get target room details
            $newRoom = $this->roomRepository->findRoomById($newRoomId);
            if (!$newRoom || (int)$newRoom['hotel_id'] !== $hotelId) {
                throw new Exception('Target room not found or unauthorized.');
            }

            $nowStr = date('Y-m-d H:i:s');

            // 1. Close old room assignment
            $this->stayRepository->updateStayRoomCheckout((int)$oldStayRoom['id'], $nowStr);

            // 2. Add new room to stay
            $newRoomType = $this->roomRepository->findRoomTypeById((int)$newRoom['room_type_id']);
            $pricePerNight = (float)$oldStayRoom['price_per_night'];
            $extraBedPrice = (float)$oldStayRoom['extra_bed_price'];

            // Offer pricing override if requested
            if (isset($data['price_per_night'])) {
                $pricePerNight = (float)$data['price_per_night'];
            }

            $this->stayRepository->addRoomToStay([
                'stay_id' => $stayId,
                'room_id' => $newRoomId,
                'room_type_id' => (int)$newRoom['room_type_id'],
                'price_per_night' => $pricePerNight,
                'extra_bed_price' => $extraBedPrice,
                'has_extra_bed' => (bool)$oldStayRoom['has_extra_bed'],
                'checked_in_at' => $nowStr
            ]);

            // 3. Move stay guests mapping to new room
            $upGuests = $this->pdo->prepare('UPDATE stay_guests SET room_id = :new_room_id WHERE stay_id = :stay_id AND room_id = :old_room_id');
            $upGuests->execute([
                ':new_room_id' => $newRoomId,
                ':stay_id' => $stayId,
                ':old_room_id' => $oldRoomId
            ]);

            // 4. Update room statuses
            // Old room moves to Cleaning (or custom requested)
            $oldRoomNextStatus = $data['old_room_status'] ?? 'Cleaning';
            $this->roomRepository->updateRoomStatus($oldRoomId, $oldRoomNextStatus);
            $this->roomRepository->logStatusChange($oldRoomId, 'Occupied', $oldRoomNextStatus, 'Room Shift Out', $userId);

            // New room moves to Occupied
            $this->roomRepository->updateRoomStatus($newRoomId, 'Occupied');
            $this->roomRepository->logStatusChange($newRoomId, $newRoom['status'], 'Occupied', 'Room Shift In', $userId);

            // 5. Log Room Shift History
            $this->stayRepository->logRoomShift([
                'stay_id' => $stayId,
                'old_room_id' => $oldRoomId,
                'new_room_id' => $newRoomId,
                'reason' => $reason,
                'shifted_by' => $userId
            ]);

            // Audit log
            $this->auditLogService->log(
                'stay_room_shift',
                $stayId,
                $hotelId,
                'room_shift',
                ['room_id' => $oldRoomId],
                ['room_id' => $newRoomId, 'reason' => $reason],
                $userId
            );

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function postFolioItem(int $hotelId, int $stayId, array $data, int $userId): array
    {
        StayValidator::validateFolioItem($data);

        $stay = $this->stayRepository->findStayById($hotelId, $stayId);
        if (!$stay || $stay['status'] !== 'Active') {
            throw new Exception('Active stay not found or unauthorized.');
        }

        $folioData = [
            'hotel_id' => $hotelId,
            'stay_id' => $stayId,
            'item_type' => $data['item_type'],
            'description' => trim($data['description']),
            'amount' => (float)$data['amount'],
            'tax_amount' => (float)($data['tax_amount'] ?? 0.00),
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'created_by' => $userId
        ];

        $folioId = $this->stayRepository->createFolioItem($folioData);

        $this->auditLogService->log(
            'folio_item',
            $folioId,
            $hotelId,
            'post_charge',
            null,
            $folioData,
            $userId
        );

        return array_merge(['id' => $folioId], $folioData);
    }

    public function collectPayment(int $hotelId, int $stayId, array $data, int $userId): array
    {
        if (empty($data['payment_method']) || !isset($data['amount']) || (float)$data['amount'] <= 0.00) {
            throw new Exception('Valid payment method and positive amount are required.');
        }

        $stay = $this->stayRepository->findStayById($hotelId, $stayId);
        if (!$stay || $stay['status'] !== 'Active') {
            throw new Exception('Active stay not found or unauthorized.');
        }

        $this->pdo->beginTransaction();
        try {
            $paymentAmt = (float)$data['amount'];
            $payId = $this->stayRepository->createPayment([
                'hotel_id' => $hotelId,
                'stay_id' => $stayId,
                'payment_method' => $data['payment_method'],
                'amount' => $paymentAmt,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'created_by' => $userId
            ]);

            $folioItem = $this->stayRepository->createFolioItem([
                'hotel_id' => $hotelId,
                'stay_id' => $stayId,
                'item_type' => 'payment_credit',
                'description' => sprintf('Payment (%s)', $data['payment_method']),
                'amount' => -$paymentAmt,
                'reference_type' => 'payments',
                'reference_id' => $payId,
                'created_by' => $userId
            ]);

            $this->auditLogService->log(
                'payment',
                $payId,
                $hotelId,
                'collect_payment',
                null,
                ['amount' => $paymentAmt, 'stay_id' => $stayId],
                $userId
            );

            $this->pdo->commit();
            return [
                'payment_id' => $payId,
                'folio_item_id' => $folioItem,
                'amount' => $paymentAmt,
                'payment_method' => $data['payment_method']
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function performCheckout(int $hotelId, int $stayId, array $data, int $userId, bool $hasOverridePermission): array
    {
        $this->pdo->beginTransaction();
        try {
            $stay = $this->stayRepository->findStayById($hotelId, $stayId);
            if (!$stay || $stay['status'] !== 'Active') {
                throw new Exception('Active stay not found or unauthorized.');
            }

            $nowStr = date('Y-m-d H:i:s');
            
            // 1. Populate/finalize daily room charges automatically before calculating balance
            $this->generateMissingRoomCharges($hotelId, $stay, $userId);

            // Reload stay after generating charges to get fresh folio
            $stay = $this->stayRepository->findStayById($hotelId, $stayId);

            // 2. Sum up folio balances
            $totalCharges = 0.00;
            $totalPayments = 0.00;
            foreach ($stay['folio'] as $item) {
                $amount = (float)$item['amount'];
                if ($amount > 0) {
                    $totalCharges += $amount;
                } else {
                    $totalPayments += abs($amount);
                }
            }

            $balance = $totalCharges - $totalPayments;

            // 3. Settlement checks
            if (abs($balance) > 0.01) {
                // If remaining balance, checkout is blocked unless overridden
                if (empty($data['override_unsettled']) || !$data['override_unsettled']) {
                    throw new Exception(sprintf('Folio balance is not settled (Outstanding: %.2f). Settlement is required.', $balance));
                }

                if (!$hasOverridePermission) {
                    throw new Exception('You do not have permission to override unsettled charges on checkout.');
                }

                // If override is confirmed, post an adjustment charge/credit to settle it to zero
                $adjustmentAmt = -$balance;
                $this->stayRepository->createFolioItem([
                    'hotel_id' => $hotelId,
                    'stay_id' => $stayId,
                    'item_type' => 'adjustment',
                    'description' => 'Checkout Unsettled Balance Adjustment Override',
                    'amount' => $adjustmentAmt,
                    'created_by' => $userId
                ]);

                $this->auditLogService->log(
                    'folio_item',
                    (int)$this->pdo->lastInsertId(),
                    $hotelId,
                    'adjust_balance_override',
                    null,
                    ['adjustment' => $adjustmentAmt],
                    $userId
                );
            }

            // 4. Update stay status
            $this->stayRepository->updateStayStatus($stayId, 'Completed', $nowStr, $userId);

            // 5. Relieve rooms and set status to 'Cleaning'
            foreach ($stay['rooms'] as $sRoom) {
                if ($sRoom['checked_out_at'] === null) {
                    $this->stayRepository->updateStayRoomCheckout((int)$sRoom['id'], $nowStr);
                    
                    $roomId = (int)$sRoom['room_id'];
                    $this->roomRepository->updateRoomStatus($roomId, 'Cleaning');
                    $this->roomRepository->logStatusChange($roomId, 'Occupied', 'Cleaning', 'Stay Checkout Completion', $userId);
                }
            }

            // 6. Log audit
            $this->auditLogService->log(
                'stay',
                $stayId,
                $hotelId,
                'checkout',
                ['status' => 'Active'],
                ['status' => 'Completed', 'checkout_at' => $nowStr],
                $userId
            );

            $this->pdo->commit();
            return $this->stayRepository->findStayById($hotelId, $stayId);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function generateMissingRoomCharges(int $hotelId, array $stay, int $userId): void
    {
        $folioItems = $stay['folio'];
        
        foreach ($stay['rooms'] as $sRoom) {
            $checkinTime = strtotime($sRoom['checked_in_at']);
            $checkoutTime = $sRoom['checked_out_at'] !== null ? strtotime($sRoom['checked_out_at']) : time();

            $startDate = date('Y-m-d', $checkinTime);
            $endDate = date('Y-m-d', $checkoutTime);

            // Generate daily charges for each day of stay in this room
            $currentDate = $startDate;
            while (strtotime($currentDate) <= strtotime($endDate)) {
                // If they checked out on $currentDate, do not bill for the night of $currentDate (standard hotel rule)
                if (strtotime($currentDate) === strtotime($endDate) && date('H', $checkoutTime) < 12 && $startDate !== $endDate) {
                    break;
                }

                // Check if a room charge for this room on this date already exists in the folio
                $alreadyCharged = false;
                foreach ($folioItems as $fItem) {
                    if ($fItem['item_type'] === 'room_charge' 
                        && $fItem['reference_type'] === 'stay_rooms' 
                        && (int)$fItem['reference_id'] === (int)$sRoom['id']
                        && date('Y-m-d', strtotime($fItem['created_at'])) === $currentDate
                    ) {
                        $alreadyCharged = true;
                        break;
                    }
                }

                if (!$alreadyCharged) {
                    // Post room charge
                    $price = (float)$sRoom['price_per_night'];
                    $this->stayRepository->createFolioItem([
                        'hotel_id' => $hotelId,
                        'stay_id' => (int)$stay['id'],
                        'item_type' => 'room_charge',
                        'description' => sprintf('Room %s Night Charge (%s)', $sRoom['room_number'], $currentDate),
                        'amount' => $price,
                        'reference_type' => 'stay_rooms',
                        'reference_id' => (int)$sRoom['id'],
                        'created_by' => $userId
                    ]);

                    // Post extra bed charge if applicable
                    if ($sRoom['has_extra_bed'] && (float)$sRoom['extra_bed_price'] > 0.00) {
                        $this->stayRepository->createFolioItem([
                            'hotel_id' => $hotelId,
                            'stay_id' => (int)$stay['id'],
                            'item_type' => 'extra_bed',
                            'description' => sprintf('Extra Bed - Room %s (%s)', $sRoom['room_number'], $currentDate),
                            'amount' => (float)$sRoom['extra_bed_price'],
                            'reference_type' => 'stay_rooms',
                            'reference_id' => (int)$sRoom['id'],
                            'created_by' => $userId
                        ]);
                    }
                }

                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }
        }
    }
}
