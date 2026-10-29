#!/usr/bin/env python3
"""Read-only HTTP and asset checks, against an isolated candidate or the live site."""
import hashlib
import http.cookiejar
from html.parser import HTMLParser
import os
import pathlib
import re
import socket
import subprocess
import sys
import tempfile
import time
import urllib.parse
import urllib.request


class References(HTMLParser):
    def __init__(self):
        HTMLParser.__init__(self)
        self.assets, self.pages = set(), set()

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        for key in ['src', 'href', 'poster']:
            value = attrs.get(key, '')
            path = urllib.parse.urlsplit(value).path
            if value.startswith('/') and not value.startswith('//'):
                if path.startswith(('/build/', '/assets/', '/uploads/')):
                    self.assets.add(path)
                elif tag == 'a' and path.startswith(('/product/', '/blog/')):
                    self.pages.add(path)


def inspect_site(candidate, base, host):
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def get(path):
        url = base + urllib.parse.quote(urllib.parse.unquote(path), safe='/%?=&')
        # REG.RU's protection may first serve an HTML refresh while setting RCPC.
        for attempt in range(3):
            request = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 BiofarmDeploy/1.0', 'Host': host})
            with opener.open(request, timeout=20) as response:
                body, kind = response.read(), response.headers.get_content_type()
                if response.status != 200:
                    raise RuntimeError('HTTP check failed: ' + path)
            if kind == 'text/html' and re.search(br'<meta[^>]+http-equiv=["\x27]?refresh', body, re.I):
                match = re.search(br'url=([^"\x27<>\s]+)', body, re.I)
                if match:
                    next_url = urllib.parse.urljoin(url, match.group(1).decode('utf-8'))
                    if urllib.parse.urlsplit(next_url).netloc != urllib.parse.urlsplit(base).netloc:
                        raise RuntimeError('Unexpected protection redirect')
                    url = next_url
                continue
            return body, kind
        raise RuntimeError('Protection challenge did not clear: ' + path)

    refs = References()
    pages = ['/', '/catalog', '/blog', '/faq', '/certificates', '/admin']
    for path in pages:
        body, kind = get(path)
        if kind != 'text/html' or b'<html' not in body.lower() or b'/build/assets/' not in body:
            raise RuntimeError('Invalid HTML or missing build references: ' + path)
        refs.feed(body.decode('utf-8'))
    details = sorted(refs.pages)
    for path in details:
        body, kind = get(path)
        if kind != 'text/html' or b'<html' not in body.lower():
            raise RuntimeError('Detail page failed: ' + path)
        refs.feed(body.decode('utf-8'))
    for path in sorted(refs.assets):
        local = candidate / 'public' / urllib.parse.unquote(path).lstrip('/')
        if not local.is_file():
            raise RuntimeError('Referenced file missing: ' + path)
        body, kind = get(path)
        if hashlib.sha256(body).digest() != hashlib.sha256(local.read_bytes()).digest():
            raise RuntimeError('Served file differs from release: ' + path)
        if path.endswith('.css') and kind != 'text/css':
            raise RuntimeError('CSS has invalid Content-Type: ' + path)
        if path.endswith('.js') and kind not in ['application/javascript', 'text/javascript']:
            raise RuntimeError('JavaScript has invalid Content-Type: ' + path)
    print('HTTP verified: {} pages, {} assets (SHA-256 and MIME).'.format(len(pages) + len(details), len(refs.assets)))


def main():
    candidate = pathlib.Path(sys.argv[1]).resolve()
    domain = 'biofarm.store'
    if sys.argv[2] == 'live':
        inspect_site(candidate, 'https://' + domain, domain)
        return
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    with tempfile.TemporaryDirectory(prefix='biofarm-health-') as work:
        router = pathlib.Path(work) / 'router.php'
        router.write_text('<?php $p = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH); '
                          'if ($p !== "/" && is_file($_SERVER["DOCUMENT_ROOT"].rawurldecode($p))) return false; '
                          'require $_SERVER["DOCUMENT_ROOT"]."/index.php";')
        with open(os.path.join(work, 'php.log'), 'w') as log:
            process = subprocess.Popen(['/opt/php/8.4/bin/php', '-S', '127.0.0.1:' + str(port),
                                        '-t', str(candidate / 'public'), str(router)],
                                       cwd=str(candidate), stdout=log, stderr=log)
            try:
                for attempt in range(50):
                    if process.poll() is not None:
                        raise RuntimeError('Candidate PHP server exited')
                    try:
                        with socket.create_connection(('127.0.0.1', port), timeout=0.2):
                            break
                    except OSError:
                        time.sleep(0.1)
                inspect_site(candidate, 'http://127.0.0.1:' + str(port), domain)
            finally:
                process.terminate()
                process.wait(timeout=10)


if __name__ == '__main__':
    main()
