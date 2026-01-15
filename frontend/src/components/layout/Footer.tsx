import { Building2 } from 'lucide-react';

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
          {/* Legal Information */}
          <div className="sm:col-span-2 lg:col-span-1">
            <div className="flex items-center gap-2 mb-6">
              <Building2 className="h-6 w-6" />
              <h4 className="font-bold text-lg">Реквизиты организации</h4>
            </div>
            <div className="space-y-4 text-sm">
              <div>
                <p className="text-primary-foreground/90 font-semibold mb-2">Название организации:</p>
                <p className="text-primary-foreground/70 leading-relaxed">
                  ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "ИНСТИТУТ ИЗУЧЕНИЯ БИОЛОГИЧЕСКИ АКТИВНЫХ ВЕЩЕСТВ "БИОФАРМ"
                </p>
              </div>
              <div>
                <p className="text-primary-foreground/90 font-semibold mb-2">Юридический адрес:</p>
                <p className="text-primary-foreground/70 leading-relaxed">
                  634045, РОССИЯ, ТОМСКАЯ ОБЛАСТЬ, Г.О. ГОРОД ТОМСК, Г ТОМСК, ТЕР. АПРЕЛЬ ПОСЕЛОК, УЛ ЛИСТОПАДНАЯ, Д. 77
                </p>
              </div>
            </div>
          </div>

          {/* Реквизиты - Column 2 */}
          <div>
            <h4 className="font-bold mb-4">Реквизиты</h4>
            <div className="space-y-4 text-sm">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-primary-foreground/90 font-semibold mb-1">ИНН:</p>
                  <p className="text-primary-foreground/70">7017490966</p>
                </div>
                <div>
                  <p className="text-primary-foreground/90 font-semibold mb-1">КПП:</p>
                  <p className="text-primary-foreground/70">701701001</p>
                </div>
                <div className="col-span-2">
                  <p className="text-primary-foreground/90 font-semibold mb-1">ОГРН:</p>
                  <p className="text-primary-foreground/70">1227000001557</p>
                </div>
              </div>
              <div>
                <p className="text-primary-foreground/90 font-semibold mb-2">Расчетный счет:</p>
                <p className="text-primary-foreground/70">40702810610001034144</p>
              </div>
            </div>
          </div>

          {/* Банк - Column 3 */}
          <div>
            <h4 className="font-bold mb-4">Банк</h4>
            <div className="space-y-4 text-sm">
              <div>
                <p className="text-primary-foreground/70 mb-3">АО «ТБанк»</p>
                <div className="space-y-2 text-primary-foreground/70">
                  <p><span className="font-semibold">ИНН банка:</span> 7710140679</p>
                  <p><span className="font-semibold">БИК банка:</span> 044525974</p>
                  <p><span className="font-semibold">Корр. счет:</span> 30101810145250000974</p>
                  <p className="text-xs mt-2">
                    <span className="font-semibold">Адрес банка:</span> 127287, г. Москва, ул. Хуторская 2-я, д. 38А, стр. 26
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Links - Column 4 */}
          <div>
            <h4 className="font-bold mb-4">{footerLinks.info.title}</h4>
            <ul className="space-y-3">
              {footerLinks.info.links.map((link) => (
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
        </div>

        {/* Bottom */}
        <div className="border-t border-primary-foreground/10 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
          <p className="text-primary-foreground/50 text-sm">
            © {new Date().getFullYear()} BioFarm. Все права защищены.
          </p>
          <div className="flex items-center gap-4">
            <span className="text-primary-foreground/50 text-sm">
              Сделано VNS Studio
            </span>
          </div>
        </div>
      </div>
    </footer>
  );
};
