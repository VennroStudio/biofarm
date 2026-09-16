"""Standalone visual prototypes. Reads a saved public-content snapshot; does not change the app."""
from pathlib import Path
from html import escape as e
import json, shutil

ROOT = Path(__file__).resolve().parent
PUBLIC = ROOT.parents[1] / "new-backend/public"
SITE = "http://localhost:8088"
SECTIONS = json.loads((ROOT / "source-home.json").read_text())
BY_ID = {x["id"]: x for x in SECTIONS if x["id"]}
PRODUCTS = {p["id"]: p for p in json.loads((ROOT / "source-products.json").read_text())}
PIDS = [34, 32, 29, 35]
KEYS = {34: "osina", 32: "chaga", 29: "rhodiola", 35: "mix"}
NAMES = {34: "Экстракт коры осины", 32: "Чага с клеточным соком пихты", 29: "Родиола розовая", 35: "Микс экстрактов лопуха, осины и пихты"}

def local(src):
    source = PUBLIC / src.lstrip("/")
    dest = ROOT / "assets" / source.name
    if source.exists() and not dest.exists(): shutil.copy2(source, dest)
    return "assets/" + source.name

def url(path):
    return path if path.startswith(("http", "mailto:", "#")) else SITE + path

def money(n): return f"{int(n):,}".replace(",", " ") + " ₽"

ICONS = {
 "leaf": '<path d="M20 3C10 2 3 7 4 15c1 7 11 8 15 0 2-4 1-12 1-12Z"/><path d="m3 22 14-15M7 17l-1-7m5 3 6 1"/>',
 "arrow": '<path d="M4 12h15m-6-6 6 6-6 6"/>',
 "out": '<path d="M7 17 19 5M8 5h11v11"/>',
 "bag": '<path d="M5 8h14l1 13H4L5 8Z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/>',
 "search": '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
 "play": '<path d="m9 5 11 7-11 7Z"/>',
 "doc": '<path d="M6 2h9l5 5v15H4V2h2Z"/><path d="M14 2v6h6M8 12h8M8 16h8"/>',
 "box": '<path d="m3 7 9-5 9 5v11l-9 5-9-5V7Z"/><path d="m3 7 9 5 9-5M12 12v11m-5-18 9 5"/>',
 "flask": '<path d="M8 2h8M9 2v7l-6 11c-.5 1 .5 2 2 2h14c1.5 0 2.5-1 2-2L15 9V2M6 15h12"/>',
 "capsule": '<path d="M5 19a6 6 0 0 1 0-8l6-6a6 6 0 0 1 8 8l-6 6a6 6 0 0 1-8 0Z"/><path d="m8 8 8 8"/>',
 "grid": '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
 "shield": '<path d="m12 2 9 4v6c0 6-9 10-9 10S3 18 3 12V6l9-4Z"/><path d="m8 12 3 3 5-6"/>',
 "gift": '<path d="M3 8h18v5H3Zm2 5v9h14v-9M12 8v14"/><path d="M12 8C1 8 5-3 12 8Zm0 0C23 8 19-3 12 8Z"/>',
 "truck": '<path d="M2 4h13v13H2ZM15 9h4l3 4v4h-7"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="18" r="3"/>',
 "check": '<path d="m5 12 5 5L20 6"/>',
 "close": '<path d="m5 5 14 14M5 19 19 5"/>',
 "mail": '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 5 10 8L22 5"/>',
}
def ico(name, cls=""):
    return '<svg class="icon '+cls+'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'+ICONS[name]+'</svg>'
def link(text, href, cls="text-link", icon="arrow"):
    return '<a class="'+cls+'" href="'+e(url(href))+'">'+e(text)+ico(icon)+'</a>'
def label(text): return '<p class="eyebrow">'+e(text)+'</p>'
def head(kicker, title, desc="", action=""):
    return '<div class="section-head"><div>'+label(kicker)+'<h2>'+title+'</h2>'+('<p class="intro">'+e(desc)+'</p>' if desc else '')+'</div>'+action+'</div>'
def pack(pid, cls=""):
    return '<img class="pack '+cls+'" src="assets/'+KEYS[pid]+'.png" alt="'+e(PRODUCTS[pid]["name"])+'" width="420" height="420">'
def product_href(pid): return SITE + "/product/" + PRODUCTS[pid]["slug"]


REVIEWS=json.loads((ROOT/"source-reviews.json").read_text())
REVIEWS=sorted(REVIEWS,key=lambda r:['Алина','Виктория','Лилия','Людмила','Светлана'].index(r['name']))
POSTER=local("/uploads/images/index/poster.webp")
FOREST=local("/assets/images/home/hero-poster.jpg")
for sec in (BY_ID["certificates"],):
    for x in sec["links"]:
        if x["href"].startswith("/uploads/"):local(x["href"])

