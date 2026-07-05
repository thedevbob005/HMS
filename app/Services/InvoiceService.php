<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InvoiceRepository;
use App\Repositories\StayRepository;
use App\Services\AuditLogService;
use PDO;
use Exception;

class InvoiceService
{
    private InvoiceRepository $invoiceRepository;
    private StayRepository $stayRepository;
    private AuditLogService $auditLogService;
    private PDO $pdo;

    public function __construct(
        InvoiceRepository $invoiceRepository,
        StayRepository $stayRepository,
        AuditLogService $auditLogService,
        PDO $pdo
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->stayRepository = $stayRepository;
        $this->auditLogService = $auditLogService;
        $this->pdo = $pdo;
    }

    public function generateInvoiceForStay(int $hotelId, int $stayId, int $userId): array
    {
        $stay = $this->stayRepository->findStayById($hotelId, $stayId);
        if (!$stay) {
            throw new Exception('Stay not found or unauthorized.');
        }

        // Verify if invoice already exists
        $existing = $this->invoiceRepository->findInvoiceByStayId($hotelId, $stayId);
        if ($existing) {
            return $existing;
        }

        $folioItems = $stay['folio'];
        $taxCalculations = $this->calculateGst($folioItems);

        $invoiceNum = $this->invoiceRepository->getNextInvoiceNumber($hotelId);

        $invoiceData = [
            'hotel_id' => $hotelId,
            'stay_id' => $stayId,
            'invoice_number' => $invoiceNum,
            'guest_id' => (int)$stay['guest_id'],
            'subtotal' => $taxCalculations['subtotal'],
            'cgst' => $taxCalculations['cgst'],
            'sgst' => $taxCalculations['sgst'],
            'igst' => $taxCalculations['igst'],
            'discount' => $taxCalculations['discount'],
            'total_amount' => $taxCalculations['total_amount'],
            'status' => 'Paid',
            'created_by' => $userId
        ];

        $invoiceId = $this->invoiceRepository->createInvoice($invoiceData);

        // Update folio items to store invoice reference
        $stmtUpdateFolio = $this->pdo->prepare('
            UPDATE folio_items 
            SET tax_amount = :tax_amount 
            WHERE id = :id
        ');

        foreach ($folioItems as $item) {
            if (isset($taxCalculations['item_taxes'][$item['id']])) {
                $stmtUpdateFolio->execute([
                    ':tax_amount' => $taxCalculations['item_taxes'][$item['id']],
                    ':id' => $item['id']
                ]);
            }
        }

        $this->auditLogService->log(
            'invoice',
            $invoiceId,
            $hotelId,
            'generate_invoice',
            null,
            $invoiceData,
            $userId
        );

        return array_merge(['id' => $invoiceId], $invoiceData);
    }

    public function calculateGst(array $folioItems): array
    {
        $subtotal = 0.00;
        $cgst = 0.00;
        $sgst = 0.00;
        $igst = 0.00;
        $discount = 0.00;
        $itemTaxes = [];

        foreach ($folioItems as $item) {
            $itemType = $item['item_type'];
            $amount = (float)$item['amount'];

            // Skip payments credits
            if ($itemType === 'payment_credit') {
                continue;
            }

            if ($itemType === 'adjustment') {
                if ($amount < 0) {
                    $discount += abs($amount);
                } else {
                    $subtotal += $amount;
                }
                continue;
            }

            // Charges carry GST
            $subtotal += $amount;

            $cgstRate = 0.00;
            $sgstRate = 0.00;

            if ($itemType === 'room_charge') {
                // If nightly tariff <= 7500 -> 12% GST, else 18% GST
                if ($amount <= 7500.00) {
                    $cgstRate = 0.06;
                    $sgstRate = 0.06;
                } else {
                    $cgstRate = 0.09;
                    $sgstRate = 0.09;
                }
            } else {
                // Services (extra_bed, kitchen_order, late_checkout) -> 18% GST
                $cgstRate = 0.09;
                $sgstRate = 0.09;
            }

            $itemCgst = round($amount * $cgstRate, 2);
            $itemSgst = round($amount * $sgstRate, 2);

            $cgst += $itemCgst;
            $sgst += $itemSgst;

            $itemTaxes[$item['id']] = $itemCgst + $itemSgst;
        }

        $totalAmount = $subtotal + $cgst + $sgst + $igst - $discount;

        return [
            'subtotal' => $subtotal,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'discount' => $discount,
            'total_amount' => $totalAmount,
            'item_taxes' => $itemTaxes
        ];
    }

    public function listInvoices(int $hotelId, array $filters = []): array
    {
        return $this->invoiceRepository->findAllInvoices($hotelId, $filters);
    }

    public function getInvoice(int $hotelId, int $invoiceId): ?array
    {
        return $this->invoiceRepository->findInvoiceById($hotelId, $invoiceId);
    }

    public function getInvoiceByStayId(int $hotelId, int $stayId): ?array
    {
        return $this->invoiceRepository->findInvoiceByStayId($hotelId, $stayId);
    }
}
