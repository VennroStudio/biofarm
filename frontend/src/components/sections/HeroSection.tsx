import { motion } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { Play, ArrowDown } from 'lucide-react';

export const HeroSection = () => {
  return (
    <section className="relative min-h-screen flex items-center justify-center overflow-hidden">
      {/* Video Background */}
      <div className="absolute inset-0 z-0">
        <video
          autoPlay
          muted
          loop
          playsInline
          className="w-full h-full object-cover"
          poster="https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=1920&q=80"
        >
          <source
            src="https://biofarm.store/uploads/videos/biofarm.mp4"
            type="video/mp4"
          />
        </video>
        <div className="absolute inset-0 gradient-hero" />
      </div>

      {/* Content */}
      <div className="relative z-10 container mx-auto px-4 text-center">
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.2 }}
        >
          <span className="inline-block px-4 py-2 mb-6 text-sm font-medium bg-white/10 backdrop-blur-sm rounded-full text-white border border-white/20">
            Натуральные продукты БИОФАРМ
          </span>
        </motion.div>

        <motion.h1
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.4 }}
          className="text-4xl md:text-6xl lg:text-7xl font-display font-bold text-white mb-6 text-balance"
        >
          Качество, проверенное
          <br />
          <span className="text-accent">природой</span>
        </motion.h1>

        <motion.p
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.6 }}
          className="text-lg md:text-xl text-white/80 max-w-2xl mx-auto mb-10"
        >
          Мы поставляем экологически чистые продукты напрямую из собственных лабораторий.
        </motion.p>

        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.8 }}
          className="flex flex-col sm:flex-row gap-4 justify-center items-center"
        >
          <Button
            size="lg"
            className="gradient-primary text-primary-foreground px-8 py-6 text-lg shadow-premium-lg"
            onClick={() => {
              const catalogSection = document.getElementById('catalog');
              if (catalogSection) {
                catalogSection.scrollIntoView({ behavior: 'smooth' });
              } else {
                window.location.href = '/catalog';
              }
            }}
          >
            Смотреть каталог
          </Button>
          <Button
            size="lg"
            variant="outline"
            className="bg-white/10 backdrop-blur-sm border-white/30 text-white hover:bg-white/20 px-8 py-6 text-lg"
            onClick={() => {
              const videoSection = document.getElementById('video');
              if (videoSection) {
                videoSection.scrollIntoView({ behavior: 'smooth' });
              }
            }}
          >
            <Play className="mr-2 h-5 w-5" />
            О производстве
          </Button>
        </motion.div>
      </div>

      {/* Scroll Indicator */}
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 1.5 }}
        className="absolute bottom-8 left-1/2 -translate-x-1/2 z-10"
      >
        <motion.div
          animate={{ y: [0, 10, 0] }}
          transition={{ duration: 2, repeat: Infinity }}
          className="flex flex-col items-center gap-2 text-white/60"
        >
          <span className="text-xs uppercase tracking-widest">Листайте вниз</span>
          <ArrowDown className="h-5 w-5" />
        </motion.div>
      </motion.div>
    </section>
  );
};
