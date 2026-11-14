import {chromium} from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
const base=process.env.DOCS_URL || 'http://127.0.0.1:8899/docs/';
const output=process.env.CHECK_OUTPUT || 'verification/site-check';
fs.mkdirSync(output,{recursive:true});
const browser=await chromium.launch({headless:true,executablePath:process.env.CHROMIUM});
const page=await browser.newPage({viewport:{width:1440,height:1000}});
const errors=[];page.on('pageerror',e=>errors.push(e.message));
const results=[];
try {
 await page.goto(base,{waitUntil:'networkidle'});
 if(await page.locator('html').getAttribute('lang')!=='en')throw Error('Wrong language');
 if(await page.locator('html').getAttribute('dir')!=='ltr')throw Error('Wrong direction');
 if(!(await page.locator('h1').innerText()).includes('Write PHP naturally.'))throw Error('Wrong homepage');
 if(await page.getByText('العربية',{exact:true}).count())throw Error('Inactive language selector present');
 await page.screenshot({path:path.join(output,'home.png'),fullPage:true});
 for(const route of ['installation','operations/diagnostics','operations/compatibility','reference/examples','reference/overview']){
  const response=await page.goto(base+'guide/'+route+'/',{waitUntil:'networkidle'});
  if(response.status()!==200)throw Error('Missing guide '+route);
  const body=await page.locator('main').innerText();
  if(/gh auth|gh release download|private repository|historical.*FPM|endurance pass|lint-only|npm run/i.test(body))throw Error('Internal text in '+route);
  results.push({page:route,status:'PASS'});
 }
 await page.goto(base+'guide/quickstart/',{waitUntil:'networkidle'});
 await page.locator('input[type="search"],input[placeholder*="Search"]').first().fill('HttpClient');
 await page.locator('[class*="suggestion"], [class*="Suggestion"]').first().waitFor({state:'visible',timeout:10000});
 await page.keyboard.press('Escape');
 await page.setViewportSize({width:390,height:844});
 await page.goto(base,{waitUntil:'networkidle'});
 if(await page.evaluate(()=>document.documentElement.scrollWidth>window.innerWidth+1))throw Error('Mobile overflow');
 await page.screenshot({path:path.join(output,'mobile.png'),fullPage:true});
 for(const route of ['ar/','ar/guide/installation/','guide/reference/verification/','evidence/examples-bundle-r1.json','evidence/examples-source-r1.json']){
  const response=await page.request.get(base+route);
  if(response.status()!==404)throw Error('Removed resource still served: '+route+' '+response.status());
 }
 if(errors.length)throw Error(JSON.stringify(errors));
 results.push({locale:'en',direction:'ltr',home:'PASS',search:'PASS',mobile:'PASS',removedPages:'PASS'});
 fs.writeFileSync(path.join(output,'report.json'),JSON.stringify({url:base,results,errors},null,2)+'\n');
 console.log(JSON.stringify(results));
} finally {await browser.close();}
