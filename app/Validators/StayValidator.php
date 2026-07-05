<?php

declare(strict_types=1);

namespace App\Validators;

use Exception;

class StayValidator
{
    public static function validateCheckin(array $data): void
    {
        if (empty($data['expected_checkout_at'])) {
            throw new Exception('Expected checkout date/time is required.');
        }

        $expectedCheckout = strtotime($data['expected_checkout_at']);
        if ($expectedCheckout === false) {
            throw new Exception('Invalid expected checkout date/time format.');
        }

        if ($expectedCheckout <= time()) {
            throw new Exception('Expected checkout date must be in the future.');
        }

        // If walk-in, validate rooms & guest
        if (empty($data['reservation_id'])) {
            if (empty($data['rooms']) || !is_array($data['rooms'])) {
                throw new Exception('Rooms selection is required for walk-in check-in.');
            }
            if (empty($data['guest_id'])) {
                throw new Exception('Primary guest ID is required.');
            }
        }
    }

    public static function validateRoomShift(array $data): void
    {
        if (empty($data['new_room_id'])) {
            throw new Exception('Target room ID is required.');
        }

        if (empty($data['reason']) || trim($data['reason']) === '') {
            throw new Exception('A reason must be provided for a room shift.');
        }
    }

    public static function validateFolioItem(array $data): void
    {
        if (empty($data['item_type'])) {
            throw new Exception('Item type is required.');
        }

        $validTypes = ['room_charge', 'extra_bed', 'kitchen_order', 'late_checkout', 'adjustment', 'payment_credit'];
        if (!in_array($data['item_type'], $validTypes)) {
            throw new Exception('Invalid folio item type.');
        }

        if (empty($data['description']) || trim($data['description']) === '') {
            throw new Exception('Description is required.');
        }

        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            throw new Exception('Amount is required and must be numeric.');
        }
    }
}
