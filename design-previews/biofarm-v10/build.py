"""Standalone visual prototypes. Reads a saved public-content snapshot; does not change the app."""
from pathlib import Path
from html import escape as e
from html.parser import HTMLParser
import json, re, shutil, urllib.request

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

class ReviewsParser(HTMLParser):
    def __init__(self):
        super().__init__(); self.r=None; self.items=[]; self.in_h=False; self.in_p=False
    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if tag=="article" and "data-review-card" in a: self.r={"name":"","text":"","images":[],"rating":5}
        if self.r is not None:
            if tag=="h4": self.in_h=True
            if tag=="p" and "line-clamp" in a.get("class",""): self.in_p=True
            if "data-lightbox-image" in a: self.r["images"].append(local(a["data-lightbox-image"]))
            if a.get("aria-label","").startswith("Оценка "): self.r["rating"]=int(re.search(r"\d",a["aria-label"]).group())
    def handle_data(self, s):
        if self.r is not None:
            if self.in_h:self.r["name"]+=s.strip()
            if self.in_p:self.r["text"]+=s.strip()
    def handle_endtag(self, tag):
        if tag=="h4":self.in_h=False
        if tag=="p":self.in_p=False
        if tag=="article" and self.r is not None:self.items.append(self.r);self.r=None

parser=ReviewsParser()
parser.feed(urllib.request.urlopen(SITE,timeout=20).read().decode())
REVIEWS=parser.items
(ROOT/"source-reviews.json").write_text(json.dumps(REVIEWS,ensure_ascii=False,indent=2))
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

def hero(kind):
    photos=""
    for pid in [32,34,29]:
        photos+='<a class="hero-product hp-'+KEYS[pid]+'" href="'+product_href(pid)+'">'+pack(pid)+'<span class="hero-caption"><strong>'+e(NAMES[pid])+'</strong><small>'+e(PRODUCTS[pid]["weight"])+' · '+money(PRODUCTS[pid]["price"])+'</small>'+ico("out")+'</span></a>'
    title={"a":"Природа Сибири.<br><em>Забота каждый день.</em>","b":"Сила природы.<br><em>Точность науки.</em>","c":"Больше природы<br><em>в вашей жизни.</em>"}[kind]
    return '<section class="hero" id="top"><div class="wrap hero-grid"><div class="hero-copy">'+label("Институт изучения биологически активных веществ")+'<h1>'+title+'</h1><p class="hero-description">Растительные экстракты и капсулы БИОФАРМ из собственной лаборатории.</p><div class="hero-actions">'+link("Выбрать продукт","#catalog","button primary")+ '<button class="video-open text-link">'+ico("play")+'О производстве</button></div><div class="hero-note">'+ico("leaf")+'<span>Сибирские растения.<br>Собственная лаборатория.</span></div></div><div class="hero-scene"><div class="scene-shape"></div><div class="scene-orb"></div>'+photos+'</div></div></section>'

def categories():
    cats=[("Оздоровительная продукция","Наша продукция","leaf","/?category=1#catalog"),("Экстракты и концентраты","Растительные компоненты","flask","/?category=2#catalog"),("Капсулы","Удобная форма выпуска","capsule","/?category=4#catalog"),("Весь каталог","Все продукты БИОФАРМ","grid","/catalog")]
    cards="".join('<a class="category-card" href="'+url(href)+'"><div>'+ico(icon)+'</div><strong>'+title+'</strong><small>'+sub+'</small>'+ico("arrow")+'</a>' for title,sub,icon,href in cats)
    return '<section class="categories wrap section"><div>'+label("С чего начнём?")+'<h2>Продукты<br>для вашей заботы</h2><p class="intro">Выберите удобную форму или познакомьтесь со всем ассортиментом.</p></div><div class="category-grid">'+cards+'</div></section>'

