#!/usr/bin/env bash
# REG.RU shared hosting only; the existing Docker Makefile is independent.
set -euo pipefail
umask 022
root="${BIOFARM_DEPLOY_ROOT:-$HOME}"
tools="$root/biofarm-tools"
exec 9>"$tools/deploy.lock"
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }
export PATH="$tools/node/bin:$PATH"
php=/opt/php/8.4/bin/php
mode="${1:-update}"
case "$mode" in update|prepare|rollback) ;; *) echo 'Usage: biofarm-update [prepare|rollback]' >&2; exit 1;; esac

if [ "$mode" = rollback ]; then
    exec python3 "$tools/release.py" rollback "$root"
fi

repo="$root/biofarm-repository.git"
git --git-dir="$repo" fetch --prune origin '+refs/heads/main:refs/heads/main'
revision=$(git --git-dir="$repo" rev-parse --verify 'refs/heads/main^{commit}')
name="$(date -u +%Y%m%d-%H%M%S)-${revision:0:8}"
candidate="$root/biofarm-releases/$name"
mkdir -m 755 "$candidate"
git --git-dir="$repo" archive "$revision:new-backend" | tar -xf - -C "$candidate"

# Files generated through the admin stay outside Git releases.
if [ -e "$candidate/.env" ]; then echo 'Refusing tracked production .env' >&2; exit 1; fi
ln -s "$root/biofarm-shared/.env" "$candidate/.env"
if [ -e "$candidate/public/uploads" ]; then
    mv "$candidate/public/uploads" "$candidate/repository-upload-seeds"
fi
ln -s "$root/biofarm-shared/uploads" "$candidate/public/uploads"
mkdir -p "$candidate/var/cache" "$candidate/var/log"
find "$candidate" -type d -exec chmod 755 {} +
find "$candidate" -type f -exec chmod 644 {} +
printf '%s\n' "$revision" > "$candidate/RELEASE_COMMIT"

cd "$candidate"
"$php" "$tools/composer.phar" install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader
"$php" "$tools/composer.phar" check-platform-reqs --no-dev
npm ci --no-audit --no-fund
npm test
npm run typecheck
npm run build
find src config public -path public/uploads -prune -o -type f -name '*.php' -print0 | xargs -0 -n1 "$php" -l > "$candidate/var/log/php-lint.log"
"$php" tests/product-certificates.php
"$php" tests/image-upload.php

python3 "$tools/release.py" verify "$root" "$candidate"
python3 "$tools/health.py" "$candidate" candidate
if [ "$mode" = prepare ]; then
    printf 'PREPARED=%s\n' "$candidate"
    exit 0
fi

# This is a backup only. No imports, fixtures or schema updates run here.
bash "$tools/backup.sh"
python3 "$tools/release.py" activate "$root" "$candidate"

# Subsequent updates use the deployment scripts from the verified Git release.
# Rename atomically so a running shell never reads a partially overwritten script.
for script in backup.sh health.py release.py update.sh; do
    install -m 700 "$candidate/deploy/hosting/$script" "$tools/.$script.next"
    mv "$tools/.$script.next" "$tools/$script"
done

printf 'ACTIVE=%s\n' "$candidate"
printf 'Rollback: %s/bin/biofarm-update rollback\n' "$root"
