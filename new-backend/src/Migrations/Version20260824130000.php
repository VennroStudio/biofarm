<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;
use Override;

final class Version20260824130000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Finalize content, guest orders, FAQ, certificates, promo codes and SEO settings.';
    }

    /**
     * @throws JsonException
     */
    #[Override]
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('orders') && $schema->getTable('orders')->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE orders MODIFY user_id INT DEFAULT NULL');
        }

        if ($schema->hasTable('promo_code_redemptions') && $schema->getTable('promo_code_redemptions')->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE promo_code_redemptions MODIFY user_id INT DEFAULT NULL');
        }

        if ($schema->hasTable('faq_items') && $schema->getTable('faq_items')->hasColumn('page_id')) {
            $this->addSql('ALTER TABLE faq_items MODIFY page_id VARCHAR(255) DEFAULT NULL');
        }

        if (!$schema->hasTable('user_addresses')) {
            $this->addSql(
                'CREATE TABLE user_addresses (
                    id INT AUTO_INCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    label VARCHAR(100) DEFAULT NULL,
                    name VARCHAR(255) DEFAULT NULL,
                    phone VARCHAR(50) DEFAULT NULL,
                    email VARCHAR(255) DEFAULT NULL,
                    city VARCHAR(255) NOT NULL,
                    address VARCHAR(500) NOT NULL,
                    postal_code VARCHAR(30) DEFAULT NULL,
                    comment VARCHAR(500) DEFAULT NULL,
                    is_default TINYINT DEFAULT 0 NOT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    deleted_at DATETIME DEFAULT NULL,
                    INDEX idx_user_addresses_user (user_id),
                    INDEX idx_user_addresses_default (user_id, is_default),
                    INDEX idx_user_addresses_deleted (deleted_at),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4'
            );
        }

        $this->seedSettings();
        $this->seedPages();
        $this->seedFaq();
        $this->seedCertificates();
        $this->seedPromoCodes();
        $this->disableCmsDuplicatesOfSystemRoutes();
    }

    #[Override]
    public function down(Schema $schema): void
    {
        if ($schema->hasTable('user_addresses')) {
            $this->addSql('DROP TABLE user_addresses');
        }

        if ($schema->hasTable('faq_items') && $schema->getTable('faq_items')->hasColumn('page_id')) {
            $this->addSql('ALTER TABLE faq_items MODIFY page_id VARCHAR(100) DEFAULT NULL');
        }

        if ($schema->hasTable('promo_code_redemptions') && $schema->getTable('promo_code_redemptions')->hasColumn('user_id')) {
            $this->addSql('UPDATE promo_code_redemptions SET user_id = 0 WHERE user_id IS NULL');
            $this->addSql('ALTER TABLE promo_code_redemptions MODIFY user_id INT NOT NULL');
        }

        if ($schema->hasTable('orders') && $schema->getTable('orders')->hasColumn('user_id')) {
            $this->addSql('UPDATE orders SET user_id = 0 WHERE user_id IS NULL');
            $this->addSql('ALTER TABLE orders MODIFY user_id INT NOT NULL');
        }
    }

    /**
     * @throws JsonException
     */
    private function seedSettings(): void
    {
        $settings = [
            'cart_enabled'                       => true,
            'registration_enabled'               => true,
            'referral_enabled'                   => true,
            'withdrawals_enabled'                => true,
            'favorites_enabled'                  => true,
            'order_bonus_enabled'                => true,
            'order_bonus_percent'                => 5,
            'order_bonus_spend_limit_percent'    => 30,
            'welcome_bonus_enabled'              => true,
            'welcome_bonus_amount'               => 100,
            'promo_codes_enabled'                => true,
            'free_delivery_threshold'            => 3000,
            'cdek_delivery_price'                => 350,
            'post_delivery_price'                => 250,
            'order_emails_enabled'               => false,
            'home_features_enabled'              => true,
            'home_catalog_enabled'               => true,
            'home_video_enabled'                 => true,
            'home_blog_enabled'                  => true,
            'home_about_enabled'                 => true,
            'home_marketplaces_enabled'          => true,
            'home_certificates_enabled'          => true,
            'home_loyalty_enabled'               => true,
            'home_reviews_enabled'               => true,
            'home_contacts_enabled'              => true,
            'yandex_metrika_enabled'             => false,
            'yandex_metrika_id'                  => '',
            'referral_percent'                   => 5,
            'seo_product_title_template'         => '{name} — купить натуральный продукт БИОФАРМ',
            'seo_product_description_template'   => '{name}: описание, состав, цена и сертификаты качества. Натуральная продукция БИОФАРМ с доставкой по России.',
            'seo_category_title_template'        => '{h1} — БИОФАРМ',
            'seo_category_description_template'  => 'Каталог продукции БИОФАРМ в категории {name}. Натуральные растительные экстракты и БАДы с доставкой по России.',
            'seo_attribute_title_template'       => '{h1} — БИОФАРМ',
            'seo_attribute_description_template' => '{h1}: натуральная продукция БИОФАРМ с понятным составом и доставкой по России.',
            'site_name'                          => 'БИОФАРМ',
            'site_phone'                         => '+7 (983) 233-82-72',
            'site_email'                         => 'bio.active@bk.ru',
            'site_logo_url'                      => '/uploads/images/logo.png',
            'site_default_og_image'              => '/uploads/images/logo.png',
            'site_address_country'               => 'RU',
            'site_address_region'                => 'Томская область',
            'site_address_locality'              => 'Томск',
            'site_address_street'                => 'особая экономическая зона микрорайон Академгородок, проспект Развитие 3Е',
            'robots_extra_disallow'              => '',
        ];

        foreach ($settings as $key => $value) {
            $this->addSql(
                'INSERT INTO site_settings (`key`, value)
                 VALUES (:key, :value)
                 ON DUPLICATE KEY UPDATE `key` = `key`',
                [
                    'key'   => $key,
                    'value' => json_encode(['value' => $value], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ],
            );
        }
    }

    private function seedPages(): void
    {
        $this->systemPage('faq', 'FAQ', 'Вопросы и ответы', 'Вопросы и ответы — БИОФАРМ', 'Ответы на частые вопросы о продукции, заказах, доставке и бонусной программе БИОФАРМ.', 85, true);
        $this->systemPage('loyalty', 'Бонусная программа', 'Бонусная программа БИОФАРМ', 'Бонусная программа — БИОФАРМ', 'Бонусы, промокоды и реферальная программа БИОФАРМ для покупателей.', 86, true);
        $this->systemPage('email_verification', 'Подтверждение email', 'Подтверждение email', 'Подтверждение email — БИОФАРМ', 'Служебная страница подтверждения email БИОФАРМ.', 160, false);
        $this->systemPage('certificates', 'Сертификаты качества', 'Сертификаты качества', 'Сертификаты качества — БИОФАРМ', 'Сертификаты, декларации и документы качества на продукцию БИОФАРМ.', 80, true);

        $this->customPage(
            'dostavka',
            'Доставка',
            'Доставка продукции БИОФАРМ',
            'Доставка — БИОФАРМ',
            'Условия доставки натуральной продукции БИОФАРМ по России.',
            '<p>Доставляем продукцию БИОФАРМ по России. Стоимость рассчитывается при оформлении заказа и зависит от выбранного способа доставки.</p><p>При заказе от суммы, указанной в настройках сайта, доставка становится бесплатной.</p>',
            200,
        );
        $this->customPage(
            'oplata',
            'Оплата',
            'Оплата заказа',
            'Оплата — БИОФАРМ',
            'Способы оплаты заказов БИОФАРМ.',
            '<p>Оплата заказа выполняется после подтверждения состава и контактных данных. На этапе запуска сайта доступные способы оплаты могут уточняться администратором.</p>',
            210,
        );
        $this->customPage(
            'vozvrat',
            'Возврат',
            'Возврат и обмен',
            'Возврат — БИОФАРМ',
            'Условия возврата и обмена продукции БИОФАРМ.',
            '<p>Возврат и обмен продукции выполняются по действующим правилам интернет-торговли. Если возник вопрос по заказу, свяжитесь с нами через контакты на сайте.</p>',
            220,
        );
    }

    private function seedFaq(): void
    {
        $items = [
            ['faq', null, 'Как выбрать продукт БИОФАРМ?', 'Ориентируйтесь на назначение, состав и описание товара. Если сомневаетесь, оставьте заявку — мы поможем подобрать подходящий продукт.', 10],
            ['faq', null, 'Где посмотреть сертификаты?', 'Сертификаты и документы качества доступны на отдельной странице сайта и в карточках товаров, если документ привязан к конкретной продукции.', 20],
            ['faq', null, 'Можно ли оформить заказ без регистрации?', 'Да, если корзина и заказы включены в настройках сайта. Зарегистрированные пользователи дополнительно получают доступ к бонусам и истории заказов.', 30],
            ['loyalty', null, 'Как начисляются бонусы?', 'Бонусы начисляются за оплаченные заказы, если бонусная программа включена администратором сайта.', 10],
            ['loyalty', null, 'Можно ли списывать бонусы при заказе?', 'Да, зарегистрированный пользователь может списать бонусы в пределах лимита, заданного в настройках сайта.', 20],
            ['loyalty', null, 'Как работает реферальная программа?', 'Партнер получает бонусы за покупки приглашенных пользователей, если реферальная программа включена.', 30],
            ['email_verification', null, 'Зачем подтверждать email?', 'Подтверждение email защищает личный кабинет и помогает получать уведомления по заказам.', 10],
            ['email_verification', null, 'Что делать, если письмо не пришло?', 'Проверьте папку со спамом и правильность адреса. Если письмо не пришло, повторите регистрацию или вход позже.', 20],
            ['certificates', null, 'Какие документы доступны?', 'На сайте можно размещать декларации, сертификаты и протоколы контроля качества.', 10],
            ['certificates', null, 'Можно ли скачать документ?', 'Да, каждый документ открывается или скачивается по кнопке рядом с карточкой сертификата.', 20],
        ];

        foreach ($items as [$scope, $pageId, $question, $answer, $sortOrder]) {
            $this->addSql(
                'INSERT INTO faq_items (question, answer, page_scope, page_id, is_active, sort_order, created_at)
                 SELECT :question, :answer, :scope, :pageId, 1, :sortOrder, UTC_TIMESTAMP()
                 WHERE NOT EXISTS (
                     SELECT 1 FROM faq_items
                     WHERE page_scope = :scope
                       AND question = :question
                     LIMIT 1
                 )',
                [
                    'question'  => $question,
                    'answer'    => $answer,
                    'scope'     => $scope,
                    'pageId'    => $pageId,
                    'sortOrder' => $sortOrder,
                ],
            );
        }
    }

    private function seedCertificates(): void
    {
        $certificates = [
            [
                'title'         => 'Протокол контроля качества БИОФАРМ',
                'file_path'     => '/uploads/certificates/demo/quality-protocol.pdf',
                'document_type' => 'protocol',
                'description'   => 'Демонстрационный протокол для проверки блока сертификатов на главной и странице документов.',
                'sort_order'    => 10,
            ],
            [
                'title'         => 'Декларация соответствия БИОФАРМ',
                'file_path'     => '/uploads/certificates/demo/declaration.pdf',
                'document_type' => 'declaration',
                'description'   => 'Демонстрационная декларация соответствия для заполнения раздела документов.',
                'sort_order'    => 20,
            ],
        ];

        foreach ($certificates as $certificate) {
            $this->addSql(
                'INSERT INTO certificates (title, file_path, document_type, description, is_active, sort_order, created_at)
                 SELECT :title, :filePath, :documentType, :description, 1, :sortOrder, UTC_TIMESTAMP()
                 WHERE NOT EXISTS (
                     SELECT 1 FROM certificates
                     WHERE title = :title
                     LIMIT 1
                 )',
                [
                    'title'        => $certificate['title'],
                    'filePath'     => $certificate['file_path'],
                    'documentType' => $certificate['document_type'],
                    'description'  => $certificate['description'],
                    'sortOrder'    => $certificate['sort_order'],
                ],
            );
        }
    }

    private function seedPromoCodes(): void
    {
        $promoCodes = [
            ['BIOFARM10', 'percent', 10, 1000, 100],
            ['SIBIR15', 'percent', 15, 2500, 100],
            ['WELCOME300', 'fixed', 300, 3000, 50],
        ];

        foreach ($promoCodes as [$code, $type, $value, $minOrderTotal, $usageLimit]) {
            $this->addSql(
                'INSERT INTO promo_codes (code, type, value, min_order_total, usage_limit, is_active, created_at)
                 SELECT :code, :type, :value, :minOrderTotal, :usageLimit, 1, UTC_TIMESTAMP()
                 WHERE NOT EXISTS (
                     SELECT 1 FROM promo_codes
                     WHERE code = :code
                     LIMIT 1
                 )',
                [
                    'code'          => $code,
                    'type'          => $type,
                    'value'         => $value,
                    'minOrderTotal' => $minOrderTotal,
                    'usageLimit'    => $usageLimit,
                ],
            );
        }
    }

    private function systemPage(
        string $systemKey,
        string $title,
        string $h1,
        string $seoTitle,
        string $seoDescription,
        int $sortOrder,
        bool $indexable,
    ): void {
        $this->addSql(
            'INSERT INTO pages (page_type, system_key, title, h1, seo_title, seo_description, og_title, og_description, is_published, is_indexable, show_in_sitemap, sort_order, created_at)
             VALUES (\'system\', :systemKey, :title, :h1, :seoTitle, :seoDescription, :seoTitle, :seoDescription, 1, :indexable, :indexable, :sortOrder, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
                h1 = COALESCE(NULLIF(h1, \'\'), VALUES(h1)),
                seo_title = COALESCE(NULLIF(seo_title, \'\'), VALUES(seo_title)),
                seo_description = COALESCE(NULLIF(seo_description, \'\'), VALUES(seo_description)),
                og_title = COALESCE(NULLIF(og_title, \'\'), VALUES(og_title)),
                og_description = COALESCE(NULLIF(og_description, \'\'), VALUES(og_description)),
                is_published = 1,
                is_indexable = VALUES(is_indexable),
                show_in_sitemap = VALUES(show_in_sitemap),
                deleted_at = NULL,
                updated_at = UTC_TIMESTAMP()',
            [
                'systemKey'      => $systemKey,
                'title'          => $title,
                'h1'             => $h1,
                'seoTitle'       => $seoTitle,
                'seoDescription' => $seoDescription,
                'indexable'      => $indexable ? 1 : 0,
                'sortOrder'      => $sortOrder,
            ],
        );
    }

    private function customPage(
        string $slugPath,
        string $title,
        string $h1,
        string $seoTitle,
        string $seoDescription,
        string $content,
        int $sortOrder,
    ): void {
        $this->addSql(
            'INSERT INTO pages (page_type, slug_path, template, title, h1, content, seo_title, seo_description, og_title, og_description, is_published, is_indexable, show_in_sitemap, show_in_footer, sort_order, published_at, created_at)
             VALUES (\'custom\', :slugPath, \'legal\', :title, :h1, :content, :seoTitle, :seoDescription, :seoTitle, :seoDescription, 1, 1, 1, 0, :sortOrder, UTC_TIMESTAMP(), UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
                template = COALESCE(NULLIF(template, \'\'), VALUES(template)),
                h1 = COALESCE(NULLIF(h1, \'\'), VALUES(h1)),
                content = COALESCE(NULLIF(content, \'\'), VALUES(content)),
                seo_title = COALESCE(NULLIF(seo_title, \'\'), VALUES(seo_title)),
                seo_description = COALESCE(NULLIF(seo_description, \'\'), VALUES(seo_description)),
                og_title = COALESCE(NULLIF(og_title, \'\'), VALUES(og_title)),
                og_description = COALESCE(NULLIF(og_description, \'\'), VALUES(og_description)),
                is_published = 1,
                is_indexable = 1,
                show_in_sitemap = 1,
                deleted_at = NULL,
                updated_at = UTC_TIMESTAMP()',
            [
                'slugPath'       => $slugPath,
                'title'          => $title,
                'h1'             => $h1,
                'content'        => $content,
                'seoTitle'       => $seoTitle,
                'seoDescription' => $seoDescription,
                'sortOrder'      => $sortOrder,
            ],
        );
    }

    private function disableCmsDuplicatesOfSystemRoutes(): void
    {
        foreach ([
            'certificates',
            'faq',
            'loyalnost',
            'email-verification',
            'privacy',
            'oferta',
            'cart',
            'checkout',
            'order-success',
            'login',
            'profile',
        ] as $slugPath) {
            $this->addSql(
                'UPDATE pages
                 SET deleted_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
                 WHERE page_type = \'custom\'
                   AND slug_path = :slugPath
                   AND deleted_at IS NULL',
                ['slugPath' => $slugPath],
            );
        }
    }
}
