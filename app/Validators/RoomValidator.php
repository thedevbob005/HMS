<?php

declare(strict_types=1);

namespace App\Validators;

use Exception;

class RoomValidator
{
    public static function validateRoomType(array $data): void
    {
        if (empty($data['name']) || trim($data['name']) === '') {
            throw new Exception('Room type name is required.');
        }

        if (!isset($data['base_price']) || (float)$data['base_price'] <= 0.00) {
            throw new Exception('Base price is required and must be greater than zero.');
        }

        if (isset($data['max_occupancy']) && (int)$data['max_occupancy'] <= 0) {
            throw new Exception('Max occupancy must be at least 1.');
        }
    }

    public static function validateRoom(array $data): void
    {
        if (empty($data['room_number']) || trim($data['room_number']) === '') {
            throw new Exception('Room number is required.');
        }

        if (empty($data['room_type_id'])) {
            throw new Exception('Room type ID is required.');
        }

        if (isset($data['floor']) && (int)$data['floor'] < 0) {
            throw new Exception('Floor number cannot be negative.');
        }
    }

    public static function validateStatusChange(array $data): void
    {
        if (empty($data['status'])) {
            throw new Exception('Status field is required.');
        }

        $newStatus = trim($data['status']);
        if (in_array($newStatus, ['Maintenance', 'Blocked']) && (empty($data['reason']) || trim($data['reason']) === '')) {
            throw new Exception(sprintf('A reason must be provided when placing a room in %s status.', $newStatus));
        }
    }

    public static function validateRateRules(array $data): void
    {
        if (isset($data['seasonal_rates'])) {
            if (!is_array($data['seasonal_rates'])) {
                throw new Exception('Seasonal rates must be an array.');
            }
            foreach ($data['seasonal_rates'] as $rule) {
                if (empty($rule['start_date']) || empty($rule['end_date'])) {
                    throw new Exception('Seasonal rules require start_date and end_date.');
                }
                if (!isset($rule['rate']) || (float)$rule['rate'] <= 0.00) {
                    throw new Exception('Seasonal rates require a rate greater than zero.');
                }
            }
        }

        if (isset($data['holiday_rates'])) {
            if (!is_array($data['holiday_rates'])) {
                throw new Exception('Holiday rates must be an array.');
            }
            foreach ($data['holiday_rates'] as $rule) {
                if (empty($rule['date'])) {
                    throw new Exception('Holiday rules require a date.');
                }
                if (!isset($rule['rate']) || (float)$rule['rate'] <= 0.00) {
                    throw new Exception('Holiday rates require a rate greater than zero.');
                }
            }
        }
    }
}
