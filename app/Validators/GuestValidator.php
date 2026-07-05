<?php

declare(strict_types=1);

namespace App\Validators;

use Exception;

class GuestValidator
{
    public static function validateGuest(array $data): void
    {
        if (empty($data['first_name']) || trim($data['first_name']) === '') {
            throw new Exception('First name is required.');
        }

        if (empty($data['last_name']) || trim($data['last_name']) === '') {
            throw new Exception('Last name is required.');
        }

        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address format.');
            }
        }

        if (!empty($data['phone'])) {
            if (!preg_match('/^\+?[0-9]{7,15}$/', $data['phone'])) {
                throw new Exception('Invalid phone number format.');
            }
        }
    }

    public static function validateDocument(array $data): void
    {
        if (empty($data['document_type'])) {
            throw new Exception('Document type is required.');
        }

        $validTypes = ['Aadhaar', 'Passport', 'Driving License', 'Voter ID'];
        if (!in_array($data['document_type'], $validTypes)) {
            throw new Exception('Invalid document type. Allowed: Aadhaar, Passport, Driving License, Voter ID.');
        }

        if (empty($data['document_number']) || trim($data['document_number']) === '') {
            throw new Exception('Document number is required.');
        }

        $docType = $data['document_type'];
        $docNum = trim($data['document_number']);

        if ($docType === 'Aadhaar') {
            // Clean non-numeric characters for check
            $cleanAadhaar = preg_replace('/[^0-9]/', '', $docNum);
            if (strlen($cleanAadhaar) !== 12) {
                throw new Exception('Aadhaar number must be exactly 12 digits.');
            }
        }
    }

    public static function maskDocumentNumber(string $type, string $number): string
    {
        $number = trim($number);
        if ($type === 'Aadhaar') {
            $clean = preg_replace('/[^0-9]/', '', $number);
            return 'XXXX-XXXX-' . substr($clean, -4);
        }

        if (strlen($number) <= 4) {
            return $number;
        }

        // Mask all but last 4 characters for others too
        return str_repeat('X', strlen($number) - 4) . substr($number, -4);
    }
}
