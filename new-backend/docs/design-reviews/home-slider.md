# Homepage banner slider

Five slides replace the previous homepage hero. Copy and catalogue links are HTML, not baked into the backgrounds. No additional brand logo is displayed in the banners.

## Artwork

The five `public/assets/images/home/slider/*-original.jpg` files are unchanged copies of the customer's reference images (intro, care, health, nature, daily). CSS windows show the lower product section, preserving every bottle and label exactly. The surrounding reference headlines, logos and buttons are outside the visible window. Desktop layout places the original artwork on the right; mobile places it below the copy/video. Do not regenerate the bottles or their labels.

Only `botanical-background.webp` was generated using the built-in imagegen tool. Final prompt:

> Use case ads-marketing. Generate a background-only panoramic 2.5:1 hero website image (2000x800). Warm luminous ivory cream and pale peach beige plaster, delicate soft botanical sunlight shadows from offscreen leaves, a few realistic fresh green leaves at upper LEFT and lower LEFT corners, mostly empty and subtle. Inspired by premium natural supplement photography. Entire center and right very pale cream beige softly lit, large empty space for overlaid original product photography and text in HTML. No products, no jars, no text, no logo, no buttons, no platform, no laboratory objects. Single continuous background. Palette #fcebd9 #f5e4d1 with foliage green.

The supplied `Родиола-1.mp4` is encoded as H.264/AAC at 1280×720 with faststart at `public/assets/videos/home/rhodiola.mp4`; the poster is its first-second frame. Audio is retained and initially muted, with native player controls.

## Behavior

- Wide layout: video on the left, original product artwork on the right.
- Mobile layout: video above the assortment; copy above product pairs.
- Arrows, dots, keyboard arrows, horizontal swipe and automatic rotation (8 seconds).
- Rotation waits while video plays and continues after manual navigation, hover and focus. There is no pause/stop control. Only a hidden browser tab suspends playback.
- Reduced-motion preference disables video autoplay and visual transitions.
- Hidden slides are inert; a live region announces manual navigation. The first slide and native video controls work without JavaScript.
- All catalogue buttons link to `/catalog`.

Assets are in Git (not database uploads) and travel with the normal repository deployment. No production pull is part of this change.