def catalog():
    cards=""
    for pid in PIDS:
        p=PRODUCTS[pid]; key=KEYS[pid]
        cards+='<article class="product-card" data-type="'+("capsules" if pid in [29,32] else "extracts")+'"><a href="'+product_href(pid)+'" class="product-visual '+key+'">'+pack(pid)+'</a><div class="product-copy"><p class="product-type">'+("Капсулы" if pid in [29,32] else "Растительный экстракт")+'</p><a href="'+product_href(pid)+'" class="product-title">'+e(NAMES[pid])+'</a><p class="weight">'+e(p["weight"])+'</p><div class="buy-row"><strong>'+money(p["price"])+'</strong><button class="add-cart" data-id="'+str(pid)+'" aria-label="В корзину: '+e(NAMES[pid])+'">'+ico("bag")+'<span>В корзину</span></button></div></div></article>'
    return '<section class="catalog-section section" id="catalog"><div class="wrap">'+head("Наша продукция","Выбирайте своё", "Натуральные продукты из собственной лаборатории.",link("Весь каталог","/catalog"))+'<div class="filters" aria-label="Фильтр каталога"><button class="active" data-filter="all" aria-pressed="true">Все продукты</button><button data-filter="extracts" aria-pressed="false">Экстракты</button><button data-filter="capsules" aria-pressed="false">Капсулы</button></div><div class="product-grid">'+cards+'</div></div></section>'

def production():
    txt=BY_ID["video"]["text"]
    return '<section class="production section wrap" id="production"><div class="production-photo"><img src="'+POSTER+'" alt="Растительные компоненты и готовые формы продукции БИОФАРМ" loading="lazy"><button class="video-open video-button" aria-label="Смотреть видео о производстве">'+ico("play")+'</button><span class="photo-caption">БИОФАРМ · Наше производство</span></div><div class="production-copy">'+label(txt[0])+'<h2>'+e(txt[1])+'</h2><p class="intro">'+e(" ".join(txt[2].split()))+'</p><div class="process-points">'+"".join('<div><span>0'+str(i+1)+'</span><p>'+e(t)+'</p></div>' for i,t in enumerate(txt[3:6]))+'</div><button class="video-open text-link">Смотреть видео '+ico("arrow")+'</button></div></section>'

def about():
    txt=BY_ID["about"]["text"]
    body="«Биофарм» "+ " ".join(txt[3].split())
    return '<section class="about section"><div class="wrap about-grid"><div>'+label("О компании")+'<h2>Наука и природа —<br>основа БИОФАРМ</h2></div><div><p class="large-copy">'+e(body)+'</p>'+link("Познакомиться с производством","#production")+link("Обсудить сотрудничество","#partner")+'</div></div></section>'

def documents():
    sec=BY_ID["certificates"]; cards=""
    for i,x in enumerate(sec["links"][1:]):
        title,product,desc=x["text"][:3]
        cards+='<a class="document-card" href="'+local(x["href"])+'" target="_blank" rel="noopener"><div class="document-preview"><div class="paper">'+ico("leaf")+'<small>БИОФАРМ</small><strong>'+e(title)+'</strong><span class="paper-rule"></span><span class="paper-rule short"></span><span class="paper-rule"></span><span class="paper-rule short"></span><b>ДЕМО</b></div><span class="file-chip">PDF '+ico("out")+'</span></div><div class="document-copy"><h3>'+e(title)+'</h3><p class="product-ref">'+e(product)+'</p><p>'+e(desc)+'</p><span class="text-link">Открыть документ '+ico("out")+'</span></div></a>'
    return '<section class="documents section wrap" id="documents">'+head("Документы","Качество —<br>в открытом доступе",sec["text"][2],link("Все сертификаты","/certificates"))+'<div class="documents-grid">'+cards+'</div></section>'

def blog():
    sec=BY_ID["blog"]; cards=""
    for i,x in enumerate(sec["links"][1:]):
        t=x["text"]; image=local(sec["images"][i])
        cards+='<a class="article-card article-'+str(i)+'" href="'+url(x["href"])+'"><div class="article-photo"><img src="'+image+'" alt="'+e(t[2])+'" loading="lazy"></div><div class="article-copy"><div class="article-meta"><span>'+e(t[0])+'</span><time>18 апреля 2026</time></div><h3>'+e(t[2])+'</h3><p>'+e(t[3])+'</p><span class="text-link">Читать статью '+ico("arrow")+'</span></div></a>'
    return '<section class="blog section" id="blog"><div class="wrap">'+head("Полезное","Больше знать —<br>проще выбирать","Материалы из блога БИОФАРМ.",link("Все статьи","/blog"))+'<div class="blog-grid">'+cards+'</div></div></section>'

