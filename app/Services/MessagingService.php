<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MessageQueueRepository;
use App\Services\AuditLogService;
use Exception;

class MessagingService
{
    private MessageQueueRepository $queueRepository;
    private AuditLogService $auditLogService;

    public function __construct(
        MessageQueueRepository $queueRepository,
        AuditLogService $auditLogService
    ) {
        $this->queueRepository = $queueRepository;
        $this->auditLogService = $auditLogService;
    }

    public function enqueueNotification(int $hotelId, string $channel, string $recipient, string $type, array $variables): int
    {
        $data = [
            'hotel_id' => $hotelId,
            'channel' => $channel, // SMS, WhatsApp
            'recipient' => $recipient,
            'message_type' => $type,
            'variables' => $variables,
            'status' => 'queued'
        ];

        return $this->queueRepository->enqueue($data);
    }

    public function processQueue(int $limit = 10): array
    {
        $pending = $this->queueRepository->fetchPending($limit);
        $results = [];

        $authKey = $_ENV['MSG91_AUTH_KEY'] ?? getenv('MSG91_AUTH_KEY') ?: null;

        foreach ($pending as $item) {
            $id = (int)$item['id'];
            $hotelId = (int)$item['hotel_id'];
            $recipient = $item['recipient'];
            $type = $item['message_type'];
            $variables = json_decode($item['variables'] ?? '{}', true);
            $retryCount = (int)$item['retry_count'];

            $body = $this->resolveMessageTemplate($type, $variables);

            try {
                if ($authKey === null) {
                    // MOCK MODE
                    $providerResponse = json_encode([
                        'success' => true,
                        'message' => 'Message successfully mocked (No MSG91 API key provided)',
                        'body' => $body
                    ]);
                    $this->queueRepository->updateStatus($id, 'sent', $retryCount, null, $providerResponse);
                    $results[] = ['id' => $id, 'status' => 'sent', 'mode' => 'mock'];
                } else {
                    // REAL MSG91 CALL
                    $response = $this->sendMsg91Request($authKey, $item['channel'], $recipient, $body, $type, $variables);
                    if ($response['success']) {
                        $this->queueRepository->updateStatus($id, 'sent', $retryCount, null, $response['raw']);
                        $results[] = ['id' => $id, 'status' => 'sent', 'mode' => 'api'];
                    } else {
                        $newRetry = $retryCount + 1;
                        $status = $newRetry >= 3 ? 'failed' : 'queued';
                        $this->queueRepository->updateStatus($id, $status, $newRetry, $response['error'], $response['raw']);
                        $results[] = ['id' => $id, 'status' => $status, 'mode' => 'api', 'error' => $response['error']];
                    }
                }
            } catch (Exception $e) {
                $newRetry = $retryCount + 1;
                $status = $newRetry >= 3 ? 'failed' : 'queued';
                $this->queueRepository->updateStatus($id, $status, $newRetry, $e->getMessage(), null);
                $results[] = ['id' => $id, 'status' => $status, 'mode' => 'exception', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    private function resolveMessageTemplate(string $type, array $vars): string
    {
        $guestName = $vars['guest_name'] ?? 'Guest';
        $hotelName = $vars['hotel_name'] ?? 'our Hotel';
        $rooms = $vars['rooms'] ?? '';
        $checkin = $vars['checkin_date'] ?? '';
        $checkout = $vars['checkout_date'] ?? '';
        $resId = $vars['reservation_id'] ?? '';
        $stayId = $vars['stay_id'] ?? '';
        $amount = isset($vars['amount']) ? number_format((float)$vars['amount'], 2) : '0.00';
        $invNum = $vars['invoice_number'] ?? '';

        switch ($type) {
            case 'booking_confirm':
                return "Hello {$guestName}, your booking at {$hotelName} for Room {$rooms} from {$checkin} to {$checkout} is confirmed! Ref: #{$resId}.";
            case 'booking_cancel':
                return "Hello {$guestName}, your booking Ref: #{$resId} at {$hotelName} has been cancelled.";
            case 'checkin_confirm':
                return "Hello {$guestName}, welcome to {$hotelName}! You have checked in to Room {$rooms}. Stay ID: #{$stayId}.";
            case 'checkout_receipt':
                return "Hello {$guestName}, thank you for staying at {$hotelName}. Your invoice {$invNum} is settled for {$amount} INR. See you again!";
            case 'payment_reminder':
                return "Hello {$guestName}, this is a reminder for an outstanding balance of {$amount} INR at {$hotelName}.";
            default:
                return "Hello {$guestName}, update regarding stay at {$hotelName}.";
        }
    }

    private function sendMsg91Request(string $authKey, string $channel, string $recipient, string $body, string $type, array $vars): array
    {
        $url = 'https://control.msg91.com/api/v5/flow/';
        
        // Define simple flow payload based on template registration guidelines
        $payload = [
            'template_id' => $_ENV['MSG91_TEMPLATE_' . strtoupper($type)] ?? 'default_template_id',
            'sender' => $_ENV['MSG91_SENDER_ID'] ?? 'HMSAPP',
            'short_url' => '0',
            'recipients' => [
                [
                    'mobiles' => $recipient,
                    'var1' => $body
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'authkey: ' . $authKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'raw' => null
            ];
        }

        $resData = json_decode((string)$response, true);
        $success = ($httpCode === 200 && isset($resData['type']) && $resData['type'] === 'success');

        return [
            'success' => $success,
            'error' => !$success ? ($resData['message'] ?? 'API response type failure') : null,
            'raw' => $response
        ];
    }

    public function listLogs(int $hotelId, array $filters = []): array
    {
        return $this->queueRepository->listMessages($hotelId, $filters);
    }
}
