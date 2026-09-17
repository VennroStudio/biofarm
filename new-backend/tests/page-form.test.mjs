import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';
import ts from 'typescript';
const source = await readFile(new URL('../assets/react/admin/features/pages/model/pageForm.ts', import.meta.url), 'utf8');
const { outputText } = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } });
const exports = {};
vm.runInNewContext(outputText, { exports });
const valid = { ...exports.emptyPageForm, title: 'Страница', slug_path: 'info/test' };

test('page validation respects fixed system routes and integer order', () => {
  assert.equal(exports.pageFormIssue(valid), null);
  assert.equal(exports.pageFormIssue({ ...valid, title: ' ' }).field, 'title');
  assert.equal(exports.pageFormIssue({ ...valid, slug_path: '' }).field, 'slug_path');
  assert.equal(exports.pageFormIssue({ ...valid, page_type: 'system', slug_path: '' }), null);
  for (const sort_order of ['1.5', 'bad', '2147483648']) assert.equal(exports.pageFormIssue({ ...valid, sort_order }).field, 'sort_order');
});

test('page payload preserves HTML, navigation and social fields across tabs', () => {
  const form = { ...valid, content: '<h2>Раздел</h2><p><strong>Текст</strong></p>', template: 'legal', excerpt: 'Вступление', og_image: '/cover.png', og_title: 'Превью', show_in_footer: true };
  const payload = exports.pagePayloadFromForm(form);
  assert.equal(payload.content, form.content);
  assert.equal(payload.template, 'legal');
  assert.equal(payload.ogImage, '/cover.png');
  assert.equal(payload.ogTitle, 'Превью');
  assert.equal(payload.showInFooter, true);
  assert.equal(exports.pagePayloadFromForm({ ...form, is_indexable: false, show_in_sitemap: true }).showInSitemap, false);
  const system = exports.pagePayloadFromForm({ ...form, page_type: 'system' });
  for (const field of ['content', 'template', 'slugPath', 'excerpt', 'publishedAt']) assert.equal(system[field], null);
});
