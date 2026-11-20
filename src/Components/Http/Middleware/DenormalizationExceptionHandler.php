<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use App\Components\Validator\ValidationException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class DenormalizationExceptionHandler implements MiddlewareInterface
{
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ExtraAttributesException $exception) {
            $violations = array_map(
                static fn (string $attr): ConstraintViolation => new ConstraintViolation(
                    'The attribute is not allowed.',
                    '',
                    [],
                    null,
                    $attr,
                    null,
                ),
                $exception->getExtraAttributes(),
            );
            throw new ValidationException(new ConstraintViolationList($violations));
        } catch (NotNormalizableValueException $exception) {
            throw new ValidationException(new ConstraintViolationList([
                $this->buildViolation($exception),
            ]));
        } catch (PartialDenormalizationException $exception) {
            $violations = array_map(
                $this->buildViolation(...),
                iterator_to_array($exception->getErrors()),
            );
            throw new ValidationException(new ConstraintViolationList($violations));
        }
    }

    private function buildViolation(NotNormalizableValueException $e): ConstraintViolation
    {
        $expected = implode(', ', (array)$e->getExpectedTypes());
        $current = (string)$e->getCurrentType();
        $message = \sprintf('The type must be one of "%s" ("%s" given).', $expected, $current);

        if ($e->canUseMessageForUser()) {
            $message .= ' ' . $e->getMessage();
        }

        return new ConstraintViolation($message, '', [], null, $e->getPath(), null);
    }
}
