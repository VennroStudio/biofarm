import { Link } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { Button } from '@/components/ui/button';
import { useDocumentTitle } from '@/hooks/useDocumentTitle';

const operatorName =
  'ООО «Институт изучения биологически активных веществ "Биофарм"»';

const PersonalDataConsent = () => {
  useDocumentTitle('Согласие на обработку персональных данных');

  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      <main className="flex-1 container mx-auto px-4 mt-10 py-16 md:py-16">
        <Button variant="ghost" size="sm" className="mb-8 -ml-2" asChild>
          <Link to="/login">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Назад
          </Link>
        </Button>

        <article
          className="prose prose-lg max-w-3xl mx-auto
            prose-headings:font-display prose-headings:text-foreground prose-headings:font-bold
            prose-h1:text-3xl prose-h1:mb-6
            prose-h2:text-xl prose-h2:mt-10 prose-h2:mb-4
            prose-p:text-muted-foreground prose-p:leading-relaxed
            prose-strong:text-foreground prose-strong:font-semibold
            prose-ul:text-muted-foreground prose-ul:my-4
            prose-li:my-1
            prose-a:text-primary prose-a:no-underline hover:prose-a:underline"
        >
          <h1>Согласие на обработку персональных данных</h1>
          <p>
            Настоящим я, действуя свободно, своей волей и в своём интересе, даю
            согласие {operatorName} (далее — Оператор) на обработку моих
            персональных данных на условиях, изложенных ниже.
          </p>

          <h2>1. Какие данные обрабатываются</h2>
          <p>
            Оператор может обрабатывать следующие категории персональных данных,
            предоставленных мной при регистрации, оформлении заказа или иным
            образом в рамках использования сайта и сервисов BioFarm:
          </p>
          <ul>
            <li>фамилия, имя, отчество (при наличии);</li>
            <li>адрес электронной почты;</li>
            <li>номер телефона;</li>
            <li>адрес доставки (при оформлении заказа);</li>
            <li>иные данные, которые я самостоятельно укажу в профиле или при
              обращении в поддержку.</li>
          </ul>

          <h2>2. Цели обработки</h2>
          <p>Персональные данные обрабатываются в целях:</p>
          <ul>
            <li>регистрации и ведения учётной записи на сайте;</li>
            <li>исполнения договора купли-продажи, доставки товаров, приёма
              оплаты;</li>
            <li>информирования о статусе заказа и работе сервиса;</li>
            <li>обработки обращений и обратной связи;</li>
            <li>соблюдения требований законодательства Российской Федерации.</li>
          </ul>

          <h2>3. Действия с персональными данными</h2>
          <p>
            Оператор вправе осуществлять сбор, запись, систематизацию,
            накопление, хранение, уточнение (обновление, изменение), извлечение,
            использование, передачу (предоставление, доступ), обезличивание,
            блокирование, удаление и уничтожение персональных данных — в объёме,
            необходимом для указанных целей.
          </p>

          <h2>4. Передача третьим лицам</h2>
          <p>
            Персональные данные могут быть переданы перевозчикам, платёжным
            системам и иным подрядчикам Оператора исключительно для исполнения
            заказа и оказания услуг, а также по запросу уполномоченных
            государственных органов в случаях, предусмотренных законом.
          </p>

          <h2>5. Срок действия согласия</h2>
          <p>
            Согласие действует до достижения целей обработки, отзыва согласия
            субъектом персональных данных либо до прекращения деятельности
            Оператора, если иное не предусмотрено законодательством Российской
            Федерации.
          </p>

          <h2>6. Отзыв согласия</h2>
          <p>
            Я вправе отозвать настоящее согласие, направив обращение на адрес
            электронной почты, указанный в разделе контактов на сайте, либо
            иным способом, позволяющим подтвердить факт получения обращения
            Оператором. Отзыв не влияет на законность обработки, осуществлённой
            до момента отзыва.
          </p>

          <p className="text-sm not-prose pt-6 text-muted-foreground">
            Актуальная редакция: 14 апреля 2026 г. Также действует{' '}
            <Link to="/privacy" className="text-primary hover:underline">
              политика конфиденциальности
            </Link>
            .
          </p>
        </article>
      </main>
      <Footer />
    </div>
  );
};

export default PersonalDataConsent;
