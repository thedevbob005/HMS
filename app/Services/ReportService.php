<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReportRepository;
use DateTime;
use DateInterval;
use DatePeriod;
use Exception;

class ReportService
{
    private ReportRepository $reportRepository;

    public function __construct(ReportRepository $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    public function getCollectionReport(int $hotelId, string $startDate, string $endDate): array
    {
        $rows = $this->reportRepository->getDailyCollections($hotelId, $startDate, $endDate);
        
        $grouped = [];
        foreach ($rows as $row) {
            $date = $row['collection_date'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'methods' => [],
                    'day_total' => 0.00,
                    'day_count' => 0
                ];
            }
            $grouped[$date]['methods'][] = [
                'payment_method' => $row['payment_method'],
                'total_amount' => (float)$row['total_amount'],
                'transaction_count' => (int)$row['transaction_count']
              ];
            $grouped[$date]['day_total'] += (float)$row['total_amount'];
            $grouped[$date]['day_count'] += (int)$row['transaction_count'];
        }

        return array_values($grouped);
    }

    public function getOccupancyReport(int $hotelId, string $startDate, string $endDate): array
    {
        $totalRooms = $this->reportRepository->getHotelRoomsCount($hotelId);
        $stayRooms = $this->reportRepository->getActiveStayRoomsForRange($hotelId, $startDate, $endDate);

        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day'); // Include end date

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        $report = [];
        foreach ($period as $dateObj) {
            $dateStr = $dateObj->format('Y-m-d');
            $occupiedCount = 0;

            foreach ($stayRooms as $sr) {
                $checkin = $sr['checkin_date'];
                // If checked out is null, we check expected checkout
                $checkout = $sr['checkout_date'] ?? $sr['expected_checkout_date'];

                if ($checkin <= $dateStr && $checkout >= $dateStr) {
                    $occupiedCount++;
                }
            }

            // Guard: occupancy count cannot exceed total rooms
            if ($totalRooms > 0 && $occupiedCount > $totalRooms) {
                $occupiedCount = $totalRooms;
            }

            $rate = $totalRooms > 0 ? ($occupiedCount / $totalRooms) * 100 : 0.00;

            $report[] = [
                'date' => $dateStr,
                'total_rooms' => $totalRooms,
                'occupied_rooms' => $occupiedCount,
                'occupancy_rate' => round($rate, 2)
            ];
        }

        return array_reverse($report);
    }

    public function getGSTReport(int $hotelId, string $startDate, string $endDate): array
    {
        $rows = $this->reportRepository->getGSTBreakdown($hotelId, $startDate, $endDate);
        
        $report = [];
        $totalTaxable = 0.00;
        $totalTax = 0.00;
        $totalCGST = 0.00;
        $totalSGST = 0.00;

        foreach ($rows as $row) {
            $taxable = (float)$row['taxable_amount'];
            $tax = (float)$row['tax_amount'];
            // In Indian GST model, GST is split 50/50 into CGST (Central GST) and SGST (State GST)
            $cgst = round($tax * 0.5, 2);
            $sgst = round($tax * 0.5, 2);

            $report['breakdown'][] = [
                'item_type' => $row['item_type'],
                'taxable_amount' => $taxable,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'total_tax' => $tax
            ];

            $totalTaxable += $taxable;
            $totalTax += $tax;
            $totalCGST += $cgst;
            $totalSGST += $sgst;
        }

        $report['totals'] = [
            'total_taxable' => $totalTaxable,
            'total_cgst' => $totalCGST,
            'total_sgst' => $totalSGST,
            'total_tax' => $totalTax,
            'total_gross' => $totalTaxable + $totalTax
        ];

        return $report;
    }

    public function getRevenueReport(int $hotelId, string $startDate, string $endDate): array
    {
        $rows = $this->reportRepository->getRevenueByCenter($hotelId, $startDate, $endDate);
        
        $grouped = [];
        foreach ($rows as $row) {
            $date = $row['revenue_date'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'centers' => [],
                    'day_total' => 0.00
                ];
            }
            $grouped[$date]['centers'][] = [
                'item_type' => $row['item_type'],
                'amount' => (float)$row['total_amount']
            ];
            $grouped[$date]['day_total'] += (float)$row['total_amount'];
        }

        return array_values($grouped);
    }
}
