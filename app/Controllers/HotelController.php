<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\HotelService;
use App\Support\ApiResponse;
use App\Validators\HotelValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class HotelController
{
    private HotelService $hotelService;

    public function __construct(HotelService $hotelService)
    {
        $this->hotelService = $hotelService;
    }

    public function listGroups(Request $request, Response $response): Response
    {
        $groups = $this->hotelService->listAllGroups();
        return ApiResponse::success($groups, 'Hotel groups retrieved successfully.');
    }

    public function createGroup(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = (array)$request->getParsedBody();

        try {
            HotelValidator::validateGroup($data);
            $group = $this->hotelService->createHotelGroup($data, (int)$user['user_id']);
            return ApiResponse::success($group, 'Hotel group created successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function listHotels(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $hotels = $this->hotelService->listAccessibleHotels((int)$user['user_id']);
        return ApiResponse::success($hotels, 'Hotels retrieved successfully.');
    }

    public function createHotel(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = (array)$request->getParsedBody();

        try {
            HotelValidator::validateHotel($data);
            $hotel = $this->hotelService->createHotel($data, (int)$user['user_id']);
            return ApiResponse::success($hotel, 'Hotel created successfully.', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
