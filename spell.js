window.TTSPELL=function(){
  if(!window.TTWORDS) return JSON.stringify({err:'wordlist not loaded'});
  function textOf(root){
    var sk={SCRIPT:1,STYLE:1,NOSCRIPT:1,SVG:1,CODE:1,PRE:1};
    var out=[],w=document.createTreeWalker(root,NodeFilter.SHOW_TEXT,{acceptNode:function(n){
      var p=n.parentElement; if(!p||sk[p.tagName])return NodeFilter.FILTER_REJECT;
      var cs=getComputedStyle(p); if(cs.display==='none'||cs.visibility==='hidden')return NodeFilter.FILTER_REJECT;
      return n.textContent.trim()?NodeFilter.FILTER_ACCEPT:NodeFilter.FILTER_REJECT;}});
    var n; while(n=w.nextNode()) out.push(n.textContent);
    return out.join(' ');
  }
  var t=textOf(document.body);
  var toks=t.split(/[^A-Za-z'’-]+/);
  var seen={},bad=[];
  toks.forEach(function(raw){
    var w=raw.replace(/^['’-]+|['’-]+$/g,'');
    if(w.length<3||w.length>24) return;
    var lw=w.toLowerCase();
    if(seen[lw])return; seen[lw]=1;
    if(/^[A-Z]{2,}$/.test(w))return;                 // acronyms
    if(/[A-Z]/.test(w.slice(1)))return;              // CamelCase / brand marks
    var base=lw.replace(/[’']s$/,'');
    if(TTWORDS.has(lw)||TTWORDS.has(base))return;
    if(base.endsWith('s')&&TTWORDS.has(base.slice(0,-1)))return;
    if(base.endsWith('es')&&TTWORDS.has(base.slice(0,-2)))return;
    if(base.endsWith('ed')&&(TTWORDS.has(base.slice(0,-2))||TTWORDS.has(base.slice(0,-1))))return;
    if(base.endsWith('ing')&&(TTWORDS.has(base.slice(0,-3))||TTWORDS.has(base.slice(0,-3)+'e')))return;
    if(base.indexOf('-')>-1&&base.split('-').every(function(p){return !p||TTWORDS.has(p)}))return;
    bad.push(w);
  });
  return JSON.stringify({words:t.split(/\s+/).length,unknown:bad.length,list:bad.slice(0,90)});
};
