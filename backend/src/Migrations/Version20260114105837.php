<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114105837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE biofarm_blog_posts (id BIGINT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, excerpt VARCHAR(500) NOT NULL, content LONGTEXT NOT NULL, image VARCHAR(500) NOT NULL, categoryId VARCHAR(50) NOT NULL, authorId INT NOT NULL, readTime INT NOT NULL, isPublished TINYINT(1) DEFAULT 0 NOT NULL, createdAt INT NOT NULL, updatedAt INT DEFAULT NULL, INDEX IDX_CATEGORY (categoryId), INDEX IDX_PUBLISHED (isPublished), UNIQUE INDEX UNIQUE_SLUG (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE biofarm_order_items (id BIGINT AUTO_INCREMENT NOT NULL, orderId BIGINT NOT NULL, productId BIGINT NOT NULL, productName VARCHAR(255) NOT NULL, price INT NOT NULL, quantity INT NOT NULL, INDEX IDX_ORDER (orderId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE biofarm_orders (id VARCHAR(50) NOT NULL, userId BIGINT NOT NULL, status VARCHAR(20) NOT NULL, paymentStatus VARCHAR(20) NOT NULL, total INT NOT NULL, bonusUsed INT DEFAULT 0 NOT NULL, bonusEarned INT DEFAULT 0 NOT NULL, shippingAddress JSON NOT NULL, paymentMethod VARCHAR(20) NOT NULL, trackingNumber VARCHAR(100) DEFAULT NULL, createdAt INT NOT NULL, paidAt INT DEFAULT NULL, updatedAt INT DEFAULT NULL, INDEX IDX_USER (userId), INDEX IDX_STATUS (status), INDEX IDX_PAYMENT_STATUS (paymentStatus), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE biofarm_reviews (id VARCHAR(50) NOT NULL, productId BIGINT NOT NULL, userId VARCHAR(50) DEFAULT NULL, userName VARCHAR(255) NOT NULL, rating INT NOT NULL, text LONGTEXT NOT NULL, images JSON DEFAULT NULL, source VARCHAR(50) NOT NULL, isApproved TINYINT(1) DEFAULT 0 NOT NULL, createdAt INT NOT NULL, updatedAt INT DEFAULT NULL, INDEX IDX_PRODUCT (productId), INDEX IDX_APPROVED (isApproved), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE biofarm_settings (`key` VARCHAR(50) NOT NULL, value LONGTEXT NOT NULL, updatedAt INT NOT NULL, PRIMARY KEY(`key`)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE biofarm_users (id BIGINT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, passwordHash VARCHAR(255) NOT NULL, referredBy VARCHAR(50) DEFAULT NULL, bonusBalance INT DEFAULT 0 NOT NULL, isPartner TINYINT(1) DEFAULT 0 NOT NULL, isActive TINYINT(1) DEFAULT 1 NOT NULL, cardNumber VARCHAR(50) DEFAULT NULL, createdAt INT NOT NULL, updatedAt INT DEFAULT NULL, INDEX IDX_ACTIVE (isActive), INDEX IDX_PARTNER (isPartner), UNIQUE INDEX UNIQUE_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE biofarm_withdrawals (id VARCHAR(50) NOT NULL, userId BIGINT NOT NULL, amount INT NOT NULL, status VARCHAR(20) NOT NULL, createdAt INT NOT NULL, processedAt INT DEFAULT NULL, processedBy VARCHAR(255) DEFAULT NULL, updatedAt INT DEFAULT NULL, INDEX IDX_USER (userId), INDEX IDX_STATUS (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE biofarm_blog_posts');
        $this->addSql('DROP TABLE biofarm_order_items');
        $this->addSql('DROP TABLE biofarm_orders');
        $this->addSql('DROP TABLE biofarm_reviews');
        $this->addSql('DROP TABLE biofarm_settings');
        $this->addSql('DROP TABLE biofarm_users');
        $this->addSql('DROP TABLE biofarm_withdrawals');
    }
}
