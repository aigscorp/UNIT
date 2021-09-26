<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 29.07.2021
 * Time: 23:28
 */

namespace App\Pages;

use App\Application as App;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Link\SubmitLink;


class Production extends \App\Pages\Base
{
    public $items = array();
    public $sizes = [];
    public $employee = [];
    public $type_work;
    public $workers = [];
    public $ta;


    private $masterID = null;
    private $modelID = null;
    public function getModelID(){ return $this->modelID; }
    public function setModelID($id){ $this->modelID = $id; }
    public function getMasterID(){ return $this->masterID; }
    public function setMasterID($id){ $this->masterID = $id; }

    public function __construct($params = null)
    {
        parent::__construct($params);

        $conn = \ZDB\DB::getConnect();
        $sql = "select p.id as id, p.name as name, p.size as size, m.in_work as in_work from model as m, pasport as p where p.id = m.pasport_id";

        $rs = $conn->Execute($sql);
        foreach($rs as $r){
            $this->items[] = new Model($r['id'], $r['name'], $r['size'], $r['in_work']);
        }

        $emp = "SELECT employee_id, login, emp_name FROM employees";
        $rs = $conn->Execute($emp);
        foreach ($rs as $r){
            $this->workers[] = new Worker($r['employee_id'], $r['emp_name'], $r['login'] );
        }

        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);
        $this->add(new Panel('detailProduction'))->setVisible(true);
//        $this->detailProduction->setVisible(false);
        $this->detailProduction->add(new DataView('list',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"items")),$this,'listOnRow'))->Reload();

//**********************START typeWorkFORM******************************

        $this->add(new Form('sizeQuantityForm'))->setVisible(false);
        $this->sizeQuantityForm->add(new DataView('sizeQuantityList',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "sizes")), $this, 'listQuantitySizeOnRow'));
        $this->sizeQuantityForm->add(new SubmitLink('saveQuantitySize'))->onClick($this, 'saveQuantitySizeOnClick');
        $this->sizeQuantityForm->add(new SubmitLink('cancelQuantitySize'))->onClick($this, 'saveQuantitySizeOnClick');
        $this->sizeQuantityForm->add(new Label('sizeAndQuantity'));

        $this->add(new Form('typeWorkForm'))->setVisible(false);
        $this->typeWorkForm->add(new ClickLink('addSize'))->onClick($this, 'addSizeOnClick');
        $this->typeWorkForm->add(new Label('showSize'));
        $this->typeWorkForm->add(new Label('model'));
        $this->typeWorkForm->add(new DataView('typeWorkList',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "employee")), $this, 'listTypeWorkOnRow'))->Reload();

        $this->typeWorkForm->add(new SubmitButton('saveProduct'))->onClick($this, 'saveProductOnClick', true);
        $this->typeWorkForm->add(new SubmitButton('cancelProduct'))->onClick($this, 'saveProductOnClick', true);
        $this->add(new Form('panelmaster'))->setVisible(false);
        $this->panelmaster->add(new Label('modelworktype'));
        $this->panelmaster->add(new DataView('listmaster',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "workers")), $this, 'listMasterOnRow'))->Reload();
        $this->panelmaster->add(new SubmitLink('saveWorker'))->onClick($this, 'saveWorkerOnClick');
        $this->panelmaster->add(new SubmitLink('cancelWorker'))->onClick($this, 'saveWorkerOnClick');
