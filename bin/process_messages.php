<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$container = require __DIR__ . '/../config/container.php';

use App\Services\MessagingService;

try {
    $messagingService = $container->get(MessagingService::class);
    
    echo sprintf("[%s] Starting MSG91 messaging queue processor...\n", date('Y-m-d H:i:s'));
    
    $results = $messagingService->processQueue(15);
    
    if (empty($results)) {
        echo "No pending notifications in queue.\n";
    } else {
        foreach ($results as $res) {
            if ($res['status'] === 'sent') {
                echo sprintf(" - Message ID %d successfully sent (Mode: %s)\n", $res['id'], $res['mode']);
            } else {
                echo sprintf(" - Message ID %d failed (Mode: %s). Error: %s\n", $res['id'], $res['mode'], $res['error'] ?? 'Unknown');
            }
        }
    }
    
    echo sprintf("[%s] Process finished.\n", date('Y-m-d H:i:s'));
} catch (Exception $e) {
    echo sprintf("[%s] CRITICAL: Messaging worker failed. Error: %s\n", date('Y-m-d H:i:s'), $e->getMessage());
    exit(1);
}
