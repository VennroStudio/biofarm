import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { HeroSection } from '@/components/sections/HeroSection';
import { FeaturesSection } from '@/components/sections/FeaturesSection';
import { CatalogSection } from '@/components/sections/CatalogSection';
import { VideoSection } from '@/components/sections/VideoSection';
import { BlogSection } from '@/components/sections/BlogSection';
import { SeoSection } from '@/components/sections/SeoSection';
import { MarketplacesSection } from '@/components/sections/MarketplacesSection';
import { ReviewsSection } from '@/components/sections/ReviewsSection';
import { ContactsSection } from '@/components/sections/ContactsSection';
import { useDocumentTitle } from '@/hooks/useDocumentTitle';

const Index = () => {
  useDocumentTitle('Качество, проверенное природой');
  return (
    <div className="min-h-screen">
      <Header />
      <main>
        <HeroSection />
        <FeaturesSection />
        <CatalogSection />
        <VideoSection />
        <BlogSection />
        <SeoSection />
        <MarketplacesSection />
        <ReviewsSection />
        <ContactsSection />
      </main>
      <Footer />
    </div>
  );
};

export default Index;
