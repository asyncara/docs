import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
const require=createRequire(import.meta.url);
const config=require('../docusaurus.config.js');
assert.deepEqual(config.i18n.locales,['en'],'Only English should be published');
assert.equal(config.i18n.defaultLocale,'en');
assert(!config.themeConfig.navbar.items.some(item=>item.type==='localeDropdown'),'Remove the inactive language selector');
function files(dir){return fs.readdirSync(dir,{withFileTypes:true}).flatMap(entry=>{
 const name=path.join(dir,entry.name);return entry.isDirectory()?files(name):[name];
});}
const pages=files('docs').filter(name=>/\.mdx?$/.test(name));
const prohibited=[
 /\bgh\s+(?:auth|release\s+download)\b/i,
 /private repositor(?:y|ies)/i,
 /\/home\/coder\//,
 /\/tmp\/asyncara[^\s]*/,
 /(?:historical|uninstrumented).*\bFPM\b/i,
 /(?:endurance|soak) campaign/i,
 /reference\/verification|\/evidence\//,
];
for(const name of [...pages,...files('src/pages')]){
 const content=fs.readFileSync(name,'utf8');
 for(const pattern of prohibited)assert(!pattern.test(content),`${name}: internal administration/report text: ${pattern}`);
}
assert(!fs.existsSync('static/evidence'),'Engineering reports must not be served as site assets');
console.log(`PASS: ${pages.length} published guides; English only; no repository-access instructions or internal report assets`);
