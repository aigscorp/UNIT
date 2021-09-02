
function check_saveDefectModel() {
  let res = false;
  let elems = $('.form-check-input');
  elems.each(function(index){
    let item = $(this);
    if(item[0].checked == true) res = true;
  })
  if(res == false){
    $('.modal-body').text('Не выбрано описание брака!');
    $('#msg').click();
  } 
  return res;
}
window.onload = function () {
  // console.log('Loaded');
};