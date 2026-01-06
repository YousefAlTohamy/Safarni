<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\Repositories\TourRepositoryInterface;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for Tour data access operations.
 */
class TourRepository extends BaseRepository implements TourRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(Tour $model)
    {
        parent::__construct($model);
    }

    /**
     * Get featured tours that are active and not expired.
     */
    public function getFeaturedTours(int $limit = 5): Collection
    {
        return $this->model
            ->available()
            ->featured()
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get latest tours that are active and not expired.
     */
    public function getLatestTours(int $limit = 10): Collection
    {
        return $this->model
            ->available()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Find tour by slug.
     */
    public function findBySlug(string $slug): ?Tour
    {
        return $this->model
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Get tours with filtering and sorting.
     *
     * @param array<string, mixed> $filters
     * @param string|null $sortBy
     * @return Collection
     */
    public function getToursWithFilters(array $filters = [], ?string $sortBy = null): Collection
    {
        $query = $this->model->newQuery();

        // Apply price range filter
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Add bookings count using withCount (will work with the relationship)
        $query->withCount('bookings as bookings_count');
        
        // Add reviews count using withCount (will work with the relationship)
        $query->withCount('reviews as reviews_count');

        // Apply sorting
        switch ($sortBy) {
            case 'price_low_high':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high_low':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'most_booked':
                $query->orderBy('bookings_count', 'desc');
                break;
            case 'most_reviewed':
                $query->orderBy('reviews_count', 'desc');
                break;
            case 'most_popular':
                // Most popular = combination of bookings, reviews, and rating
                $query->orderByRaw('(bookings_count * 2 + reviews_count + COALESCE(rating, 0) * 10) DESC');
                break;
            case 'biggest_deals':
                // Biggest deals = lowest price with good rating (price/rating ratio)
                $query->orderByRaw('(price / NULLIF(rating, 0)) ASC');
                break;
            default:
                // Default: order by created_at desc
                $query->orderBy('created_at', 'desc');
                break;
        }

        return $query->get();
    }
}