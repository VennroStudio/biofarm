<?php

declare(strict_types=1);

namespace App\Console;

use App\Modules\Entity\Product\Product;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Консольная команда с доступом к основной БД (EntityManager) и второй БД (Connection).
 */
final class OtherDbCommand extends Command
{
    private const IMAGE_BASE_URL = 'https://biofarm.store/';

    /**
     * Транслитерация кириллицы в латиницу и формирование slug (например: "Клеточный сок пихты" → "kletochnyy-sok-pihty").
     */
    private static function slugFromName(string $name): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];
        $name = mb_strtolower($name);
        $result = '';
        $len = mb_strlen($name);
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($name, $i, 1);
            if (isset($map[$ch])) {
                $result .= $map[$ch];
            } elseif (preg_match('/[a-z0-9]/', $ch)) {
                $result .= $ch;
            } elseif ($ch === ' ' || $ch === '-' || $ch === '_') {
                $result .= '-';
            }
        }
        return preg_replace('/-+/', '-', trim($result, '-'));
    }

    private static function fullImageUrl(string $path): string
    {
        $path = ltrim($path, '/');
        // Пути вида images/xxx → uploads/images/xxx (как у остальных картинок)
        if (str_starts_with($path, 'images/') && !str_starts_with($path, 'uploads/')) {
            $path = 'uploads/' . $path;
        }
        return self::IMAGE_BASE_URL . $path;
    }

    public function __construct(
        private readonly EntityManagerInterface $mainEm,
        private readonly Connection $otherConnection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:other-db')
            ->setDescription('Работа с основной и второй БД одновременно (проверка подключений)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Товары из старой БД (biofarm_back.products)
            $io->section('Товары из старой БД (SELECT * FROM products)');
            $rows = $this->otherConnection->executeQuery('SELECT * FROM `products`')->fetchAllAssociative();
            $io->writeln('Найдено товаров: ' . count($rows));

            if (count($rows) === 0) {
                $io->writeln('Нет записей.');
                return Command::SUCCESS;
            }

            // Фото товаров из img_products, сгруппированные по product_id
            $imgRows = $this->otherConnection->executeQuery('SELECT * FROM `img_products`')->fetchAllAssociative();
            $imagesByProductId = [];
            foreach ($imgRows as $img) {
                $pid = (int)($img['product_id'] ?? 0);
                if (!isset($imagesByProductId[$pid])) {
                    $imagesByProductId[$pid] = [];
                }
                $imagesByProductId[$pid][] = $img['images'] ?? '';
            }

            // Импорт всех товаров в основную БД (biofarm_products)
            $io->section('Импорт товаров в основную БД');
            $productRepository = $this->mainEm->getRepository(Product::class);
            $imported = 0;

            foreach ($rows as $row) {
                $oldId = (int)($row['id'] ?? 0);
                $name = (string)($row['name'] ?? '');
                $slug = self::slugFromName($name);
                // Уникальность slug: если уже есть — добавляем старый id
                if ($productRepository->findOneBy(['slug' => $slug]) !== null) {
                    $slug = $slug . '-' . $oldId;
                }

                $rawImage = trim((string)($row['image'] ?? ''));
                $image = $rawImage !== '' ? self::fullImageUrl($rawImage) : '';
                $rawImages = $imagesByProductId[$oldId] ?? [];
                $images = array_map(self::fullImageUrl(...), array_filter($rawImages));
                $images = $images ?: null;

                $categoryId = (string)($row['category_id'] ?? '1');
                $weight = (string)($row['size'] ?? '');
                $description = (string)($row['description'] ?? '');
                $ingredients = isset($row['compound']) && (string)$row['compound'] !== '' ? (string)$row['compound'] : null;
                $wbLink = isset($row['wb']) && (string)$row['wb'] !== '' ? (string)$row['wb'] : null;
                $ozonLink = isset($row['ozon']) && (string)$row['ozon'] !== '' ? (string)$row['ozon'] : null;

                $product = Product::create(
                    slug: $slug,
                    name: $name,
                    categoryId: $categoryId,
                    price: 0,
                    image: $image,
                    weight: $weight,
                    description: $description,
                    shortDescription: null,
                    oldPrice: null,
                    images: $images,
                    badge: null,
                    ingredients: $ingredients,
                    features: null,
                    wbLink: $wbLink,
                    ozonLink: $ozonLink,
                    isActive: true,
                );
                $this->mainEm->persist($product);
                $imported++;
            }

            $this->mainEm->flush();
            $io->success("Импортировано товаров: {$imported}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Ошибка: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
