import {chromium} from '@playwright/test';
import fs from 'node:fs';
const base=process.env.DOCS_URL || 'http://127.0.0.1:8899/docs/';
const browser=await chromium.launch({headless:true,executablePath:process.env.CHROMIUM});
const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
const results=[];
for(const [locale,dir,title]of [['','ltr','Write PHP naturally.'],['ar/','rtl','اكتب PHP بوضوح.']]){
 await page.goto(base+locale,{waitUntil:'networkidle'});
 if(await page.locator('html').getAttribute('lang')!==(locale?'ar':'en'))throw Error('Wrong language');
 if(await page.locator('html').getAttribute('dir')!==dir)throw Error('Wrong direction');
 if(!(await page.locator('h1').innerText()).includes(title))throw Error('Wrong homepage');
 await page.screenshot({path:`verification/home-${locale?'ar':'en'}.png`,fullPage:true});
 await page.goto(base+locale+'guide/quickstart/',{waitUntil:'networkidle'});
 const input=page.locator('input[type="search"],input[placeholder*="Search"],input[placeholder*="بحث"]').first();
 await input.fill('HttpClient');await page.waitForTimeout(1000);
 const hits=page.locator('[class*="suggestion"], [class*="Suggestion"]');
 if(await hits.count()===0)throw Error('Search returned no results');
 await page.keyboard.press('Escape');
 await page.setViewportSize({width:390,height:844});
 await page.goto(base+locale,{waitUntil:'networkidle'});
 if(await page.evaluate(()=>document.documentElement.scrollWidth>window.innerWidth+1))throw Error('Mobile overflow');
 await page.screenshot({path:`verification/mobile-${locale?'ar':'en'}.png`,fullPage:true});
 await page.setViewportSize({width:1440,height:1000});
 results.push({locale:locale?'ar':'en',direction:dir,home:'PASS',guide:'PASS',search:'PASS',mobile:'PASS'});
}
if(errors.length)throw Error(JSON.stringify(errors));
fs.writeFileSync('verification/site-check.json',JSON.stringify({url:base,results,errors},null,2)+'\n');await browser.close();console.log(JSON.stringify(results));