def header():
    return '''<header class="site-header wrap"><a class="brand" href="#top">'''+ico("leaf")+'''<span>БИОФАРМ</span></a>
    <nav aria-label="Главное меню"><a href="#catalog">Каталог</a><a href="#production">Производство</a><a href="#documents">Документы</a><a href="#blog">Блог</a><a href="#partner">Партнёрам</a></nav>
    <div class="header-actions"><button class="icon-button search-toggle" aria-label="Открыть поиск">'''+ico("search")+'''</button><button class="icon-button cart-toggle" aria-label="Открыть корзину">'''+ico("bag")+'''<span class="cart-count" hidden>0</span></button></div>
    <form class="search-panel" role="search" action="http://localhost:8088/catalog" hidden><label for="search">Поиск по каталогу</label><input id="search" name="search" placeholder="Название товара"><button class="button">Найти '''+ico("arrow")+'''</button></form></header>'''


def categories():
    cats=[("Оздоровительная продукция","Наша продукция","leaf","/?category=1#catalog"),("Экстракты и концентраты","Растительные компоненты","flask","/?category=2#catalog"),("Капсулы","Удобная форма выпуска","capsule","/?category=4#catalog"),("Весь каталог","Все продукты БИОФАРМ","grid","/catalog")]
    cards="".join('<a class="category-card" href="'+url(href)+'"><div>'+ico(icon)+'</div><strong>'+title+'</strong><small>'+sub+'</small>'+ico("arrow")+'</a>' for title,sub,icon,href in cats)
    return '<section class="categories wrap section"><div>'+label("С чего начнём?")+'<h2>Категории <br>продуктов</h2><p class="intro">Растительные экстракты и капсулы БИОФАРМ.</p></div><div class="category-grid">'+cards+'</div></section>'

def catalog():
    cards=""
    for pid in PIDS:
        p=PRODUCTS[pid]; key=KEYS[pid]
        cards+='<article class="product-card" data-type="'+("capsules" if pid in [29,32] else "extracts")+'"><a href="'+product_href(pid)+'" class="product-visual '+key+'">'+pack(pid)+'</a><div class="product-copy"><p class="product-type">'+("Капсулы" if pid in [29,32] else "Растительный экстракт")+'</p><a href="'+product_href(pid)+'" class="product-title">'+e(NAMES[pid])+'</a><p class="weight">'+e(p["weight"])+'</p><div class="buy-row"><strong>'+money(p["price"])+'</strong><button class="add-cart" data-id="'+str(pid)+'" aria-label="В корзину: '+e(NAMES[pid])+'">'+ico("bag")+'<span>В корзину</span></button></div></div></article>'
    return '<section class="catalog-section section" id="catalog"><div class="wrap">'+head("Наша продукция","Популярные продукты", "",link("Весь каталог","/catalog"))+'<div class="filters" aria-label="Фильтр каталога"><button class="active" data-filter="all" aria-pressed="true">Все продукты</button><button data-filter="extracts" aria-pressed="false">Экстракты</button><button data-filter="capsules" aria-pressed="false">Капсулы</button></div><div class="product-grid">'+cards+'</div></div></section>'





def reviews():
    return '<section class="reviews section wrap" id="reviews">'+head("Отзывы","Отзывы покупателей","",'<div class="review-controls"><button class="icon-button review-prev" aria-label="Предыдущие отзывы">'+ico("arrow","reverse")+'</button><button class="icon-button review-next" aria-label="Следующие отзывы">'+ico("arrow")+'</button></div>')+'<div class="reviews-grid" aria-live="polite"></div><div class="review-dots"></div></section>'

def marketplaces():
    cards=""
    markets=[("Wildberries","wb","https://www.wildberries.ru/seller/1009387"),("Ozon","ozon","https://www.ozon.ru/seller/institut-izucheniya-biologicheski-aktivnyh-veshchestv-biofarm-647407/")]
    for name,key,href in markets:
        cards+='<article class="market-card '+key+'"><div class="market-top"><span class="market-logo">'+("WB" if key=="wb" else "ozon")+'</span><div><h3>'+name+'</h3><p>Официальный магазин</p></div></div><p class="market-description">Каталог продукции БИОФАРМ<br>и оформление заказа на '+name+'.</p><img class="parcel" src="assets/parcel.png" alt="" loading="lazy">'+link("Перейти на "+name,href,"button market-button","out")+'</article>'
    return '<section class="marketplaces section wrap" id="marketplaces">'+head("Где купить","БИОФАРМ на маркетплейсах","")+'<div class="market-grid">'+cards+'</div></section>'


