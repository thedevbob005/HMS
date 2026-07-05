<?php

declare(strict_types=1);

use Slim\App;
use App\Controllers\HealthCheckController;
use App\Controllers\SampleProtectedController;
use App\Controllers\HotelController;
use App\Controllers\UserController;
use App\Controllers\RoomTypeController;
use App\Controllers\RoomController;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\HotelScopingMiddleware;
use App\Controllers\GuestController;
use App\Controllers\ReservationController;
use App\Controllers\StayController;
use App\Controllers\InvoiceController;
use App\Controllers\ReportController;
use App\Controllers\AadhaarVerificationController;
use App\Controllers\HousekeepingController;
use App\Controllers\InventoryController;
use App\Controllers\PurchaseController;

return function (App $app) {
    // Health Check Endpoint
    $app->get('/api/health', HealthCheckController::class);

    // Public authentication endpoint
    $app->post('/api/login', UserController::class . ':login');

    // Public / Protected Global routes (without Hotel Scoping)
    $app->group('/api', function ($group) {
        // Hotel Groups
        $group->get('/hotel-groups', HotelController::class . ':listGroups');
        $group->post('/hotel-groups', HotelController::class . ':createGroup');

        // Hotels
        $group->get('/hotels', HotelController::class . ':listHotels');
        $group->post('/hotels', HotelController::class . ':createHotel');

        // Users & Roles
        $group->get('/users', UserController::class . ':listUsers');
        $group->post('/users', UserController::class . ':createUser');
        $group->post('/users/{userId}/access', UserController::class . ':updateAccess');
        $group->get('/roles', UserController::class . ':listRoles');
    })->add(AuthenticationMiddleware::class);

    // Hotel Scoped Routes (require HotelScopingMiddleware)
    $app->group('/api/hotels/{hotelId}', function ($group) {
        // Sample
        $group->get('/sample', SampleProtectedController::class);

        // Room Types
        $group->get('/room-types', RoomTypeController::class . ':listRoomTypes');
        $group->post('/room-types', RoomTypeController::class . ':createRoomType');
        $group->post('/room-types/{roomTypeId}/rates', RoomTypeController::class . ':configureRates');
        $group->get('/room-types/{roomTypeId}/calculate-rate', RoomTypeController::class . ':calculateRate');

        // Rooms
        $group->get('/rooms', RoomController::class . ':listRooms');
        $group->post('/rooms', RoomController::class . ':createRoom');
        $group->post('/rooms/{roomId}/status', RoomController::class . ':changeStatus');

        // Guests
        $group->get('/guests', GuestController::class . ':listGuests');
        $group->post('/guests', GuestController::class . ':createGuest');
        $group->get('/guests/{guestId}', GuestController::class . ':getGuest');
        $group->get('/guests/{guestId}/documents', GuestController::class . ':listDocuments');
        $group->post('/guests/{guestId}/documents', GuestController::class . ':uploadDocument');
        $group->get('/guests/{guestId}/documents/{docId}/decrypt', GuestController::class . ':decryptDocumentNumber');
        $group->post('/guests/{guestId}/documents/{docId}/verify/otp-request', AadhaarVerificationController::class . ':requestOtp');
        $group->post('/guests/{guestId}/documents/{docId}/verify/otp-submit', AadhaarVerificationController::class . ':verifyOtp');
        $group->post('/guests/{guestId}/documents/{docId}/verify/manual-fallback', AadhaarVerificationController::class . ':applyManualFallback');
        $group->get('/guests/{guestId}/documents/{docId}/verification-logs', AadhaarVerificationController::class . ':listLogs');

        // Reservations
        $group->get('/reservations', ReservationController::class . ':listReservations');
        $group->post('/reservations', ReservationController::class . ':createReservation');
        $group->get('/reservations/{resId}', ReservationController::class . ':getReservation');
        $group->post('/reservations/{resId}/cancel', ReservationController::class . ':cancelReservation');
        $group->get('/rooms/{roomId}/check-availability', ReservationController::class . ':checkAvailability');

        // Stays
        $group->post('/stays/check-in', StayController::class . ':checkin');
        $group->get('/stays', StayController::class . ':listStays');
        $group->get('/stays/{stayId}', StayController::class . ':getStay');
        $group->post('/stays/{stayId}/room-shift', StayController::class . ':roomShift');
        $group->post('/stays/{stayId}/folio', StayController::class . ':postFolioItem');
        $group->post('/stays/{stayId}/payments', StayController::class . ':collectPayment');
        $group->post('/stays/{stayId}/check-out', StayController::class . ':checkout');

        // Invoices
        $group->get('/invoices', InvoiceController::class . ':listInvoices');
        $group->get('/invoices/{invoiceId}', InvoiceController::class . ':getInvoice');
        $group->get('/stays/{stayId}/invoice', InvoiceController::class . ':getStayInvoice');

        // Reports
        $group->get('/reports/collection', ReportController::class . ':getCollectionReport');

        // Message Queue / Logs
        $group->get('/message-queue', InvoiceController::class . ':listMessages');

        // Housekeeping & Maintenance
        $group->get('/housekeeping/tasks', HousekeepingController::class . ':listTasks');
        $group->post('/housekeeping/tasks', HousekeepingController::class . ':createTask');
        $group->post('/housekeeping/tasks/{taskId}/assign', HousekeepingController::class . ':assignTask');
        $group->post('/housekeeping/tasks/{taskId}/status', HousekeepingController::class . ':updateStatus');

        // Inventory
        $group->get('/inventory/items', InventoryController::class . ':listItems');
        $group->post('/inventory/items', InventoryController::class . ':createItem');
        $group->post('/inventory/items/{itemId}/adjust', InventoryController::class . ':adjustStock');
        $group->get('/inventory/vendors', InventoryController::class . ':listVendors');
        $group->post('/inventory/vendors', InventoryController::class . ':createVendor');
        $group->get('/inventory/items/{itemId}/ledger', InventoryController::class . ':getLedger');

        // Purchases
        $group->get('/purchases/orders', PurchaseController::class . ':listOrders');
        $group->post('/purchases/orders', PurchaseController::class . ':createOrder');
        $group->get('/purchases/orders/{poId}', PurchaseController::class . ':getOrder');
        $group->post('/purchases/orders/{poId}/approve', PurchaseController::class . ':approveOrder');
        $group->post('/purchases/orders/{poId}/receive', PurchaseController::class . ':receiveOrder');
        $group->get('/purchases/receipts', PurchaseController::class . ':listReceipts');
        $group->get('/purchases/receipts/{grnId}', PurchaseController::class . ':getReceipt');
    })
    ->add(HotelScopingMiddleware::class)
    ->add(AuthenticationMiddleware::class);
};
