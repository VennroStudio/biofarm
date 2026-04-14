import { Link } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { Button } from '@/components/ui/button';
import { useDocumentTitle } from '@/hooks/useDocumentTitle';
import {
  publicOfferBlocks,
  type PublicOfferBlock,
} from '@/data/publicOfferContent';

const proseArticle =
  'prose prose-lg max-w-3xl mx-auto ' +
  'prose-headings:font-display prose-headings:text-foreground prose-headings:font-bold ' +
  'prose-h1:text-3xl prose-h1:mb-2 ' +
  'prose-h2:text-xl prose-h2:mt-10 prose-h2:mb-4 ' +
  'prose-h3:text-lg prose-h3:font-semibold prose-h3:mt-6 prose-h3:mb-3 ' +
  'prose-p:text-muted-foreground prose-p:leading-relaxed ' +
  'prose-strong:text-foreground prose-strong:font-semibold ' +
  'prose-ul:text-muted-foreground prose-ul:my-4 ' +
  'prose-li:my-1 ' +
  'prose-a:text-primary prose-a:no-underline hover:prose-a:underline';

function renderBlock(block: PublicOfferBlock, index: number) {
  switch (block.type) {
    case 'h1':
      return <h1 key={index}>{block.text}</h1>;
    case 'lead':
      return (
        <p
          key={index}
          className="not-prose text-lg text-muted-foreground -mt-2 mb-8"
        >
          {block.text}
        </p>
      );
    case 'h2':
      return <h2 key={index}>{block.text}</h2>;
    case 'h3':
      return <h3 key={index}>{block.text}</h3>;
    case 'p':
      return <p key={index}>{block.text}</p>;
    case 'ul':
      return (
        <ul key={index}>
          {block.items.map((item, j) => (
            <li key={j}>{item}</li>
          ))}
        </ul>
      );
    default:
      return null;
  }
}

const PublicOffer = () => {
  useDocumentTitle('Публичная оферта');

  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      <main className="flex-1 container mx-auto px-4 mt-10 py-16 md:py-16">
        <Button variant="ghost" size="sm" className="mb-8 -ml-2" asChild>
          <Link to="/">
            <ArrowLeft className="mr-2 h-4 w-4" />
            На главную
          </Link>
        </Button>

        <article className={proseArticle}>
          {publicOfferBlocks.map((block, i) => renderBlock(block, i))}

          <p className="text-sm not-prose pt-8 text-muted-foreground border-t border-border mt-10">
            См. также{' '}
            <Link to="/privacy" className="text-primary hover:underline">
              политику конфиденциальности
            </Link>
            {' '}и{' '}
            <Link
              to="/personal-data-consent"
              className="text-primary hover:underline"
            >
              согласие на обработку персональных данных
            </Link>
            .
          </p>
        </article>
      </main>
      <Footer />
    </div>
  );
};

export default PublicOffer;
