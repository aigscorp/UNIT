let elem = null;
window.onload = function () {
  console.log('Load control production');
  let table = document.querySelectorAll('[data-model="model"]')[0];
  let id = table.id;
  let model_id = id.replace('model_', '');
  table.addEventListener('click', (ev)=>{
    console.log(ev.target);
    elem = ev.target;
  });
};