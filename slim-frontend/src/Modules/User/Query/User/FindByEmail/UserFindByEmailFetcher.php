<?php

declare(strict_types=1);

namespace App\Modules\User\Query\User\FindByEmail;

use App\Modules\User\ReadModel\User\UserByEmail;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class UserFindByEmailFetcher
{
    private const string TABLE = 'users';

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception
     */
    public function fetchAny(UserFindByEmailQuery $query): ?UserByEmail
    {
        $row = $this->connection->createQueryBuilder()
            ->select('id', 'email', 'password', 'first_name', 'role', 'status', 'deleted_at')
            ->from(self::TABLE)
            ->where('email = :email')
            ->setParameter('email', mb_strtolower($query->email))
            ->setMaxResults(1)
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        /** @var array{
         *     id: int,
         *     email: string,
         *     password: string,
         *     first_name: string,
         *     role: int,
         *     status: int,
         *     deleted_at: string|null
         * } $row */
        return UserByEmail::fromRow($row);
    }

    /**
     * @throws Exception
     */
    public function fetchNotDeleted(UserFindByEmailQuery $query): ?UserByEmail
    {
        $row = $this->connection->createQueryBuilder()
            ->select('id', 'email', 'password', 'first_name', 'role', 'status', 'deleted_at')
            ->from(self::TABLE)
            ->where('email = :email')
            ->andWhere('deleted_at IS NULL')
            ->setParameter('email', mb_strtolower($query->email))
            ->setMaxResults(1)
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        /** @var array{
         *     id: int,
         *     email: string,
         *     password: string,
         *     first_name: string,
         *     role: int,
         *     status: int,
         *     deleted_at: string|null
         * } $row */
        return UserByEmail::fromRow($row);
    }
}