def partner():
    t=BY_ID["partner"]["text"]
    benefits="".join('<div class="partner-benefit">'+ico(icon)+'<div><h3>'+e(t[idx])+'</h3><p>'+e(t[idx+1])+'</p></div></div>' for idx,icon in [(2,"leaf"),(4,"doc"),(6,"box"),(8,"shield")])
    return '<section class="partner-section section" id="partner"><div class="wrap partner-panel"><div class="partner-copy">'+label("Сотрудничество с нами")+'<h2>Растём <br><em>вместе</em></h2><p class="partner-intro">'+e(t[1])+'</p><div class="partner-benefits">'+benefits+'</div></div><form class="partner-form"><div class="form-number">'+ico("mail")+'<span>Для магазинов и партнёров</span></div><h3>Обсудим сотрудничество</h3><p>Заполните форму, и мы свяжемся с вами в ближайшее время.</p><div class="field-row"><label>Ваше имя <span>*</span><input name="name" required autocomplete="name" placeholder="Как к вам обращаться"></label><label>Телефон<input name="phone" type="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__"></label></div><label>Email <span>*</span><input name="email" type="email" autocomplete="email" required placeholder="name@company.ru"></label><label>Сообщение <span>*</span><textarea name="message" required rows="3" placeholder="Расскажите о вашей компании и предложении"></textarea></label><button type="submit" class="button form-submit">Отправить заявку '+ico("arrow")+'</button><small>Нажимая кнопку, вы соглашаетесь с <a href="'+SITE+'/privacy">политикой конфиденциальности</a></small><p class="form-feedback" role="status" hidden></p></form></div></section>'

def footer():
    t=BY_ID["contacts"]["text"]
    address=t[t.index("Адрес")+1]
    return '<footer class="site-footer"><div class="wrap footer-grid"><div><a class="brand" href="#top">'+ico("leaf")+'<span>БИОФАРМ</span></a><p>Институт изучения<br>биологически активных веществ</p></div><div><h3>Покупателям</h3>'+link("Каталог","/catalog")+link("Доставка","/dostavka")+link("Оплата","/oplata")+link("Возврат","/vozvrat")+'</div><div><h3>О компании</h3>'+link("Производство","#production")+link("Сертификаты","/certificates")+link("Блог","/blog")+link("Сотрудничество","#partner")+'</div><div class="contacts" id="contacts"><h3>Контакты</h3><p>'+e(address)+'</p><a href="mailto:bio.active@bk.ru">bio.active@bk.ru</a><p>Пн–Пт: 10:00–19:00<br>Сб: 10:00–17:00 · Вс: выходной</p></div></div><div class="wrap footer-bottom"><span>© 2026 БИОФАРМ</span><a href="'+SITE+'/oferta">Публичная оферта</a><a href="'+SITE+'/privacy">Политика конфиденциальности</a></div></footer>'

def compact_hero():
    photos=''
    for pid in [32,34,29]:
        name={32:'Чага',34:'Экстракт коры осины',29:'Родиола розовая'}[pid]
        photos+='<a class="hero-product hp-'+KEYS[pid]+'" href="'+product_href(pid)+'" aria-label="'+e(NAMES[pid])+' — подробнее">'+pack(pid)+'<span class="hero-caption">'+name+' '+ico('out')+'</span></a>'
    return '<div class="hero-shell" id="top">'+header()+'<section class="hero wrap"><div class="hero-copy"><h1>Природа Сибири.<br>Забота каждый день.</h1><p>Растительные экстракты и капсулы БИОФАРМ</p>'+link('Смотреть каталог','#catalog','button hero-cta')+'<div class="hero-note">'+ico('leaf')+'Природа. Наука. Забота.</div></div><div class="hero-scene"><span class="orbit orbit-one"></span><span class="orbit orbit-two"></span><span class="capsule cap-one"></span><span class="capsule cap-two"></span><span class="capsule cap-three"></span>'+photos+'</div></section></div>'

def compact_production():
    return '<section class="production section" id="production"><div class="wrap production-inner"><div class="production-copy"><h2>От сырья<br>до готового продукта</h2><p>Контролируем каждый этап: от отбора сырья до производства, фасовки и выпуска партии.</p><div class="production-facts"><span>'+ico('flask')+'Собственная лаборатория</span><span>'+ico('box')+'Полный цикл</span><span>'+ico('shield')+'Контроль партий</span></div><button class="button video-open">'+ico('play')+'Смотреть видео</button></div></div></section>'

