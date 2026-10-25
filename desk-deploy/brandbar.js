/* 3G's Apparel Group brand bar: a gold-on-purple strip under the header that slides left to right.
   Pauses on hover, and stays still for people who turn off motion in their system settings. */
(function(){
  var items = [
    ["3G'S APPAREL GROUP", 1], ['Excellence Is A Habit'], ['Built By Faith'], ['Urban Canvas Co.', 1],
    ['Kings Don’t Quit'], ['Say What You Want Custom Tees', 1], ['Walk Like Royalty'],
    ['Grahams Global Fundraising Group', 1], ['Progress Over Perfection'], ['Discipline Wins'],
    ['Helping People Help Themselves'], ['Excellence Proven Since 1/6/1971']
  ];
  function run(){
    if (document.getElementById('g3bar')) return;
    var header = document.querySelector('header'); if (!header) return;
    var one = items.map(function(x){ return '<span class="' + (x[1] ? 'g3b-strong' : '') + '">' + x[0] + '</span><img src="brand/3gs-mark.webp" alt="" aria-hidden="true">'; }).join('');
    var bar = document.createElement('div');
    bar.id = 'g3bar'; bar.setAttribute('role', 'marquee'); bar.setAttribute('aria-label', "3G's Apparel Group: Excellence Is A Habit, Built By Faith");
    bar.innerHTML = '<style>' +
      '#g3bar{position:relative;overflow:hidden;background:linear-gradient(90deg,#170822,#2A1140 30%,#3d1a5c 50%,#2A1140 70%,#170822);border-top:1px solid rgba(201,162,78,.55);border-bottom:1px solid rgba(201,162,78,.55);height:38px;display:flex;align-items:center}' +
      '#g3bar::before,#g3bar::after{content:"";position:absolute;top:0;bottom:0;width:60px;z-index:2;pointer-events:none}' +
      '#g3bar::before{left:0;background:linear-gradient(90deg,#170822,transparent)}#g3bar::after{right:0;background:linear-gradient(-90deg,#170822,transparent)}' +
      '.g3b-track{display:flex;align-items:center;white-space:nowrap;width:max-content;animation:g3slide 60s linear infinite}' +
      '#g3bar:hover .g3b-track{animation-play-state:paused}' +
      '.g3b-track span{font:600 13px/1 Georgia,"Times New Roman",serif;letter-spacing:.08em;text-transform:uppercase;color:#E9D8B0;padding:0 14px}' +
      '.g3b-track span.g3b-strong{color:#E0BE6A;font-weight:800}' +
      '.g3b-track img{width:24px;height:24px;object-fit:contain;filter:drop-shadow(0 1px 2px rgba(0,0,0,.5))}' +
      '@keyframes g3slide{from{transform:translateX(-50%)}to{transform:translateX(0)}}' +
      '@media (prefers-reduced-motion:reduce){.g3b-track{animation:none}}' +
      '</style><div class="g3b-track">' + one + one + '</div>';
    header.parentNode.insertBefore(bar, header.nextSibling);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
  setTimeout(run, 1500);
})();

/* Market ticker: turns the CRM's .crm-ticker strip into a right-to-left scrolling ticker.
   Re-applies whenever the CRM re-renders. Pauses on hover; still for reduced-motion users. */
(function(){
  var css = '.crm-ticker.tt-mv{display:block!important;flex-wrap:nowrap!important;overflow:hidden;position:relative;white-space:nowrap}' +
    '.crm-ticker.tt-mv::before,.crm-ticker.tt-mv::after{content:"";position:absolute;top:0;bottom:0;width:40px;z-index:2;pointer-events:none}' +
    '.crm-ticker.tt-mv::before{left:0;background:linear-gradient(90deg,#170822,transparent)}' +
    '.crm-ticker.tt-mv::after{right:0;background:linear-gradient(-90deg,#170822,transparent)}' +
    '.tt-tk{display:inline-flex;align-items:center;width:max-content;animation:ttTick 40s linear infinite;will-change:transform}' +
    '.tt-tk>*{margin-right:30px;flex:none;white-space:nowrap}' +
    '.crm-ticker.tt-mv:hover .tt-tk{animation-play-state:paused}' +
    '@keyframes ttTick{from{transform:translateX(0)}to{transform:translateX(-50%)}}' +
    '@media (prefers-reduced-motion:reduce){.tt-tk{animation:none}}';
  function addCss(){ if (document.getElementById('tt-tk-css')) return; var st = document.createElement('style'); st.id = 'tt-tk-css'; st.textContent = css; (document.head || document.documentElement).appendChild(st); }
  function wrap(el){
    if (el.querySelector('.tt-tk')) return;
    var kids = Array.prototype.slice.call(el.childNodes).filter(function(n){ return n.nodeType === 1 || (n.nodeType === 3 && n.textContent.trim()); });
    if (!kids.length) return;
    var track = document.createElement('div'); track.className = 'tt-tk';
    kids.forEach(function(n){ track.appendChild(n.nodeType === 3 ? (function(){ var s = document.createElement('span'); s.textContent = n.textContent; return s; })() : n); });
    el.textContent = ''; el.appendChild(track); el.classList.add('tt-mv');
    var orig = Array.prototype.slice.call(track.children), n = 0;
    function clone(c){ var k = c.cloneNode(true); k.setAttribute('aria-hidden', 'true'); return k; }
    while (track.scrollWidth < el.clientWidth && n++ < 8) orig.forEach(function(c){ track.appendChild(clone(c)); });
    Array.prototype.slice.call(track.children).forEach(function(c){ track.appendChild(clone(c)); });
    requestAnimationFrame(function(){ var half = track.scrollWidth / 2; if (half > 0) track.style.animationDuration = Math.max(20, Math.round(half / 55)) + 's'; });
  }
  function scan(){ addCss(); var els = document.querySelectorAll('.crm-ticker'); for (var i = 0; i < els.length; i++) wrap(els[i]); }
  function start(){ scan(); if (window.MutationObserver) new MutationObserver(function(){ scan(); }).observe(document.body, {childList: true, subtree: true}); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();

/* 3G's Command Center theme layer: the same 10 themes, dark screen option, cinematic backdrops,
   living color glow, and the approved 3G's crest that the Layout C preview uses.
   Lives here so the desk page itself does not need to be rewritten. */
(function(){
  var TH = [
    ['ggfg-navy','GGFG Navy','#0D1F2D','#F9F5EF','#14304A','#24435F','#A9B8C6','#D4AF37','#1A3A5C','crown'],
    ['cream-classic','Cream Classic','#F9F5EF','#0D1F2D','#FFFFFF','#D9D2C3','#5B6B7A','#1A3A5C','#D4AF37','crown'],
    ['midnight-gold','Midnight Gold','#070B12','#F5E9C8','#101826','#2A2415','#B8A77A','#D4AF37','#2A2415','torch'],
    ['emerald','Emerald','#0B1F18','#EAF6EF','#11302A','#1F4A3E','#9CC7B5','#2E7D5E','#0B3B2B','crown'],
    ['royal-purple','Royal Purple','#1A1027','#F4EEFA','#241838','#3B2A5A','#B7A6D0','#B794F4','#3B1F6B','scepter'],
    ['ember','Ember','#1C100C','#FBEDE4','#2A1812','#4A2A1E','#D6A891','#E0803A','#6B2A12','torch'],
    ['ocean','Ocean','#0A1A2A','#E6F3FA','#12283D','#1F3F5C','#93B8CF','#38BDF8','#0B3A5C','crown'],
    ['forest','Forest','#14190F','#EEF2E6','#1D2517','#34412A','#B2BE9E','#A3B86B','#2E3D1C','crown'],
    ['slate-mono','Slate Mono','#16181D','#F2F3F5','#1F222A','#353A46','#9AA1AE','#E5E7EB','#2A2F3A','crown'],
    ['rose-gold','Rose Gold','#1E1416','#FBEFEF','#2A1C20','#4A3038','#CFAEB3','#E0A99A','#5A2E38','scepter']
  ];
  var IMG = {crown: 'brand/bg-crown.jpg', torch: 'brand/bg-torch.jpg', scepter: 'brand/bg-scepter.jpg'};
  var CREST = 'brand/3gs-crest-v2.png';
  function art(acc, sec){
    return 'radial-gradient(38% 34% at 18% 22%, color-mix(in srgb, ' + acc + ' 45%, transparent), transparent 70%), ' +
      'radial-gradient(34% 30% at 82% 74%, color-mix(in srgb, ' + sec + ' 80%, transparent), transparent 72%), ' +
      'linear-gradient(160deg, transparent 0 60%, color-mix(in srgb, ' + acc + ' 14%, transparent) 60% 61%, transparent 61%)';
  }
  function run(){
    try {
      if (document.getElementById('ttThemeCss')) return;
      var css = '';
      TH.forEach(function(t){
        css += 'html[data-theme="' + t[0] + '"]{--bg:' + t[2] + ';--ink:' + t[3] + ';--card:' + t[4] + ';--line:' + t[5] + ';--mute:' + t[6] + ';--gold:' + t[7] + ';--mark:' + t[7] + ';--gold-ink:' + t[7] + ';--art:' + art(t[7], t[8]) + ';}';
        css += 'html[data-theme="' + t[0] + '"] #cinema{background:linear-gradient(180deg,color-mix(in srgb,var(--bg) 35%,transparent),var(--bg) 92%),url("' + IMG[t[9]] + '") center top / cover no-repeat}';
      });
      css += 'html{background:var(--bg)}' +
        'body{background:transparent!important;color:var(--ink)}' +
        'header{background:var(--card)!important;color:var(--ink)!important;border-bottom:2px solid var(--gold)!important}' +
        'header small{color:var(--mute)!important}' +
        'nav{background:var(--card)!important;border-bottom:1px solid var(--line)!important}' +
        'nav button{background:var(--card)!important;color:var(--ink)!important;border-color:var(--line)!important}' +
        'nav button[aria-pressed=true]{background:var(--gold)!important;color:var(--bg)!important;border-color:var(--gold)!important}' +
        '.btn.ghost{background:var(--card);color:var(--ink)}' +
        '.stlm{background:var(--card);border-color:var(--line)}.stlm a{color:var(--ink)}.stlm a:hover{background:var(--line)}' +
        '.card{background:var(--card)}' +
        'input,textarea,select.f{background:var(--card);color:var(--ink)}' +
        '#cinema{position:fixed;inset:0;width:100%;height:100%;z-index:-1;pointer-events:none}' +
        'body::after{content:"";position:fixed;inset:-20%;z-index:-1;pointer-events:none;opacity:.85;background:var(--art);transform-origin:50% 100%;transform:perspective(900px) rotateX(18deg) scale(1.15);animation:ttDrift 18s ease-in-out infinite alternate}' +
        '@keyframes ttDrift{from{transform:perspective(900px) rotateX(18deg) translate3d(-2%,-1%,0) scale(1.15)}to{transform:perspective(900px) rotateX(14deg) translate3d(2%,1%,0) scale(1.2)}}' +
        'body.dark-screen #cinema{filter:brightness(.55) saturate(1.1)}body.dark-screen::after{opacity:.5}' +
        '#ttThemeUI{display:inline-flex;gap:8px;align-items:center;flex-wrap:wrap;font:600 13px system-ui}' +
        '#ttThemeUI select{font:600 13px system-ui;border-radius:999px;padding:6px 10px;border:1px solid var(--line);background:var(--card);color:var(--ink);max-width:170px}' +
        '#ttThemeUI label{display:flex;gap:4px;align-items:center;cursor:pointer}' +
        '@media (prefers-reduced-motion:reduce){body::after{animation:none!important}}';
      var st = document.createElement('style'); st.id = 'ttThemeCss'; st.textContent = css; document.head.appendChild(st);

      if (!document.getElementById('cinema')) {
        var c = document.createElement('div'); c.id = 'cinema'; c.setAttribute('aria-hidden', 'true'); document.body.insertBefore(c, document.body.firstChild);
      }

      // Crest in the header and on the login screen
      document.querySelectorAll('img.g3-logo').forEach(function(im){ im.src = CREST; im.style.objectFit = 'contain'; im.style.background = 'transparent'; });
      var loginImg = document.querySelector('#login img'); if (loginImg) { loginImg.src = CREST; loginImg.style.objectFit = 'contain'; }

      // Theme dropdown + Dark screen, placed in the header (or on the login screen before login)
      var host = document.querySelector('header') || document.getElementById('login');
      var anchor = document.getElementById('out');
      var wrap = document.createElement('span'); wrap.id = 'ttThemeUI';
      wrap.innerHTML = '<select id="ttTheme" aria-label="Theme">' + TH.map(function(t){ return '<option value="' + t[0] + '">' + t[1] + '</option>'; }).join('') + '</select>' +
        '<label><input type="checkbox" id="ttDark"> Dark screen</label>';
      if (host) { if (anchor && anchor.parentNode === host) host.insertBefore(wrap, anchor); else host.appendChild(wrap); }

      var sel = document.getElementById('ttTheme'), dk = document.getElementById('ttDark');
      function setTheme(id){ if (!TH.some(function(t){ return t[0] === id; })) id = 'ggfg-navy'; document.documentElement.setAttribute('data-theme', id); sel.value = id; try { localStorage.setItem('cc-theme', id); } catch (e) {} }
      function setDark(on){ dk.checked = on; document.body.classList.toggle('dark-screen', on); try { localStorage.setItem('cc-dark', on ? '1' : '0'); } catch (e) {} }
      var saved = null, savedDark = false; try { saved = localStorage.getItem('cc-theme'); savedDark = localStorage.getItem('cc-dark') === '1'; } catch (e) {}
      setTheme(saved || 'ggfg-navy');
      setDark(savedDark);
      sel.addEventListener('change', function(){ setTheme(sel.value); });
      dk.addEventListener('change', function(){ setDark(dk.checked); });
    } catch (err) { if (window.console) console.warn('Command Center theme layer:', err); }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
  setTimeout(run, 1600);
})();
