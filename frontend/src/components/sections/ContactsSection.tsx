import { useRef } from 'react';
import { motion, useInView } from 'framer-motion';
import { MapPin, Clock, Mail, Phone, MessageCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';

const contactInfo = [
  {
    icon: MapPin,
    title: 'Адрес',
    lines: ['634045, Россия, Томская область, г. Томск, пос. Апрель, Листопадная улица, д. 77'],
  },
  {
    icon: Clock,
    title: 'Время работы',
    lines: ['Пн-Пт: 10:00 - 19:00', 'Сб: 10:00 - 17:00', 'Вс: Выходной'],
  },
  {
    icon: Mail,
    title: 'Связаться с нами',
    lines: ['bio.active@bk.ru'],
    buttons: [
      { label: 'WhatsApp', icon: MessageCircle, href: 'https://wa.me/79138748420' },
      { label: 'Email', icon: Mail, href: 'mailto:bio.active@bk.ru' },
    ],
  },
];

export const ContactsSection = () => {
  const ref = useRef(null);
  const isInView = useInView(ref, { once: true, margin: '-100px' });

  return (
    <section id="contacts" className="py-20 lg:py-32">
      <div className="container mx-auto px-4">
        <motion.div
          ref={ref}
          initial={{ opacity: 0, y: 30 }}
          animate={isInView ? { opacity: 1, y: 0 } : { opacity: 0, y: 30 }}
          transition={{ duration: 0.6 }}
          className="text-center mb-12"
        >
          <span className="inline-block text-accent font-medium mb-4">
            Контакты
          </span>
          <h2 className="text-3xl md:text-4xl font-display font-bold text-foreground mb-6">
            Свяжитесь с нами
          </h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Остались вопросы? Мы всегда на связи и готовы помочь вам с выбором продукции.
          </p>
        </motion.div>

        <div className="grid md:grid-cols-3 gap-8">
          {contactInfo.map((item, index) => (
            <motion.div
              key={item.title}
              initial={{ opacity: 0, y: 30 }}
              animate={isInView ? { opacity: 1, y: 0 } : { opacity: 0, y: 30 }}
              transition={{ duration: 0.6, delay: index * 0.15 }}
              className="bg-card rounded-2xl p-8 text-center shadow-premium hover:shadow-premium-lg transition-all"
            >
              <div className="w-14 h-14 rounded-full gradient-primary flex items-center justify-center mx-auto mb-6">
                <item.icon className="h-6 w-6 text-primary-foreground" />
              </div>
              <h3 className="text-lg font-bold text-foreground mb-4">
                {item.title}
              </h3>
              <div className="space-y-2 mb-6">
                {item.lines.map((line) => (
                  <p key={line} className="text-muted-foreground">
                    {line}
                  </p>
                ))}
              </div>
              {item.buttons && (
                <div className="flex flex-col sm:flex-row gap-3 justify-center">
                  {item.buttons.map((button) => (
                    <Button
                      key={button.label}
                      variant="outline"
                      size="sm"
                      asChild
                    >
                      <a href={button.href} target="_blank" rel="noopener noreferrer">
                        <button.icon className="mr-2 h-4 w-4" />
                        {button.label}
                      </a>
                    </Button>
                  ))}
                </div>
              )}
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
};
