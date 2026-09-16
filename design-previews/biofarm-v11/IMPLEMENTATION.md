# Compact homepage implementation plan

Goal: turn home-final.png into a complete standalone browser prototype, retaining the compact density requested by the user.

Architecture: standalone HTML/CSS/JS in this directory, same approach as v10. Reuse verified source data, packshots, customer photos, PDFs and UI interaction contracts. Do not change application templates or APIs.

Spec: home-final.png, PROMPT-FINAL.md, PROMPT-CORRECTION.md and current user instruction to implement with code.

- [x] Create build.py producing index.html from saved v10 data. Preserve product links, prices, all four form fields, source reviews, actual PDF files and marketplace destinations.
- [x] Create style.css reproducing the mint photographic header, floating products, compact sections, 4-column product cards, wide production banner, horizontal documents, 3 articles, reviews, approved marketplace cards, bonus banner, pale partner form and footer.
- [x] Copy and adapt preview.js interactions: search/menu, in-memory cart, review carousel and photo modal, video modal, local-only form validation. No request submission.
- [x] Verify files and HTTP links. Inspect desktop and mobile in browser, verify hero entry points, document links, photo enlargement, form fields and no horizontal overflow. Correct visual deviations before delivery.
- [x] Open the complete prototype and give the direct local URL.
