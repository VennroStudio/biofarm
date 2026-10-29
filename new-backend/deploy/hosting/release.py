#!/usr/bin/env python3
"""Validate and atomically activate a REG.RU release. No database writes."""
import json
import hashlib
import os
import pathlib
import shutil
import signal
import subprocess
import sys
import uuid


def validate_files(root, candidate):
    root, candidate = pathlib.Path(root).resolve(), pathlib.Path(candidate).resolve()
    if candidate.parent != root / 'biofarm-releases' or not candidate.is_dir():
        raise RuntimeError('Release must be a directory inside biofarm-releases')
    shared = root / 'biofarm-shared'
    for path, target in [(candidate / '.env', shared / '.env'),
                         (candidate / 'public/uploads', shared / 'uploads')]:
        if not path.is_symlink() or path.resolve() != target.resolve() or not path.exists():
            raise RuntimeError('Release must use shared storage: ' + str(path))
    for name in ['public/index.php', 'public/.htaccess', 'vendor/autoload.php']:
        path = candidate / name
        if not path.is_file() or path.stat().st_size == 0:
            raise RuntimeError('Required file missing or empty: ' + name)
    build = candidate / 'public/build'
    manifest = json.loads((build / '.vite/manifest.json').read_text())
    for entry in ['assets/styles/app.css', 'assets/react/mount.tsx', 'assets/react/admin/main.tsx']:
        if entry not in manifest or not manifest[entry].get('file'):
            raise RuntimeError('Required build entry missing: ' + entry)
    for entry in manifest.values():
        for name in [entry['file']] + entry.get('css', []) + entry.get('assets', []):
            path = (build / name).resolve()
            if build.resolve() not in path.parents or not path.is_file() or path.stat().st_size == 0:
                raise RuntimeError('Build asset missing or invalid: ' + name)
        for name in entry.get('imports', []) + entry.get('dynamicImports', []):
            if name not in manifest:
                raise RuntimeError('Build import missing: ' + name)
    return candidate


def atomic_link(target, link):
    temporary = link.parent / ('.' + link.name + '-' + uuid.uuid4().hex)
    try:
        temporary.symlink_to(target)
        os.replace(str(temporary), str(link))
    finally:
        if temporary.is_symlink():
            temporary.unlink()


def preserve_assets(previous, candidate):
    source, target = previous / 'public/build/assets', candidate / 'public/build/assets'
    for path in source.rglob('*'):
        if path.is_symlink():
            raise RuntimeError('Unexpected symlink in build assets')
        if not path.is_file():
            continue
        destination = target / path.relative_to(source)
        if destination.exists():
            if hashlib.sha256(path.read_bytes()).digest() != hashlib.sha256(destination.read_bytes()).digest():
                raise RuntimeError('Build asset filename collision: ' + path.name)
        else:
            destination.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(str(path), str(destination))


def activate(root, candidate, health):
    root = pathlib.Path(root).resolve()
    candidate = validate_files(root, candidate)
    current = root / 'biofarm-current'
    if not current.is_symlink():
        raise RuntimeError('biofarm-current must already be a symlink')
    previous = current.resolve()
    if previous.parent != root / 'biofarm-releases' or not previous.is_dir():
        raise RuntimeError('Current release is outside managed storage')
    if previous == candidate:
        raise RuntimeError('Release already active')
    preserve_assets(previous, candidate)
    health(candidate, False)
    record = root / 'biofarm-tools/previous-release'
    old_record = record.read_text() if record.exists() else None
    handlers = {}

    def interrupted(number, frame):
        raise RuntimeError('Activation interrupted by signal ' + str(number))

    for number in [signal.SIGTERM, signal.SIGHUP, signal.SIGINT]:
        handlers[number] = signal.signal(number, interrupted)
    try:
        # Persist recovery target before the switch, including for SIGKILL/power loss.
        # Catchable signals restore the site automatically; SIGKILL permits manual rollback.
        temporary = record.with_name('.previous-' + uuid.uuid4().hex)
        temporary.write_text(str(previous) + '\n')
        os.replace(str(temporary), str(record))
        atomic_link(candidate, current)
        health(candidate, True)
    except BaseException:
        atomic_link(previous, current)
        if old_record is None:
            if record.exists():
                record.unlink()
        else:
            record.write_text(old_record)
        raise
    finally:
        for number, handler in handlers.items():
            signal.signal(number, handler)


def rollback(root, health):
    root = pathlib.Path(root).resolve()
    candidate = pathlib.Path((root / 'biofarm-tools/previous-release').read_text().strip())
    activate(root, candidate, health)


def main():
    if len(sys.argv) < 3 or sys.argv[1] not in ['verify', 'activate', 'rollback']:
        raise RuntimeError('Usage: release.py verify|activate ROOT RELEASE; release.py rollback ROOT')
    mode, root = sys.argv[1], pathlib.Path(sys.argv[2]).resolve()

    def health(path, live):
        subprocess.check_call([sys.executable, str(root / 'biofarm-tools/health.py'),
                               str(path), 'live' if live else 'candidate'])

    if mode == 'rollback':
        rollback(root, health)
    elif mode == 'verify':
        validate_files(root, pathlib.Path(sys.argv[3]))
    else:
        activate(root, pathlib.Path(sys.argv[3]), health)
    print('Release operation succeeded: ' + mode)


if __name__ == '__main__':
    try:
        main()
    except Exception as error:
        print('Deployment stopped: ' + str(error), file=sys.stderr)
        sys.exit(1)
