import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';
import ts from 'typescript';
const source = await readFile(new URL('../assets/react/admin/features/products/model/productForm.ts', import.meta.url), 'utf8');
const { outputText } = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } });
const exports = {};
vm.runInNewContext(outputText, { exports, require: () => ({ listFromLines: value => value.split('\n').map(v => v.trim()).filter(Boolean) }), URL });
const valid = { ...exports.emptyProductForm, name: 'Тест', category_id: 'category', weight: '100 г', price: '500', description: '<p>Описание</p>', image_items: [exports.imageItem('/test.png', 0, true)] };
test('validation finds required fields even outside the visible tab', () => {
  assert.equal(exports.productFormIssue(valid), null);
  assert.equal(exports.productFormIssue({ ...valid, name: '  ' }).field, 'name');
  assert.equal(exports.productFormIssue({ ...valid, description: '<p><br></p>' }).field, 'description');
  assert.equal(exports.productFormIssue({ ...valid, description: '<p>&nbsp;</p>' }).field, 'description');
  assert.equal(exports.productFormIssue({ ...valid, image_items: [] }).field, 'images');
});
test('prices match integer ruble API and marketplace links reject unsafe URLs', () => {
  for (const price of ['0', '-1', '10.5', 'no']) assert.equal(exports.productFormIssue({ ...valid, price }).field, 'price');
  assert.equal(exports.productFormIssue({ ...valid, wb_link: 'javascript:alert(1)' }).field, 'wb_link');
  assert.equal(exports.productFormIssue({ ...valid, ozon_link: 'https://ozon.ru/product/test' }), null);
});
test('saving another tab preserves rich HTML, stored GTIN and selected relationships', () => {
  const form = { ...valid, description: '<p><span style="color: red">Текст</span></p>', gtin: '1234567890123', certificate_ids: [1], related_blog_post_ids: [2], attribute_value_ids: [3], product_group_id: '4' };
  const payload = exports.productPayloadFromForm(form);
  assert.equal(payload.description, form.description);
  assert.equal(payload.gtin, form.gtin);
  assert.equal(JSON.stringify(payload.certificateIds), '[1]');
  assert.equal(JSON.stringify(payload.relatedBlogPostIds), '[2]');
  assert.equal(JSON.stringify(payload.attributeValueIds), '[3]');
  assert.equal(payload.productGroupId, 4);
});
