<?php

namespace App\Http\Controllers\Api;

use App\Models\Tour;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\TourService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\TourResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TourController extends Controller
{
    use ApiResponse;
    public function __construct(
        protected TourService $tourService
    ) {}
    public function index(): AnonymousResourceCollection|JsonResponse
    {
        $tours = $this->tourService->getAllTours();
        return TourResource::collection($tours);
    }

    public function show(string $slug): JsonResponse
    {
        $tour = $this->tourService->getTourBySlug($slug);
        if (!$tour) {
            return $this->notFoundResponse('Tour not found');
        }
        return $this->successResponse(new TourResource($tour));
    }

    public function availability(): JsonResponse
    {
        $tours = $this->tourService->getAllTours(5);
        return $this->successResponse([
            'data' => TourResource::collection($tours),
        ]);
    }   

    public function recommendations(): JsonResponse
    {
        $tours = $this->tourService->getAllTours(5);
        return $this->successResponse([
            'data' => TourResource::collection($tours),
        ]);
    }
    public function destination(Request $request): JsonResponse
    {
        $query = Tour::select('location')->distinct();

        if ($request->filled('search')) {
            $query->where('location', 'like', '%' . $request->search . '%');
        }

        $destinations = $query->limit(10)->pluck('location');

        return $this->successResponse([
            $destinations,
        ]);
    }
    public function toursInDestination(Request $request): JsonResponse
    {
        $tours = Tour::where('location', $request->destination)->get();
        if (!$tours) {
            return $this->notFoundResponse('Tours not found');
        }
        return $this->successResponse([
            'data' => TourResource::collection($tours),
        ]);
    }
}
