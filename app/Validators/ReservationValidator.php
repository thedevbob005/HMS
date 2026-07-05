<?php

declare(strict_types=1);

namespace App\Validators;

use Exception;

class ReservationValidator
{
    public static function validateReservation(array $data): void
    {
        if (empty($data['guest_id'])) {
            throw new Exception('Primary guest ID is required.');
        }

        if (empty($data['checkin_date']) || empty($data['checkout_date'])) {
            throw new Exception('Check-in and check-out dates are required.');
        }

        $checkin = strtotime($data['checkin_date']);
        $checkout = strtotime($data['checkout_date']);

        if ($checkin === false || $checkout === false) {
            throw new Exception('Invalid check-in or check-out date format.');
        }

        if ($checkin >= $checkout) {
            throw new Exception('Check-in date must be strictly before check-out date.');
        }

        if (empty($data['booking_source'])) {
            throw new Exception('Booking source is required.');
        }

        $validSources = ['Walk-in', 'Phone', 'Other'];
        if (!in_array($data['booking_source'], $validSources)) {
            throw new Exception('Invalid booking source. Allowed: Walk-in, Phone, Other.');
        }

        if (empty($data['rooms']) || !is_array($data['rooms'])) {
            throw new Exception('At least one room selection is required to book a reservation.');
        }

        foreach ($data['rooms'] as $room) {
            if (empty($room['room_id'])) {
                throw new Exception('Each selected room must specify a room_id.');
            }
        }
    }
}
