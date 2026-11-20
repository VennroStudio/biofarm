<?php

declare(strict_types=1);

namespace App\Http\Web\Seo;

use App\Components\Seo\SeoUrlGenerator;
use App\Components\Setting\SiteSettings;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class RobotsController implements RequestHandlerInterface
{
    public function __construct(
        private SeoUrlGenerator $urls,
        private SiteSettings $settings,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->customRobotsTxt() ?? $this->defaultRobotsTxt();

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->getBody()->write($body);

        return $response;
    }

    private function defaultRobotsTxt(): string
    {
        $disallow = [
            '/admin',
            '/login',
            '/cart',
            '/checkout',
            '/order-success',
            '/profile',
        ];

        $disallow = [
            ...$disallow,
            ...$this->extraDisallow(),
        ];

        return implode("\n", [
            'User-agent: *',
            ...array_map(static fn (string $path): string => 'Disallow: ' . $path, array_unique($disallow)),
            '',
            'Sitemap: ' . $this->urls->absolute('/sitemap.xml'),
            '',
        ]);
    }

    private function customRobotsTxt(): ?string
    {
        $value = $this->settings->get('robots_txt', '');
        if (!\is_string($value) || trim($value) === '') {
            return null;
        }

        $body = str_replace(["\r\n", "\r"], "\n", $value);

        return rtrim($body, "\n") . "\n";
    }

    /**
     * @return list<string>
     */
    private function extraDisallow(): array
    {
        $value = $this->settings->get('robots_extra_disallow', '');
        if (!\is_string($value) || trim($value) === '') {
            return [];
        }

        $paths = [];
        foreach (preg_split('/\R/', $value) ?: [] as $line) {
            $path = trim($line);
            if ($path === '' || str_starts_with($path, '#')) {
                continue;
            }

            if (!str_starts_with($path, '/')) {
                $path = '/' . $path;
            }

            $paths[] = $path;
        }

        return $paths;
    }
}
