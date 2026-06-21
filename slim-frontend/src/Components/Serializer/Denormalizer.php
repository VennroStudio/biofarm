<?php

declare(strict_types=1);

namespace App\Components\Serializer;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use UnexpectedValueException;

final readonly class Denormalizer
{
    private const array DEFAULT_CONTEXT = [
        DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
        AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES            => true,
        AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT    => true,
    ];

    private const array STRICT_CONTEXT = [
        DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
        AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES            => true,
    ];

    public function __construct(
        private DenormalizerInterface $denormalizer,
    ) {}

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     * @throws ExceptionInterface
     */
    public function denormalize(mixed $data, string $type): object
    {
        /** @var T */
        return $this->assertObject(
            $this->denormalizer->denormalize($data, $type, null, self::DEFAULT_CONTEXT),
            $type,
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     * @throws ExceptionInterface
     */
    public function denormalizeStrict(mixed $data, string $type): object
    {
        /** @var T */
        return $this->assertObject(
            $this->denormalizer->denormalize($data, $type, null, self::STRICT_CONTEXT),
            $type,
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    private function assertObject(mixed $result, string $type): object
    {
        if (!\is_object($result)) {
            throw new UnexpectedValueException(\sprintf(
                'Expected object of type "%s", got "%s".',
                $type,
                get_debug_type($result),
            ));
        }

        /** @var T $result */
        return $result;
    }
}
