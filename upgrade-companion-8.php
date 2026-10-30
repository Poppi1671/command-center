<?php
/* 3G's Companion upgrade 8 (Oct 30, 2026): big living-face dock on every Command Center page (narrator, talk, voice, modes, chart). Open once with ?go=3df5c74b7357edb4. */
if (($_GET['go'] ?? '') !== '3df5c74b7357edb4') { http_response_code(403); exit('Not allowed.'); }
header('Content-Type: text/plain; charset=utf-8');
$root = (basename(__DIR__) === '_install') ? dirname(__DIR__) : __DIR__; $tag = '.bak-20261030-dock2'; $out = ''; $bad = 0;
$P = json_decode('[["desk/navgroups.js", "s.src = \'/desk/companion-dock.js?v=1\';", "s.src = \'/desk/companion-dock.js?v=2\';", "dock version bump"]]', true); $files = [];
$DOCK = <<<'DOCKFILE'
/* 3G's Companion dock v2 (Oct 30, 2026): a big living-face companion on every Command Center page.
   Gold button bottom-right opens a tall panel: large animated face (mouth moves with the voice, moods),
   mode chips (Teacher, Narrator, Trading mentor, ...), Talk mic, Voice mode, sources, stock chart, Studio link. */
(function(){
var K='';function rk(){try{K=sessionStorage.getItem('ttDesk')||''}catch(e){}return K}
function $(i){return document.getElementById(i)}
function call(a,b){return fetch('/api/companion.php?a='+a,{method:'POST',headers:{'Content-Type':'application/json','X-TT-Key':K},body:JSON.stringify(b||{})}).then(function(r){return r.json()})}
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
var MOODTXT={happy:'😊 happy',calm:'🙂 calm',thoughtful:'🤔 thinking',concerned:'😕 concerned',laugh:'😂 laughing'};
function init(){
if($('ccDock'))return;
['/desk/host-talk.js?v=2','/desk/companion-face.js?v=1'].forEach(function(u){var s=document.createElement('script');s.src=u;document.head.appendChild(s)});
var css=document.createElement('style');css.textContent=
'#ccDock{position:fixed;right:16px;bottom:16px;z-index:9998;font-family:system-ui,sans-serif}'+
'#ccDockBtn{width:84px;height:84px;border-radius:50%;border:3px solid #C9A24E;background:#14110D center/cover;color:#C9A24E;font:800 30px system-ui;cursor:pointer;box-shadow:0 0 18px rgba(201,162,78,.7)}'+
'#ccDockP{display:none;position:fixed;right:16px;bottom:16px;width:520px;max-width:calc(100vw - 32px);height:min(880px,calc(100vh - 32px));background:#1d1812;color:#F5EBDA;border:2px solid #C9A24E;border-radius:18px;flex-direction:column;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.6)}#ccDockP.on{display:flex}'+
'#ccDockH{display:flex;gap:8px;align-items:center;padding:10px 12px;border-bottom:1px solid #3a3022}#ccDockH select{flex:1;min-width:0;background:#0f0c08;color:#F5EBDA;border:1px solid #4a3d2a;border-radius:8px;padding:8px;font-size:15px}#ccDockH a{color:#C9A24E;font-size:13px;font-weight:700;text-decoration:none}#ccDockX{background:none;border:0;color:#F5EBDA;font-size:22px;cursor:pointer;padding:0 4px}'+
'#ccFace{position:relative;background:radial-gradient(ellipse at center,#2a2218,#14110D);display:flex;flex-direction:column;align-items:center;padding:10px 10px 4px}#ccFace canvas,#ccFace img,#ccFace video{width:min(300px,70vw);height:auto;border-radius:18px;border:3px solid #C9A24E;display:none}#ccMood{font-size:13px;opacity:.85;margin:6px 0 0;min-height:18px}#ccName{font-weight:800;color:#C9A24E;font-size:18px}'+
'#ccModes{display:flex;gap:6px;overflow-x:auto;padding:8px 10px;scrollbar-width:none;border-bottom:1px solid #3a3022}#ccModes button{flex:0 0 auto;background:transparent;border:1px solid #C9A24E;color:#C9A24E;border-radius:999px;padding:5px 11px;font:700 12px system-ui;cursor:pointer}#ccModes button.on{background:#C9A24E;color:#14110D}'+
'#ccChart{display:none;width:calc(100% - 20px);height:120px;margin:8px 10px 0;border-radius:10px;background:#0f0c08;border:1px solid #3a3022}'+
'#ccDockM{flex:1;overflow:auto;padding:10px}#ccDockM .b{margin:6px 0;padding:8px 12px;border-radius:12px;max-width:90%;white-space:pre-wrap;font-size:15px;line-height:1.4}#ccDockM .u{background:#C9A24E;color:#14110D;margin-left:auto;font-weight:600}#ccDockM .c{background:#2a2218;border:1px solid #4a3d2a}#ccDockM .src{font-size:12px;opacity:.8;margin-top:5px}#ccDockM .src a{color:#C9A24E}'+
'#ccDockF{display:flex;gap:6px;padding:8px;border-top:1px solid #3a3022;align-items:center;flex-wrap:wrap}#ccDockF input{flex:1;min-width:140px;background:#0f0c08;color:#F5EBDA;border:1px solid #4a3d2a;border-radius:8px;padding:10px;font-size:16px}#ccDockF button,#ccDockM button{background:#C9A24E;color:#14110D;border:0;border-radius:8px;padding:9px 12px;font-weight:800;cursor:pointer}#ccDockF button.g{background:transparent;border:2px solid #C9A24E;color:#C9A24E}#ccDockM button{font-size:12px;margin-top:6px;display:inline-block}#ccDockF label{font-size:13px;display:flex;align-items:center;gap:4px}'+
'#ccDockN{display:none;gap:6px;padding:6px 10px;align-items:center}#ccDockN select{flex:1;min-width:0;background:#0f0c08;color:#F5EBDA;border:1px solid #4a3d2a;border-radius:8px;padding:6px}#ccDockN button{background:#C9A24E;color:#14110D;border:0;border-radius:8px;padding:7px 12px;font-weight:800;cursor:pointer}'+
'@media(max-width:600px){#ccDockP{right:0;bottom:0;width:100vw;max-width:100vw;height:100vh;border-radius:0}}';
document.head.appendChild(css);
var d=document.createElement('div');d.id='ccDock';d.innerHTML=
'<div id="ccDockP"><div id="ccDockH"><select id="ccDockS"></select><a href="/desk/companion.html">Studio</a><button id="ccDockX" aria-label="Close">×</button></div>'+
'<div id="ccFace"><canvas id="ccCv"></canvas><img id="ccIm" alt=""><video id="ccVd" playsinline></video><div id="ccName"></div><div id="ccMood"></div></div>'+
'<div id="ccModes"></div><canvas id="ccChart"></canvas>'+
'<div id="ccDockM"></div>'+
'<div id="ccDockN"><select id="ccDockT"><option value="markets">Markets</option><option value="economy">Economy</option><option value="ecommerce">Ecommerce</option><option value="apparel">Apparel &amp; POD</option><option value="ai">AI for business</option><option value="fundraise">Fundraising</option><option value="trending">Trending now</option></select><button id="ccDockNB">News</button></div>'+
'<div id="ccDockF"><input id="ccDockI" placeholder="Talk to your companion" maxlength="1500"><button id="ccDockG">Send</button><button id="ccMic" class="g">🎤 Talk</button><label><input id="ccVm" type="checkbox">Voice</label></div></div>'+
'<button id="ccDockBtn" title="Companion" aria-label="Open companion">★</button>';
document.body.appendChild(d);
var cur='',list=[],MODE='companion',FACE=null,curC=null;
function setMood(m){if(!m)return;if(FACE)FACE.setMood(m);$('ccMood').textContent=MOODTXT[m]||''}
function face(img,meta){var cv=$('ccCv'),im=$('ccIm');if(!img){cv.style.display='none';im.style.display='none';return}
 if(window.CompanionFace){if(!FACE)FACE=new CompanionFace(cv);FACE.setImage('data:image/png;base64,'+img,meta||{mx:0.5,my:0.66,mw:0.18}).then(function(ok){if(ok){im.style.display='none';cv.style.display='block';setMood('calm')}})}
 else{im.src='data:image/png;base64,'+img;im.style.display='block'}}
function speak(t,then){call('speak',{id:cur,text:t}).then(function(r){if(!r.ok){if(then)then();return}
 if(FACE&&FACE.img){FACE.speak('audio/mpeg',r.audio,then);return}
 if(window.HostTalk&&curC&&curC.host){HostTalk.play('audio/mpeg',r.audio,then);return}
 var a=new Audio('data:audio/mpeg;base64,'+r.audio);a.onended=function(){if(then)then()};a.play().catch(function(){})})}
function again(){if($('ccVm').checked&&!rec&&!(mrec&&mrec.state==='recording'))$('ccMic').click()}
function bub(cls,t,speakable,src){var x=document.createElement('div');x.className='b '+cls;x.textContent=t;
 if(speakable){var b=document.createElement('button');b.textContent='Hear it';b.onclick=function(){speak(t)};x.appendChild(document.createElement('br'));x.appendChild(b)}
 if(src&&src.length){var s=document.createElement('div');s.className='src';s.textContent='Sources: ';src.slice(0,3).forEach(function(z,i){var a=document.createElement('a');a.href=z.u;a.target='_blank';a.rel='noopener';a.textContent=z.s+(z.t?' · '+z.t:'');s.appendChild(a);if(i<Math.min(src.length,3)-1)s.appendChild(document.createTextNode('  |  '))});x.appendChild(s)}
 $('ccDockM').appendChild(x);$('ccDockM').scrollTop=1e6}
function drawChart(ch){var c=$('ccChart');if(!ch||!ch.series||ch.series.length<2){c.style.display='none';return}c.style.display='block';var W=c.width=c.clientWidth*2,H=c.height=240,x=c.getContext('2d'),s=ch.series,mn=Math.min.apply(null,s),mx=Math.max.apply(null,s),pad=24;x.clearRect(0,0,W,H);var up=s[s.length-1]>=s[0];x.strokeStyle=up?'#4ade80':'#f87171';x.lineWidth=4;x.beginPath();s.forEach(function(v,i){var px=pad+(W-2*pad)*i/(s.length-1),py=H-pad-(H-2*pad)*(v-mn)/((mx-mn)||1);i?x.lineTo(px,py):x.moveTo(px,py)});x.stroke();x.fillStyle='#E0BE6A';x.font='bold 26px system-ui';x.fillText(ch.sym+'  '+ch.last.toFixed(2)+'  '+(ch.chg>=0?'+':'')+ch.chg.toFixed(2)+'%',pad,24);x.fillStyle='#8b7d66';x.font='18px system-ui';x.fillText('60 days, daily close',pad,H-6)}
function pick(id){cur=id;$('ccDockM').innerHTML='';drawChart(null);call('get',{id:id}).then(function(r){if(!r.ok)return;curC=r.c;window.__ccHost=!!r.c.host;var bt=$('ccDockBtn');if(r.img){bt.style.backgroundImage='url(data:image/png;base64,'+r.img+')';bt.textContent=''}else{bt.style.backgroundImage='none';bt.textContent='★'}
 $('ccName').textContent=r.c.name;face(r.img,r.c.face);
 r.c.history.slice(-10).forEach(function(h){bub(h.r==='user'?'u':'c',h.c,h.r!=='user')});
 if(!r.c.history.length)bub('c','Hi, I am '+r.c.name+', an AI. Pick a mode above, or just talk to me.',false);
 $('ccDockN').style.display=r.c.host?'flex':'none';
 if(r.c.history.length&&(Date.now()/1000-(r.c.last||0))>8*3600){call('greet',{id:id}).then(function(g){if(g.ok&&cur===id){bub('c',g.text,true);setMood(g.mood);if($('ccVm').checked)speak(g.text,again)}})}})}
call('status').then(function(s){if(!s.ok)return;if(s.modes){var m=$('ccModes');Object.keys(s.modes).forEach(function(k){var b=document.createElement('button');b.textContent=s.modes[k];b.setAttribute('data-m',k);if(k===MODE)b.className='on';b.onclick=function(){MODE=k;[].forEach.call(m.children,function(z){z.className=z.getAttribute('data-m')===k?'on':''});if(k==='narrator')bub('c','Narrator mode. Paste any text and I will read it to you, or ask me to read a public-domain book.',false);if(k==='teacher')bub('c','Teacher mode. Tell me what you want to learn.',false)};m.appendChild(b)})}
 if(!s.companions.length)return;list=s.companions.slice().sort(function(a,b){return (b.host?1:0)-(a.host?1:0)});list.forEach(function(c){var o=document.createElement('option');o.value=c.id;o.textContent=(c.host?'Host: ':'')+c.name;$('ccDockS').appendChild(o)});
 var want=''; try{want=localStorage.getItem('ccDockPick')||''}catch(e){} var first=list.filter(function(c){return c.id===want})[0]||list[0];$('ccDockS').value=first.id;pick(first.id)});
$('ccDockNB').onclick=function(){var b=$('ccDockNB');b.disabled=true;b.textContent='...';call('hostnews',{topic:$('ccDockT').value}).then(function(r){b.disabled=false;b.textContent='News';bub('c',r.ok?r.text:(r.err||'No news'),!!r.ok);if(r.ok&&$('ccVm').checked)speak(r.text)}).catch(function(){b.disabled=false;b.textContent='News'})};
$('ccDockS').onchange=function(){try{localStorage.setItem('ccDockPick',$('ccDockS').value)}catch(e){}pick($('ccDockS').value)};
$('ccDockBtn').onclick=function(){if(!list.length){location.href='/desk/companion.html';return}$('ccDockP').classList.add('on');$('ccDockBtn').style.display='none'};
$('ccDockX').onclick=function(){$('ccDockP').classList.remove('on');$('ccDockBtn').style.display='block';if(FACE)FACE.stop()};
function send(){var t=$('ccDockI').value.trim();if(!t||!cur)return;$('ccDockI').value='';bub('u',t,false);$('ccDockG').disabled=true;call('chat',{id:cur,text:t,mode:MODE}).then(function(r){$('ccDockG').disabled=false;if(!r.ok){bub('c',r.err||'No answer',false);return}bub('c',r.reply,true,r.sources);setMood(r.mood);drawChart(r.chart);if($('ccVm').checked||MODE==='narrator')speak(r.reply,again)}).catch(function(){$('ccDockG').disabled=false})}
$('ccDockG').onclick=send;$('ccDockI').addEventListener('keydown',function(e){if(e.key==='Enter')send()});
var SR=window.SpeechRecognition||window.webkitSpeechRecognition,rec=null,mrec=null,mchunks=[];
$('ccMic').onclick=function(){if(!cur)return;if(rec||(mrec&&mrec.state==='recording')){if(rec)rec.stop();if(mrec&&mrec.state==='recording')mrec.stop();return}
 if(SR){rec=new SR();rec.lang='en-US';rec.interimResults=false;rec.maxAlternatives=1;$('ccMic').textContent='Listening…';rec.onresult=function(e){$('ccDockI').value=e.results[0][0].transcript};rec.onerror=function(){};rec.onend=function(){rec=null;$('ccMic').textContent='🎤 Talk';if($('ccDockI').value.trim())send()};rec.start();return}
 if(!navigator.mediaDevices||!window.MediaRecorder){bub('c','This browser cannot record. Try Chrome.',false);return}
 navigator.mediaDevices.getUserMedia({audio:true}).then(function(st){mchunks=[];mrec=new MediaRecorder(st);mrec.ondataavailable=function(e){if(e.data.size)mchunks.push(e.data)};mrec.onstop=function(){st.getTracks().forEach(function(t){t.stop()});$('ccMic').textContent='🎤 Talk';var bl=new Blob(mchunks,{type:mrec.mimeType||'audio/webm'});var fr=new FileReader();fr.onload=function(){call('listen',{audio:String(fr.result).split(',')[1],mime:bl.type}).then(function(r){if(r.ok){$('ccDockI').value=r.text;send()}else bub('c',r.err||'Could not hear that',false)})};fr.readAsDataURL(bl)};mrec.start();$('ccMic').textContent='Listening…';setTimeout(function(){if(mrec&&mrec.state==='recording')mrec.stop()},30000)}).catch(function(){bub('c','Microphone is blocked. Allow it in the browser.',false)})};
}
if(rk())init();else{var n=0,t=setInterval(function(){n++;if(rk()){clearInterval(t);init()}else if(n>600)clearInterval(t)},2000)}
})();
DOCKFILE;
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) { if (!is_file($f)) { $out .= "MISSING $p[0]\n"; $bad++; continue; } $files[$f] = file_get_contents($f); } }
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) continue; $n = substr_count($files[$f], $p[1]);
  if ($n !== 1) { $out .= "ANCHOR x$n ($p[3]) in $p[0]\n"; $bad++; continue; }
  $files[$f] = str_replace($p[1], $p[2], $files[$f]); $out .= "ok  $p[3]\n"; }
if ($bad) { echo "STOPPED, nothing changed.\n$out"; exit; }
$dk = $root . '/desk/companion-dock.js'; if (!is_file($dk . $tag)) copy($dk, $dk . $tag); file_put_contents($dk, $DOCK, LOCK_EX); $out .= "ok  desk/companion-dock.js replaced (backup kept)\n";
foreach ($files as $f => $c) { if (!is_file($f . $tag)) copy($f, $f . $tag); file_put_contents($f, $c, LOCK_EX); $out .= "ok  " . str_replace($root . '/', '', $f) . " patched\n"; }
@unlink(__FILE__); echo $out . "\nDONE.\n";
