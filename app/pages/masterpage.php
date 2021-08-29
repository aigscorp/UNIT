<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 28.08.2021
 * Time: 20:02
 */

namespace App\Pages;

use App\Application as App;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;

class MasterPage extends \App\Pages\Base
{
    public $id;
    public $emp;
    public $listWorkEmployees = [];
    public $list_model = [];
    public $list_size = [];
//    public $select_work = "";

    public function __construct($params = null)
    {
        parent::__construct($params);
//        echo "<pre>";
//        $sess = $_SESSION;
//        foreach ($sess as $ses){
//            if(is_object($ses) == false){
//                echo $ses;
//            }
//        }
//        echo "</pre>";
        $userlogin = $_SESSION['userlogin'];

        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT employee_id, emp_name FROM employees e WHERE e.login = " . "'" . $userlogin . "'";
        $rs = $conn->Execute($sql);
//        print_r($rs->fields);

        $emp_id = $rs->fields['employee_id'];
        $emp_name = $rs->fields['emp_name'];

        $query = "SELECT CONCAT(p.name, ', ', p.size) as name, p.comment, t.pasport_id, t.type_work, m.typework_id, m.detail 
                  FROM masters m, typework t, model md, pasport p 
                  WHERE m.emp_id = " . $emp_id. " AND t.id = m.typework_id 
                  AND md.pasport_id = t.pasport_id AND md.in_work = true AND p.id = md.pasport_id";
        $rs = $conn->Execute($query);
        $type_works = [];
        foreach ($rs as $r){
            $pid = $r['pasport_id'];
            $fnd = false;
            foreach ($this->listWorkEmployees as $employee){
                if($employee->getID() == $pid){
                    $employee->typework[$r['type_work']] = $r['detail'];
                    $fnd = true;
                }
            }
            if($fnd == false){
                $this->listWorkEmployees[] = new WorksMaster($r['pasport_id'], $r['name'], $r['type_work'],
                    $r['detail'], $r['comment']);
            }
            if(in_array($r['type_work'], $type_works) == false){
                $type_works[] = $r['type_work'];
            }
        }



        $this->add(new Form('masterForm'));
        $this->masterForm->add(new DropDownChoice('workTypeMaster', $type_works))->onChange($this, 'workTypeMasterOnChange');

        $this->add(new Panel('panelModelMaster'));
        $this->panelModelMaster->add(new DataView('listWorkMaster',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "list_model")),$this,'listWorkMasterOnRow'));

        $this->add(new Form('tableWorkForm'))->setVisible(false);
        $this->tableWorkForm->add(new Label('modelNameSize'));
        $this->tableWorkForm->add(new Label('showTypeWork'));
        $this->tableWorkForm->add(new DataView('listWorkModelMaster',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "list_size")), $this, 'listWorkModelMasterOnRow'));

        $a = 1;
        $b = $a + 9;
    }

    public function workTypeMasterOnChange()
    {
        $val = $this->masterForm->workTypeMaster->getValue();
        $works = $this->masterForm->workTypeMaster;
        $option = $works->getOptionList();
        $select_work = $option[$val];
        $this->list_model = [];
        foreach ($this->listWorkEmployees as $employee){
            if(is_array($employee->typework) == true){
                foreach ($employee->typework as $key=>$value){
                    if($key == $select_work){
                        $this->list_model[] = $employee;
                    }
                }
            }
        }
//        var_dump($select);
        $this->panelModelMaster->listWorkMaster->Reload();
        $this->masterForm->workTypeMaster->setValue($val);
        $this->updateAjax(array('workTypeMaster'));
    }

    public function listWorkMasterOnRow($row)
    {
        $item = $row->getDataItem();

        $row->add(new ClickLink('modelName'))->onClick($this, 'modelNameOnClick');
        $row->modelName->setValue($item->model);

    }

    public function modelNameOnClick($sender)
    {
//        print_r($sender->id);
        $owner = $sender->getOwner();
        $item = $owner->getDataItem();
        $type = $this->masterForm->workTypeMaster->getvalue();
        $opt = $this->masterForm->workTypeMaster;
        $select = $opt->getOptionList();
        $work = $select[$type];

        $detail = $item->typework[$work];

        $res = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>/i',$item->quantity,$all_size);
        $res = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>/i',$detail,$all_master);

        array_shift($all_size);
        array_shift($all_master);
        array_shift($all_master);
        $size_master = array_merge($all_size, $all_master);
//        var_dump($size_master);

        $this->list_size = [];

        for($i = 0; $i < count($size_master[0]); $i++){
            $this->list_size[] = new WorksSize($i+1, $size_master[0][$i], $size_master[1][$i], $size_master[2][$i]);
        }
        $this->tableWorkForm->listWorkModelMaster->Reload();
        $this->tableWorkForm->modelNameSize->setText($item->model);
        $this->tableWorkForm->showTypeWork->setText($work);
        $this->masterForm->setVisible(false);
        $this->panelModelMaster->setVisible(false);
        $this->tableWorkForm->setVisible(true);
    }

    public function listWorkModelMasterOnRow($row)
    {
        $item = $row->getDataItem();
        $row->add(new Label('itemSizeModel', $item->size));
        $row->add(new TextInput('itemCountModel'));
        $row->itemCountModel->setText($item->total_quantity - $item->master_quantity);
        $row->add(new Label('itemTotalModel', $item->total_quantity));
        $row->add(new Label('itemRemainModel'));
    }

}

class WorksMaster implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $model;
    public $typework = [];
//    public $detail= [];
    public $quantity;
//    public $size;

    public function __construct($id, $model, $typework, $detail, $quantity)
    {
        $this->id = $id;
        $this->model = $model;
        $this->typework[$typework] = $detail;
//        $this->detail[] = $detail;
        $this->quantity = $quantity;
//        $this->size = $size;
    }

    public function getID() { return $this->id; }
}

class WorksSize implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $size;
    public $total_quantity;
    public $master_quantity;
//    public $model;
//    public $detail;

    public function __construct($id, $size, $total_quantity, $master_quantity)
    {
        $this->id = $id;
        $this->size = $size;
        $this->total_quantity = $total_quantity;
        $this->master_quantity = $master_quantity;
//        $this->detail = $detail;
    }

    public function getID() { return $this->id; }
}