def reviews():
    return '<section class="reviews section wrap" id="reviews">'+head("Отзывы","Что говорят наши клиенты","",'<div class="review-controls"><button class="icon-button review-prev" aria-label="Предыдущие отзывы">'+ico("arrow","reverse")+'</button><button class="icon-button review-next" aria-label="Следующие отзывы">'+ico("arrow")+'</button></div>')+'<div class="reviews-grid" aria-live="polite"></div><div class="review-dots"></div></section>'

def marketplaces():
    cards=""
    markets=[("Wildberries","wb","https://www.wildberries.ru/seller/1009387"),("Ozon","ozon","https://www.ozon.ru/seller/institut-izucheniya-biologicheski-aktivnyh-veshchestv-biofarm-647407/")]
    for name,key,href in markets:
        cards+='<article class="market-card '+key+'"><div class="market-top"><span class="market-logo">'+("WB" if key=="wb" else "ozon")+'</span><div><h3>'+name+'</h3><p>Официальный магазин</p></div></div><p class="market-description">Каталог продукции БИОФАРМ<br>и оформление заказа на '+name+'.</p><img class="parcel" src="assets/parcel.png" alt="" loading="lazy">'+link("Перейти на "+name,href,"button market-button","out")+'</article>'
    return '<section class="marketplaces section wrap" id="marketplaces">'+head("Где купить","БИОФАРМ<br>на маркетплейсах","Покупайте нашу продукцию на популярных маркетплейсах.")+'<div class="market-grid">'+cards+'</div></section>'

def loyalty():
    return '<section class="loyalty section wrap" id="loyalty"><div class="loyalty-head">'+label("Бонусная программа")+'<h2>Покупайте<br>с выгодой</h2>'+link("Условия программы","/loyalnost")+'</div><div class="loyalty-item"><strong>5<span>%</span></strong><h3>Бонусами за заказ</h3><p>Бонусы начисляются после заказа и доступны для оплаты следующих покупок.</p></div><div class="loyalty-item">'+ico("gift")+'<h3>Промокоды</h3><p>Скидочные коды можно применить при оформлении заказа в корзине.</p></div><div class="loyalty-item">'+ico("truck")+'<h3>Бесплатная доставка</h3><p>При заказе от <b>3 000 ₽</b>.</p></div></section>'

def partner():
    t=BY_ID["partner"]["text"]
    benefits="".join('<div class="partner-benefit">'+ico(icon)+'<div><h3>'+e(t[idx])+'</h3><p>'+e(t[idx+1])+'</p></div></div>' for idx,icon in [(2,"leaf"),(4,"doc"),(6,"box"),(8,"shield")])
    return '<section class="partner-section section" id="partner"><div class="wrap partner-panel"><div class="partner-copy">'+label("Сотрудничество с нами")+'<h2>Растём<br><em>вместе.</em></h2><p class="partner-intro">'+e(t[1])+'</p><div class="partner-benefits">'+benefits+'</div></div><form class="partner-form"><div class="form-number">'+ico("mail")+'<span>Для магазинов и партнёров</span></div><h3>Давайте знакомиться</h3><p>Заполните форму, и мы свяжемся с вами в ближайшее время.</p><div class="field-row"><label>Ваше имя <span>*</span><input name="name" required autocomplete="name" placeholder="Как к вам обращаться"></label><label>Телефон<input name="phone" type="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__"></label></div><label>Email <span>*</span><input name="email" type="email" autocomplete="email" required placeholder="name@company.ru"></label><label>Сообщение <span>*</span><textarea name="message" required rows="5" placeholder="Расскажите о вашей компании и предложении"></textarea></label><button type="submit" class="button form-submit">Отправить заявку '+ico("arrow")+'</button><small>Нажимая кнопку, вы соглашаетесь с <a href="'+SITE+'/privacy">политикой конфиденциальности</a></small><p class="form-feedback" role="status" hidden></p></form></div></section>'

