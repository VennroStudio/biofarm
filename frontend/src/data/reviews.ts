// Reviews data layer - easily replaceable with API calls

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

export const reviews: Review[] = [
  {
    id: '1',
    productId: 1,
    userId: '1',
    userName: 'Анна К.',
    rating: 5,
    text: 'Прекрасный мёд! Очень ароматный и вкусный. Заказываю уже не первый раз, качество всегда на высоте.',
    images: ['https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=200&q=80'],
    createdAt: '2024-12-10',
    isApproved: true,
    source: 'site',
  },
  {
    id: '2',
    productId: 1,
    userId: '2',
    userName: 'Сергей П.',
    rating: 4,
    text: 'Хороший мёд, но доставка была долгой. В остальном всё отлично.',
    createdAt: '2024-12-08',
    isApproved: true,
    source: 'wildberries',
  },
  {
    id: '3',
    productId: 2,
    userId: '3',
    userName: 'Мария В.',
    rating: 5,
    text: 'Гречишный мёд — мой фаворит! Насыщенный вкус, настоящий!',
    createdAt: '2024-12-05',
    isApproved: true,
    source: 'ozon',
  },
  {
    id: '4',
    productId: 4,
    userId: '4',
    userName: 'Олег Т.',
    rating: 5,
    text: 'Льняное масло отличного качества. Использую для салатов каждый день.',
    createdAt: '2024-12-01',
    isApproved: true,
    source: 'site',
  },
  {
    id: '5',
    productId: 1,
    userId: '5',
    userName: 'Новый отзыв',
    rating: 3,
    text: 'Ожидает модерации',
    createdAt: '2024-12-20',
    isApproved: false,
    source: 'site',
  },
];

// Reviews API abstraction
export const reviewsApi = {
  getProductReviews: async (productId: number): Promise<Review[]> => {
    await new Promise(resolve => setTimeout(resolve, 200));
    const stored = localStorage.getItem('reviews');
    const allReviews: Review[] = stored ? JSON.parse(stored) : reviews;
    return allReviews.filter(r => r.productId === productId && r.isApproved);
  },

  getAllReviews: async (): Promise<Review[]> => {
    await new Promise(resolve => setTimeout(resolve, 200));
    const stored = localStorage.getItem('reviews');
    return stored ? JSON.parse(stored) : reviews;
  },

  addReview: async (review: Omit<Review, 'id' | 'createdAt' | 'isApproved'>): Promise<Review> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    
    const newReview: Review = {
      ...review,
      id: String(Date.now()),
      createdAt: new Date().toISOString(),
      isApproved: false, // Requires moderation
    };
    
    const stored = localStorage.getItem('reviews');
    const allReviews: Review[] = stored ? JSON.parse(stored) : reviews;
    allReviews.unshift(newReview);
    localStorage.setItem('reviews', JSON.stringify(allReviews));
    
    return newReview;
  },

  approveReview: async (reviewId: string): Promise<Review | null> => {
    await new Promise(resolve => setTimeout(resolve, 200));
    const stored = localStorage.getItem('reviews');
    const allReviews: Review[] = stored ? JSON.parse(stored) : reviews;
    const review = allReviews.find(r => r.id === reviewId);
    
    if (review) {
      review.isApproved = true;
      localStorage.setItem('reviews', JSON.stringify(allReviews));
      return review;
    }
    return null;
  },

  deleteReview: async (reviewId: string): Promise<boolean> => {
    await new Promise(resolve => setTimeout(resolve, 200));
    const stored = localStorage.getItem('reviews');
    const allReviews: Review[] = stored ? JSON.parse(stored) : reviews;
    const filtered = allReviews.filter(r => r.id !== reviewId);
    localStorage.setItem('reviews', JSON.stringify(filtered));
    return true;
  },
};
