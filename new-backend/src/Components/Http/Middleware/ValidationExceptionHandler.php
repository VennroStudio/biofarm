<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use App\Components\Http\Response\JsonValidationsResponse;
use App\Components\Validator\ValidationException;
use JsonException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final readonly class ValidationExceptionHandler implements MiddlewareInterface
{
    /**
     * @throws JsonException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ValidationException $exception) {
            $validations = self::violationsToArray($exception->getViolations());
            return new JsonValidationsResponse($validations);
        }
    }

    /**
     * @return array<int, array{field: string, message: string}>
     */
    private static function violationsToArray(ConstraintViolationListInterface $violations): array
    {
        $result = [];
        foreach ($violations as $violation) {
            $result[] = [
                'field'   => $violation->getPropertyPath(),
                'message' => (string)$violation->getMessage(),
            ];
        }
        return $result;
    }
}
