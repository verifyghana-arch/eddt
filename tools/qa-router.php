<?php
// Loopback-only development router. Never configure this as a production router.
if(!in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)){http_response_code(403);exit;}
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$root=dirname(__DIR__);
if($path==='/qa/pdf'){
 $files=['batch-demand'=>$root.'/var/qa/batch-demand-test.pdf','batch-invitation'=>$root.'/var/qa/batch-invitation-test.pdf','demand'=>$root.'/samples/EDDT Demand Notice.pdf','invitation'=>$root.'/samples/EDDT Invitation Letter.pdf','agreement'=>$root.'/EDDT-NdiGIS_System_Purchase_Agreement (1).pdf','generated-demand'=>$root.'/var/qa/demand.pdf','generated-invitation'=>$root.'/var/qa/invitation.pdf','generated-receipt'=>$root.'/var/qa/receipt.pdf'];
 $file=$files[$_GET['file']??'']??null;if(!$file||!is_file($file)){http_response_code(404);exit;}header('Content-Type: application/pdf');readfile($file);return;
}
if($path==='/qa/view'){header('Content-Type: text/html');echo '<!doctype html><html><head><title>PDF layout review</title><style>body{margin:0;background:#d6dad7;font-family:Arial}canvas{display:block;margin:20px auto;box-shadow:0 2px 15px #0003;max-width:100%;height:auto}pre{white-space:pre-wrap;background:white;margin:20px;padding:20px}</style></head><body><div id="pages"></div><pre id="text"></pre><script type="module" src="/qa/view.js"></script></body></html>';return;}
if($path==='/qa/view.js'){header('Content-Type: application/javascript');echo <<<'JS'
import * as pdfjs from '/qa/pdf.mjs';
pdfjs.GlobalWorkerOptions.workerSrc='/qa/pdf.worker.mjs';
const name=new URLSearchParams(location.search).get('file')||'demand';
const pdf=await pdfjs.getDocument('/qa/pdf?file='+encodeURIComponent(name)).promise;
document.title=name+' · '+pdf.numPages+' pages';
const texts=[];
for(let n=1;n<=pdf.numPages;n++){const page=await pdf.getPage(n);const viewport=page.getViewport({scale:1.25});const canvas=document.createElement('canvas');canvas.width=viewport.width;canvas.height=viewport.height;document.querySelector('#pages').append(canvas);await page.render({canvasContext:canvas.getContext('2d'),viewport}).promise;texts.push((await page.getTextContent()).items.map(i=>i.str).join(' '));}
document.querySelector('#text').textContent=texts.join('\n\nPAGE BREAK\n\n');
JS;return;}
if(in_array($path,['/qa/pdf.mjs','/qa/pdf.worker.mjs'],true)){header('Content-Type: application/javascript');readfile($root.'/var/qa/'.basename($path));return;}
if($path==='/qa/health'){header('Content-Type: application/json');echo json_encode(['gd'=>extension_loaded('gd'),'zip'=>extension_loaded('zip')]);return;}
if(str_starts_with($path,'/assets/'))return false;
require $root.'/public/index.php';
