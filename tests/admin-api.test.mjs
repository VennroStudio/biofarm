import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const source = await readFile(new URL('../assets/react/admin/api/client.ts', import.meta.url), 'utf8');
const { outputText } = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS } });
const json = (data, status = 200) => Response.json(data, { status });
function client(fetch, storage = new Map([['biofarm_admin_access_token', 'expired'], ['biofarm_admin_user', '{"id":1}']]), locks) {
  const exports = {};
  const events = [];
  const localStorage = { getItem: (key) => storage.get(key) ?? null, setItem: (key, value) => storage.set(key, value), removeItem: (key) => storage.delete(key) };
  runInNewContext(outputText, { exports, fetch, Headers, FormData, Event, localStorage, navigator: { locks }, window: { dispatchEvent: (event) => events.push(event.type) } });
  return { api: exports, storage, events };
}

test('concurrent expired admin requests refresh once and retry, including auth/me', async () => {
  let refreshes = 0;
  const { api } = client(async (path, options) => {
    if (path === '/admin/api/auth/refresh') {
      refreshes++;
      assert.equal(options.credentials, 'same-origin');
      return json({ data: { access_token: 'renewed' } });
    }
    return options.headers.get('Authorization') === 'Bearer renewed' ? json({ data: { id: 1 } }) : json({}, 401);
  });
  await Promise.all([api.me(), api.request('/admin/api/products'), api.request('/admin/api/categories')]);
  assert.equal(refreshes, 1);
  assert.equal(api.getToken(), 'renewed');
});

test('two admin tabs coordinate refresh through a shared browser lock', async () => {
  const storage = new Map([['biofarm_admin_access_token', 'expired']]);
  let tail = Promise.resolve();
  const locks = { request: (_name, callback) => {
    const result = tail.then(callback);
    tail = result.catch(() => undefined);
    return result;
  } };
  let refreshes = 0;
  const fetch = async (path, options) => {
    if (path.endsWith('/refresh')) {
      refreshes++;
      return json({ data: { access_token: 'renewed' } });
    }
    return options.headers.get('Authorization') === 'Bearer renewed' ? json({ data: { id: 1 } }) : json({}, 401);
  };
  const first = client(fetch, storage, locks).api;
  const second = client(fetch, storage, locks).api;
  await Promise.all([first.me(), second.me()]);
  assert.equal(refreshes, 1);
  assert.equal(first.getToken(), 'renewed');
  assert.equal(second.getToken(), 'renewed');
});

for (const failure of ['network', 503]) test(`temporary refresh failure ${failure} keeps session`, async () => {
  const { api } = client(async (path) => {
    if (path.endsWith('/refresh')) { if (failure === 'network') throw new TypeError('Offline'); return json({}, failure); }
    return json({}, 401);
  });
  await assert.rejects(api.me());
  assert.equal(api.getToken(), 'expired');
  assert.ok(api.getStoredAdmin());
});

for (const status of [401,403,422]) test(`invalid refresh ${status} clears only admin session`, async () => {
  const { api, storage, events } = client(async (path) => json({}, path.endsWith('/refresh') ? status : 401));
  storage.set('biofarm_access_token', 'site-session');
  await assert.rejects(api.me());
  assert.equal(api.getToken(), null);
  assert.equal(storage.get('biofarm_access_token'), 'site-session');
  assert.ok(events.includes('biofarm-admin-session-cleared'));
});

test('PATCH body and image FormData survive the retry', async () => {
  for (const body of [{ name: 'Edited' }, new FormData()]) {
    const seen = [];
    const { api } = client(async (path, options) => {
      if (path.endsWith('/refresh')) return json({ data: { access_token: 'renewed' } });
      seen.push(options);
      return options.headers.get('Authorization') === 'Bearer renewed' ? json({ data: {} }) : json({}, 401);
    });
    await api.request('/admin/api/products/1', { method: 'PATCH', body });
    assert.equal(seen.length, 2);
    assert.equal(seen[1].method, 'PATCH');
    assert.equal(seen[1].body, body instanceof FormData ? body : JSON.stringify(body));
  }
});

test('refresh finishing after logout cannot restore the session', async () => {
  let release;
  const pending = new Promise((resolve) => { release = resolve; });
  let started;
  const ready = new Promise((resolve) => { started = resolve; });
  const { api } = client(async (path) => {
    if (path.endsWith('/refresh')) { started(); return pending; }
    return json({}, 401);
  });
  const request = api.me();
  await ready;
  api.clearSession();
  release(json({ data: { access_token: 'renewed' } }));
  await assert.rejects(request);
  assert.equal(api.getToken(), null);
});

test('late old 401 reuses updated token; retry 401 does not loop', async () => {
  let release;
  let refreshes = 0;
  const slow = new Promise((resolve) => { release = resolve; });
  const { api } = client(async (path, options) => {
    if (path.endsWith('/refresh')) { refreshes++; return json({ data: { access_token: 'renewed' } }); }
    if (options.headers.get('Authorization') === 'Bearer renewed') return json({ data: {} });
    if (path.endsWith('/slow')) return slow;
    return json({}, 401);
  });
  const late = api.request('/admin/api/slow');
  await api.me();
  release(json({},401));
  await late;
  assert.equal(refreshes, 1);
  let calls = 0;
  const denied = client(async (path) => { calls++; return path.endsWith('/refresh') ? json({data:{access_token:'renewed'}}) : json({},401); });
  await assert.rejects(denied.api.me());
  assert.equal(calls, 3);
  assert.equal(denied.api.getToken(), null);
});
