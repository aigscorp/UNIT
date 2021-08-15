console.log('Контрагенты');

window.onload = function () {
  let btn_agent = document.getElementById('agent');
  console.log(btn_agent);
  var acc = document.getElementsByClassName("accordion");
  var i;

  for (i = 0; i < acc.length; i++) {
      acc[i].addEventListener("click", function() {
          /* Toggle between adding and removing the "active" class,
          to highlight the button that controls the panel */
          this.classList.toggle("active");

          /* Toggle between hiding and showing the active panel */
          var panel = this.nextElementSibling;
          if (panel.style.display === "block") {
              panel.style.display = "none";
          } else {
              panel.style.display = "block";
          }
      });
  }

  let account = document.getElementById('account') || null;
  if(account == null) return;
  account.addEventListener('click', (ev)=>{
    console.log(ev.target);
    let elems = document.querySelectorAll(".accord-insert") || null;
    // console.log('elems = ', elems);
    let cnt = elems.length;
    cnt++;
    let del = "del-elem" + cnt;

    let tpl = `
    <div class="accord-insert">
      <div class="accord-wrap" style="width: 100%; background: #fff;">
        <div class="accord-items">
            <label for="bic${cnt}">БИК</label>
            <input type="text" id="bic${cnt}" name="bic${cnt}"/>
        </div>
        <div class="accord-items">
            <label for="bankname${cnt}">Банк</label>
            <input type="text" id="bankname${cnt}" name="bankname${cnt}"/>
        </div>
        <div class="accord-items">
            <label for="korr${cnt}">Корр. счет</label>
            <input type="text" id="korr${cnt}" name="korr${cnt}"/>
        </div>
        <div class="accord-items">
            <label for="account${cnt}">Расчетный счет</label>
            <input type="text" id="account${cnt}" name="account${cnt}"/>
        </div>
      </div>
      <div style="position: absolute; top: 0; right: 0;" id="${del}">
        <img src="/shoes/public/img/delete.svg" alt="Delete"  style="width: 14px;
            border: 1px solid red; border-radius: 2px; padding: 1px;"/>
      </div>
    </div>`;
    account.insertAdjacentHTML('beforebegin', tpl);
    let ins = document.getElementById(del);
    // console.log('ins:', ins);
    ins.addEventListener('click', (ev)=>{
      // console.log(ev.target);
      ins.parentNode.remove();
    })
  })
};