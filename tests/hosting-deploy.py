#!/usr/bin/env python3
"""Run with python3 tests/hosting-deploy.py; never connects to a real site or DB."""
import importlib.util
import json
import pathlib
import signal
import subprocess
import sys
import tempfile
import time
import unittest

MODULE = pathlib.Path(__file__).resolve().parents[1] / 'deploy/hosting/release.py'
spec = importlib.util.spec_from_file_location('hosting_release', str(MODULE))
release = importlib.util.module_from_spec(spec)
spec.loader.exec_module(release)


class DeploymentSafety(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = pathlib.Path(self.temp.name).resolve()
        self.shared = self.root / 'biofarm-shared'
        (self.shared / 'uploads').mkdir(parents=True)
        (self.shared / '.env').write_text('SECRET=preserved\n')
        self.old = self.make_release('old')
        self.new = self.make_release('new')
        (self.root / 'biofarm-current').symlink_to(self.old)
        (self.root / 'biofarm-tools').mkdir()
        self.calls = []

    def make_release(self, name):
        path = self.root / 'biofarm-releases' / name
        for directory in ['public/build/.vite', 'public/build/assets', 'vendor']:
            (path / directory).mkdir(parents=True, exist_ok=True)
        for filename in ['public/index.php', 'public/.htaccess', 'vendor/autoload.php']:
            (path / filename).write_text('fixture')
        (path / '.env').symlink_to(self.shared / '.env')
        (path / 'public/uploads').symlink_to(self.shared / 'uploads')
        manifest = {}
        for entry, filename in [('assets/styles/app.css', 'app.css'), ('assets/react/mount.tsx', 'mount.js'), ('assets/react/admin/main.tsx', 'admin.js')]:
            manifest[entry] = {'file': 'assets/' + filename}
            (path / 'public/build/assets' / filename).write_text('fixture')
        (path / 'public/build/.vite/manifest.json').write_text(json.dumps(manifest))
        return path

    def healthy(self, path, live):
        self.calls.append((path, live))

    def test_success_activates_verified_release_and_remembers_previous(self):
        release.activate(self.root, self.new, self.healthy)
        self.assertEqual((self.root / 'biofarm-current').resolve(), self.new)
        self.assertEqual(self.calls, [(self.new, False), (self.new, True)])
        self.assertEqual((self.root / 'biofarm-tools/previous-release').read_text().strip(), str(self.old))
        self.assertEqual((self.shared / '.env').read_text(), 'SECRET=preserved\n')

    def test_missing_css_prevents_switch(self):
        (self.new / 'public/build/assets/app.css').unlink()
        with self.assertRaises(RuntimeError):
            release.activate(self.root, self.new, self.healthy)
        self.assertEqual((self.root / 'biofarm-current').resolve(), self.old)
        self.assertEqual(self.calls, [])

    def test_failed_candidate_does_not_switch(self):
        def fail(path, live):
            raise RuntimeError('candidate HTTP 500')
        with self.assertRaises(RuntimeError):
            release.activate(self.root, self.new, fail)
        self.assertEqual((self.root / 'biofarm-current').resolve(), self.old)

    def test_failed_live_check_restores_previous_site(self):
        def fail_live(path, live):
            if live:
                raise RuntimeError('public HTTP 500')
        with self.assertRaises(RuntimeError):
            release.activate(self.root, self.new, fail_live)
        self.assertEqual((self.root / 'biofarm-current').resolve(), self.old)
        self.assertFalse((self.root / 'biofarm-tools/previous-release').exists())
        self.assertTrue((self.shared / '.env').exists())

    def test_rollback_switches_back_and_preserves_uploaded_files(self):
        release.activate(self.root, self.new, self.healthy)
        (self.shared / 'uploads/customer.pdf').write_bytes(b'new-upload')
        release.rollback(self.root, self.healthy)
        self.assertEqual((self.root / 'biofarm-current').resolve(), self.old)
        self.assertEqual((self.old / 'public/uploads/customer.pdf').read_bytes(), b'new-upload')

    def test_uploads_must_point_to_shared_storage(self):
        (self.new / 'public/uploads').unlink()
        (self.new / 'public/uploads').mkdir()
        with self.assertRaises(RuntimeError):
            release.activate(self.root, self.new, self.healthy)
        self.assertEqual((self.root / 'biofarm-current').resolve(), self.old)

    def test_refuses_release_outside_managed_directory(self):
        with self.assertRaises(RuntimeError):
            release.activate(self.root, self.shared, self.healthy)
        self.assertEqual((self.root / 'biofarm-current').resolve(), self.old)

    def test_refuses_manifest_path_traversal(self):
        manifest = self.new / 'public/build/.vite/manifest.json'
        data = json.loads(manifest.read_text())
        data['assets/styles/app.css']['file'] = '../../../.env'
        manifest.write_text(json.dumps(data))
        with self.assertRaises(RuntimeError):
            release.activate(self.root, self.new, self.healthy)
        self.assertEqual((self.root / 'biofarm-current').resolve(), self.old)

    def test_keeps_old_chunks_available_for_already_open_tabs(self):
        (self.old / 'public/build/assets/old-editor.js').write_text('old lazy chunk')
        release.activate(self.root, self.new, self.healthy)
        self.assertEqual((self.new / 'public/build/assets/old-editor.js').read_text(), 'old lazy chunk')
        (self.new / 'public/build/assets/new-editor.js').write_text('new lazy chunk')
        release.rollback(self.root, self.healthy)
        self.assertEqual((self.old / 'public/build/assets/new-editor.js').read_text(), 'new lazy chunk')

    def test_records_rollback_target_before_live_switch_check(self):
        def check_record(path, live):
            if live:
                self.assertEqual((self.root / 'biofarm-tools/previous-release').read_text().strip(), str(self.old))
        release.activate(self.root, self.new, check_record)

    def test_sigterm_during_live_check_rolls_back(self):
        marker = self.root / 'checking-live'
        script = '''import importlib.util,pathlib,time,sys
s=importlib.util.spec_from_file_location('release',sys.argv[1]);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
def health(path,live):
    if live:
        pathlib.Path(sys.argv[4]).write_text('ready')
        time.sleep(30)
m.activate(pathlib.Path(sys.argv[2]),pathlib.Path(sys.argv[3]),health)
'''
        process = subprocess.Popen([sys.executable, '-c', script, str(MODULE), str(self.root), str(self.new), str(marker)], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        try:
            deadline = time.monotonic() + 5
            while not marker.exists() and time.monotonic() < deadline and process.poll() is None:
                time.sleep(0.02)
            self.assertTrue(marker.exists(), 'Child did not reach live health check')
            process.send_signal(signal.SIGTERM)
            process.communicate(timeout=5)
            self.assertNotEqual(process.returncode, 0)
            self.assertEqual((self.root / 'biofarm-current').resolve(), self.old)
        finally:
            if process.poll() is None:
                process.kill()
            process.communicate()


if __name__ == '__main__':
    unittest.main(verbosity=2)
