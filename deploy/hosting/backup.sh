#!/usr/bin/env bash
set -euo pipefail
umask 077
root="${BIOFARM_DEPLOY_ROOT:-$HOME}"
root=$(cd "$root" && pwd -P)
app=$(readlink -f "$root/biofarm-current")
if [ "$(dirname "$app")" != "$root/biofarm-releases" ]; then
    echo 'Unexpected current release' >&2
    exit 1
fi
release="biofarm-releases/$(basename "$app")"
node=$(readlink -f "$root/biofarm-tools/node")
if [ "$(dirname "$node")" != "$root/biofarm-tools" ] || [ ! -x "$node/bin/node" ]; then
    echo 'Unexpected Node.js installation' >&2
    exit 1
fi
test "$(readlink -f "$app/.env")" = "$root/biofarm-shared/.env"
test "$(readlink -f "$app/public/uploads")" = "$root/biofarm-shared/uploads"
test "$(readlink -f "$root/www/biofarm.store")" = "$app/public"
backup="$root/biofarm-backups"
mkdir -p -m 700 "$backup"
work=$(mktemp -d)
archive=
cleanup() {
    if [ -n "$archive" ]; then rm -f -- "$archive"; fi
    rm -f -- "$work/mysql.cnf" "$work/database-name" "$work/database.sql.gz" \
        "$work/crontab.txt" "$work/RESTORE.txt" "$work/archive-files.txt"
    rmdir "$work"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' HUP TERM
archive=$(mktemp "$backup/.biofarm-full.XXXXXX")
/opt/php/8.4/bin/php /dev/stdin "$app" "$work" <<'PHP'
<?php
require $argv[1].'/vendor/autoload.php';
Dotenv\Dotenv::createImmutable($argv[1])->safeLoad();
$escape = static fn(string $s): string => '"'.str_replace(["\\", '"', "\n", "\r"], ["\\\\", '\\"', '\\n', '\\r'], $s).'"';
$lines = ['[client]'];
foreach (['host'=>'DB_HOST', 'port'=>'DB_PORT', 'user'=>'DB_USER', 'password'=>'DB_PASSWORD'] as $key=>$env) {
    if (!isset($_ENV[$env])) throw new RuntimeException("Missing $env");
    $lines[] = $key.'='.$escape($_ENV[$env]);
}
file_put_contents($argv[2].'/mysql.cnf', implode("\n", $lines)."\n");
file_put_contents($argv[2].'/database-name', $_ENV['DB_NAME']);
PHP
name=$(cat "$work/database-name")
mysqldump --defaults-extra-file="$work/mysql.cnf" --single-transaction --quick --routines --events --triggers --no-tablespaces --set-gtid-purged=OFF --column-statistics=0 "$name" | gzip -6 > "$work/database.sql.gz"
rm -f -- "$work/mysql.cnf" "$work/database-name"
gzip -t "$work/database.sql.gz"
crontab -l > "$work/crontab.txt"
cat > "$work/RESTORE.txt" <<EOF
Biofarm production backup, $(date -u +%FT%TZ)
Release: $(cat "$app/RELEASE_COMMIT")
Original hosting root: $root

1. Extract this archive into a private recovery directory. It contains production
   credentials and customer data.
2. Restore database.sql.gz to a separate, empty MySQL database. Configure its
   connection in biofarm-shared/.env for the recovered installation.
3. The site root is $release/public. The archive includes the
   PHP handler settings, dependencies, built assets and uploads. If the hosting path changes, repoint
   biofarm-current, the release's .env and public/uploads, www/biofarm.store and
   bin/biofarm-update from the old root to the recovered installation.
4. Verify the site with biofarm-tools/health.py, then enable the saved crontab
   with paths adjusted to the recovery root. Recreate biofarm-repository.git from
   the Biofarm Git remote and configure its Git access before the next deployment.
EOF
tar --exclude="$release/node_modules" --exclude="$release/repository-upload-seeds" \
    --exclude="$release/var/cache/*" --exclude="$release/var/log/*" \
    --exclude='biofarm-shared/ozon-catalog-cron.log' --exclude='biofarm-shared/.env.lock' \
    -czpf "$archive" -C "$root" "$release" biofarm-shared biofarm-current www/biofarm.store php-bin/biofarm.store \
    bin/biofarm-update biofarm-tools/{backup.sh,health.py,release.py,update.sh,composer.phar,node} \
    "biofarm-tools/$(basename "$node")" -C "$work" database.sql.gz crontab.txt RESTORE.txt
gzip -t "$archive"
tar -tzf "$archive" > "$work/archive-files.txt"
for path in database.sql.gz crontab.txt RESTORE.txt biofarm-current www/biofarm.store \
    biofarm-shared/.env biofarm-shared/uploads/ php-bin/biofarm.store/php.ini "$release/RELEASE_COMMIT" \
    "$release/public/index.php" "$release/public/build/.vite/manifest.json" "$release/vendor/autoload.php"; do
    grep -Fxq -- "$path" "$work/archive-files.txt"
done
mv -fT -- "$archive" "$backup/biofarm-full.tar.gz"
archive=
printf 'BACKUP=%s\n' "$backup/biofarm-full.tar.gz"
