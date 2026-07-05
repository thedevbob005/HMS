<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\IdentityVerificationRepository;
use App\Repositories\GuestRepository;
use App\Services\AuditLogService;
use PDO;
use Exception;

class AadhaarVerificationService
{
    private IdentityVerificationRepository $verificationRepository;
    private GuestRepository $guestRepository;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(
        IdentityVerificationRepository $verificationRepository,
        GuestRepository $guestRepository,
        AuditLogService $auditLogService,
        PDO $pdo
    ) {
        $this->verificationRepository = $verificationRepository;
        $this->guestRepository = $guestRepository;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    public function requestOtp(int $hotelId, int $guestId, int $docId, int $userId): array
    {
        $guest = $this->guestRepository->findGuestById($hotelId, $guestId);
        if (!$guest) {
            throw new Exception('Guest not found or unauthorized.');
        }

        $doc = $this->findDocumentByIdAndGuestId($docId, $guestId);
        if (!$doc) {
            throw new Exception('Identity document not found.');
        }

        if (strcasecmp($doc['document_type'], 'Aadhaar') !== 0) {
            throw new Exception('OTP verification is only supported for Aadhaar cards.');
        }

        $aadhaarNum = $this->decrypt($doc['document_number_encrypted']);
        if (!preg_match('/^\d{12}$/', $aadhaarNum)) {
            throw new Exception('Aadhaar number must be exactly 12 numeric digits.');
        }

        $apiKey = $_ENV['SANDBOX_API_KEY'] ?? getenv('SANDBOX_API_KEY') ?: null;
        $baseUrl = $_ENV['SANDBOX_BASE_URL'] ?? getenv('SANDBOX_BASE_URL') ?: 'https://api.sandbox.co.in';

        $clientId = '';
        $message = '';
        $mode = '';

        if ($apiKey === null) {
            // MOCK MODE
            $mode = 'mock';
            $clientId = 'mock-client-' . bin2hex(random_bytes(8));
            $message = 'Mock OTP successfully sent to linked mobile. Enter 123456 to verify.';
        } else {
            // REAL SANDBOX.CO.IN CALL
            $mode = 'api';
            $url = rtrim($baseUrl, '/') . '/kyc/aadhaar/okyc/otp';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['aadhaar_number' => $aadhaarNum]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: ' . $apiKey,
                'x-api-key: ' . $apiKey,
                'Content-Type: application/json',
                'accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception('Sandbox API network error: ' . $error);
            }

            $resData = json_decode((string)$response, true);
            
            if ($httpCode === 200 && isset($resData['data']['client_id'])) {
                $clientId = $resData['data']['client_id'];
                $message = $resData['message'] ?? 'OTP sent successfully.';
            } else {
                $errMsg = $resData['message'] ?? 'Failed to request OTP from Sandbox.';
                
                // Log failed attempt
                $this->verificationRepository->createLog([
                    'hotel_id' => $hotelId,
                    'guest_id' => $guestId,
                    'document_id' => $docId,
                    'action' => 'otp_request_failed',
                    'status' => 'failure',
                    'provider' => 'sandbox.co.in',
                    'reference_id' => null,
                    'details_json' => $response,
                    'created_by' => $userId
                ]);

                throw new Exception($errMsg);
            }
        }

        // Update document status to pending
        $stmtUpdate = $this->pdo->prepare('
            UPDATE guest_identity_documents 
            SET verification_status = :status, updated_by = :userId 
            WHERE id = :id
        ');
        $stmtUpdate->execute([
            ':status' => 'pending',
            ':userId' => $userId,
            ':id' => $docId
        ]);

        // Insert log record
        $this->verificationRepository->createLog([
            'hotel_id' => $hotelId,
            'guest_id' => $guestId,
            'document_id' => $docId,
            'action' => 'otp_requested',
            'status' => 'success',
            'provider' => 'sandbox.co.in',
            'reference_id' => $clientId,
            'details_json' => json_encode(['mode' => $mode, 'message' => $message]),
            'created_by' => $userId
        ]);

        return [
            'success' => true,
            'client_id' => $clientId,
            'message' => $message,
            'mode' => $mode
        ];
    }

    public function verifyOtp(int $hotelId, int $guestId, int $docId, string $otp, string $clientId, int $userId): array
    {
        $guest = $this->guestRepository->findGuestById($hotelId, $guestId);
        if (!$guest) {
            throw new Exception('Guest not found or unauthorized.');
        }

        $doc = $this->findDocumentByIdAndGuestId($docId, $guestId);
        if (!$doc) {
            throw new Exception('Identity document not found.');
        }

        if ($doc['verification_status'] !== 'pending') {
            throw new Exception('Document verification status is not pending. Request OTP first.');
        }

        $apiKey = $_ENV['SANDBOX_API_KEY'] ?? getenv('SANDBOX_API_KEY') ?: null;
        $baseUrl = $_ENV['SANDBOX_BASE_URL'] ?? getenv('SANDBOX_BASE_URL') ?: 'https://api.sandbox.co.in';

        $verifiedData = [];
        $rawResponse = '';
        $mode = '';

        if ($apiKey === null) {
            // MOCK MODE
            $mode = 'mock';
            if ($otp !== '123456') {
                $this->verificationRepository->createLog([
                    'hotel_id' => $hotelId,
                    'guest_id' => $guestId,
                    'document_id' => $docId,
                    'action' => 'otp_verify_failed',
                    'status' => 'failure',
                    'provider' => 'sandbox.co.in',
                    'reference_id' => $clientId,
                    'details_json' => json_encode(['error' => 'Incorrect OTP code']),
                    'created_by' => $userId
                ]);
                throw new Exception('Incorrect OTP code. In mock mode, use 123456.');
            }

            $verifiedData = [
                'full_name' => 'Michael Scott',
                'gender' => 'Male',
                'dob' => '1964-03-15',
                'address' => 'Scranton, Pennsylvania, USA'
            ];
            $rawResponse = json_encode(['success' => true, 'mock' => true, 'data' => $verifiedData]);
        } else {
            // REAL SANDBOX.CO.IN CALL
            $mode = 'api';
            $url = rtrim($baseUrl, '/') . '/kyc/aadhaar/okyc/otp/verify';
            
            $payload = [
                'client_id' => $clientId,
                'otp' => $otp
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: ' . $apiKey,
                'x-api-key: ' . $apiKey,
                'Content-Type: application/json',
                'accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception('Sandbox API network error: ' . $error);
            }

            $rawResponse = (string)$response;
            $resData = json_decode($rawResponse, true);

            if ($httpCode === 200 && isset($resData['data']['full_name'])) {
                $verifiedData = [
                    'full_name' => $resData['data']['full_name'],
                    'gender' => $resData['data']['gender'] ?? 'N/A',
                    'dob' => $resData['data']['dob'] ?? 'N/A',
                    'address' => $resData['data']['address'] ?? 'N/A'
                ];
            } else {
                $errMsg = $resData['message'] ?? 'Failed to verify OTP with Sandbox.';
                
                $this->verificationRepository->createLog([
                    'hotel_id' => $hotelId,
                    'guest_id' => $guestId,
                    'document_id' => $docId,
                    'action' => 'otp_verify_failed',
                    'status' => 'failure',
                    'provider' => 'sandbox.co.in',
                    'reference_id' => $clientId,
                    'details_json' => $rawResponse,
                    'created_by' => $userId
                ]);

                throw new Exception($errMsg);
            }
        }

        // OTP verified successfully
        $this->pdo->beginTransaction();
        try {
            // 1. Update guest name to match verified name from Sandbox
            $nameParts = explode(' ', $verifiedData['full_name'], 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            $stmtUpdateGuest = $this->pdo->prepare('
                UPDATE guests 
                SET first_name = :first_name, last_name = :last_name, updated_by = :userId 
                WHERE id = :id
            ');
            $stmtUpdateGuest->execute([
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':userId' => $userId,
                ':id' => $guestId
            ]);

            // 2. Update document verification details
            $stmtUpdateDoc = $this->pdo->prepare('
                UPDATE guest_identity_documents 
                SET is_verified = 1, verification_status = :status, verification_timestamp = NOW(), 
                    provider_reference_id = :refId, verification_metadata = :meta, updated_by = :userId
                WHERE id = :id
            ');
            $stmtUpdateDoc->execute([
                ':status' => 'verified',
                ':refId' => $clientId,
                ':meta' => json_encode($verifiedData),
                ':userId' => $userId,
                ':id' => $docId
            ]);

            // 3. Create success verification log
            $this->verificationRepository->createLog([
                'hotel_id' => $hotelId,
                'guest_id' => $guestId,
                'document_id' => $docId,
                'action' => 'otp_verified',
                'status' => 'success',
                'provider' => 'sandbox.co.in',
                'reference_id' => $clientId,
                'details_json' => $rawResponse,
                'created_by' => $userId
            ]);

            // 4. Record audit trail
            $this->auditLogService->log(
                'guest_identity_document',
                $docId,
                $hotelId,
                'verify',
                ['verification_status' => $doc['verification_status']],
                ['verification_status' => 'verified', 'verified_name' => $verifiedData['full_name']],
                $userId
            );

            $this->pdo->commit();
            return [
                'success' => true,
                'message' => 'Guest identity verified successfully.',
                'verified_name' => $verifiedData['full_name'],
                'mode' => $mode
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function applyManualFallback(int $hotelId, int $guestId, int $docId, string $reason, int $userId): array
    {
        $guest = $this->guestRepository->findGuestById($hotelId, $guestId);
        if (!$guest) {
            throw new Exception('Guest not found or unauthorized.');
        }

        $doc = $this->findDocumentByIdAndGuestId($docId, $guestId);
        if (!$doc) {
            throw new Exception('Identity document not found.');
        }

        if (empty(trim($reason))) {
            throw new Exception('Manual override reason is required.');
        }

        $this->pdo->beginTransaction();
        try {
            $meta = [
                'manual_fallback_reason' => $reason,
                'overridden_by' => $userId
            ];

            // 1. Update document
            $stmtUpdateDoc = $this->pdo->prepare('
                UPDATE guest_identity_documents 
                SET is_verified = 1, verification_status = :status, verification_timestamp = NOW(), 
                    verification_metadata = :meta, updated_by = :userId
                WHERE id = :id
            ');
            $stmtUpdateDoc->execute([
                ':status' => 'manual_fallback',
                ':meta' => json_encode($meta),
                ':userId' => $userId,
                ':id' => $docId
            ]);

            // 2. Create verification log
            $this->verificationRepository->createLog([
                'hotel_id' => $hotelId,
                'guest_id' => $guestId,
                'document_id' => $docId,
                'action' => 'manual_fallback',
                'status' => 'success',
                'provider' => 'manual',
                'reference_id' => null,
                'details_json' => json_encode($meta),
                'created_by' => $userId
            ]);

            // 3. Record audit trail
            $this->auditLogService->log(
                'guest_identity_document',
                $docId,
                $hotelId,
                'manual_fallback_used',
                ['verification_status' => $doc['verification_status']],
                ['verification_status' => 'manual_fallback', 'reason' => $reason],
                $userId
            );

            $this->pdo->commit();
            return [
                'success' => true,
                'message' => 'Manual verification override applied successfully.',
                'status' => 'manual_fallback'
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listLogs(int $docId): array
    {
        return $this->verificationRepository->findLogsByDocumentId($docId);
    }

    private function findDocumentByIdAndGuestId(int $docId, int $guestId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM guest_identity_documents 
            WHERE id = :id AND guest_id = :guest_id
            LIMIT 1
        ');
        $stmt->execute([':id' => $docId, ':guest_id' => $guestId]);
        $res = $stmt->fetch();
        return $res ? $res : null;
    }

    // AES-256-GCM Secure Decryption helper
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
