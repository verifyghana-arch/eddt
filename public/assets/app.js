'use strict';
(() => {
 const body=document.body,toggle=document.querySelector('.mobile-toggle'),scrim=document.querySelector('[data-nav-close]');
 const setNav=open=>{body.classList.toggle('nav-open',open);toggle?.setAttribute('aria-expanded',String(open));if(scrim)scrim.hidden=!open;};
 toggle?.addEventListener('click',()=>setNav(!body.classList.contains('nav-open')));scrim?.addEventListener('click',()=>setNav(false));
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&body.classList.contains('nav-open')){setNav(false);toggle?.focus();}});
 matchMedia('(min-width:761px)').addEventListener('change',event=>{if(event.matches)setNav(false);});
 document.querySelectorAll('[data-back]').forEach(el=>el.addEventListener('click',()=>history.back()));
 document.querySelectorAll('form[method="post"]').forEach(form=>form.addEventListener('submit',()=>{if(!form.checkValidity()||form.classList.contains('correspondence-actions'))return;form.querySelectorAll('button[type="submit"],button:not([type])').forEach(button=>{button.disabled=true;button.dataset.label=button.textContent;button.textContent='Working…';});}));
 window.addEventListener('pageshow',()=>document.querySelectorAll('button[data-label]').forEach(button=>{button.disabled=false;button.textContent=button.dataset.label;}));
})();
