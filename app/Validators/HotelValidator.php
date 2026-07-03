<?php

declare(strict_types=1);

namespace App\Validators;

use Exception;

class HotelValidator
{
    public static function validateGroup(array $data): void
    {
        if (empty($data['name']) || trim($data['name']) === '') {
            throw new Exception('Hotel group name is required.');
        }
    }

    public static function validateHotel(array $data): void
    {
        if (empty($data['name']) || trim($data['name']) === '') {
            throw new Exception('Hotel name is required.');
        }

        if (empty($data['hotel_group_id'])) {
            throw new Exception('Hotel group ID is required.');
        }
    }
}
