import { motion } from 'framer-motion';
import { useInView } from 'framer-motion';
import { useRef } from 'react';
import { Leaf, Award, Truck, Shield } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

const features = [
  {
    icon: Leaf,
    title: '100% натуральная продукция',
    description: 'Мы уверены в качестве своего продукта, поэтому смело отправляем пробную партию!',
  },
  {
    icon: Award,
    title: 'Сертифицировано',
    description: 'Вся продукция сертифицирована и соответствует высшим стандартам качества.',
  },
  {
    icon: Truck,
    title: 'Без предоплаты',
    description: 'Поставка на реализацию на  месяц без предоплаты: продали — оплатили — повторяем.',
  },
  {
    icon: Shield,
    title: 'Гарантия качества',
    description: 'Не продадите — заберём товар обратно. Без оплат, комиссий и штрафов.',
  },
];

const containerVariants = {
  hidden: { opacity: 0 },
  visible: {
    opacity: 1,
    transition: {
      staggerChildren: 0.15,
    },
  },
};

const itemVariants = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.6 } },
};

export const FeaturesSection = () => {
  const ref = useRef(null);
  const isInView = useInView(ref, { once: true, margin: '-100px' });

  return (
    <section id="partner" className="py-20 lg:py-32 bg-secondary/30">
      <div className="container mx-auto px-4">
        <div className="grid lg:grid-cols-2 gap-16 items-start">
          {/* Features Grid */}
          <motion.div
            ref={ref}
            variants={containerVariants}
            initial="hidden"
            animate={isInView ? 'visible' : 'hidden'}
          >
            <motion.span 
              variants={itemVariants}
              className="inline-block text-accent font-medium mb-4"
            >
              Сотрудничество с нами
            </motion.span>
            <motion.h2 
              variants={itemVariants}
              className="text-3xl md:text-4xl font-display font-bold text-foreground mb-12"
            >
              Мы рады надежным партнерам!
            </motion.h2>

            <div className="grid sm:grid-cols-2 gap-6">
              {features.map((feature) => (
                <motion.div
                  key={feature.title}
                  variants={itemVariants}
                  className="bg-card p-6 rounded-lg shadow-premium hover:shadow-premium-lg transition-shadow"
                >
                  <div className="w-12 h-12 rounded-lg gradient-primary flex items-center justify-center mb-4">
                    <feature.icon className="h-6 w-6 text-primary-foreground" />
                  </div>
                  <h3 className="text-lg font-bold text-foreground mb-2">
                    {feature.title}
                  </h3>
                  <p className="text-muted-foreground text-sm">
                    {feature.description}
                  </p>
                </motion.div>
              ))}
            </div>
          </motion.div>

          {/* Contact Form */}
          <motion.div
            initial={{ opacity: 0, x: 30 }}
            animate={isInView ? { opacity: 1, x: 0 } : { opacity: 0, x: 30 }}
            transition={{ duration: 0.8, delay: 0.3 }}
            className="bg-card p-8 lg:p-10 rounded-2xl shadow-premium-lg"
          >
            <h3 className="text-2xl font-display font-bold text-foreground mb-2">
              Остались вопросы?
            </h3>
            <p className="text-muted-foreground mb-8">
              Заполните форму и мы свяжемся с вами в ближайшее время
            </p>

            <form className="space-y-6">
              <div className="grid sm:grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-foreground mb-2 block">
                    Ваше имя
                  </label>
                  <Input placeholder="Иван Иванов" className="bg-background" />
                </div>
                <div>
                  <label className="text-sm font-medium text-foreground mb-2 block">
                    Телефон
                  </label>
                  <Input placeholder="+7 (999) 123-45-67" className="bg-background" />
                </div>
              </div>
              <div>
                <label className="text-sm font-medium text-foreground mb-2 block">
                  Email
                </label>
                <Input type="email" placeholder="your@email.com" className="bg-background" />
              </div>
              <div>
                <label className="text-sm font-medium text-foreground mb-2 block">
                  Сообщение
                </label>
                <Textarea
                  placeholder="Напишите ваш вопрос..."
                  rows={4}
                  className="bg-background resize-none"
                />
              </div>
              <Button type="submit" className="w-full gradient-primary text-primary-foreground py-6">
                Отправить заявку
              </Button>
              <p className="text-xs text-muted-foreground text-center">
                Нажимая кнопку, вы соглашаетесь с политикой конфиденциальности
              </p>
            </form>
          </motion.div>
        </div>
      </div>
    </section>
  );
};
