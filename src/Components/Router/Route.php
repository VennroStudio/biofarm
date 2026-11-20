<?php

declare(strict_types=1);

namespace App\Components\Router;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

final class Route
{
    public static function getArgument(ServerRequestInterface $request, string $name): string
    {
        $argument = RouteContext::fromRequest($request)
            ->getRoute()
            ?->getArgument($name);

        if ($argument === null) {
            throw new InvalidArgumentException("Route argument '{$name}' not found.");
        }

        return $argument;
    }

    public static function getArgumentToInt(ServerRequestInterface $request, string $name): int
    {
        return (int)self::getArgument($request, $name);
    }
}
