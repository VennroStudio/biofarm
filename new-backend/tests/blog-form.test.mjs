import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';
import ts from 'typescript';
const source = await readFile(new URL('../assets/react/admin/features/blog/model/blogForm.ts', import.meta.url), 'utf8');
const { outputText } = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } });
const exports = {};
vm.runInNewContext(outputText, { exports });
const valid = { ...exports.emptyBlogForm, title: 'Статья', excerpt: 'Анонс', content: '<p>Текст</p>', image: '/cover.jpg' };

test('article validation finds required fields across all tabs', () => {
  assert.equal(exports.blogFormIssue(valid), null);
  for (const field of ['title', 'category_id', 'author_name', 'image', 'excerpt']) {
    assert.equal(exports.blogFormIssue({ ...valid, [field]: ' ' }).field, field);
  }
  for (const content of ['', '<p><br></p>', '<p>&nbsp;</p>']) assert.equal(exports.blogFormIssue({ ...valid, content }).field, 'content');
  for (const read_time of ['0', '-1', '1.5', 'bad']) assert.equal(exports.blogFormIssue({ ...valid, read_time }).field, 'read_time');
});

test('saving a draft preserves rich content, cover, SEO and publication state', () => {
  const form = { ...valid, content: '<h2>Заголовок</h2><p><strong>Текст</strong></p>', image_alt: 'Обложка', seo_title: 'Поиск', is_published: false };
  const payload = exports.blogPayloadFromForm(form);
  assert.equal(payload.content, form.content);
  assert.equal(payload.image, form.image);
  assert.equal(payload.imageAlt, form.image_alt);
  assert.equal(payload.seoTitle, form.seo_title);
  assert.equal(payload.isPublished, false);
  assert.equal(payload.publishedAt, null);
});