def compact_documents():
    items=''
    titles=['Протокол контроля качества','Декларация соответствия','Протокол качества партии']
    for i,x in enumerate(BY_ID['certificates']['links'][1:]):
        items+='<a class="document-card" href="'+local(x['href'])+'" target="_blank" rel="noopener"><div class="paper" aria-hidden="true"><span>БИОФАРМ</span><b>'+titles[i]+'</b><i></i><i></i><i></i><i></i><i></i></div><span class="document-label">'+titles[i]+'</span><small class="demo-label">Демо</small>'+ico('out')+'</a>'
    return '<section class="documents wrap" id="documents"><div class="document-rail">'+link('Сертификаты и документы','/certificates','document-heading','doc')+items+'</div></section>'

def compact_blog():
    sec=BY_ID['blog']; items=''
    for i,x in enumerate(sec['links'][1:]):
        t=x['text'];img=local(sec['images'][i])
        items+='<a class="article-card" href="'+url(x['href'])+'"><div class="article-photo"><img src="'+img+'" alt="'+e(t[2])+'" loading="lazy"><span class="article-tag">'+e(t[0])+'</span><time>18 апреля 2026</time></div><div class="article-copy"><h3>'+e(t[2])+'</h3>'+ico('arrow')+'</div></a>'
    return '<section class="blog section wrap" id="blog">'+head('','Больше знать — проще выбирать','',link('Все статьи','/blog'))+'<div class="blog-grid">'+items+'</div></section>'

def compact_loyalty():
    return '<section class="loyalty section" id="loyalty"><div class="wrap loyalty-inner"><div class="gift-image"><img src="assets/parcel.png" alt="Подарочная посылка БИОФАРМ" loading="lazy"></div><div class="loyalty-copy"><div class="loyalty-heading"><div><h2>Покупайте с выгодой</h2><p>Бонусная программа для следующих покупок в магазине БИОФАРМ.</p></div>'+link('Условия программы','/loyalnost')+'</div><div class="loyalty-facts"><div><span class="percent">%</span><p><strong>5% бонусами</strong><br>за заказы</p></div><div>'+ico('gift')+'<p><strong>Промокоды</strong><br>при оформлении заказа</p></div><div>'+ico('truck')+'<p><strong>Бесплатная доставка</strong><br>от 3 000 ₽</p></div></div></div></div></section>'

def compact_partner():
    content=partner()
    content=content.replace('</div><label>Email','</div><div class="field-row"><label>Email')
    content=content.replace('</textarea></label><button','</textarea></label></div><button')
    content=content.replace('100% натуральная продукция','Натуральная продукция')
    content=content.replace('Мы уверены в качестве своего продукта, поэтому смело отправляем пробную партию!','Отправляем пробную партию.')
    content=content.replace('Вся продукция сертифицирована и соответствует высшим стандартам качества.','Документы на продукцию.')
    content=content.replace('Поставка на реализацию на месяц без предоплаты: продали — оплатили — повторяем.','Реализация на месяц.')
    content=content.replace('Гарантия качества','Не продадите — заберём').replace('Не продадите — заберём товар обратно. Без оплат, комиссий и штрафов.','Без комиссий и штрафов.')
    return content

body=compact_hero()+'<main>'+categories()+catalog()+compact_production()+compact_documents()+compact_blog()+reviews()+marketplaces()+compact_loyalty()+compact_partner()+'</main>'+footer()
data=json.dumps({pid:{'name':NAMES[pid],'price':PRODUCTS[pid]['price'],'image':'assets/'+KEYS[pid]+'.png'} for pid in PIDS},ensure_ascii=False)
dialogs='<dialog id="media-modal" aria-label="Просмотр"><button class="modal-close icon-button" aria-label="Закрыть просмотр">'+ico('close')+'</button><div class="media-content"></div></dialog><dialog id="cart-modal" aria-label="Корзина прототипа"><button class="modal-close icon-button" aria-label="Закрыть корзину">'+ico('close')+'</button><h2>Ваша корзина</h2><div class="cart-items"></div><p class="cart-total"></p><p class="prototype-hint">Демонстрация дизайна. Заказ не отправляется.</p></dialog><div class="toast" role="status" hidden></div>'
page='<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Визуальный прототип главной страницы БИОФАРМ"><title>БИОФАРМ — компактный дизайн</title><link rel="stylesheet" href="style.css"></head><body>'+body+dialogs+'<script>const reviewsData='+json.dumps(REVIEWS,ensure_ascii=False)+'; const productData='+data+';</script><script src="preview.js"></script></body></html>'
(ROOT/'index.html').write_text(page)
print('Built compact homepage: '+str(ROOT/'index.html'))