//**********************END typeWorkFORM******************************

    }

    public function listQuantitySizeOnRow($row)
    {
        $item = $row->getDataItem();
        $row->add(new Label('oneSizeModel', $item->size));
        $row->add(new TextInput('quantity', $item->quantity));
        $row->add(new Label('inwork', $item->work . ": " . $item->model_quantity));
    }

    public function saveQuantitySizeOnClick($sender)
    {
//        $s = $sender->getOwner()->getChildComponents();
        if($sender->id == "saveQuantitySize"){
            $listQuans = $sender->getOwner()->getComponent('sizeQuantityList')->getChildComponents();
            $crr = [];
            foreach ($listQuans as $listQuan){
                $lists = $listQuan->getChildComponents();
                $sz = 0;
                $quan = 0;
                foreach ($lists as $k=>$list){
                    if(str_starts_with($k,"oneSizeModel") == true){
                        $sz = $list->getText();
                    }else if(str_starts_with($k,"quantity") == true){
                        $quan = $list->getText();
                    }
                }
                $crr[$sz] = $quan;
            }

            foreach ($this->employee as $t){
                if($t->type == $this->type_work){
                    foreach ($crr as $k=>$v){
                        $t->sz_qty[$k] = strval(intval($t->sz_qty[$k]) - intval($v));
                    }
                }
            }

            foreach ($this->workers as $worker){
                if($worker->getID() == $this->getMasterID()){
                    $worker->setType($this->type_work, $crr);
//                    $worker->size_qnt = $crr;
//                    $worker->list_size_qnt[] = $crr;
                    break;
                }
            }

            $show_sz = "";
            foreach($crr as $k=>$v){
                foreach($this->sizes as $sz){
                    if($k == $sz->size){
                        $sz->quantity = $v;
                        if($v != 0){
                            $show_sz .= $k . " " . "(" . $v . ")" . ", ";
                        }
                        break;
                    }
                }    
            }

//            $this->typeWorkForm->showSize->setText($show_sz); показываем выбранные размеры и кол-во.



            $listmasters = $this->panelmaster->listmaster->getChildComponents();
            foreach ($listmasters as $listmaster){
                $datamaster = $listmaster->getDataItem();
                if($datamaster->getID() == $this->getMasterID()){
                    $listcomps = $listmaster->getChildComponents();
                    foreach ($listcomps as $key=>$val){
                        if(str_starts_with($key, "selectwork") == true){
                           $val->setText($show_sz);
                        }
                    }
                }
            }
        }
        $this->sizeQuantityForm->setVisible(false);
        $this->panelmaster->setVisible(true);
//        $this->typeWorkForm->setVisible(true);
    }

    public function saveProductOnClick1($sender){

        $test_select = true;
        $id = $sender->id;
        $list_selected = $this->typeWorkForm->typeWorkList->getChildComponents(); //->getItemData();
        foreach ($list_selected as $sel_work){
            $number = $sel_work->getNumber();
            $is_selected_data = $sel_work->getComponent("selected_" . $number);
            if($is_selected_data->getText() == null || $is_selected_data->getText() == ""){
                $test_select = false;
                break;
            }
        }

        if($test_select == false){
            $msg = "Не выбраны размеры и количество работ для мастеров.";
            $js = "
            $('#model').append(\"<div style='margin: 10px 5px'><p style='color: darkred; font-size: .9em'>{$msg}</p></div>\");
            $('#model').children().fadeOut(3000, \"linear\", function(){\$('#model').children().remove()} );
            ";
            $this->updateAjax(array(), $js);
        }else{
            $js2 = "
             let orig = window.location.origin;
             window.location = orig + '/index.php?p=/App/Pages/Production'              
            ";
            $this->updateAjax(array(), $js2);
        }


    }
    public function saveProductOnClick($sender)
    {
        //  save to BD model, master
        // проверить поле "размеры и количество"
        $test_select = true;

        $list_selected = $this->typeWorkForm->typeWorkList->getChildComponents(); //->getItemData();
        foreach ($list_selected as $sel_work){
            $number = $sel_work->getNumber();
            $is_selected_data = $sel_work->getComponent("selected_" . $number);
            if($is_selected_data->getText() == null || $is_selected_data->getText() == ""){
               $test_select = false;
               break;
            }
        }

        if($sender->id == "saveProduct" && $test_select == true){
//            echo "save data type_work table and update table model<br>";

        //проверить товар на складе если есть запуск в производство, зарезервировать в модели количество комплектующих со склада
        //store_id поле резерв quantity


             $tmp_count = 0;
             $upd = "UPDATE model SET in_work = true WHERE pasport_id = " . $this->getModelID();
             $conn = \ZDB\DB::getConnect();
             $conn->Execute($upd);
            
            $temp = [];
            foreach($this->workers as $wrk){
                $types = $wrk->getListType();
                if($types != null) $temp = array_merge($temp, $types);
            }
            $typeworks = array_unique($temp);

            $masters = [];

            foreach ($typeworks as $typework){
                //записать в typework виды работ и паспорт модели
                $sql = "INSERT INTO typework(type_work, pasport_id) VALUES (" . "'" . $typework . "'" . "," .
                    "'" . $this->getModelID() . "'" . ")";
                $conn->Execute($sql);
                $last_id = $conn->_insertid();
//                $last_id = ++$tmp_count;
                foreach ($this->workers as $worker){
                    $list_types = $worker->getListType();
                    if($list_types != null){//count($worker->type) != 0
                        foreach ($list_types as $wt){
                            if($wt == $typework){
                                $emp_type = new \stdClass();
                                $emp_type->emp_id = $worker->id;
                                $emp_type->typework_id = $last_id;
                                $str_sz_q = "";
                                $str_sz_made = "";
                                $type_sz_quan = $worker->getType($typework);
                                if($type_sz_quan != null){
                                    $qty_work_master = 0;
                                    foreach ($type_sz_quan as $s=>$q){
                                        $str_sz_q .= "<size>" . $s . "</size>" . "<quantity>" . $q . "</quantity>";
                                        $str_sz_made .= "<size>" . $s . "</size>" . "<done>" . 0 . "</done>";
                                        $qty_work_master += intval($q);
                                    }
                                }
                                $detail_work = "<master>" . $worker->worker . "</master>" . "<work>" . $typework . "</work>" . $str_sz_q;
                                $emp_type->detail = $detail_work;
                                $emp_type->qty_works = $qty_work_master;
                                $emp_type->made_work = $str_sz_made;
                                $masters[] = $emp_type;
                            }
                        }
                    }
                }

            }
            //записать в табл. masters работы, размеры, количество
            foreach ($masters as $master){
                $sql = "INSERT INTO masters(typework_id, emp_id, detail, init_quantity, made_work) 
                    VALUES ('{$master->typework_id}', '{$master->emp_id}', '{$master->detail}', '{$master->qty_works}', '{$master->made_work}')";
                $conn->Execute($sql);
            }
            $js2 = "
                 let orig = window.location.origin;
                 window.location = orig + '/index.php?p=/App/Pages/Production'              
                ";
            $this->updateAjax(array(), $js2);

//            App::Redirect("\\App\\Pages\\Production");
        }else {
            if($sender->id == 'saveProduct'){
                $msg = "Не выбраны размеры и количество работ для мастеров.";
                $js = "
                    $('#model').append(\"<div style='margin: 10px 5px'><p style='color: darkred; font-size: .9em'>{$msg}</p></div>\");
                    $('#model').children().fadeOut(3000, \"linear\", function(){\$('#model').children().remove()} );
                    ";
                $this->updateAjax(array(), $js);
            }else{
                $js2 = "
                 let orig = window.location.origin;
                 window.location = orig + '/index.php?p=/App/Pages/Production'              
                ";
                $this->updateAjax(array(), $js2);
            }
        }

//        if($sender->id == 'cancelProduct'){
//            $this->typeWorkForm->setVisible(false);
//            $this->detailProduction->setVisible(true);
//        }

    }

    public function addSizeOnClick($sender)
    {
        $master = $sender->getOwner()->getDataItem();
        $model_id = $this->getModelID();
        $modelName = "";
        $modelSize = "";
        $select_work = $sender->getOwner()->getChildComponents();
        $numb = $sender->getOwner()->getNumber();

        foreach ($this->items as $item){
            if($item->getID() == $model_id){
                $modelName = $item->modelName;
                $modelSize = $item->size;
                break;
            }
        }
        $current_work = $this->type_work;
        foreach ($this->employee as $t){
            if($t->type == $current_work){
                $t->reset();
                $sizer = $t->sz_qty;
                break;
            }
        }


        $this->sizes = [];
        $arr = explode("-", $modelSize);

        for($i = intval(trim($arr[0])), $k = 1; $i <= intval(trim($arr[1])); $i++, $k++){
            $this->sizes[] = new SizeQuantity($k, $i, $sizer[$i], $current_work);
        }

        $this->sizeQuantityForm->sizeQuantityList->Reload();
        $this->sizeQuantityForm->sizeAndQuantity->setText($modelName . ", ввести количество для каждого размера," . " мастер " . $master->worker);
        $this->typeWorkForm->setVisible(false);
        $this->sizeQuantityForm->setVisible(true);
    }

    public function listMasterOnRow($row)
    {
        $item = $row->getDataItem();
        $row->add(new Label('worker', $item->worker));
        $row->add(new Label('login', $item->login));
        $row->add(new Label('selectwork'));
        $row->add(new ClickLink('master'))->onClick($this, 'masterSizeOnClick');
    }

    public function masterSizeOnClick($sender)
    {
        $master = $sender->getOwner()->getDataItem();
        $this->setMasterID($master->getID());

        $this->panelmaster->setVisible(false);
        $this->addSizeOnClick($sender);
    }

    public function listTypeWorkOnRow($row)
    {
        $item = $row->getDataItem();

        $row->add(new ClickLink('employee'))->onClick($this, 'empOnClick');
        $row->add(new Label('typeWork', $item->type));
        $row->add(new Label('selected'));
//        $row->add(new Label('selected', new \Zippy\Binding\PropertyBinding($item, 'selected')));
        //        $row->add(new DropDownChoice('employee', $arr))->onChange($this, "onSize", true);
    }

    public function saveWorkerOnClick($sender)
    {
        $id = $sender->id;

        if($id == 'saveWorker'){
            $type_work = $this->panelmaster->modelworktype->getText();
            $arr_model_work = explode(",", $type_work);
            $emps = "";
            $masters = $this->panelmaster->listmaster->getChildComponents();
            foreach ($masters as $master){
                $childs = $master->getChildComponents();
                $item = $master->getDataItem();
                foreach ($childs as $key=>$child){
                    if(str_starts_with($key, "selectwork") == true){
                        $val = $child->getText();
                        $idw = $item->getID();
                        foreach ($this->workers as $worker){
                            if($idw == $worker->getID() && $val != null){
                                $emps .= $worker->worker . ": ";
                                $sq = "";
                                $type_sz_quantity = $worker->getType($this->type_work);
                                if(count($type_sz_quantity) != 0){
                                    foreach ($type_sz_quantity as $type=>$vals){
                                        $sq .= $type . "(" . $vals . ")" . ", ";
                                    }
                                }
                                if($sq != "") $emps .= $sq;
                                // break;
                            }
                        }
                    }
                }
            }
            $ta = $this->ta;
            $trr = $this->typeWorkForm->typeWorkList->getChildComponents();
            foreach ($trr as $k=>$t){
                $ch = $t->getChildComponents();
                foreach ($ch as $key=>$c){
                    if($key == $ta){
                        $c->setText($emps);
                        break;
                    }
                }
            }
        }
        $this->typeWorkForm->setVisible(true);
        $this->panelmaster->setVisible(false);
    }
    public function empOnClick($sender)
    {
        $type_work = $sender->getOwner()->getDataItem();
        $model_name = $this->typeWorkForm->model->getText(); //->model->getValue();

        $trr = $sender->getOwner()->getChildComponents();
        foreach ($trr as $k=>$t){
            if(str_starts_with($k, "selected") == true){
                $this->ta = $t->id;
                break;
            }
        }
        $this->type_work = $type_work->type;

        $this->panelmaster->modelworktype->setText($model_name . ', ' . $type_work->type);
        $this->panelmaster->listmaster->Reload();

        $this->typeWorkForm->setVisible(false);
        $this->panelmaster->setVisible(true);
    }

    public function listOnRow($row){
        $item = $row->getDataItem();

        $row->add(new Label('modelName',$item->modelName . ', ' . $item->size));
        $row->add(new ClickLink('modelWork'))->onClick($this, 'modelWorkOnClick');
        if($item->in_work == true){
            $row->modelWork->setAttribute('class', 'btn btn-outline-secondary disabled');
            $row->modelWork->setValue("В работе");
            $row->modelName->setAttribute('class', 'btn btn-outline-success model');
        }
//        $row->modelWork->setAttribute('style', $item->in_work == true ? 'disabled' : null);
        $row->add(new ClickLink('modelUpdate'))->onClick($this, 'modelUpdateOnClick', true);
        $row->add(new ClickLink('modelCancel'))->onClick($this, 'modelCancelOnClick');
        if($item->in_work == false){
            $row->modelCancel->setAttribute('class', 'btn btn-outline-secondary disabled');
        }

//    $row->add(new ClickLink('edit'))->onClick($this,'editOnClick');
    }

    public function modelWorkOnClick($sender)
    {
        $elems = $sender->getOwner();
        $items = $elems->getDataItem();
        $this->setModelID($items->getID());

        $this->detailProduction->setVisible(false);
        $this->typeWorkForm->setVisible(true);
        $this->typeWorkForm->model->setText($items->modelName . ", размеры: " . $items->size);
//        $crr = explode("-", $items->size);
//        for($i = intval($crr[0]), $k = 1; $i <= intval($crr[1]); $i++, $k++){
//            $this->sizes[] = new SizeQuantity($k, $i);
//        }
        $conn = \ZDB\DB::getConnect();
        $modelName = $items->modelName;
        $modelSize = $items->size;
        $sql = "SELECT pt.id as id, pt.detail as detail, p.comment as comment FROM `pasport_tax` as pt, `pasport` as p 
                      WHERE p.id = pt.pasport_id and detail LIKE \"<work>%\" " .
                      " AND p.name = " . "'" . $modelName . "'" . " AND p.size = " . "'" . $modelSize . "'";

        $res = $conn->Execute($sql);
        $this->employee = [];
        foreach ($res as $r){
            $work = $r['detail'];
            $wrk = preg_replace('/(<work>)*?(<\/work>)*?/', "", $work);
            $pm = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>/i', $r['comment'], $matches);
            $sz_quan = [];
            for($i = 0; $i < count($matches[1]); $i++){
                $sz_quan[$matches[1][$i]] = $matches[2][$i];
            }
            $this->employee[] = new Employee($r['id'], $wrk, $matches[1], $matches[2], $sz_quan);
        }
        $this->typeWorkForm->typeWorkList->Reload();
    }

    /* убрать модель из производства, удалить список работ у мастеров  */
    public function modelCancelOnClick($sender){
        $pasport_id = $sender->getOwner()->getDataItem()->id;
        //удалить записи в masters, defect_model
        $conn = \ZDB\DB::getConnect();
        $sql_del = "SELECT t.id as typework_id, m.id as model_id FROM typework t, model m 
                    WHERE m.pasport_id = t.pasport_id AND t.pasport_id = " . $pasport_id;
        $rs = $conn->Execute($sql_del);

        $model_id = 0;
        $typework = [];
        foreach ($rs as $r){
            $model_id = intval($r['model_id']);
            $typework[] = intval($r['typework_id']);
        }
        $typework_list = implode("','", $typework);
        $sql = "DELETE FROM masters WHERE typework_id IN('{$typework_list}')";
        $conn->Execute($sql);

        $sql_defect = "DELETE FROM defect_model WHERE model_id = " . $model_id;
        $conn->Execute($sql_defect);

        $sql_typework = "DELETE FROM typework WHERE pasport_id = " . $pasport_id;
        $conn->Execute($sql_typework);

        $sql_model = "UPDATE model SET in_work = false WHERE pasport_id = " . $pasport_id;
        $conn->Execute($sql_model);
//        $this->detailProduction->list->Reload();
        App::Redirect("\\App\\Pages\\Production");
    }

    //обновить модель при появлении брака, списать товар на производство модели определенного размера
    //в паспорте модели уточнить кол-во моделей бракованого размера
    public function modelUpdateOnClick($sender){
        $model_obj = $sender->getOwner()->getDataItem();//object
        $pasport_id = $model_obj->id;

        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT p.comment, d.id as defect_id, d.detail, m.defect 
                FROM pasport p, model m, defect_model d 
                WHERE p.id=m.pasport_id AND d.model_id = m.id AND d.status = false AND p.id = " . $pasport_id;

        $rs = $conn->Execute($sql);
        $msg = " Нет обновлений по браку.";
        if($rs->fields != false){
            $defect_obj = new \stdClass();
            foreach ($rs as $r){
                $defect_obj->comment = $r['comment'];
                $defect_obj->detail_defect[] = $r['detail'];
                $defect_obj->defect_id[] = $r['defect_id'];
                $defect_obj->defect_count = $r['defect'];
            }
            //"<size>40</size><quantity>100</quantity>,<size>41</size><quantity>100</quantity>,<size>42</size><quantity>200</quantity>,<size>43</size><quantity>200</quantity>,<size>44</size><quantity>100</quantity>,"
            $res = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>,/i',$defect_obj->comment,$match);
            $arr_size_qty = [];
            if($res != null && $res != false){
                $arr_size_qty = array_combine($match[1], $match[2]);
            }
            //<master>Марина</master><work>Сборка</work><size>40</size><defect>Описание: Брак на коже. Моя вина. </defect>
            $match = [];
            $defect_count_sz = 0;
            foreach ($defect_obj->detail_defect as $dd){
                $res_dd = preg_match('/\<size\>([0-9]+)\<\/size\>/',$dd,$match);
                if($res_dd != false){
                    foreach ($arr_size_qty as $key_arr=>$val_arr){
                        if($key_arr == $match[1]){
                            $arr_size_qty[$key_arr] = intval($arr_size_qty[$key_arr]) + 1;
                            $defect_count_sz++;
                        }
                    }
                }
            }
            $str_comment = "";
            foreach ($arr_size_qty as $k1=>$v1){
                $str_comment .= "<size>" . $k1 . "</size>" . "<quantity>" . $v1 . "</quantity>" . ",";
            }
            $sql = "UPDATE pasport SET comment = '{$str_comment}' WHERE pasport.id = " . $pasport_id;
            $conn->Execute($sql);

            $defect_id = implode("','", $defect_obj->defect_id);
            $sql_defect = "UPDATE defect_model SET status = true WHERE id IN('{$defect_id}')";
            $conn->Execute($sql_defect);

            $defect_count = $defect_count_sz;
            if($defect_obj->defect_count != null) $defect_count += $defect_obj->defect_count; // + $defect_count_sz;
            $sql_model_defect = "UPDATE model SET defect = '{$defect_count}' WHERE pasport_id = " . $pasport_id;
            $conn->Execute($sql_model_defect);

            $msg = "Обновлено количество моделей " . $defect_count_sz . ".";
        }
        $js = "$('#detailProductionShow').append(\"<div class='alert alert-dark alert-dismissible fade show' role='alert'><strong>{$msg}</strong><button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div>\");";
        $this->updateAjax(array(), $js);
    }
}

//$('#detailProductionShow').children().fadeOut(3000, "linear", function(){$('#detailProductionShow').children().remove()} );

class Worker implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $login;
    public $worker;
    private $type;
    private $size_qnt;
//    public $list_size_qnt;

    public function __construct($id,$worker,$login)
    {//, $type = "",$size_qnt = "")
        $this->id = $id;
        $this->worker=$worker;
        $this->login = $login;
    }
    public function setType($type, $size_qnt){
        $this->type[$type] = $size_qnt;
        $this->size_qnt = $size_qnt;
    }

    public function getType($type){
        return $this->type[$type];
    }

    public function getListType(){
        $res = [];
        foreach ($this->type as $t=>$q){
            $res[] = $t;
        }
        return $res;
    }
    //требование  интерфейса
    public function getID() { return $this->id;}
}

class SizeQuantity implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $size;
    public $quantity;
    public $model_quantity;
    public $work;

    public function __construct($id, $size, $model_quantity, $work, $quantity = 0)
    {
        $this->id = $id;
        $this->size = $size;
        $this->work = $work;
        $this->model_quantity = $model_quantity;
        $this->quantity = $quantity;
    }

    public function getID() { return $this->id;}
}

class Employee implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $type;
    public $size;
    public $quantity;
    public $sz_qty;

    public function __construct($id, $type, $size, $quantity, $sz_qty)
    {
        $this->id = $id;
        $this->type = $type;
        $this->size = $size;
        $this->quantity = $quantity;
        $this->sz_qty = $sz_qty;
    }
    public function reset(){
        if(count($this->sz_qty) > 0){
           for($j = 0; $j < count($this->quantity); $j++){
               $k = $this->size[$j];
               $v = $this->quantity[$j];
               $this->sz_qty[$k] = $v;
           }
        }
    }
//    public function getEmp() { return $this->emp; }
    public function getID() { return $this->id; }
}

class Model implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $modelName;
    public $size;
    public $in_work;

    public function __construct($id, $modelName, $size, $in_work=false)
    {
        $this->id = $id;
        $this->modelName = $modelName;
        $this->size = $size;
        $this->in_work = $in_work;
    }

    public function getID()
    {
        return $this->id;
    }
}