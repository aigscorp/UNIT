function check_saveDefectModel() {
  // let msg = document.getElementById('message');
  // msg.style.display = 'block';
  let msg = document.getElementById('exampleModal');
  msg.style.display = "block";
  msg.style.paddingRight = "15px";
  msg.removeAttribute('aria-hidden');
  msg.setAttribute('aria-modal', true);
  msg.classList.add('show');
//<div class="modal fade show" id="exampleModal" tabindex="-1"
// aria-labelledby="exampleModalLabel" style="padding-right: 15px; display: block;" aria-modal="true">

  // msg.addEventListener('click', (ev)=>{
  //   console.log(ev.target);
  //
  // });
// msg.onClick();
  return false;
}
window.onload = function () {
  console.log('Loaded');
};