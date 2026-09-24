'use strict';
document.querySelectorAll('.correspondence-actions').forEach(form=>{
 form.addEventListener('submit',()=>{const input=form.querySelector('[name=request_key]');setTimeout(()=>{input.value=crypto.randomUUID();},500);});
});
