<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Interfaces\Repositories\TourRepositoryInterface;

class TourService
{
    public function __construct(
        protected TourRepositoryInterface $tourRepository
    ) {}

    public function getAllTours(): Collection
    {
        return $this->tourRepository->all();
    }
    public function getTourById(int $id): ?object
    {
        return $this->tourRepository->find($id);
    }
    public function getTourBySlug(string $slug): ?object
    {
        return $this->tourRepository->findFirstWhere(['slug' => $slug]);
    }
    public function createTour(array $data): Model
    {
        return $this->tourRepository->create($data);
    }
    public function updateTour(int $id, array $data): bool
    {
        return $this->tourRepository->update($id, $data);
    }
}