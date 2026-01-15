<?php

declare(strict_types=1);

namespace App\Http;

use App\Modules\Entity\BlogPost\BlogPost;
use App\Modules\Entity\Category\Category;
use App\Modules\Entity\Order\Order;
use App\Modules\Entity\OrderItem\OrderItem;
use App\Modules\Entity\Product\Product;
use App\Modules\Entity\Review\Review;
use App\Modules\Entity\User\User;
use App\Modules\Entity\Withdrawal\Withdrawal;

final class Serializer
{
    /** @return array<string, mixed> */
    public static function product(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'slug' => $product->getSlug(),
            'name' => $product->getName(),
            'category' => $product->getCategoryId(),
            'price' => $product->getPrice(),
            'oldPrice' => $product->getOldPrice(),
            'image' => $product->getImage(),
            'images' => $product->getImages(),
            'badge' => $product->getBadge(),
            'weight' => $product->getWeight(),
            'description' => $product->getDescription(),
            'shortDescription' => $product->getShortDescription(),
            'ingredients' => $product->getIngredients(),
            'features' => $product->getFeatures(),
            'wbLink' => $product->getWbLink(),
            'ozonLink' => $product->getOzonLink(),
            'isActive' => $product->isActive(),
        ];
    }

    /** @return array<string, mixed> */
    public static function user(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'phone' => $user->getPhone(),
            'bonusBalance' => $user->getBonusBalance(),
            'isPartner' => $user->isPartner(),
            'isActive' => $user->isActive(),
            'cardNumber' => $user->getCardNumber(),
        ];
    }

    /** @return array<string, mixed> */
    public static function order(Order $order, array $items = []): array
    {
        return [
            'id' => $order->getId(),
            'userId' => $order->getUserId(),
            'status' => $order->getStatus(),
            'paymentStatus' => $order->getPaymentStatus(),
            'total' => $order->getTotal(),
            'bonusUsed' => $order->getBonusUsed(),
            'bonusEarned' => $order->getBonusEarned(),
            'shippingAddress' => $order->getShippingAddress(),
            'paymentMethod' => $order->getPaymentMethod(),
            'trackingNumber' => $order->getTrackingNumber(),
            'referredBy' => $order->getReferredBy(),
            'createdAt' => date('c', $order->getCreatedAt()),
            'paidAt' => $order->getPaidAt() ? date('c', $order->getPaidAt()) : null,
            'items' => $items,
        ];
    }

    /** @return array<string, mixed> */
    public static function review(Review $review): array
    {
        return [
            'id' => $review->getId(),
            'productId' => $review->getProductId(),
            'userId' => $review->getUserId(),
            'userName' => $review->getUserName(),
            'rating' => $review->getRating(),
            'text' => $review->getText(),
            'images' => $review->getImages(),
            'source' => $review->getSource(),
            'isApproved' => $review->isApproved(),
            'createdAt' => date('c', $review->getCreatedAt()),
        ];
    }

    /** @return array<string, mixed> */
    public static function blogPost(BlogPost $post): array
    {
        return [
            'id' => $post->getId(),
            'slug' => $post->getSlug(),
            'title' => $post->getTitle(),
            'excerpt' => $post->getExcerpt(),
            'content' => $post->getContent(),
            'image' => $post->getImage(),
            'category' => $post->getCategoryId(),
            'authorId' => $post->getAuthorId(),
            'readTime' => $post->getReadTime(),
            'date' => date('Y-m-d', $post->getCreatedAt()),
        ];
    }

    /** @return array<string, mixed> */
    public static function withdrawal(Withdrawal $withdrawal): array
    {
        return [
            'id' => $withdrawal->getId(),
            'userId' => $withdrawal->getUserId(),
            'amount' => $withdrawal->getAmount(),
            'status' => $withdrawal->getStatus(),
            'createdAt' => date('c', $withdrawal->getCreatedAt()),
            'processedAt' => $withdrawal->getProcessedAt() ? date('c', $withdrawal->getProcessedAt()) : null,
            'processedBy' => $withdrawal->getProcessedBy(),
        ];
    }

    /** @return array<string, mixed> */
    public static function category(Category $category): array
    {
        return [
            'id' => $category->getId(),
            'slug' => $category->getSlug(),
            'name' => $category->getName(),
            'createdAt' => date('c', $category->getCreatedAt()),
            'updatedAt' => $category->getUpdatedAt() ? date('c', $category->getUpdatedAt()) : null,
        ];
    }
}
