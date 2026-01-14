import { useRef } from 'react';
import { motion, useInView } from 'framer-motion';

export const SeoSection = () => {
  const ref = useRef(null);
  const isInView = useInView(ref, { once: true, margin: '-100px' });

  return (
    <section id="about" className="py-20 lg:py-32 relative overflow-hidden">
      {/* Background Image */}
      <div className="absolute inset-0 z-0">
        <img
          src="https://biofarm.store/uploads/images/bg2.jpeg"
          alt=""
          className="w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-r from-background/95 via-background/85 to-background/70" />
      </div>

      <div className="container mx-auto px-4 relative z-10">
        <motion.div
          ref={ref}
          initial={{ opacity: 0, y: 30 }}
          animate={isInView ? { opacity: 1, y: 0 } : { opacity: 0, y: 30 }}
          transition={{ duration: 0.8 }}
          className="max-w-3xl"
        >
          <span className="inline-block text-accent font-medium mb-4">
            О компании
          </span>
          <h2 className="text-3xl md:text-4xl font-display font-bold text-foreground mb-8">
            Натуральные продукты из лаборатории БИОФАРМ
          </h2>

          <div className="prose prose-lg text-muted-foreground">
            <p className="mb-6">
              <strong className="text-foreground">«Биофарм»</strong> — институт изучения
              биологически активных веществ. Мы работаем на стыке науки и природы: изучаем
              компоненты растений Сибири и создаём продукты на основе природных
              биокомплексов, чтобы раскрывать их потенциал для поддержания здоровья.
            </p>

            <p className="mb-6">
              Сибирь — регион экстремальных температур: летом до <strong className="text-foreground">+45</strong>,
              зимой до <strong className="text-foreground">-55</strong>. Чтобы выживать в таких
              условиях, местные травы и деревья накапливают защитные вещества с выраженными
              антиоксидантными свойствами. Особое место занимает
              <strong className="text-foreground"> сибирская пихта</strong> — «жемчужина» тайги,
              богатая биологически активными соединениями, витаминами и микроэлементами.
            </p>

            <p>
              В нашей <strong className="text-foreground">собственной лаборатории</strong> мы
              исследуем свойства природных компонентов, разрабатываем и совершенствуем
              технологии в области лесобиохимии, чтобы получать качественные продукты с
              высоким содержанием микро- и макроэлементов, полифенолов и каротиноидов и
              хорошей усвояемостью. Мы ведём научные проекты совместно с российскими и
              зарубежными партнёрами и выпускаем линейку продуктов на основе сибирской
              пихты, ориентированную на профилактику и поддержку организма.
            </p>
          </div>
        </motion.div>
      </div>
    </section>
  );
};
