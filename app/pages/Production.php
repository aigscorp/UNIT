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

    public function __construct($params = null)
    {
        parent::__construct($params);

        $conn = \ZDB\DB::getConnect();
        $sql = "select p.name as name, p.size as size from model as m, pasport as p where p.id = m.pasport_id";

        $rs = $conn->Execute($sql);
        foreach($rs as $r){
            $this->items[] = new Model($r->id, $r['name'], $r['size']);
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

        $this->add(new Form('typeWorkForm'))->setVisible(false);
        $this->typeWorkForm->add(new Label('model'));
        $this->typeWorkForm->add(new DataView('typeWorkList',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "employee")), $this, 'listSizeOnRow'))->Reload();


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

    public function listSizeOnRow($row)
    {
        $item = $row->getDataItem();

        $row->add(new ClickLink('employee'))->onClick($this, 'empOnClick');
        $row->add(new Label('typeWork', $item->type));
        $row->add(new TextArea('price'));
        //        $row->add(new DropDownChoice('employee', $arr))->onChange($this, "onSize", true);
    }

    public function onSize($sender)
    {
        $this->test[] = $sender->getValue();
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
//                        $val = $childs[$key]->getValue();
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
                        $c->setValue($emps);
                        break;
                    }
                }
//                $ch[$ta]->setText($emps);
            }
            $a=10;
            $b=$a+6;
//            $this->typeWorkForm->typeWorkList->$ta->setText($emps);

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
            if(str_starts_with($k, "price") == true){
                $this->ta = $t->id;
                break;
            }

        }
//        $this->ta = $this->typeWorkForm->typeWorkList->getChildComponents();
//        $conn = \ZDB\DB::getConnect();
//        $emp = "SELECT employee_id, login, emp_name FROM employees";
//        $rs = $conn->Execute($emp);
//
//        $this->workers = [];
//        foreach ($rs as $r){
//            $this->workers[] = new Worker($r['employee_id'], $r['emp_name'], $r['login']);
//        }
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
//        $sz = $items->size;
//        $this->typeWorkForm->typeWorkList->Reload();

        $this->detailProduction->setVisible(false);
        $this->typeWorkForm->setVisible(true);
//        $this->typeWorkForm->typeWorkList->setVisible(true);
//        $this->typeWorkForm->newsize->setVisible(true);
        $this->typeWorkForm->model->setText($items->modelName);

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

