#!/usr/bin/env python3
"""Lint every PHP example; optionally execute local examples with a matching module."""
import argparse, hashlib, json, os, pathlib, re, selectors, subprocess, tempfile, time, urllib.request, urllib.error
root=pathlib.Path(__file__).resolve().parent.parent
p=argparse.ArgumentParser();p.add_argument('--lint-only',action='store_true');p.add_argument('--php',default=os.environ.get('PHP','php'));p.add_argument('--extension');p.add_argument('--autoload');p.add_argument('--output');p.add_argument('--skip-processpool',action='store_true');args=p.parse_args()
results=[]
def command(cmd,env=None,timeout=30):
 r=subprocess.run(cmd,cwd=root,env=env,text=True,capture_output=True,timeout=timeout)
 if r.returncode:raise RuntimeError(f'{cmd}: exit {r.returncode}\n{r.stdout}\n{r.stderr}')
 return r
snippets=0
for path in sorted(list(root.glob('docs/**/*.md'))+list(root.glob('i18n/**/*.md'))):
 for n,code in enumerate(re.findall(r'```php\n(.*?)\n```',path.read_text(),re.S),1):
  code=code if code.lstrip().startswith('<?php') else '<?php\n'+code
  with tempfile.NamedTemporaryFile(suffix='.php',mode='w')as f:
   f.write(code);f.flush();command([args.php,'-n','-l',f.name]);snippets+=1
for path in sorted((root/'examples').glob('*.php')):command([args.php,'-n','-l',str(path)])
results.append({'check':'syntax','status':'PASS','snippets':snippets,'exampleFiles':len(list((root/'examples').glob('*.php')))})
if not args.lint_only:
 if not args.extension or not args.autoload:p.error('Runtime checks require --extension and --autoload')
 base=[args.php,'-n','-d','extension='+str(pathlib.Path(args.extension).resolve())]
 env=os.environ|{'ASYNCARA_AUTOLOAD':str(pathlib.Path(args.autoload).resolve())}
 expected={'tasks.php':'{"a":"first","b":"second"}\n','shared-waiter.php':'waiter cancelled\nkept\n','queue.php':'55\n','sqlite.php':'[{"id":1,"title":"hello"}]\n','files.php':'90000\n','streams.php':''.join(f'demo:{n}\n'for n in range(5)),'network.php':'PING\n','processes.php':json.dumps([hashlib.sha256(x.encode()).hexdigest()for x in ['one','two']],separators=(',',':'))+'\n'}
 if args.skip_processpool:del expected['processes.php'];results.append({'check':'processes.php','status':'NOT_EXECUTED','reason':'Bundle missing worker entrypoint; test complete runtime separately'})
 for backend in ['epoll','poll']:
  current=env|{'ASYNCARA_BACKEND':backend}
  for name,want in expected.items():
   start=time.monotonic();r=command(base+['examples/'+name],current)
   if r.stdout!=want or r.stderr:raise RuntimeError(f'{backend}/{name}: unexpected output {r.stdout!r} {r.stderr!r}')
   results.append({'check':name,'backend':backend,'status':'PASS','seconds':time.monotonic()-start})
  server=subprocess.Popen(base+['examples/server.php'],cwd=root,env=current|{'ASYNCARA_LISTEN':'127.0.0.1:0'},stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True)
  try:
   selector=selectors.DefaultSelector();selector.register(server.stdout,selectors.EVENT_READ)
   if not selector.select(10):raise RuntimeError('Server did not become ready')
   line=server.stdout.readline().strip();selector.close()
   if not line.startswith('LISTEN '):raise RuntimeError('Server startup: '+line)
   url='http://'+line[7:]
   def request(path,data=None):
    req=urllib.request.Request(url+path,data=data,headers={'Content-Type':'application/json'})
    try:
     with urllib.request.urlopen(req,timeout=10)as response:return response.status,response.read()
    except urllib.error.HTTPError as response:return response.code,response.read()
   assert request('/status')==(200,b'{"status":"OK"}')
   assert request('/data',b'{"a":1}')==(201,b'{"received":{"a":1}}')
   assert request('/data',b'{bad')[0]==400
   assert request('/work/1?delay_ms=-1')[0]==400
   assert request('/missing')[0]==404
   for http_backend in ['curl-multi','hyper-prototype']:
    r=command(base+['examples/http.php'],current|{'ASYNCARA_DEMO_URL':url,'ASYNCARA_HTTP_BACKEND':http_backend})
    values=[json.loads(line)for line in r.stdout.splitlines()]
    assert sorted(x['body']['id']for x in values)==['1','2','3','4']
    assert all(x['body']['delay_ms']==10 for x in values)
    results.append({'check':'http.php + server.php','backend':backend,'httpBackend':http_backend,'status':'PASS','responses':4})
   results.append({'check':'server routes/json/errors','backend':backend,'status':'PASS','requests':5})
  finally:
   # This script owns only this child; never signals an unrelated service.
   if server.poll()is None:server.terminate()
   try:out,err=server.communicate(timeout=10)
   except subprocess.TimeoutExpired:server.kill();out,err=server.communicate();raise RuntimeError('Example server failed graceful termination')
   if err:raise RuntimeError('Server stderr: '+err)
report={'schema':1,'php':command([args.php,'-n','-r','echo PHP_VERSION;']).stdout,'results':results}
if args.extension:report['extensionSha256']=hashlib.sha256(pathlib.Path(args.extension).read_bytes()).hexdigest()
if args.output:
 target=pathlib.Path(args.output);target.parent.mkdir(parents=True,exist_ok=True)
 with target.open('x')as f:json.dump(report,f,indent=2);f.write('\n')
print(json.dumps(report,indent=2))
