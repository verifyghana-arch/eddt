'use strict';
(() => {
 const form=document.querySelector('#letter-run-form');if(!form)return;
 form.addEventListener('submit',async event=>{
  event.preventDefault();const button=form.querySelector('button'),progress=document.querySelector('#letter-progress');button.disabled=true;
  try{let result;do{const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});if(!response.ok)throw Error('Generation stopped. Refresh this page and resume after reviewing the error.');result=await response.json();progress.textContent=`${result.completed} completed, ${result.pending} pending, ${result.skipped} skipped, ${result.failed} failed.`;if(result.failed){progress.textContent+=' Review batch records and retry failed letters.';break;}await new Promise(r=>setTimeout(r,100));}while(result.pending);if(result.status==='completed'){window.location.reload();}}
  catch(error){progress.textContent=error.message;}finally{button.disabled=false;button.textContent='Generate / resume letters';delete button.dataset.label;}
 });
})();
