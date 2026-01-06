<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\Tour;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for Tour data access operations.
 */
interface TourRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get featured tours that are active and not expired.
     */
    public function getFeaturedTours(int $limit = 5): Collection;

    /**
     * Get latest tours that are active and not expired.
     */
    public function getLatestTours(int $limit = 10): Collection;

    /**
     * Find tour by slug.
     */
    public function findBySlug(string $slug): ?Tour;

    /**
     * Get tours with filtering and sorting.
     *
     * @param array<string, mixed> $filters
     * @param string|null $sortBy
     * @return Collection
     */
    public function getToursWithFilters(array $filters = [], ?string $sortBy = null): Collection;
}