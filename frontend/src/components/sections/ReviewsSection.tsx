import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Star, ChevronLeft, ChevronRight, Quote } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { reviewsApi, Review } from '@/data/reviews';
import { getProducts } from '@/data/products';

interface DisplayReview {
  id: string;
  name: string;
  avatar: string;
  rating: number;
  text: string;
  date: string;
  product: string;
  images?: string[];
}

export const ReviewsSection = () => {
  const [reviews, setReviews] = useState<DisplayReview[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [lightboxImage, setLightboxImage] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    Promise.all([
      reviewsApi.getAllReviews(true), // Получаем только одобренные отзывы
      getProducts()
    ])
      .then(([reviewsData, productsData]) => {
        // Take first 5 approved reviews
        const approvedReviews = reviewsData.slice(0, 5);
        
        const displayReviews: DisplayReview[] = approvedReviews.map((review) => {
          const product = productsData.find(p => p.id === review.productId);
          return {
            id: review.id,
            name: review.userName,
            avatar: `https://ui-avatars.com/api/?name=${encodeURIComponent(review.userName)}&background=random`,
            rating: review.rating,
            text: review.text,
            date: new Date(review.createdAt).toLocaleDateString('ru-RU', {
              day: 'numeric',
              month: 'long',
              year: 'numeric'
            }),
            product: product?.name || 'Товар',
            images: review.images || [],
          };
        });
        
        // If we have less than 3 reviews, duplicate them to have at least 3 for carousel
        if (displayReviews.length > 0 && displayReviews.length < 3) {
          while (displayReviews.length < 3) {
            displayReviews.push(...displayReviews);
          }
        }
        
        setReviews(displayReviews);
      })
      .catch((error) => {
        console.error('Failed to load reviews:', error);
      })
      .finally(() => {
        setLoading(false);
      });
  }, []);

  if (loading) {
    return (
      <section id="reviews" className="py-20 lg:py-32 bg-secondary/30">
        <div className="container mx-auto px-4">
          <div className="text-center">
            <p className="text-muted-foreground">Загрузка отзывов...</p>
          </div>
        </div>
      </section>
    );
  }

  if (reviews.length === 0) {
    return null;
  }

  const nextSlide = () => {
    setCurrentIndex((prev) => (prev + 1) % reviews.length);
  };

  const prevSlide = () => {
    setCurrentIndex((prev) => (prev - 1 + reviews.length) % reviews.length);
  };

  const visibleReviews = [
    reviews[currentIndex],
    reviews[(currentIndex + 1) % reviews.length],
    reviews[(currentIndex + 2) % reviews.length],
  ];

  return (
    <section id="reviews" className="py-20 lg:py-32 bg-secondary/30">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={!loading ? { opacity: 1, y: 0 } : { opacity: 0, y: 30 }}
          transition={{ duration: 0.6 }}
          className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12"
        >
          <div>
            <span className="inline-block text-accent font-medium mb-4">
              Отзывы
            </span>
            <h2 className="text-3xl md:text-4xl font-display font-bold text-foreground">
              Что говорят наши клиенты
            </h2>
          </div>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="icon"
              onClick={prevSlide}
              className="rounded-full"
            >
              <ChevronLeft className="h-5 w-5" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              onClick={nextSlide}
              className="rounded-full"
            >
              <ChevronRight className="h-5 w-5" />
            </Button>
          </div>
        </motion.div>

        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {visibleReviews.map((review, index) => (
            <motion.div
              key={`${review.id}-${currentIndex}-${index}`}
              initial={{ opacity: 0, y: 30 }}
              animate={!loading ? { opacity: 1, y: 0 } : { opacity: 0, y: 30 }}
              transition={{ duration: 0.6, delay: 0.2 + index * 0.15 }}
              className="bg-card rounded-2xl p-6 shadow-premium relative"
            >
              <Quote className="absolute top-6 right-6 h-8 w-8 text-primary/10" />
              
              <div className="flex items-center gap-4 mb-4">
                <img
                  src={review.avatar}
                  alt={review.name}
                  className="w-12 h-12 rounded-full object-cover"
                />
                <div>
                  <h4 className="font-bold text-foreground">{review.name}</h4>
                  <p className="text-sm text-muted-foreground">{review.product}</p>
                </div>
              </div>

              <div className="flex gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star
                    key={i}
                    className={`h-4 w-4 ${
                      i < review.rating
                        ? 'text-accent fill-accent'
                        : 'text-muted-foreground'
                    }`}
                  />
                ))}
              </div>

              <p className="text-muted-foreground mb-4 line-clamp-3">
                {review.text}
              </p>

              {/* Review Images */}
              {review.images && review.images.length > 0 && (
                <div className="flex gap-2 mb-4 overflow-x-auto">
                  {review.images.map((img, imgIndex) => (
                    <img
                      key={imgIndex}
                      src={img}
                      alt={`Фото к отзыву ${imgIndex + 1}`}
                      className="w-16 h-16 rounded-lg object-cover cursor-pointer hover:opacity-80 transition-opacity flex-shrink-0"
                      onClick={() => setLightboxImage(img)}
                    />
                  ))}
                </div>
              )}

              <p className="text-sm text-muted-foreground/60">{review.date}</p>
            </motion.div>
          ))}
        </div>

        {/* Pagination Dots */}
        <div className="flex justify-center gap-2 mt-8">
          {reviews.map((_, index) => (
            <button
              key={index}
              onClick={() => setCurrentIndex(index)}
              className={`w-2 h-2 rounded-full transition-all ${
                index === currentIndex
                  ? 'bg-primary w-6'
                  : 'bg-primary/30 hover:bg-primary/50'
              }`}
            />
          ))}
        </div>
      </div>

      {/* Lightbox */}
      {lightboxImage && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4"
          onClick={() => setLightboxImage(null)}
        >
          <motion.img
            initial={{ scale: 0.9 }}
            animate={{ scale: 1 }}
            src={lightboxImage}
            alt="Увеличенное фото"
            className="max-w-full max-h-[90vh] rounded-lg object-contain"
          />
        </motion.div>
      )}
    </section>
  );
};
