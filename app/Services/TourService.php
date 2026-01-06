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

    /**
     * Get tours with filters and sorting.
     *
     * @param array<string, mixed> $filters
     * @param string|null $sortBy
     * @return Collection
     */
    public function getToursWithFilters(array $filters = [], ?string $sortBy = null): Collection
    {
        return $this->tourRepository->getToursWithFilters($filters, $sortBy);
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
