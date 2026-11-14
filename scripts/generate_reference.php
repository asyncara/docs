<?php
declare(strict_types=1);
$root=$argv[1]??'';
if(!is_file($root.'/autoload.php'))throw new RuntimeException('Usage: php scripts/generate_reference.php /path/to/asyncara');
require $root.'/autoload.php';
if(is_file($root.'/vendor/autoload.php'))require $root.'/vendor/autoload.php';
$groups=[
'runtime'=>['Asyncara','Asyncara\\Async','Asyncara\\Runtime','Asyncara\\Future','Asyncara\\Task','Asyncara\\Awaitable','Asyncara\\Timer','Asyncara\\TaskGroup','Asyncara\\CancellationSource','Asyncara\\CancellationToken','Asyncara\\WaitResult','Asyncara\\TaskResult','Asyncara\\Context','Asyncara\\Trace','Asyncara\\Hook','Asyncara\\HookReport'],
'concurrency'=>['Asyncara\\Concurrency\\Queue','Asyncara\\Concurrency\\Lock','Asyncara\\Concurrency\\Semaphore','Asyncara\\Concurrency\\Event','Asyncara\\Stream\\AsyncStream','Asyncara\\Stream\\ReadableStream','Asyncara\\Stream\\Emitter'],
'server'=>['Asyncara\\App','Asyncara\\Server\\Request','Asyncara\\Server\\Response','Asyncara\\Server\\StreamResponse','Asyncara\\Server\\Route','Asyncara\\Server\\RequestBodyStream'],
'http'=>['Asyncara\\HTTP\\HttpClient','Asyncara\\HTTP\\Response','Asyncara\\HTTP\\ResponseHead','Asyncara\\HTTP\\ResponseStream','Asyncara\\HTTP\\Psr\\Client'],
'databases'=>['Asyncara\\Database\\Database','Asyncara\\SQLite\\Connection','Asyncara\\SQLite\\Statement','Asyncara\\PostgreSQL\\Connection','Asyncara\\PostgreSQL\\Statement','Asyncara\\MySQL\\Connection','Asyncara\\MySQL\\Statement','Asyncara\\Redis\\Connection','Asyncara\\Pool\\ConnectionPool'],
'io'=>['Asyncara\\IO\\Files','Asyncara\\IO\\File','Asyncara\\IO\\TcpStream','Asyncara\\IO\\TcpServer','Asyncara\\IO\\UdpSocket','Asyncara\\IO\\Dns','Asyncara\\WebSocket\\Client','Asyncara\\WebSocket\\Server','Asyncara\\WebSocket\\Connection','Asyncara\\WebSocket\\Message','Asyncara\\Pool\\ProcessPool'],
'errors'=>array_map(fn($n)=>'Asyncara\\Exception\\'.$n,['OperationException','CancelledException','TimeoutException','WorkerException','FilesystemException','QueueClosedException','CapacityException','DeadlockException'])+[],
];
$groups['errors']=array_merge($groups['errors'],['Asyncara\\Redis\\ServerException','Asyncara\\WebSocket\\ProtocolException','Asyncara\\Server\\RequestBodyException','Asyncara\\HTTP\\Psr\\NetworkException','Asyncara\\HTTP\\Psr\\RequestException']);
$labels=['runtime'=>['Runtime','التشغيل'],'concurrency'=>['Concurrency and streams','التزامن والتدفقات'],'server'=>['Native Server','الخادم'],'http'=>['HTTP client','عميل HTTP'],'databases'=>['Databases and pools','قواعد البيانات والمجمعات'],'io'=>['Files, networking and processes','الملفات والشبكات والعمليات'],'errors'=>['Errors','الأخطاء']];
$guides=['runtime'=>'runtime/tasks','concurrency'=>'runtime/synchronization','server'=>'server/routing','http'=>'http/client','databases'=>'data/transactions-pools','io'=>'io/filesystem','errors'=>'runtime/cancellation'];
$inventory=[];
foreach($groups as$group=>$classes){
 $body='';
 foreach($classes as$class){
  $r=new ReflectionClass($class);$body.="\n## `{$class}`\n\n";
  if($parent=$r->getParentClass())$body.="Extends `{$parent->getName()}`.\n\n";
  $lines=[];
  foreach($r->getConstants()as$name=>$value)if(is_scalar($value))$lines[]='const '.$name.' = '.var_export($value,true).';';
  foreach($r->getProperties(ReflectionProperty::IS_PUBLIC)as$p){if(str_contains($p->getDocComment()?:'','@internal'))continue;$lines[]=($p->isReadOnly()?'readonly ':'').($p->hasType()?$p->getType().' ':'').'$'.$p->getName().';';}
  foreach($r->getMethods(ReflectionMethod::IS_PUBLIC)as$m){
   if($m->getDeclaringClass()->getName()!==$class||str_contains($m->getDocComment()?:'','@internal')||in_array($m->name,['__destruct','__clone','__serialize','__unserialize','__wakeup']))continue;
   $ps=[];foreach($m->getParameters()as$p){$s=($p->hasType()?$p->getType().' ':'').($p->isPassedByReference()?'&':'').($p->isVariadic()?'...':'').'$'.$p->name;if($p->isDefaultValueAvailable()){$v=$p->getDefaultValue();$s.=' = '.(is_object($v)?'new '.get_class($v).'()':($v===[]?'[]':var_export($v,true)));}$ps[]=$s;}
   $lines[]=($m->isStatic()?'static ':'').$m->name.'('.implode(', ',$ps).')'.($m->hasReturnType()?': '.$m->getReturnType():'');
   $inventory[$class][]=$m->name;
  }
  $body.=$lines?"```text\n".implode("\n",$lines)."\n```\n":"Standard inherited exception behavior.\n";
 }
 foreach(['en','ar']as$locale){$dir=dirname(__DIR__).($locale==='en'?'/docs':'/i18n/ar/docusaurus-plugin-content-docs/current').'/reference';if(!is_dir($dir))mkdir($dir,0777,true);$title=$labels[$group][$locale==='en'?0:1];$intro=$locale==='en'?"Signatures generated from the installed 0.10.0 runtime. Read the [usage guide](../{$guides[$group]}.md) for ownership, error and backend semantics. Inherited methods are listed under their declaring class. Low-level asyncara_* transport functions and internal scheduler machinery are not application APIs.":"التوقيعات مستخرجة من runtime الإصدار0.10.0. راجع [دليل الاستخدام](../{$guides[$group]}.md) للملكية والأخطاء والتنفيذ. الدوال الموروثة تحت صنفها الأصلي، ودوال asyncara_* وآليات Scheduler الداخلية ليست واجهة تطبيقات.";file_put_contents($dir.'/'.$group.'.md',"---\ntitle: \"$title\"\n---\n\n$intro\n$body");}
}
file_put_contents(dirname(__DIR__).'/scripts/api-inventory.json',json_encode($inventory,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
