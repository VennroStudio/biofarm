import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';
import ts from 'typescript';
const source=await readFile(new URL('../assets/react/site/cookiePolicy.ts',import.meta.url),'utf8');
const {outputText}=ts.transpileModule(source,{compilerOptions:{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2022}});
const exports={};vm.runInNewContext(outputText,{exports,Date,JSON});
test('optional scripts are denied without valid current unexpired explicit choices',()=>{
 for(const raw of ['', 'bad', JSON.stringify({version:'old',analytics:true,marketing:true,expiresAt:Date.now()+1000}), JSON.stringify({version:'2026-10-04',analytics:'yes',marketing:true,expiresAt:Date.now()+1000}), JSON.stringify({version:'2026-10-04',analytics:true,marketing:true,expiresAt:0})]) assert.equal(exports.readCookieChoice(raw),null);
 const choice=exports.readCookieChoice(JSON.stringify({version:'2026-10-04',analytics:false,marketing:true,expiresAt:Date.now()+1000}));
 assert.equal(choice.analytics,false);assert.equal(choice.marketing,true);
});
