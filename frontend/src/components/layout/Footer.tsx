import { Leaf, Phone, Mail, MapPin } from 'lucide-react';

const footerLinks = {
  catalog: {
    title: 'Каталог',
    links: [
      { label: 'Мёд', href: '#' },
      { label: 'Масла', href: '#' },
      { label: 'Все товары', href: '#' },
    ],
  },
  company: {
    title: 'Компания',
    links: [
      { label: 'Сотрудничество', href: '#partner' },
      { label: 'Блог', href: '#blog' },
      { label: 'О нас', href: '#about' },
      { label: 'Отзывы', href: '#reviews' },
      { label: 'Контакты', href: '#contacts' },
    ],
  },
  info: {
    title: 'Информация',
    links: [
      { label: 'Доставка', href: '#' },
      { label: 'Оплата', href: '#' },
      { label: 'Возврат', href: '#' },
      { label: 'Политика конфиденциальности', href: '#' },
    ],
  },
};

export const Footer = () => {
  return (
    <footer className="bg-primary text-primary-foreground">
      <div className="container mx-auto px-4 py-16">
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-12">
          {/* Logo & Description */}
          <div className="sm:col-span-2 lg:col-span-1">
            <div className="flex items-center gap-2 mb-4">
              <Leaf className="h-8 w-8" />
              <span className="text-2xl font-display font-bold">BioFarm</span>
            </div>
            <p className="text-primary-foreground/70 mb-6 text-sm leading-relaxed">
              Натуральные продукты с собственных ферм. 
              Мы заботимся о вашем здоровье и качестве жизни.
            </p>
            <div className="space-y-3">
              <a
                href="tel:+79991234567"
                className="flex items-center gap-3 text-primary-foreground/70 hover:text-primary-foreground transition-colors text-sm"
              >
                <Phone className="h-4 w-4" />
                +7 (999) 123-45-67
              </a>
              <a
                href="mailto:info@biofarm.store"
                className="flex items-center gap-3 text-primary-foreground/70 hover:text-primary-foreground transition-colors text-sm"
              >
                <Mail className="h-4 w-4" />
                info@biofarm.store
              </a>
              <div className="flex items-start gap-3 text-primary-foreground/70 text-sm">
                <MapPin className="h-4 w-4 mt-0.5" />
                <span>г. Серпухов, Московская область</span>
              </div>
            </div>
          </div>

          {/* Links */}
          {Object.values(footerLinks).map((section) => (
            <div key={section.title}>
              <h4 className="font-bold mb-4">{section.title}</h4>
              <ul className="space-y-3">
                {section.links.map((link) => (
                  <li key={link.label}>
                    <a
                      href={link.href}
                      className="text-primary-foreground/70 hover:text-primary-foreground transition-colors text-sm"
                    >
                      {link.label}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        {/* Bottom */}
        <div className="border-t border-primary-foreground/10 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
          <p className="text-primary-foreground/50 text-sm">
            © {new Date().getFullYear()} BioFarm. Все права защищены.
          </p>
          <div className="flex items-center gap-4">
            <span className="text-primary-foreground/50 text-sm">
              ИНН: 1234567890 | ОГРН: 1234567890123
            </span>
          </div>
        </div>
      </div>
    </footer>
  );
};
