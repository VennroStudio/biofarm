import { mountSiteHeader } from './components/layout/header';
import { mountProductCounter } from './components/product/counter-island';
import { mountProductGallery } from './components/product/gallery';
import { mountRevealEffects } from './components/ui/reveal';
import { mountHomeReviews } from './sections/home/reviews';
import { mountHomeVideo } from './sections/home/video';

mountSiteHeader();
mountProductCounter();
mountProductGallery();
mountRevealEffects();
mountHomeVideo();
mountHomeReviews();
