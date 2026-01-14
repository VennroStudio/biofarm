import { useRef } from 'react';
import { motion, useInView } from 'framer-motion';
import { ExternalLink, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';

const marketplaces = [
  {
    name: 'Wildberries',
    logo: 'WB',
    color: 'bg-purple-600',
    url: 'https://www.wildberries.ru',
    benefits: ['Быстрая доставка', 'Пункты выдачи рядом', 'Кэшбек баллами'],
  },
  {
    name: 'Ozon',
    logo: 'Ozon',
    color: 'bg-blue-600',
    url: 'https://www.ozon.ru',
    benefits: ['Premium-доставка', 'Рассрочка', 'Ozon Счёт'],
  },
];

const advantages = [
  'Официальные продавцы',
  'Гарантия качества',
  'Оригинальная продукция',
  'Быстрая доставка по всей России',
];

export const MarketplacesSection = () => {
  const ref = useRef(null);
  const isInView = useInView(ref, { once: true, margin: '-100px' });

  return (
    <section className="py-20 lg:py-32">
      <div className="container mx-auto px-4">
        <motion.div
          ref={ref}
          initial={{ opacity: 0, y: 30 }}
          animate={isInView ? { opacity: 1, y: 0 } : { opacity: 0, y: 30 }}
          transition={{ duration: 0.6 }}
          className="text-center mb-12"
        >
          <span className="inline-block text-accent font-medium mb-4">
            Где купить
          </span>
          <h2 className="text-3xl md:text-4xl font-display font-bold text-foreground mb-6">
            Наши товары на маркетплейсах
          </h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Покупайте нашу продукцию на популярных маркетплейсах. 
            Официальный магазин, быстрая доставка и гарантия качества.
          </p>
        </motion.div>

        <div className="grid lg:grid-cols-2 gap-8 mb-12">
          {marketplaces.map((marketplace, index) => (
            <motion.div
              key={marketplace.name}
              initial={{ opacity: 0, y: 30 }}
              animate={isInView ? { opacity: 1, y: 0 } : { opacity: 0, y: 30 }}
              transition={{ duration: 0.6, delay: index * 0.15 }}
              className="bg-card rounded-2xl p-8 shadow-premium hover:shadow-premium-lg transition-all"
            >
              <div className="flex items-center gap-4 mb-6">
                <div className={`w-16 h-16 ${marketplace.color} rounded-xl flex items-center justify-center text-white font-bold text-lg`}>
                  {marketplace.logo}
                </div>
                <div>
                  <h3 className="text-xl font-bold text-foreground">{marketplace.name}</h3>
                  <p className="text-muted-foreground text-sm">Официальный магазин</p>
                </div>
              </div>

              <ul className="space-y-3 mb-6">
                {marketplace.benefits.map((benefit) => (
                  <li key={benefit} className="flex items-center gap-3 text-muted-foreground">
                    <div className="w-5 h-5 rounded-full bg-primary/10 flex items-center justify-center">
                      <Check className="h-3 w-3 text-primary" />
                    </div>
                    {benefit}
                  </li>
                ))}
              </ul>

              <Button
                className="w-full gradient-primary text-primary-foreground"
                asChild
              >
                <a href={marketplace.url} target="_blank" rel="noopener noreferrer">
                  Перейти в магазин
                  <ExternalLink className="ml-2 h-4 w-4" />
                </a>
              </Button>
            </motion.div>
          ))}
        </div>

        {/* Advantages */}
        <motion.div
          initial={{ opacity: 0 }}
          animate={isInView ? { opacity: 1 } : { opacity: 0 }}
          transition={{ delay: 0.5 }}
          className="flex flex-wrap justify-center gap-4"
        >
          {advantages.map((advantage) => (
            <div
              key={advantage}
              className="flex items-center gap-2 px-4 py-2 bg-secondary rounded-full text-secondary-foreground text-sm"
            >
              <Check className="h-4 w-4 text-primary" />
              {advantage}
            </div>
          ))}
        </motion.div>
      </div>
    </section>
  );
};
