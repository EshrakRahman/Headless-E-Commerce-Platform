<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Review\StoreReviewRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    /**
     * List approved reviews for a product.
     *
     * @tags Reviews
     * @unauthenticated
     */
    public function index(Product $product): JsonResponse
    {
        $reviews = $product->reviews()
            ->with('user')
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'average_rating' => $product->avg_rating,
                'total_reviews' => $product->approvedReviews()->count(),
            ],
        ]);
    }

    /**
     * Create or update a review for a product.
     *
     * @tags Reviews
     */
    public function store(StoreReviewRequest $request, Product $product): ReviewResource
    {
        $validated = $request->validated();

        $review = Review::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'product_id' => $product->id,
            ],
            [
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?? null,
                'body' => $validated['body'],
                'is_approved' => false,
            ]
        );

        $review->load('user');

        return new ReviewResource($review);
    }

    /**
     * Delete own review.
     *
     * @tags Reviews
     */
    public function destroy(Review $review): JsonResponse
    {
        abort_if($review->user_id !== auth()->id(), 403);

        $review->delete();

        return response()->json(['message' => 'Review deleted.']);
    }

    /**
     * List the authenticated user's reviews.
     *
     * @tags Reviews
     */
    public function myReviews(): AnonymousResourceCollection
    {
        $reviews = auth()->user()
            ->reviews()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }
}
