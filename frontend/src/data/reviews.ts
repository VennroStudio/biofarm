import { api } from '@/lib/api';

export interface Review {
  id: string;
  productId: number;
  userId: string;
  userName: string;
  rating: number;
  text: string;
  images?: string[];
  createdAt: string;
  isApproved: boolean;
  source?: 'site' | 'wildberries' | 'ozon';
}

export const reviews: Review[] = [];

// Reviews API abstraction
export const reviewsApi = {
  getProductReviews: async (productId: number): Promise<Review[]> => {
    const data = await api.reviews.getByProductId(productId);
    return data.map((r: any) => ({
      id: r.id,
      productId: r.productId,
      userId: r.userId || '',
      userName: r.userName,
      rating: r.rating,
      text: r.text,
      images: r.images,
      createdAt: r.createdAt,
      isApproved: r.isApproved,
      source: r.source,
    }));
  },

  getAllReviews: async (onlyApproved: boolean = false): Promise<Review[]> => {
    const data = await api.reviews.getAll(!onlyApproved);
    return data.map((r: any) => ({
      id: r.id,
      productId: r.productId,
      userId: r.userId || '',
      userName: r.userName,
      rating: r.rating,
      text: r.text,
      images: r.images,
      createdAt: r.createdAt,
      isApproved: r.isApproved,
      source: r.source,
    }));
  },

  addReview: async (review: Omit<Review, 'id' | 'createdAt' | 'isApproved'>): Promise<Review> => {
    const data = await api.reviews.create({
      productId: review.productId,
      userName: review.userName,
      rating: review.rating,
      text: review.text,
      source: review.source || 'site',
      userId: review.userId || undefined,
      images: review.images,
    });
    
    return {
      id: data.id,
      productId: data.productId,
      userId: data.userId || '',
      userName: data.userName,
      rating: data.rating,
      text: data.text,
      images: data.images,
      createdAt: new Date().toISOString(),
      isApproved: false,
      source: review.source,
    };
  },

  updateReview: async (reviewId: string, review: Omit<Review, 'id' | 'createdAt' | 'isApproved'>): Promise<Review | null> => {
    try {
      const data = await api.reviews.update(reviewId, {
        productId: review.productId,
        userName: review.userName,
        rating: review.rating,
        text: review.text,
        source: review.source || 'site',
        userId: review.userId || undefined,
        images: review.images,
      });
      
      return {
        id: data.id,
        productId: data.productId,
        userId: data.userId || '',
        userName: data.userName,
        rating: data.rating,
        text: data.text,
        images: data.images,
        createdAt: data.createdAt || new Date().toISOString(),
        isApproved: data.isApproved,
        source: data.source,
      };
    } catch {
      return null;
    }
  },

  approveReview: async (reviewId: string): Promise<Review | null> => {
    const data = await api.reviews.approve(reviewId);
    if (!data) return null;
    
    return {
      id: data.id,
      productId: 0,
      userId: '',
      userName: '',
      rating: 0,
      text: '',
      createdAt: new Date().toISOString(),
      isApproved: data.isApproved,
    };
  },

  deleteReview: async (reviewId: string): Promise<boolean> => {
    try {
      await api.reviews.delete(reviewId);
      return true;
    } catch {
      return false;
    }
  },
};
