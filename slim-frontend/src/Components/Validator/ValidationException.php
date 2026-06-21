<?php

declare(strict_types=1);

namespace App\Components\Validator;

use LogicException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

final class ValidationException extends LogicException
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violations,
        string $message = 'Invalid input.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $message . ' ' . self::errorsText($violations),
            $code,
            $previous,
        );
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    private static function errorsText(ConstraintViolationListInterface $violations): string
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = "Field {$violation->getPropertyPath()}: {$violation->getMessage()}";
        }
        return implode(' ', $errors);
    }
}
