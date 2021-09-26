
function test_edit() {
  window.onload = function(){
    let result = false;
    let frm = document.getElementById('listform');

    frm.addEventListener("click", (ev)=>{
      // console.log('target ', ev.target);
      let item = ev.target;
      if(item.classList.contains('fa-edit')){
        let parent = item.parentElement;
        console.log(parent.id);
        let edit = document.getElementById(parent.id);
        let attr = edit.getAttribute('data-disabled');
        if(attr == false) result = true;

      }
    });
  };
}

function modal_message(text) {
  $('.modal-body').text(text);
  $('#msg').click();
}
/*
Магазин Обувщик на Малыгина есть в наличии дам в рассрочку цена 20 руб. дм. тел: 79888888888
*/
function check_suppliersend() {
  let res = false;
  let txt_area = document.getElementById('supplieranswer');


  if(txt_area.value != '' && txt_area.getAttribute('data-check') == 'true'){
    res = true;
  }else{
    modal_message('Введите сообщение для отправки и отметьте наименование товара');
  }
  return res;
}

function check_providersend() {
  let q = document.querySelectorAll("input[type=number]");
  let res = false;
  q.forEach((item)=>{
    if(item.value != "" && item.value != 0) res = true;
  });
  if(res == false){
    modal_message("Не указано планируемое количество, для заказа");
  }else {
    $('button.btn.btn-secondary').removeClass('btn-secondary').addClass('btn-info');
    $('#exampleModalLabel').text("Инфо");
    modal_message("Заказ отправлен поставщикам");
  }
  return res;
}

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

function check_saveSizeCount() {
  // let sizes = document.querySelectorAll('input[type=number]');
  let validate_size = false;
  // let validate_count = true;
  // let validate_input = true;
  let td = document.querySelectorAll('.model-master>td:nth-child(2n)');

  let arr = Array.from(td);

  for(let i = 0; i < arr.length/2; i++){
    let a = arr[2*i].children[0].value;
    let b = arr[2*i+1].children[0].innerText;
    let done = Number(a);
    if(isNaN(done) == true || (done % 1 !== 0) == true){
      $('.modal-body').text("Неверно указано количество, только целые значения");// .text(txt);
      $('#msg').click();
      return false;
    }
    if(done != 0) validate_size = true;

    let ost = Number(b);
    if(done > ost){
      $('.modal-body').text("Количество выполненых работ больше остатка");// .text(txt);
      $('#msg').click();
      return false;
    }
  }

  if(validate_size == false){
    $('.modal-body').text("Не указано количество выполненых работ.");// .text(txt);
    $('#msg').click();
  }
  return validate_size;
}

function check_saveModel(){
  let res = false;
  let modelName = document.getElementById('modelName');
  let arr = [];
  if(modelName.value == ""){
    arr.push("Отсутствует наименование модели");
  }
  let sizes = document.querySelectorAll('input[type=number]');
  let validate_size = false;
  sizes.forEach((elem)=>{
    if(elem.value != "" && elem.value != undefined) validate_size = true;
  });
  if(validate_size == false){
    arr.push("Не указано количество размеров модели");
  }

  let comment = document.getElementById('editcomment');
  let material = document.getElementById('editmaterial');
  // console.log('comment = ',comment);
  if(comment == null){
    arr.push("Отсутствует список работ для этой модели");
  }
  if(material == null){
    arr.push("Отсутствуют комплектующие модели");
  }

  if(arr.length == 0) res = true;

  if(res == false){
    let txt = "";
    arr.forEach((item)=>{
      txt += "<p>" + item + "</p>";
    });
    $('.modal-body').html(txt);// .text(txt);
    $('#msg').click();
  }
  return res;
}
$(document).ready(function () {

});
// window.onload = function () {
//
// };