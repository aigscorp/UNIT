// let elem = null;
let str = {};

let postData = async function(url='', data={}){
  const response = await fetch(url, {
    method: "POST",
    cache: "no-cache",
    headers: {
      'Content-Type': 'application/json'
    },
    redirect: 'follow',
    referrerPolicy: "no-referrer",
    body: JSON.stringify(data)
  });
  return await response.json();
};

window.onload = function () {
  console.log('Load control production');
  let table = document.querySelectorAll('[data-model="model"]')[0];

  table.addEventListener('click', (ev)=>{
    // console.log(ev.target);
    let elem = ev;
    let origin = window.location.origin;
    console.log('origin=', origin);

    let monitor = document.querySelector('.card-text');
    let color = ev.target.style.backgroundColor;
    if(color == "" || color == null){
      monitor.innerHTML = "";
      return;
    }
    let id = table.id;
    let model_id = id.replace('model_', '');
    console.log('model_id = ', model_id);
    // {"jsonrpc": "2.0", "method": "Hello", "params": {"p1": "12345",$p3:"2"}, "id": 1}

    let row = elem.target.parentElement;
    let index = elem.target.cellIndex;
    let name_size = row.cells[0].innerHTML;
    // console.log('name-size=', name_size);

    let arr = name_size.split(",");
    let size = arr[1].trim();

    let th = document.querySelectorAll(`#${table.id} th`);
    let work = th[index].innerHTML;
    // console.log('work=',work);

    let data = {
      "jsonrpc":"2.0",
      "method":"GetModelDefect",
      "params":{
        "model_id":model_id,
        "work":work,
        "size":size
      },
      "id": model_id
    };

    // console.log('data =',data);
    let url = origin + "/api/TestJsonRPC";
    let remote_url = origin + "/api/TestJsonRPC"; //https://makepro.su
    postData(url, data)
      .then((data)=>{
        console.log(data.result.answer);
        monitor.innerHTML = data.result.answer;
        // str = data["result"];
      });

  });
};