#!/usr/bin/env bash
set -euo pipefail
umask 077
root="${BIOFARM_DEPLOY_ROOT:-$HOME}"
app=$(readlink -f "$root/biofarm-current")
case "$app" in "$root"/biofarm-releases/*) ;; *) echo 'Unexpected current release' >&2; exit 1;; esac
backup="$root/biofarm-backups/$(date -u +%Y%m%d-%H%M%S)-before-update"
mkdir -m 700 "$backup"
trap 'rm -f "$backup/mysql.cnf"' EXIT
/opt/php/8.4/bin/php /dev/stdin "$app" "$backup" <<'PHP'
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
name=$(cat "$backup/database-name")
mysqldump --defaults-extra-file="$backup/mysql.cnf" --single-transaction --quick --routines --events --triggers --no-tablespaces --set-gtid-purged=OFF --column-statistics=0 "$name" | gzip -6 > "$backup/database.sql.gz"
tar -czpf "$backup/site.tar.gz" -C "$app" .
# Shared paths are symlinks in releases, so archive their real contents separately.
tar -czpf "$backup/shared.tar.gz" -C "$root/biofarm-shared" .
crontab -l > "$backup/crontab.txt"
readlink "$root/biofarm-current" > "$backup/current-target.txt"
gzip -t "$backup/database.sql.gz"
tar -tzf "$backup/site.tar.gz" > /dev/null
tar -tzf "$backup/shared.tar.gz" > /dev/null
(cd "$backup" && sha256sum database.sql.gz site.tar.gz shared.tar.gz crontab.txt current-target.txt > SHA256SUMS && sha256sum -c SHA256SUMS)
printf 'BACKUP=%s\n' "$backup"
