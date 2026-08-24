<?php

declare(strict_types=1);

namespace App\Modules\Order\Service;

use App\Components\Setting\SiteSettings;
use App\Modules\Order\Entity\Order\Order;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Throwable;
use Twig\Environment;

final readonly class OrderEmailNotifier
{
    public function __construct(
        private SiteSettings $settings,
        private MailerInterface $mailer,
        private Environment $twig,
        private Connection $connection,
        private LoggerInterface $logger,
    ) {}

    public function created(Order $order): void
    {
        $this->send($order, 'Ваш заказ принят', 'email/order-created.html.twig');
    }

    public function updated(Order $order): void
    {
        $this->send($order, 'Заказ обновлён', 'email/order-updated.html.twig');
    }

    private function send(Order $order, string $subject, string $template): void
    {
        if (!$this->settings->bool('order_emails_enabled')) {
            return;
        }

        $email = $this->customerEmail($order);
        if ($email === null) {
            return;
        }

        try {
            $this->mailer->send(new Email()
                ->to($email)
                ->subject($subject)
                ->html($this->twig->render($template, [
                    'siteName' => (string)$this->settings->get('site_name', 'БИОФАРМ'),
                    'order'    => $order,
                    'items'    => $this->items($order->id),
                ])));
        } catch (Throwable $exception) {
            $this->logger->warning('Order email was not sent.', [
                'order_id' => $order->id,
                'error'    => $exception->getMessage(),
            ]);
        }
    }

    private function customerEmail(Order $order): ?string
    {
        $email = trim($order->shippingAddress['email'] ?? '');

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }

    /**
     * @return list<array{product_name: string, price: int, quantity: int}>
     */
    private function items(string $orderId): array
    {
        try {
            $rows = $this->connection->createQueryBuilder()
                ->select('product_name', 'price', 'quantity')
                ->from('order_items')
                ->where('order_id = :orderId')
                ->setParameter('orderId', $orderId)
                ->orderBy('id')
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (DbalException) {
            return [];
        }

        return array_map(static fn (array $row): array => [
            'product_name' => (string)$row['product_name'],
            'price'        => (int)$row['price'],
            'quantity'     => (int)$row['quantity'],
        ], $rows);
    }
}
