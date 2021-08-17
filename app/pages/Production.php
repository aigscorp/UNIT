<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 29.07.2021
 * Time: 23:28
 */

namespace App\Pages;


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


class Production extends Base
{
    public $items = array();
    public $sizes = [];
    public $employee = [];
    public $test = [];
    public $workers = [];
    public $ta;

    private $modelID = null;
    public function getModelID(){ return $this->modelID; }
    public function setModelID($id){ $this->modelID = $id; }

    public function __construct($params = null)
    {
        parent::__construct($params);

        $conn = \ZDB\DB::getConnect();
        $sql = "select p.id as id, p.name as name, p.size as size from model as m, pasport as p where p.id = m.pasport_id";

        $rs = $conn->Execute($sql);
        foreach($rs as $r){
            $this->items[] = new Model($r['id'], $r['name'], $r['size']);
        }
        $this->add(new Panel('panelButton'))->setVisible(true);
        $this->panelButton->add(new ClickLink('showProduction'));
        $this->panelButton->add(new ClickLink('showWork'));
        $this->panelButton->add(new ClickLink('showStore'));
        $this->panelButton->add(new ClickLink('showCustomer'));
        $this->panelButton->add(new ClickLink('showDirector'));

        $this->add(new Panel('detailProduction'))->setVisible(true);
//        $this->detailProduction->setVisible(false);
        $this->detailProduction->add(new DataView('list',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"items")),$this,'listOnRow'))->Reload();

//**********************START typeWorkFORM******************************
        $sql = "SELECT item_id, itemname FROM items WHERE cat_id = 11";
        $res = $conn->Execute($sql);
        foreach ($res as $re){
            $this->employee[] = new Employee($re['item_id'], $re['itemname']);
        }

        $this->add(new Form('sizeQuantityForm'))->setVisible(false);
        $this->sizeQuantityForm->add(new DataView('sizeQuantityList',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "sizes")), $this, 'listQuantitySizeOnRow'));
        $this->sizeQuantityForm->add(new SubmitLink('saveQuantitySize'))->onClick($this, 'saveQuantitySizeOnClick');
        $this->sizeQuantityForm->add(new Label('sizeAndQuantity'));

        $this->add(new Form('typeWorkForm'))->setVisible(false);
        $this->add(new ClickLink('addSize'))->onClick($this, 'addSizeOnClick');
        $this->typeWorkForm->add(new Label('model'));
        $this->typeWorkForm->add(new DataView('typeWorkList',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "employee")), $this, 'listTypeWorkOnRow'))->Reload();

        $this->typeWorkForm->add(new SubmitLink('saveSize'))->onClick($this, 'saveSizeOnClick');
        $this->add(new Form('panelmaster'))->setVisible(false);
        $this->panelmaster->add(new Label('modelworktype'));
        $this->panelmaster->add(new DataView('listmaster',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "workers")), $this, 'listMasterOnRow'))->Reload();
        $this->panelmaster->add(new SubmitLink('saveWorker'))->onClick($this, 'saveWorkerOnClick');
        $this->panelmaster->add(new SubmitLink('cancelWorker'))->onClick($this, 'saveWorkerOnClick');
//**********************END typeWorkFORM******************************


//        $this->typeWorkForm->typeWorkList->add(new DropDownChoice('size', $arr))->onChange($this, "onSize", true);

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
        var_dump($crr);
        $a = 2;
        $b = $a + 9;

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
        $row->add(new CheckBox('selectworker'));//->onChange($this, 'selectworkerOnChange');
    }

    public function saveSizeOnClick($sender)
    {
//        var_dump($sender);
        $d = $sender;
        $d1 = $this->test;
        var_dump($this->workers);

        $a = 1;
        $b = $a+10;

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
                    if(str_starts_with($key, "selectworker") == true){
                        $val = $child->getValue();
                        $idw = $item->getID();
                        foreach ($this->workers as $worker){
                            if($idw == $worker->getID() && $val == true){
                                $worker->type[] = trim($arr_model_work[1]);
                                $worker->select = $val;
                                $emps .= $worker->worker . ", ";
                                break;
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
//                        $c->setValue($emps);
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
        $row->add(new ClickLink('modelUpdate'));
        $row->add(new ClickLink('modelCancel'));
//    $row->add(new ClickLink('edit'))->onClick($this,'editOnClick');
    }

    public function modelWorkOnClick($sender)
    {
        $elems = $sender->getOwner();
        $items = $elems->getDataItem();
        $this->setModelID($items->getID());

//        $sz = $items->size;
//        $this->typeWorkForm->typeWorkList->Reload();

        $this->detailProduction->setVisible(false);
        $this->typeWorkForm->setVisible(true);
//        $this->typeWorkForm->typeWorkList->setVisible(true);
//        $this->typeWorkForm->newsize->setVisible(true);
        $this->typeWorkForm->model->setText($items->modelName . ", размеры: " . $items->size);
        $crr = explode("-", $items->size);
        for($i = intval($crr[0]), $k = 1; $i <= intval($crr[1]); $i++, $k++){
            $this->sizes[] = new SizeQuantity($k, $i);
        }
//        $this->typeWorkForm->sizeCountList>Reload();

        $conn = \ZDB\DB::getConnect();
        $emp = "SELECT employee_id, login, emp_name FROM employees";
        $rs = $conn->Execute($emp);

        foreach ($rs as $r){
            $this->workers[] = new Worker($r['employee_id'], $r['emp_name'], $r['login']);
        }

//        $conn = \ZDB\DB::getConnect();
//        $sql = "SELECT user_id, userlogin, email FROM users WHERE role_id != 1";
//        $rs = $conn->Execute($sql);
//        foreach ($rs as $r){
//            var_dump($r);
//        }
    }
}




class Worker implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $login;
    public $worker;
    public $type;
    public $select;

    public function __construct($id,$worker,$login,$type = [],$select=false)
    {
        $this->id=$id;
        $this->worker=$worker;
        $this->login = $login;
        $this->type = $type;
        $this->select=$select;
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

    public function __construct($id, $modelName, $size)
    {
        $this->id = $id;
        $this->modelName = $modelName;
        $this->size = $size;
    }

    public function getID()
    {
        return $this->id;
    }
}

