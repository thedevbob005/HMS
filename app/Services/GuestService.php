<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GuestRepository;
use App\Services\AuditLogService;
use App\Validators\GuestValidator;
use PDO;
use Exception;

class GuestService
{
    private GuestRepository $guestRepository;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(GuestRepository $guestRepository, AuditLogService $auditLogService, PDO $pdo)
    {
        $this->guestRepository = $guestRepository;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    public function listGuests(int $hotelId, array $filters = []): array
    {
        return $this->guestRepository->findAllGuests($hotelId, $filters);
    }

    public function getGuest(int $hotelId, int $guestId): ?array
    {
        return $this->guestRepository->findGuestById($hotelId, $guestId);
    }

    public function createGuest(int $hotelId, array $data, int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            GuestValidator::validateGuest($data);
            $data['hotel_id'] = $hotelId;
            $data['created_by'] = $userId;

            $guestId = $this->guestRepository->createGuest($data);

            $this->auditLogService->log(
                'guest',
                $guestId,
                $hotelId,
                'create',
                null,
                $data,
                $userId
            );

            $this->pdo->commit();
            return array_merge(['id' => $guestId], $data);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateGuest(int $hotelId, int $guestId, array $data, int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            GuestValidator::validateGuest($data);
            $guest = $this->guestRepository->findGuestById($hotelId, $guestId);
            if (!$guest) {
                throw new Exception('Guest not found or unauthorized.');
            }

            $data['updated_by'] = $userId;
            $this->guestRepository->updateGuest($guestId, $data);

            $this->auditLogService->log(
                'guest',
                $guestId,
                $hotelId,
                'update',
                $guest,
                $data,
                $userId
            );

            $this->pdo->commit();
            return array_merge(['id' => $guestId], $data);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listDocuments(int $hotelId, int $guestId): array
    {
        // Verify guest belongs to this hotel
        $guest = $this->guestRepository->findGuestById($hotelId, $guestId);
        if (!$guest) {
            throw new Exception('Guest not found or unauthorized.');
        }

        return $this->guestRepository->findDocuments($guestId);
    }

    public function uploadIdentityDocument(int $hotelId, int $guestId, array $data, ?array $uploadedFile, int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            GuestValidator::validateDocument($data);
            
            $guest = $this->guestRepository->findGuestById($hotelId, $guestId);
            if (!$guest) {
                throw new Exception('Guest not found or unauthorized.');
            }

            $documentType = $data['document_type'];
            $docNumber = trim($data['document_number']);
            $masked = GuestValidator::maskDocumentNumber($documentType, $docNumber);
            $encrypted = $this->encrypt($docNumber);

            $filePath = null;
            if ($uploadedFile && isset($uploadedFile['tmp_name']) && !empty($uploadedFile['tmp_name'])) {
                // Ensure upload folder exists with blocked indexes
                $uploadDir = __DIR__ . '/../../public/uploads/guest_docs/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                    file_put_contents($uploadDir . '.htaccess', "Options -Indexes\nDeny from all\n");
                }

                // Guarded random subfolder
                $randSub = bin2hex(random_bytes(8));
                $targetDir = $uploadDir . $randSub . '/';
                mkdir($targetDir, 0755, true);

                $ext = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
                if (!in_array(strtolower($ext), $allowed)) {
                    throw new Exception('Invalid file format. Allowed: JPG, JPEG, PNG, PDF.');
                }

                $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
                $targetFile = $targetDir . $fileName;

                if (!move_uploaded_file($uploadedFile['tmp_name'], $targetFile)) {
                    throw new Exception('Failed to save uploaded file.');
                }

                // Public URL path (SPA accessible but folder directory lists blocked)
                $filePath = '/uploads/guest_docs/' . $randSub . '/' . $fileName;
            }

            $docData = [
                'guest_id' => $guestId,
                'document_type' => $documentType,
                'document_number_masked' => $masked,
                'document_number_encrypted' => $encrypted,
                'file_path' => $filePath,
                'is_verified' => $data['is_verified'] ?? 0,
                'verification_metadata' => $data['verification_metadata'] ?? null,
                'created_by' => $userId
            ];

            $docId = $this->guestRepository->createDocument($docData);

            $this->auditLogService->log(
                'guest_identity_document',
                $docId,
                $hotelId,
                'add_document',
                null,
                [
                    'document_type' => $documentType,
                    'document_number_masked' => $masked,
                    'file_path' => $filePath
                ],
                $userId
            );

            $this->pdo->commit();
            return array_merge(['id' => $docId], $docData);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function decryptDocumentNumber(int $hotelId, int $guestId, int $documentId, int $userId, string $ipAddress): string
    {
        // Enforce guest belongs to hotel
        $guest = $this->guestRepository->findGuestById($hotelId, $guestId);
        if (!$guest) {
            throw new Exception('Guest not found.');
        }

        // Get document
        $stmt = $this->pdo->prepare('
            SELECT * FROM guest_identity_documents 
            WHERE id = :id AND guest_id = :guest_id
            LIMIT 1
        ');
        $stmt->execute([':id' => $documentId, ':guest_id' => $guestId]);
        $doc = $stmt->fetch();

        if (!$doc) {
            throw new Exception('Document record not found.');
        }

        $decrypted = $this->decrypt($doc['document_number_encrypted']);

        // Log sensitive audit access
        $this->auditLogService->log(
            'guest_identity_document',
            $documentId,
            $hotelId,
            'view_sensitive_identity',
            null,
            ['document_type' => $doc['document_type'], 'accessed_by' => $userId],
            $userId,
            $ipAddress
        );

        return $decrypted;
    }

    // AES-256-GCM Secure Encryption
    private function encrypt(string $data): string
    {
        $key = $_ENV['APP_ENCRYPTION_KEY'] ?? '32_character_long_secret_key_123';
        $method = 'aes-256-gcm';
        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $tag = '';
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv, $tag);
        if ($encrypted === false) {
            throw new Exception('Encryption failed.');
        }
        return base64_encode($iv . $tag . base64_decode($encrypted));
    }

    // AES-256-GCM Decryption
    private function decrypt(string $payload): string
    {
        $key = $_ENV['APP_ENCRYPTION_KEY'] ?? '32_character_long_secret_key_123';
        $method = 'aes-256-gcm';
        $payloadBytes = base64_decode($payload);
        $ivLength = openssl_cipher_iv_length($method);
        if (strlen($payloadBytes) < $ivLength + 16) {
            throw new Exception('Invalid encrypted payload.');
        }
        $iv = substr($payloadBytes, 0, $ivLength);
        $tag = substr($payloadBytes, $ivLength, 16);
        $encryptedData = base64_encode(substr($payloadBytes, $ivLength + 16));
        
        $decrypted = openssl_decrypt($encryptedData, $method, $key, 0, $iv, $tag);
        if ($decrypted === false) {
            throw new Exception('Decryption failed.');
        }
        return $decrypted;
    }
}
