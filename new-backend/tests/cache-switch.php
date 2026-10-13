<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Components\Cacher\Cacher;
use App\Components\Cacher\NullCacher;
use App\Components\Cacher\RedisCacher;
use DI\ContainerBuilder;

function check(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}

function cacheFromConfig(): Cacher
{
    $builder = new ContainerBuilder();
    $builder->addDefinitions(require dirname(__DIR__) . '/config/common/cacher.php');
    return $builder->build()->get(Cacher::class);
}

// Invalid connection settings must never be read when caching is disabled.
putenv('REDIS=false');
putenv('REDIS_HOST');
putenv('REDIS_PORT=not-a-port');
putenv('REDIS_PASSWORD_FILE=/nonexistent/cache-switch-secret');
$cache = cacheFromConfig();
check($cache instanceof NullCacher, 'Disabled caching selects NullCacher');
foreach ([null, false, true, 42, 1.5, 'value', ['nested' => 'value'], (object)['value' => 1]] as $value) {
    check(!$cache->set('key', $value, 60), 'No value is stored');
    check($cache->get('key') === null, 'Every read is a cache miss');
}
check(!$cache->setTagged('tagged', 'value', 60, ['tag']), 'Tagged values are not stored');
$cache->delete('key');
$cache->deleteTag('tag');
$cache->expire('key', 60);
$cache->increase('counter', 2);
$cache->decrease('counter', 1);
$cache->zAdd('sorted', 1, 'member');
$cache->sAdd('set', 'member');
check($cache->get('tagged') === null && $cache->get('counter') === null, 'No tagged values or counters retained');
check($cache->mGet(['one', 'two']) === [null, null] && $cache->mGet([]) === [], 'Multi-get preserves miss positions');
check($cache->zRangeByScore('sorted', 0, 2) === [], 'Sorted set stays empty');
check($cache->zRevRangeByScore('sorted', 2, 0) === [], 'Reverse sorted set stays empty');
check($cache->sMembers('set') === [], 'Set stays empty');

putenv('REDIS=not-a-boolean');
try {
    cacheFromConfig();
    throw new LogicException('Invalid switch was accepted');
} catch (RuntimeException $error) {
    check(str_contains($error->getMessage(), "Env 'REDIS' must be boolean"), 'Invalid switch has a clear error');
}

putenv('REDIS=true');
putenv('REDIS_HOST=127.0.0.1');
putenv('REDIS_PORT=6379');
putenv('REDIS_PASSWORD=');
putenv('REDIS_PASSWORD_FILE');
unset($_ENV['REDIS_PASSWORD'], $_ENV['REDIS_PASSWORD_FILE']);
if (extension_loaded('redis')) {
    check(cacheFromConfig() instanceof RedisCacher, 'Enabled caching selects RedisCacher');
    // Missing switch preserves existing installations using Redis.
    putenv('REDIS');
    unset($_ENV['REDIS'], $_ENV['REDIS_FILE']);
    check(cacheFromConfig() instanceof RedisCacher, 'Redis remains the default for existing installations');
} else {
    try {
        cacheFromConfig();
        throw new LogicException('Enabled Redis accepted a missing extension');
    } catch (RuntimeException $error) {
        check(str_contains($error->getMessage(), 'REDIS=true requires the PHP redis extension'), 'Missing extension has a clear error');
    }
}

echo "Cache switch: disabled operations, ignored connection settings, validation and enabled configuration passed.\n";
