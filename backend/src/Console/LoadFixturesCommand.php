<?php

declare(strict_types=1);

namespace App\Console;

use App\Modules\Command\BlogPost\Create\Command as BlogPostCreateCommand;
use App\Modules\Command\BlogPost\Create\Handler as BlogPostCreateHandler;
use App\Modules\Command\Order\Create\Command as OrderCreateCommand;
use App\Modules\Command\Order\Create\Handler as OrderCreateHandler;
use App\Modules\Command\OrderItem\Create\Command as OrderItemCreateCommand;
use App\Modules\Command\OrderItem\Create\Handler as OrderItemCreateHandler;
use App\Modules\Command\Product\Create\Command as ProductCreateCommand;
use App\Modules\Command\Product\Create\Handler as ProductCreateHandler;
use App\Modules\Command\Review\Create\Command as ReviewCreateCommand;
use App\Modules\Command\Review\Create\Handler as ReviewCreateHandler;
use App\Modules\Command\Settings\Create\Command as SettingsCreateCommand;
use App\Modules\Command\Settings\Create\Handler as SettingsCreateHandler;
use App\Modules\Command\User\Create\Command as UserCreateCommand;
use App\Modules\Command\User\Create\Handler as UserCreateHandler;
use App\Modules\Command\Withdrawal\Create\Command as WithdrawalCreateCommand;
use App\Modules\Command\Withdrawal\Create\Handler as WithdrawalCreateHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class LoadFixturesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductCreateHandler $productCreateHandler,
        private readonly UserCreateHandler $userCreateHandler,
        private readonly OrderCreateHandler $orderCreateHandler,
        private readonly OrderItemCreateHandler $orderItemCreateHandler,
        private readonly ReviewCreateHandler $reviewCreateHandler,
        private readonly BlogPostCreateHandler $blogPostCreateHandler,
        private readonly WithdrawalCreateHandler $withdrawalCreateHandler,
        private readonly SettingsCreateHandler $settingsCreateHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('fixtures:load')
            ->setDescription('Load fixtures from JSON files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Loading fixtures...</info>');

        $basePath = __DIR__ . '/../../frontend/src/data';

        // Load products
        $productsData = json_decode(file_get_contents($basePath . '/products.json'), true);
        foreach ($productsData['products'] as $productData) {
            $this->productCreateHandler->handle(new ProductCreateCommand(
                slug: $productData['slug'],
                name: $productData['name'],
                categoryId: $productData['category_id'],
                price: $productData['price'],
                image: $productData['image'],
                weight: $productData['weight'],
                description: $productData['description'],
                shortDescription: $productData['short_description'],
                oldPrice: $productData['old_price'] ?? null,
                images: $productData['images'] ?? null,
                badge: $productData['badge'] ?? null,
                ingredients: $productData['ingredients'] ?? null,
                features: $productData['features'] ?? null,
                wbLink: $productData['wb_link'] ?? null,
                ozonLink: $productData['ozon_link'] ?? null,
                isActive: $productData['is_active'] ?? true,
            ));
        }
        $this->em->flush();
        $output->writeln('<info>Loaded ' . count($productsData['products']) . ' products</info>');

        // Load users
        $usersData = json_decode(file_get_contents($basePath . '/users.json'), true);
        foreach ($usersData['users'] as $userData) {
            $this->userCreateHandler->handle(new UserCreateCommand(
                email: $userData['email'],
                name: $userData['name'],
                passwordHash: password_hash($userData['password_hash'], PASSWORD_DEFAULT),
                phone: $userData['phone'] ?? null,
                referredBy: $userData['referred_by'] ?? null,
                isPartner: $userData['is_partner'] ?? false,
                isActive: $userData['is_active'] ?? true,
            ));
        }
        $this->em->flush();
        $output->writeln('<info>Loaded ' . count($usersData['users']) . ' users</info>');

        // Load orders and order items
        $ordersData = json_decode(file_get_contents($basePath . '/orders.json'), true);
        $userIds = [];
        foreach ($usersData['users'] as $idx => $userData) {
            $userIds[$userData['id']] = $idx + 1; // Map string IDs to numeric
        }
        foreach ($ordersData['orders'] as $orderData) {
            $order = $this->orderCreateHandler->handle(new OrderCreateCommand(
                orderId: $orderData['id'],
                userId: $userIds[$orderData['user_id']] ?? 1,
                total: $orderData['total'],
                shippingAddress: $orderData['shipping_address'],
                paymentMethod: $orderData['payment_method'],
                bonusUsed: $orderData['bonus_used'] ?? 0,
                status: $orderData['status'],
                paymentStatus: $orderData['payment_status'],
            ));
            $this->em->flush();

            // Create order items
            foreach ($orderData['items'] as $itemData) {
                $this->orderItemCreateHandler->handle(new OrderItemCreateCommand(
                    orderId: $order->getId(), // Order ID is string
                    productId: $itemData['product_id'],
                    productName: $itemData['product_name'],
                    price: $itemData['price'],
                    quantity: $itemData['quantity'],
                ));
            }
        }
        $this->em->flush();
        $output->writeln('<info>Loaded ' . count($ordersData['orders']) . ' orders</info>');

        // Load reviews
        $reviewsData = json_decode(file_get_contents($basePath . '/reviews.json'), true);
        foreach ($reviewsData['reviews'] as $reviewData) {
            $this->reviewCreateHandler->handle(new ReviewCreateCommand(
                reviewId: $reviewData['id'],
                productId: $reviewData['product_id'],
                userName: $reviewData['user_name'],
                rating: $reviewData['rating'],
                text: $reviewData['text'],
                source: $reviewData['source'],
                userId: $reviewData['user_id'] ?? null,
                images: $reviewData['images'] ?? null,
                isApproved: $reviewData['is_approved'] ?? false,
            ));
        }
        $this->em->flush();
        $output->writeln('<info>Loaded ' . count($reviewsData['reviews']) . ' reviews</info>');

        // Load blog posts
        $blogData = json_decode(file_get_contents($basePath . '/blog.json'), true);
        foreach ($blogData['posts'] as $postData) {
            $this->blogPostCreateHandler->handle(new BlogPostCreateCommand(
                slug: $postData['slug'],
                title: $postData['title'],
                excerpt: $postData['excerpt'],
                content: $postData['content'],
                image: $postData['image'],
                categoryId: $postData['category_id'],
                authorId: $postData['author_id'],
                readTime: $postData['read_time'],
                isPublished: $postData['is_published'] ?? true,
            ));
        }
        $this->em->flush();
        $output->writeln('<info>Loaded ' . count($blogData['posts']) . ' blog posts</info>');

        // Load withdrawals
        $withdrawalsData = json_decode(file_get_contents($basePath . '/withdrawals.json'), true);
        foreach ($withdrawalsData['withdrawals'] as $withdrawalData) {
            $this->withdrawalCreateHandler->handle(new WithdrawalCreateCommand(
                withdrawalId: $withdrawalData['id'],
                userId: $userIds[$withdrawalData['user_id']] ?? 2,
                amount: $withdrawalData['amount'],
                status: $withdrawalData['status'],
            ));
        }
        $this->em->flush();
        $output->writeln('<info>Loaded ' . count($withdrawalsData['withdrawals']) . ' withdrawals</info>');

        // Load settings
        $settingsData = json_decode(file_get_contents($basePath . '/settings.json'), true);
        $this->settingsCreateHandler->handle(new SettingsCreateCommand(
            key: 'referralPercent',
            value: (string)$settingsData['referralPercent'],
        ));
        $this->settingsCreateHandler->handle(new SettingsCreateCommand(
            key: 'orderBonusEnabled',
            value: $settingsData['orderBonusEnabled'] ? '1' : '0',
        ));
        $this->settingsCreateHandler->handle(new SettingsCreateCommand(
            key: 'orderBonusPercent',
            value: (string)$settingsData['orderBonusPercent'],
        ));
        $this->em->flush();
        $output->writeln('<info>Loaded settings</info>');

        $output->writeln('<info>Fixtures loaded successfully!</info>');

        return Command::SUCCESS;
    }
}
