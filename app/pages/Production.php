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
    public $test = [];
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
            $this->workers[] = new Worker($r['employee_id'], $r['emp_name'], $r['login']);
        }

        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);
//        $this->add(new Panel('panelButton'))->setVisible(true);
//        $this->panelButton->add(new ClickLink('showProduction'));
//        $this->panelButton->add(new ClickLink('showWork'));
//        $this->panelButton->add(new ClickLink('showStore'));
//        $this->panelButton->add(new ClickLink('showCustomer'));
//        $this->panelButton->add(new ClickLink('showDirector'));


        $this->add(new Panel('detailProduction'))->setVisible(true);
//        $this->detailProduction->setVisible(false);
        $this->detailProduction->add(new DataView('list',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"items")),$this,'listOnRow'))->Reload();

//**********************START typeWorkFORM******************************

//        $sql = "SELECT item_id, itemname FROM items WHERE cat_id = 11";
//        $res = $conn->Execute($sql);
//        foreach ($res as $re){
//            $this->employee[] = new Employee($re['item_id'], $re['itemname']);
//        }

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

        $this->typeWorkForm->add(new SubmitLink('saveSize'))->onClick($this, 'saveSizeOnClick');
        $this->typeWorkForm->add(new SubmitLink('cancelSize'))->onClick($this, 'saveSizeOnClick');
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

            foreach ($this->workers as $worker){
                if($worker->getID() == $this->getMasterID()){
                    $worker->size_qnt = $crr;
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

    public function saveSizeOnClick($sender)
    {
        //  save to BD model, master
        if($sender->id == "saveSize"){
//            echo "save data type_work table and update table model<br>";
             $upd = "UPDATE model SET in_work = true WHERE pasport_id = " . $this->getModelID();
             $conn = \ZDB\DB::getConnect();
             $conn->Execute($upd);
            
            $temp = [];
            foreach($this->workers as $wrk){
                foreach($wrk->type as $type){
                    $temp[] = $type;
                }
            }
            $typeworks = array_unique($temp);

            $masters = [];

            foreach ($typeworks as $typework){
                //записать в typework виды работ и паспорт модели
                $sql = "INSERT INTO typework(type_work, pasport_id) VALUES (" . "'" . $typework . "'" . "," .
                    "'" . $this->getModelID() . "'" . ")";
                $conn->Execute($sql);
                $last_id = $conn->_insertid();
//                echo "<pre>";
//                print_r($typework);
//                echo "last inserted record " . $last_id . "<br>";
//                echo "</pre>";

                foreach ($this->workers as $worker){
                    if(count($worker->type) != 0){
                        foreach ($worker->type as $wt){
                            if($wt == $typework){
                                $emp_type = new \stdClass();
                                $emp_type->emp_id = $worker->id;
                                $emp_type->typework_id = $last_id;
                                $str_sz_q = "";
                                if(count($worker->size_qnt) != 0){
                                    foreach ($worker->size_qnt as $s=>$q){
                                        $str_sz_q .= "<size>" . $s . "</size>" . "<quantity>" . $q . "</quantity>";
                                    }
                                }
                                $detail_work = "<master>" . $worker->worker . "</master>" . "<work>" . $typework . "</work>" . $str_sz_q;
                                $emp_type->detail = $detail_work;
                                $masters[] = $emp_type;
                            }
                        }
                    }
                }

            }
            //записать в табл. masters работы, размеры, количество
            foreach ($masters as $master){
                $sql = "INSERT INTO masters(typework_id, emp_id, detail) VALUES ("
                        . "'" . $master->typework_id . "'" . "," . "'" . $master->emp_id . "'"
                        . "," . "'" . $master->detail . "'" . ")";
                $conn->Execute($sql);

            }
            App::Redirect("\\App\\Pages\\Production");
//            echo "<pre>";
//            print_r($masters);
//            print_r($this->workers);
//            echo "</pre>";

        }
        $this->typeWorkForm->setVisible(false);
        $this->detailProduction->setVisible(true);
        $a = 1;
        $b = $a+10;

    }

    public function addSizeOnClick($sender)
    {
        $model_id = $this->getModelID();
        $modelName = "";
        $modelSize = "";

        foreach ($this->items as $item){
            if($item->getID() == $model_id){
                $modelName = $item->modelName;
                $modelSize = $item->size;
                break;
            }
        }

        $this->sizes = [];
        $arr = explode("-", $modelSize);

        for($i = intval(trim($arr[0])), $k = 1; $i <= intval(trim($arr[1])); $i++, $k++){
            $this->sizes[] = new SizeQuantity($k, $i);
        }

        $this->sizeQuantityForm->sizeQuantityList->Reload();
        $this->sizeQuantityForm->sizeAndQuantity->setText($modelName . ", ввести количество для каждого размера");
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
                            if($idw == $worker->getID() && $val == true){
                                $worker->type[] = trim($arr_model_work[2]);
                                $emps .= $worker->worker . ": ";
                                $sq = "";
                                if(count($worker->size_qnt) != 0){
                                    foreach ($worker->size_qnt as $key=>$val){
                                        $sq .= $key . "(" . $val . ")" . ", ";
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
        $row->add(new ClickLink('modelUpdate'));
        $row->add(new ClickLink('modelCancel'));
//    $row->add(new ClickLink('edit'))->onClick($this,'editOnClick');
    }

    public function modelWorkOnClick($sender)
    {
        $elems = $sender->getOwner();
        $items = $elems->getDataItem();
        $this->setModelID($items->getID());

        $this->detailProduction->setVisible(false);
        $this->typeWorkForm->setVisible(true);
//        $this->typeWorkForm->typeWorkList->setVisible(true);
//        $this->typeWorkForm->newsize->setVisible(true);
        $this->typeWorkForm->model->setText($items->modelName . ", размеры: " . $items->size);
        $crr = explode("-", $items->size);
        for($i = intval($crr[0]), $k = 1; $i <= intval($crr[1]); $i++, $k++){
            $this->sizes[] = new SizeQuantity($k, $i);
        }
        $conn = \ZDB\DB::getConnect();
//        $emp = "SELECT employee_id, login, emp_name FROM employees";
//        $rs = $conn->Execute($emp);
//
//        foreach ($rs as $r){
//            $this->workers[] = new Worker($r['employee_id'], $r['emp_name'], $r['login']);
//        }

        $modelName = $items->modelName;
        $modelSize = $items->size;
        $sql = "SELECT pt.id as id, pt.detail as detail FROM `pasport_tax` as pt, `pasport` as p 
                      WHERE p.id = pt.pasport_id and detail LIKE \"<work>%\" " .
                      " AND p.name = " . "'" . $modelName . "'" . " AND p.size = " . "'" . $modelSize . "'";

        $res = $conn->Execute($sql);
        $this->employee = [];
        foreach ($res as $r){
            $work = $r['detail'];
            $wrk = preg_replace('/(<work>)*?(<\/work>)*?/', "", $work);
            $this->employee[] = new Employee($r['id'], $wrk);
        }
        $this->typeWorkForm->typeWorkList->Reload();
    }
}




class Worker implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $login;
    public $worker;
    public $type;
    public $size_qnt;

    public function __construct($id,$worker,$login,$type = [],$size_qnt = [])
    {
        $this->id=$id;
        $this->worker=$worker;
        $this->login = $login;
        $this->type = $type;
        $this->size_qnt = $size_qnt;
    }
    //требование  интерфейса
    public function getID() { return $this->id;}
}

class SizeQuantity implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $size;
    public $quantity;

    public function __construct($id, $size, $quantity = 0)
    {
        $this->id = $id;
        $this->size = $size;
        $this->quantity = $quantity;
    }

    public function getID() { return $this->id;}
}

class Employee implements \Zippy\Interfaces\DataItem
{
    public $id;
//    public $emp = [];
    public $type;

    public function __construct($id, $type)
    {
        $this->id = $id;
//        $this->emp = $emp;
        $this->type = $type;
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