def footer():
    t=BY_ID["contacts"]["text"]
    address=t[t.index("Адрес")+1]
    return '<footer class="site-footer"><div class="wrap footer-grid"><div><a class="brand" href="#top">'+ico("leaf")+'<span>БИОФАРМ</span></a><p>Институт изучения<br>биологически активных веществ</p></div><div><h3>Покупателям</h3>'+link("Каталог","/catalog")+link("Доставка","/dostavka")+link("Оплата","/oplata")+link("Возврат","/vozvrat")+'</div><div><h3>О компании</h3>'+link("Производство","#production")+link("Сертификаты","/certificates")+link("Блог","/blog")+link("Сотрудничество","#partner")+'</div><div class="contacts" id="contacts"><h3>Контакты</h3><p>'+e(address)+'</p><a href="mailto:bio.active@bk.ru">bio.active@bk.ru</a><p>Пн–Пт: 10:00–19:00<br>Сб: 10:00–17:00 · Вс: выходной</p></div></div><div class="wrap footer-bottom"><span>© 2026 БИОФАРМ</span><a href="'+SITE+'/oferta">Публичная оферта</a><a href="'+SITE+'/privacy">Политика конфиденциальности</a></div></footer>'

variants=[("a","01","Природная лёгкость","Мята, тёплый белый и природные акценты. Свободная композиция с тремя товарами."),
          ("b","02","Лес и наука","Глубокий зелёный, лимонный акцент и крупная журнальная типографика."),
          ("c","03","Живая энергия","Тёплый абрикос, сочный зелёный и смелая композиция первого экрана.")]

for kind,num,title,desc in variants:
    toolbar='<aside class="prototype-bar"><a href="index.html">← Все варианты</a><strong>'+num+' · '+title+'</strong><nav aria-label="Варианты дизайна">'+"".join('<a '+('aria-current="page" ' if k==kind else '')+'href="'+k+'.html">'+n+'</a>' for k,n,_,_ in variants)+'</nav><span>Визуальный прототип · без отправки заявок</span></aside>'
    body=header()+hero(kind)+categories()+catalog()+production()+about()+documents()+blog()+reviews()+marketplaces()+loyalty()+partner()+footer()
    page='<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'+num+' · '+title+' — БИОФАРМ</title><link rel="stylesheet" href="design.css"></head><body class="theme-'+kind+'">'+toolbar+body+'<dialog id="media-modal" aria-label="Просмотр"><button class="modal-close icon-button" aria-label="Закрыть просмотр">'+ico("close")+'</button><div class="media-content"></div></dialog><dialog id="cart-modal" aria-label="Корзина прототипа"><button class="modal-close icon-button" aria-label="Закрыть корзину">'+ico("close")+'</button><h2>Ваша корзина</h2><div class="cart-items"></div><p class="cart-total"></p><p class="prototype-hint">Демонстрация дизайна. Заказ не отправляется.</p></dialog><div class="toast" role="status" hidden></div><script>const reviewsData='+json.dumps(REVIEWS,ensure_ascii=False)+'; const productData='+json.dumps({pid:{"name":NAMES[pid],"price":PRODUCTS[pid]["price"],"image":"assets/"+KEYS[pid]+".png"} for pid in PIDS},ensure_ascii=False)+';</script><script src="preview.js"></script></body></html>'
    (ROOT/(kind+".html")).write_text(page)

index='<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>БИОФАРМ — три направления дизайна</title><link rel="stylesheet" href="design.css"></head><body class="selection"><main class="wrap"><div class="selection-heading">'+label("БИОФАРМ · Выбор направления")+'<h1>Три характера.<br><em>Одна история.</em></h1><p>Полные главные страницы с наполнением из текущего сайта.<br>Откройте каждый вариант и пройдите страницу до формы сотрудничества.</p></div><div class="variant-grid">'
for kind,num,title,desc in variants:
    index+='<a class="variant-choice choice-'+kind+'" href="'+kind+'.html"><div class="mini-preview"><span>БИОФАРМ</span><h2>'+{"a":"Природа<br>Сибири.","b":"Сила<br>природы.","c":"Больше<br>природы."}[kind]+'</h2><img src="assets/osina.png" alt="Экстракт коры осины"></div><div class="variant-info"><span>'+num+'</span><h3>'+title+'</h3><p>'+desc+'</p><strong>Открыть всю страницу '+ico("arrow")+'</strong></div></a>'
index+='</div><div class="selection-note"><h2>Что общее для всех вариантов</h2><p>Ваши упаковки и цены · три отдельных перехода в шапке · полный текст отзывов и небольшие фото · документы · маркетплейсы без списков преимуществ · заметная форма · увеличенные отступы</p><a href="CONTENT-AUDIT.md">Сверка с текущим сайтом ↗</a></div></main></body></html>'
(ROOT/"index.html").write_text(index)
print("Built index and three complete standalone homepages.")
