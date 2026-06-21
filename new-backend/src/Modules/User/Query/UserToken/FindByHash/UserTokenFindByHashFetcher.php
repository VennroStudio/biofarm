<?php

declare(strict_types=1);

namespace App\Modules\User\Query\UserToken\FindByHash;

use App\Components\Cacher\Cacher;
use App\Modules\User\ReadModel\UserToken\UserTokenByHash;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class UserTokenFindByHashFetcher
{
    private const string TABLE = 'user_tokens';
    private const int TTL = 2592000;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
    ) {}

    /**
     * @throws Exception
     */
    public function fetch(UserTokenFindByHashQuery $query): ?UserTokenByHash
    {
        $key = 'user_token_' . $query->tokenHash;
        /** @var UserTokenByHash|null $token */
        $token = $this->cacher->get($key);

        if ($token !== null) {
            return $token;
        }

        $row = $this->connection->createQueryBuilder()
            ->select('id', 'user_id', 'expires_at', 'revoked_at', 'used_at')
            ->from(self::TABLE)
            ->where('token_hash = :tokenHash')
            ->andWhere('type = :type')
            ->setParameter('tokenHash', $query->tokenHash)
            ->setParameter('type', $query->type->value)
            ->setMaxResults(1)
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        /** @var array{
         *      id: int,
         *      user_id: int,
         *      expires_at: string,
         *      revoked_at: string|null,
         *      used_at: string|null
         * } $row */
        $token = UserTokenByHash::fromRow($row);
        $this->cacher->set($key, $token, self::TTL);

        return $token;
    }
}
