<?php

declare(strict_types=1);

namespace App\Validators;

use Exception;

class UserValidator
{
    public static function validateCreate(array $data): void
    {
        if (empty($data['username']) || trim($data['username']) === '') {
            throw new Exception('Username is required.');
        }

        if (strlen($data['username']) < 3) {
            throw new Exception('Username must be at least 3 characters long.');
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            throw new Exception('Password is required and must be at least 6 characters long.');
        }
    }

    public static function validateAccessUpdate(array $data): void
    {
        if (!isset($data['role_ids']) || !is_array($data['role_ids'])) {
            throw new Exception('Role IDs must be a valid array.');
        }

        if (!isset($data['hotel_ids']) || !is_array($data['hotel_ids'])) {
            throw new Exception('Hotel IDs must be a valid array.');
        }
    }
}
