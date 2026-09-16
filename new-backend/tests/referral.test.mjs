import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const compile = async (name) => ts.transpileModule(await readFile(new URL(`../assets/react/site/${name}.ts`, import.meta.url), 'utf8'), { compilerOptions: { module: ts.ModuleKind.CommonJS } }).outputText;
const apiSource = await compile('api');
const referralSource = await compile('referral');

async function visit(previous, incoming, validate) {
  const storage = new Map();
  if (previous) storage.set('biofarm_referral', JSON.stringify({ code: previous, expiresAt: Date.now() + 60000 }));
  const window = {
    localStorage: { getItem: key => storage.get(key) ?? null, setItem: (key, value) => storage.set(key, value), removeItem: key => storage.delete(key) },
    location: { search: incoming ? `?ref=${incoming}` : '', pathname: '/', hash: '' },
    history: { replaceState() {} },
  };
  const api = {};
  runInNewContext(apiSource, { exports: api, window });
  api.request = async path => ({ valid: await validate(path.split('/').pop()) });
  const referral = {};
  runInNewContext(referralSource, { exports: referral, window, URLSearchParams, require: () => api });
  referral.mountReferralHandler();
  await new Promise(resolve => setImmediate(resolve));
  return api.getReferralCode();
}

test('invalid first touch does not prevent a subsequent valid invitation', async () => {
  assert.equal(await visit('deleted', 'valid', code => code === 'valid'), 'valid');
});
test('existing valid first touch remains authoritative', async () => {
  assert.equal(await visit('original', 'new', () => true), 'original');
});
test('unknown invitation is not persisted', async () => {
  assert.equal(await visit(null, 'unknown', () => false), undefined);
});
test('deleted first touch is cleared even without a new invitation', async () => {
  assert.equal(await visit('deleted', null, () => false), undefined);
});
test('temporary network error does not destroy attribution', async () => {
  assert.equal(await visit('original', 'new', () => { throw new Error('offline'); }), 'original');
});
