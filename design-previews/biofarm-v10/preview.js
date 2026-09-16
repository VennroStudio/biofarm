/* Standalone presentation interactions. No orders or partnership requests are sent. */
const $ = (s, root = document) => root.querySelector(s);
const $$ = (s, root = document) => [...root.querySelectorAll(s)];
const el = (tag, cls, text) => {
  const node = document.createElement(tag);
  if (cls) node.className = cls;
  if (text !== undefined) node.textContent = text;
  return node;
};
const media = $('#media-modal');
const mediaContent = $('.media-content');
const menuButton = el('button', 'icon-button menu-toggle', '☰');
menuButton.setAttribute('aria-label', 'Открыть меню'); menuButton.setAttribute('aria-expanded', 'false');
$('.header-actions').prepend(menuButton);
menuButton.addEventListener('click', () => {
  const expanded = $('.site-header nav').classList.toggle('mobile-open');
  menuButton.setAttribute('aria-expanded', String(expanded));
});
$$('.site-header nav a').forEach(a => a.addEventListener('click', () => {
  $('.site-header nav').classList.remove('mobile-open'); menuButton.setAttribute('aria-expanded', 'false');
}));
function showPhoto(src, name) {
  const img = el('img'); img.src = src; img.alt = `Фото к отзыву: ${name}`;
  mediaContent.replaceChildren(img); media.showModal();
}
$$('.video-open').forEach(button => button.addEventListener('click', () => {
  const video = el('video'); video.src = 'http://localhost:8088/uploads/videos/biofarm.mp4';
  video.controls = true; video.autoplay = true;
  mediaContent.replaceChildren(video); media.showModal();
}));
$$('dialog').forEach(dialog => {
  $('.modal-close', dialog).addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    const r = dialog.getBoundingClientRect();
    if (event.target === dialog && (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom)) dialog.close();
  });
});
media.addEventListener('close', () => mediaContent.replaceChildren());

let reviewIndex = 0;
function renderReviews() {
  const cards = [];
  for (let offset = 0; offset < Math.min(3, reviewsData.length); offset++) {
    const review = reviewsData[(reviewIndex + offset) % reviewsData.length];
    const card = el('article', 'review-card');
    const person = el('div', 'review-person');
    person.append(el('span', 'review-avatar', review.name.slice(0, 1)));
    const identity = el('div'); identity.append(el('strong', '', review.name), el('small', '', 'Отзыв покупателя'));
    person.append(identity);
    const stars = el('div', 'stars', '★'.repeat(review.rating));
    stars.setAttribute('aria-label', `Оценка ${review.rating} из 5`);
    const photos = el('div', 'review-photos');
    review.images.forEach(src => {
      const button = el('button', 'review-photo'); button.type = 'button';
      button.setAttribute('aria-label', `Увеличить фото: ${review.name}`);
      const img = el('img'); img.src = src; img.alt = `Фото к отзыву: ${review.name}`; img.loading = 'lazy';
      button.append(img); button.addEventListener('click', () => showPhoto(src, review.name));
      photos.append(button);
    });
    card.append(person, stars, el('p', 'review-text', review.text), photos); cards.push(card);
  }
  $('.reviews-grid').replaceChildren(...cards);
  $('.review-dots').replaceChildren(...reviewsData.map((_, i) => {
    const dot = el('button', i === reviewIndex ? 'active' : '');
    dot.setAttribute('aria-label', `Отзывы: группа ${i + 1}`);
    dot.setAttribute('aria-pressed', String(i === reviewIndex));
    dot.addEventListener('click', () => { reviewIndex = i; renderReviews(); }); return dot;
  }));
}
$('.review-prev').addEventListener('click', () => { reviewIndex = (reviewIndex - 1 + reviewsData.length) % reviewsData.length; renderReviews(); });
$('.review-next').addEventListener('click', () => { reviewIndex = (reviewIndex + 1) % reviewsData.length; renderReviews(); });
renderReviews();

$$('[data-filter]').forEach(button => button.addEventListener('click', () => {
  $$('[data-filter]').forEach(item => {
    item.classList.toggle('active', item === button);
    item.setAttribute('aria-pressed', String(item === button));
  });
  $$('.product-card').forEach(card => { card.hidden = button.dataset.filter !== 'all' && card.dataset.type !== button.dataset.filter; });
}));
const searchToggle = $('.search-toggle');
searchToggle.setAttribute('aria-expanded', 'false');
searchToggle.addEventListener('click', () => {
  const panel = $('.search-panel'); panel.hidden = !panel.hidden;
  searchToggle.setAttribute('aria-expanded', String(!panel.hidden));
  if (!panel.hidden) $('input', panel).focus();
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && !$('.search-panel').hidden) {
    $('.search-panel').hidden = true; searchToggle.setAttribute('aria-expanded', 'false'); searchToggle.focus();
  }
});

const cart = new Map();
const formatPrice = price => new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
let toastTimer;
function renderCart() {
  let total = 0, count = 0;
  const lines = [];
  cart.forEach((qty, id) => {
    const product = productData[id]; count += qty; total += qty * product.price;
    const line = el('div', 'cart-line');
    const img = el('img'); img.src = product.image; img.alt = product.name;
    const copy = el('div'); copy.append(el('strong', '', product.name), el('p', '', `${qty} шт. · ${formatPrice(product.price * qty)}`));
    const remove = el('button', 'icon-button', '×'); remove.setAttribute('aria-label', `Убрать: ${product.name}`);
    remove.addEventListener('click', () => { cart.delete(id); renderCart(); });
    line.append(img, copy, remove); lines.push(line);
  });
  $('.cart-items').replaceChildren(...(lines.length ? lines : [el('p', '', 'В корзине пока нет товаров.')]));
  $('.cart-total').textContent = total ? `Итого: ${formatPrice(total)}` : '';
  $('.cart-count').textContent = count; $('.cart-count').hidden = !count;
}
$$('.add-cart').forEach(button => button.addEventListener('click', () => {
  const id = button.dataset.id; cart.set(id, (cart.get(id) || 0) + 1); renderCart();
  const toast = $('.toast'); toast.textContent = 'Добавлено в корзину прототипа'; toast.hidden = false;
  clearTimeout(toastTimer); toastTimer = setTimeout(() => { toast.hidden = true; }, 2500);
}));
$('.cart-toggle').addEventListener('click', () => { renderCart(); $('#cart-modal').showModal(); });
$('.partner-form').addEventListener('submit', event => {
  event.preventDefault();
  const feedback = $('.form-feedback');
  feedback.textContent = 'Это демонстрация формы. Данные не отправлены.'; feedback.hidden = false;
});
