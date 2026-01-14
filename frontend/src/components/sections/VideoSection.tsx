import { useState, useRef } from 'react';
import { motion, useInView } from 'framer-motion';
import { Play, X } from 'lucide-react';
import { Button } from '@/components/ui/button';

export const VideoSection = () => {
  const [isPlaying, setIsPlaying] = useState(false);
  const ref = useRef(null);
  const isInView = useInView(ref, { once: true, margin: '-100px' });

  return (
    <section id="video" className="py-20 lg:py-32 bg-primary relative overflow-hidden">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,_white_1px,_transparent_1px)] bg-[length:30px_30px]" />
      </div>

      <div className="container mx-auto px-4 relative z-10">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          {/* Text Content */}
          <motion.div
            ref={ref}
            initial={{ opacity: 0, x: -30 }}
            animate={isInView ? { opacity: 1, x: 0 } : { opacity: 0, x: -30 }}
            transition={{ duration: 0.8 }}
          >
            <span className="inline-block text-accent font-medium mb-4">
              Наше производство
            </span>
            <h2 className="text-3xl md:text-4xl font-display font-bold text-primary-foreground mb-6">
              От сырья до готового продукта
            </h2>
            <p className="text-primary-foreground/80 mb-6 leading-relaxed">
              Мы контролируем каждый этап — от отбора и входного контроля сырья до производства,
              фасовки и выпуска партии. Собственная лаборатория проверяет качество и безопасность
              по установленным показателям, а каждая партия проходит документированную проверку.
            </p>
            <ul className="space-y-3 mb-8">
              {[
                'Собственная лаборатория контроля качества',
                'Полный цикл производства',
                'Сертификация и документация на партии',
                'Гибкие условия сотрудничества',
              ].map((item, index) => (
                <motion.li
                  key={index}
                  initial={{ opacity: 0, x: -20 }}
                  animate={isInView ? { opacity: 1, x: 0 } : { opacity: 0, x: -20 }}
                  transition={{ delay: 0.3 + index * 0.1 }}
                  className="flex items-center gap-3 text-primary-foreground/90"
                >
                  <div className="w-2 h-2 rounded-full bg-accent" />
                  {item}
                </motion.li>
              ))}
            </ul>
            <Button
              size="lg"
              variant="secondary"
              className="bg-primary-foreground text-primary hover:bg-primary-foreground/90"
            >
              Подробнее о нас
            </Button>
          </motion.div>

          {/* Video Thumbnail */}
          <motion.div
            initial={{ opacity: 0, x: 30 }}
            animate={isInView ? { opacity: 1, x: 0 } : { opacity: 0, x: 30 }}
            transition={{ duration: 0.8, delay: 0.2 }}
            className="relative"
          >
            <div className="relative aspect-video rounded-2xl overflow-hidden shadow-2xl">
              <img
                src="https://cdn.pixabay.com/photo/2016/03/11/13/59/fir-1250330_1280.jpg"
                alt="Производство"
                className="w-full h-full object-cover"
              />
              <div className="absolute inset-0 bg-black/30 flex items-center justify-center">
                <button
                  onClick={() => setIsPlaying(true)}
                  className="w-20 h-20 rounded-full bg-white/90 flex items-center justify-center hover:bg-white hover:scale-110 transition-all shadow-lg"
                >
                  <Play className="h-8 w-8 text-primary ml-1" fill="currentColor" />
                </button>
              </div>
            </div>

            {/* Decorative Element */}
            <div className="absolute -bottom-6 -right-6 w-32 h-32 bg-accent/20 rounded-full blur-2xl" />
            <div className="absolute -top-6 -left-6 w-24 h-24 bg-accent/30 rounded-full blur-xl" />
          </motion.div>
        </div>
      </div>

      {/* Video Modal */}
      {isPlaying && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
          onClick={() => setIsPlaying(false)}
        >
          <button
            className="absolute top-6 right-6 text-white hover:text-accent transition-colors"
            onClick={() => setIsPlaying(false)}
          >
            <X className="h-8 w-8" />
          </button>
          <div className="w-full max-w-4xl aspect-video">
            <iframe
              width="100%"
              height="100%"
              src="https://www.youtube.com/embed/O6zCel2mbfM?autoplay=1"
              title="О производстве"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowFullScreen
              className="rounded-lg"
            />
          </div>
        </motion.div>
      )}
    </section>
  );
};
