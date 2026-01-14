import { useRef, useState } from 'react';
import { motion, useInView } from 'framer-motion';
import { Star, ChevronLeft, ChevronRight, Quote } from 'lucide-react';
import { Button } from '@/components/ui/button';

const reviews = [
  {
    id: 1,
    name: 'Анна Петрова',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&q=80',
    rating: 5,
    text: 'Отличный мёд! Заказываю уже не первый раз. Вкус натуральный, густой, ароматный. Дети в восторге!',
    date: '12 января 2024',
    product: 'Мёд цветочный',
    images: [
      'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=300&q=80',
      'https://images.unsplash.com/photo-1558642452-9d2a7deb7f62?w=300&q=80',
    ],
  },
  {
    id: 2,
    name: 'Михаил Сидоров',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&q=80',
    rating: 5,
    text: 'Льняное масло высшего качества. Принимаю каждый день натощак — самочувствие отличное!',
    date: '10 января 2024',
    product: 'Масло льняное',
    images: [
      'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=300&q=80',
    ],
  },
  {
    id: 3,
    name: 'Елена Козлова',
    avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&q=80',
    rating: 5,
    text: 'Заказывала на подарок родителям гречишный мёд. Все в восторге! Упаковка красивая, доставка быстрая.',
    date: '8 января 2024',
    product: 'Мёд гречишный',
  },
  {
    id: 4,
    name: 'Дмитрий Волков',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&q=80',
    rating: 4,
    text: 'Хорошее подсолнечное масло. Вкус как в детстве у бабушки. Рекомендую!',
    date: '5 января 2024',
    product: 'Масло подсолнечное',
    images: [
      'https://images.unsplash.com/photo-1620706857370-e1b9770e8bb1?w=300&q=80',
      'https://images.unsplash.com/photo-1599599810694-b5b37304c041?w=300&q=80',
      'https://images.unsplash.com/photo-1612187715648-a8ad6d2c7c58?w=300&q=80',
    ],
  },
  {
    id: 5,
    name: 'Ольга Новикова',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&q=80',
    rating: 5,
    text: 'Лучший мёд, который я пробовала! Теперь только BioFarm. Спасибо за качество!',
    date: '3 января 2024',
    product: 'Мёд липовый',
  },
];

export const ReviewsSection = () => {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [lightboxImage, setLightboxImage] = useState<string | null>(null);
  const ref = useRef(null);
  const isInView = useInView(ref, { once: true, margin: '-100px' });

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
          ref={ref}
          initial={{ opacity: 0, y: 30 }}
          animate={isInView ? { opacity: 1, y: 0 } : { opacity: 0, y: 30 }}
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
              key={review.id}
              initial={{ opacity: 0, y: 30 }}
              animate={isInView ? { opacity: 1, y: 0 } : { opacity: 0, y: 30 }}
              transition={{ duration: 0.6, delay: index * 0.1 }}
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
