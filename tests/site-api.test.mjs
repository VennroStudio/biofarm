import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const source = await readFile(new URL('../assets/react/site/api.ts', import.meta.url), 'utf8');
const { outputText } = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS } });
const json = (data, status = 200) => Response.json(data, { status });
const user = { id: 1, email: 'test@example.test', first_name: 'Test' };

function client(fetch, storage = new Map([['biofarm_access_token', 'expired'], ['biofarm_user', JSON.stringify(user)]]), locks) {
  const exports = {};
  runInNewContext(outputText, {
    exports, fetch, Headers, FormData,
    navigator: { locks },
    window: { localStorage: {
      getItem: (key) => storage.get(key) ?? null,
      setItem: (key, value) => storage.set(key, value),
      removeItem: (key) => storage.delete(key),
    } },
  });
  return { api: exports, storage };
}

test('expired access is refreshed once for concurrent profile requests and each request is retried', async () => {
  let refreshes = 0;
  const { api } = client(async (path, options) => {
    if (path === '/v1/auth/refresh') {
      refreshes++;
      assert.equal(options.method, 'POST');
      assert.equal(options.credentials, 'same-origin');
      return json({ data: { access_token: 'renewed', expires_in: 900 } });
    }
    if (options.headers.get('Authorization') !== 'Bearer renewed') return json({}, 401);
    return json({ data: path === '/v1/users/me' ? user : { items: [] } });
  });
  const [profile, orders, addresses] = await Promise.all([api.refreshUser(), api.getOrders(), api.getUserAddresses()]);
  assert.equal(profile.id, '1');
  assert.equal(orders.length, 0);
  assert.equal(addresses.length, 0);
  assert.equal(api.getToken(), 'renewed');
  assert.equal(refreshes, 1);
});

test('retry preserves PATCH method and payload', async () => {
  const { api } = client(async (path, options) => {
    if (path === '/v1/auth/refresh') return json({ data: { access_token: 'renewed' } });
    if (options.headers.get('Authorization') !== 'Bearer renewed') return json({}, 401);
    assert.equal(options.method, 'PATCH');
    assert.equal(JSON.parse(options.body).name, 'Updated');
    return json({ data: { ...user, name: 'Updated' } });
  });
  assert.equal((await api.updateProfile({ name: 'Updated' })).name, 'Updated');
});

for (const status of [401, 422]) {
  test(`invalid or missing refresh cookie (${status}) clears the expired session`, async () => {
    let refreshes = 0;
    const { api } = client(async (path) => {
      if (path === '/v1/auth/refresh') { refreshes++; return json({}, status); }
      return json({}, 401);
    });
    await assert.rejects(api.refreshUser());
    assert.equal(refreshes, 1);
    assert.equal(api.getToken(), null);
    assert.equal(api.getStoredUser(), null);
  });
}

for (const failure of ['network', 'server']) {
  test(`temporary refresh ${failure} failure keeps the session for the next attempt`, async () => {
    let failed = false;
    const { api } = client(async (path, options) => {
      if (path === '/v1/auth/refresh') {
        if (failed) return json({ data: { access_token: 'renewed' } });
        failed = true;
        if (failure === 'network') throw new TypeError('Offline');
        return json({}, 503);
      }
      if (options.headers.get('Authorization') === 'Bearer renewed') return json({ data: user });
      return json({}, 401);
    });
    await assert.rejects(api.refreshUser());
    assert.equal(api.getToken(), 'expired');
    assert.ok(api.getStoredUser());
    assert.equal((await api.refreshUser()).id, '1');
    assert.equal(api.getToken(), 'renewed');
  });
}

test('a retry rejected with 401 ends the session without a refresh loop', async () => {
  let requests = 0;
  const { api } = client(async (path) => {
    requests++;
    return path === '/v1/auth/refresh' ? json({ data: { access_token: 'renewed' } }) : json({}, 401);
  });
  await assert.rejects(api.refreshUser());
  assert.equal(requests, 3);
  assert.equal(api.getToken(), null);
});

test('a late 401 from an old request reuses the renewed token without rotating again', async () => {
  let releaseOldResponse;
  const oldResponse = new Promise((resolve) => { releaseOldResponse = resolve; });
  let refreshes = 0;
  const { api } = client(async (path, options) => {
    if (path === '/v1/auth/refresh') {
      refreshes++;
      return json({ data: { access_token: 'renewed' } });
    }
    if (options.headers.get('Authorization') === 'Bearer renewed') return json({ data: path === '/v1/users/me' ? user : { items: [] } });
    if (path.startsWith('/v1/orders')) return oldResponse;
    return json({}, 401);
  });
  const orders = api.getOrders();
  await api.refreshUser();
  releaseOldResponse(json({}, 401));
  assert.equal((await orders).length, 0);
  assert.equal(refreshes, 1);
  assert.equal(api.getToken(), 'renewed');
});

test('guest requests do not silently restore a previously logged-out session', async () => {
  const { api } = client(async (path) => {
    assert.equal(path, '/v1/users/me');
    return json({}, 401);
  }, new Map());
  await assert.rejects(api.refreshUser());
  assert.equal(api.getToken(), null);
});

test('incorrect login credentials do not refresh or erase an existing session', async () => {
  const { api } = client(async (path) => {
    assert.equal(path, '/v1/auth/login');
    return json({}, 401);
  });
  await assert.rejects(api.login('test@example.test', 'incorrect'));
  assert.equal(api.getToken(), 'expired');
});

test('logout during refresh cannot restore the cleared access token', async () => {
  let api;
  ({ api } = client(async (path) => {
    if (path === '/v1/auth/refresh') {
      api.clearAuth();
      return json({ data: { access_token: 'renewed' } });
    }
    return json({}, 401);
  }));
  await assert.rejects(api.refreshUser());
  assert.equal(api.getToken(), null);
});

test('two tabs share a refresh lock and reuse the refreshed access token', async () => {
  const storage = new Map([['biofarm_access_token', 'expired']]);
  let tail = Promise.resolve();
  const locks = { request: (_name, callback) => {
    const result = tail.then(callback);
    tail = result.catch(() => undefined);
    return result;
  } };
  let refreshes = 0;
  const fetch = async (path, options) => {
    if (path === '/v1/auth/refresh') {
      refreshes++;
      return json({ data: { access_token: 'renewed' } });
    }
    return options.headers.get('Authorization') === 'Bearer renewed' ? json({ data: user }) : json({}, 401);
  };
  const first = client(fetch, storage, locks).api;
  const second = client(fetch, storage, locks).api;
  const profiles = await Promise.all([first.refreshUser(), second.refreshUser()]);
  assert.equal(profiles[0].id, '1');
  assert.equal(profiles[1].id, '1');
  assert.equal(refreshes, 1);
});
