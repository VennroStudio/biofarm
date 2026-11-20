<?php

declare(strict_types=1);

namespace App\Modules\User\Query\User\GetById;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Modules\User\ReadModel\User\UserById;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class UserGetByIdFetcher
{
    private const string TABLE = 'users';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
    ) {}

    /**
     * @throws Exception
     */
    public function fetch(UserGetByIdQuery $query): UserById
    {
        $key = 'user_identity_' . $query->id;

        /** @var UserById|null $cached */
        $cached = $this->cacher->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $row = $this->connection->createQueryBuilder()
            ->select(
                'id',
                'role',
                'status',
                'first_name',
                'last_name',
                'email',
                'avatar',
                'created_at',
                'updated_at',
                'deleted_at',
            )
            ->from(self::TABLE)
            ->where('id = :id')
            ->andWhere('deleted_at IS NULL')
            ->setParameter('id', $query->id)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.user_not_found',
                code: 1
            );
        }

        /** @var array{
         *      id: int,
         *      role: int,
         *      status: int,
         *      last_name: string,
         *      first_name: string,
         *      email: string,
         *      avatar: string|null,
         *      created_at: string,
         *      updated_at: string|null,
         *      deleted_at: string|null
         * } $row */
        $user = UserById::fromRow($row);
        $this->cacher->set($key, $user, self::CACHE_TTL);

        return $user;
    }
}
