import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, ChevronLeft, ChevronRight, ZoomIn, ChevronUp, ChevronDown } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface ProductGalleryProps {
  images: string[];
  productName: string;
  badge?: string;
}

export const ProductGallery = ({ images, productName, badge }: ProductGalleryProps) => {
  const [selectedIndex, setSelectedIndex] = useState(0);
  const [isLightboxOpen, setIsLightboxOpen] = useState(false);
  const thumbnailsPerView = 4; // Количество видимых миниатюр

  const handlePrev = () => {
    setSelectedIndex((prev) => (prev === 0 ? images.length - 1 : prev - 1));
  };

  const handleNext = () => {
    setSelectedIndex((prev) => (prev === images.length - 1 ? 0 : prev + 1));
  };

  const handleThumbnailUp = () => {
    // Переключаем на предыдущее изображение (циклически)
    setSelectedIndex((prev) => (prev === 0 ? images.length - 1 : prev - 1));
  };

  const handleThumbnailDown = () => {
    // Переключаем на следующее изображение (циклически)
    setSelectedIndex((prev) => (prev === images.length - 1 ? 0 : prev + 1));
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'ArrowLeft') handlePrev();
    if (e.key === 'ArrowRight') handleNext();
    if (e.key === 'Escape') setIsLightboxOpen(false);
  };


  // Вычисляем видимые миниатюры вокруг текущего выбранного изображения
  const getVisibleThumbnails = () => {
    const half = Math.floor(thumbnailsPerView / 2);
    let start = selectedIndex - half;
    
    // Корректируем начало, если выходим за границы
    if (start < 0) {
      start = 0;
    } else if (start + thumbnailsPerView > images.length) {
      start = Math.max(0, images.length - thumbnailsPerView);
    }
    
    return {
      start,
      thumbnails: images.slice(start, start + thumbnailsPerView),
    };
  };

  const { start: thumbnailStartIndex, thumbnails: visibleThumbnails } = getVisibleThumbnails();

  return (
    <>
      <div className="flex gap-4">
        {/* Thumbnails - Left side with buttons */}
        {images.length > 1 && (
          <div className="flex flex-col gap-2 w-20 flex-shrink-0 h-full">
            {/* Up button */}
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-full rounded-lg flex-shrink-0"
              onClick={handleThumbnailUp}
            >
              <ChevronUp className="h-4 w-4" />
            </Button>
            
            {/* Thumbnails container - занимает всю доступную высоту */}
            <div className="flex-1 flex flex-col gap-2 justify-start min-h-0">
              {visibleThumbnails.map((img, idx) => {
                const actualIndex = thumbnailStartIndex + idx;
                return (
                  <button
                    key={actualIndex}
                    onClick={() => setSelectedIndex(actualIndex)}
                    className={cn(
                      'w-full flex-1 rounded-lg overflow-hidden border-2 transition-all duration-200 flex-shrink-0 min-h-0',
                      selectedIndex === actualIndex 
                        ? 'border-primary ring-2 ring-primary/20' 
                        : 'border-transparent hover:border-muted-foreground/30'
                    )}
                  >
                    <img 
                      src={img} 
                      alt={`${productName} - фото ${actualIndex + 1}`} 
                      className="w-full h-full object-cover"
                    />
                  </button>
                );
              })}
            </div>

            {/* Down button */}
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-full rounded-lg flex-shrink-0"
              onClick={handleThumbnailDown}
            >
              <ChevronDown className="h-4 w-4" />
            </Button>
          </div>
        )}

        {/* Main Image - Right side */}
        <div className="flex-1">
          <div 
            className="relative aspect-square rounded-2xl overflow-hidden bg-muted cursor-zoom-in group"
            onClick={() => setIsLightboxOpen(true)}
          >
            <motion.img
              key={selectedIndex}
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ duration: 0.3 }}
              src={images[selectedIndex]}
              alt={productName}
              className="w-full h-full object-contain"
            />
            {badge && (
              <Badge className="absolute top-4 left-4 bg-accent text-accent-foreground text-base px-4 py-2">
                {badge}
              </Badge>
            )}
            <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center">
              <div className="opacity-0 group-hover:opacity-100 transition-opacity">
                <div className="bg-white/90 rounded-full p-3">
                  <ZoomIn className="h-6 w-6 text-foreground" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Lightbox */}
      <AnimatePresence>
        {isLightboxOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-50 bg-black/95 flex items-center justify-center"
            onClick={() => setIsLightboxOpen(false)}
            onKeyDown={handleKeyDown}
            tabIndex={0}
          >
            <Button
              variant="ghost"
              size="icon"
              className="absolute top-4 right-4 text-white hover:bg-white/20"
              onClick={() => setIsLightboxOpen(false)}
            >
              <X className="h-6 w-6" />
            </Button>

            {images.length > 1 && (
              <>
                <Button
                  variant="ghost"
                  size="icon"
                  className="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:bg-white/20 h-12 w-12"
                  onClick={(e) => { e.stopPropagation(); handlePrev(); }}
                >
                  <ChevronLeft className="h-8 w-8" />
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  className="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:bg-white/20 h-12 w-12"
                  onClick={(e) => { e.stopPropagation(); handleNext(); }}
                >
                  <ChevronRight className="h-8 w-8" />
                </Button>
              </>
            )}

            <motion.img
              key={selectedIndex}
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.9 }}
              src={images[selectedIndex]}
              alt={productName}
              className="max-w-[90vw] max-h-[90vh] object-contain"
              onClick={(e) => e.stopPropagation()}
            />

            {/* Thumbnails in lightbox */}
            {images.length > 1 && (
              <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                {images.map((img, index) => (
                  <button
                    key={index}
                    onClick={(e) => { e.stopPropagation(); setSelectedIndex(index); }}
                    className={cn(
                      'w-16 h-16 rounded-lg overflow-hidden border-2 transition-all',
                      selectedIndex === index 
                        ? 'border-white' 
                        : 'border-transparent opacity-60 hover:opacity-100'
                    )}
                  >
                    <img src={img} alt="" className="w-full h-full object-cover" />
                  </button>
                ))}
              </div>
            )}
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
};
