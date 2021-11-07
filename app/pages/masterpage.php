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
    public $pasport_id;
    public $employee_name;
    public $emp_id;
    public $current_work;
    public $current_work_id;
    public $listWorkEmployees = [];
    public $list_model = [];
    public $list_size = [];
    public $list_defect = [];


//    public $select_work = "";

    public function __construct($params = null)
    {
        parent::__construct($params);

        $userlogin = $_SESSION['userlogin'];

        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT employee_id, emp_name FROM employees e WHERE e.disabled=false AND e.login = " . "'" . $userlogin . "'";
        $rs = $conn->Execute($sql);
//        print_r($rs->fields);

        if($rs->fields == false){
            App::Redirect("\\App\\Pages\\Main");
            die();
        }

        $emp_id = $rs->fields['employee_id'];
        $emp_name = $rs->fields['emp_name'];
        $this->employee_name = $emp_name;
        $this->emp_id = $emp_id;

//        $query = "SELECT p.id as pasport_id, CONCAT(p.name, ', ', p.size) as name, p.comment, k.work as type_work,
//                  m.typework_id, m.detail, m.id as master_id, k.price, k.id as work_id, m.emp_id
//                  FROM masters m, kindworks k, model md, pasport p
//                  WHERE m.emp_id = " . $emp_id. " AND k.id = m.typework_id AND md.in_work = true AND p.id = md.pasport_id AND m.finished = false";


//        $query = "SELECT pmm.id as pasport_id, pmm.name, pmm.size, pmm.comment, pmm.masters_id, pmm.typework_id as work_id, pmm.detail, k.work as type_work
//                  FROM (SELECT pm.id, pm.name, pm.size, pm.comment, pm.masters_id, m.typework_id, m.detail
//                  FROM (SELECT p.id, p.name, p.size, p.comment, md.id as masters_id
//                  FROM pasport p, model md WHERE p.id = md.pasport_id AND md.in_work = true AND md.finished = false) as pm
//                  LEFT JOIN masters m ON m.pasport_id=pm.id and m.emp_id='{$emp_id}') as pmm, kindworks k WHERE pmm.typework_id = k.id";

        $query = "SELECT pmm.id as pasport_id, pmm.name, pmm.size, pmm.comment, pmm.master_id, pmm.typework_id as work_id, pmm.detail, k.work as type_work 
                  FROM (SELECT pm.id, pm.name, pm.size, pm.comment, m.typework_id, m.detail, m.id as master_id 
                  FROM (SELECT p.id, p.name, p.size, p.comment 
                  FROM pasport p, model md WHERE p.id = md.pasport_id AND md.in_work = true AND md.finished = false) as pm 
                  LEFT JOIN masters m ON m.pasport_id=pm.id and m.emp_id='{$emp_id}') as pmm, kindworks k WHERE pmm.typework_id = k.id";

        $rs = $conn->Execute($query);
        $type_works = [];
        foreach ($rs as $r){
            $pid = $r['pasport_id'];
            $fnd = false;

            foreach ($this->listWorkEmployees as $employee) {
                if ($employee->getID() == $pid) {
                    $employee->typework[$r['type_work']] = $r['detail'];
                    $employee->master_id[$r['type_work']] = $r['master_id'];
                    $employee->made_work[$r['type_work']] = $r['detail'];
                    $fnd = true;
                }
            }
            if ($fnd == false) {
                $this->listWorkEmployees[] = new WorksMaster($r['pasport_id'], $r['name'], $r['type_work'],
                    $r['detail'], $r['comment'], $r['master_id'], $r['detail']);
            }
            if (in_array($r['type_work'], $type_works) == false) {
                $type_works[$r['work_id']] = $r['type_work'];
            }

        }

        $this->add(new Label('message'));
        if(isset($_SESSION['saveSizeCount']) == true){
            $this->message->setAttribute('data-msg', $_SESSION['saveSizeCount']);
            unset($_SESSION['saveSizeCount']);
        }
        if(isset($_SESSION['saveConfirmModel']) == true){
            $this->message->setAttribute('data-msg', $_SESSION['saveConfirmModel']);
            unset($_SESSION['saveConfirmModel']);
        }

        $this->add(new Form('masterForm'));
        ksort($type_works);
        $this->masterForm->add(new DropDownChoice('workTypeMaster', $type_works))->onChange($this, 'workTypeMasterOnChange');

        $this->add(new Panel('panelModelMaster'));
        $this->panelModelMaster->add(new DataView('listWorkMaster',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "list_model")),$this,'listWorkMasterOnRow'));

        $this->add(new Label('modelNameSize'));

        $this->add(new Form('tableWorkForm'))->setVisible(false);
//        $this->tableWorkForm->add(new SubmitLink('editDefectModel'))->onClick($this, 'editDefectModelOnClick');

        $this->tableWorkForm->add(new DataView('listWorkModelMaster',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "list_size")), $this, 'listWorkModelMasterOnRow'));
        $this->tableWorkForm->add(new SubmitLink('saveSizeCount'))->onClick($this, 'saveSizeCountOnClick', true);
        $this->tableWorkForm->add(new ClickLink('saveFinishModel'))->onClick($this, 'saveFinishModelOnClick');

        $this->add(new Form('defectForm'))->setVisible(false);
        $this->defectForm->add(new DataView('listDefectModel',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "list_defect")), $this, 'listDefectModelOnRow'));
        $this->defectForm->add(new TextArea('defectInfo'));
        $this->defectForm->add(new SubmitLink('saveDefectModel'))->onClick($this, 'saveDefectModelOnClick');

        $this->add(new Form('finishModelForm'))->setVisible(false);
        $this->finishModelForm->add(new Label('itemSizeRange'));
        $this->finishModelForm->add(new Label('itemConfirm'));
        $this->finishModelForm->add(new ClickLink('saveConfirmModel'))->onClick($this, 'saveConfirmModelOnClick');
        $this->finishModelForm->add(new ClickLink('cancelConfirmModel'))->onClick($this, 'saveConfirmModelOnClick');
    }

    public function workTypeMasterOnChange(){

        $this->message->setAttribute('data-msg', "");

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
        $this->panelModelMaster->listWorkMaster->Reload();
        $this->masterForm->workTypeMaster->setValue($val);

        $this->tableWorkForm->setVisible(false);
        $this->panelModelMaster->setVisible(true);
        $this->defectForm->setVisible(false);
        $this->modelNameSize->setVisible(false);
        $this->finishModelForm->setVisible(false);

        $this->updateAjax(array('workTypeMaster'));
    }

    public function listWorkMasterOnRow($row){
        $item = $row->getDataItem();
        $row->add(new ClickLink('modelName'))->onClick($this, 'modelNameOnClick');
        $row->modelName->setValue($item->model);
    }

    public function modelNameOnClick($sender){
        $owner = $sender->getOwner();
        $item = $owner->getDataItem();
        $type = $this->masterForm->workTypeMaster->getValue();
        $opt = $this->masterForm->workTypeMaster;
        $select = $opt->getOptionList();
        $work = $select[$type];
        $this->current_work_id = $type;
        $this->current_work = $work;
        $this->pasport_id = $item->getID();
        
        $detail = $item->typework[$work];

        $res = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>/i',$item->quantity,$all_size);
        $res = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>/i',$detail,$all_master);

        $conn = \ZDB\DB::getConnect();

        $sql = "SELECT m.detail FROM masters m, kindworks k 
                WHERE m.typework_id = k.id AND m.pasport_id = '{$this->pasport_id}' AND m.emp_id != '{$this->emp_id}' AND k.work = '{$this->current_work}' " ;

        $rs = $conn->Execute($sql);
        $other_masters = [];
        foreach ($rs as $r){
            $other_master = [];
            preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>/i',$r['detail'],$other_master);
            if(count($other_masters) == 0){
                $other_masters[0] = $other_master[2];
            }else{
                for($n = 0; $n < count($other_masters[0]); $n++){
                    $other_masters[0][$n] += $other_master[2][$n];
                }
            }
        }

        array_shift($all_size);
        array_shift($all_master);
        array_shift($all_master);
        $size_master = array_merge($all_size, $all_master, $other_masters);


        $this->list_size = [];

        for($i = 0; $i < count($size_master[0]); $i++){
            $this->list_size[] = new WorksSize($i+1, $size_master[0][$i], $size_master[1][$i], $size_master[1][$i]-$size_master[2][$i]-$size_master[3][$i]);
        }
        $this->tableWorkForm->listWorkModelMaster->Reload();
        $this->modelNameSize->setText($item->model);
        $this->modelNameSize->setVisible(true);

        $this->panelModelMaster->setVisible(false);
        $this->tableWorkForm->setVisible(true);
    }

    public function listWorkModelMasterOnRow(\Zippy\Html\DataList\DataRow $row){
        $item = $row->getDataItem();
        $row->add(new Label('itemSizeModel', $item->size));
        $row->add(new TextInput('itemCountModel'));

        $row->add(new Label('itemTotalModel', $item->total_quantity));
        $row->add(new Label('itemRemainModel', $item->master_quantity));
        $row->add(new ClickLink('itemEditDefect'))->onClick($this, 'itemEditDefectOnClick');
//        $row->add(new TextInput('itemCountModel', new \Zippy\Binding\PropertyBinding($item, 'itemCountModel')));
    }

    public function saveSizeCountOnClick($sender){
        $work_master = $this->tableWorkForm->listWorkModelMaster->getChildComponents();
        $arr = [];
        $item_count = "itemCountModel_";
        $item_size = "itemSizeModel_";
        $fields = false;
        foreach ($work_master as $wm){
            $brr = $wm->getChildComponents();
            $kol = $brr[$item_count . $wm->getNumber()]->getText();
            if($kol != "" && $kol != 0) $fields = true;
            $sz = $brr[$item_size . $wm->getNumber()]->getText();
            $arr[$sz] = $kol;
        }

        if($sender->id == 'saveSizeCount' && $fields == true){
            $conn = \ZDB\DB::getConnect();
            foreach ($this->listWorkEmployees as $emp){
                if($emp->getID() == $this->pasport_id){
                    $detail = $emp->typework[$this->current_work];
                    $master_id = $emp->master_id[$this->current_work];
//                    $made_work = $emp->made_work[$this->current_work];
                    break;
                }
            }

            $res1 = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>/i',$detail,$match1);
            $res2 = preg_match('/\<master\>.*?\<\/work\>/i',$detail,$match2);
//            $res3 = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<done\>([0-9]+)\<\/done\>/i',$made_work,$match3);
            $drr = [];
            for($i = 0; $i < count($match1[1]); $i++){
                $drr[$match1[1][$i]] = $match1[2][$i];
            }
            $write_detail = "";
            foreach ($drr as $k=>$v){
                $sum = intval($v) + intval($arr[$k]);
                $write_detail .= "<size>" . $k . "</size>" . "<quantity>" . $sum . "</quantity>";
            }

//            $str_upd_made = "";
//            $done_works = array_combine($match3[1], $match3[2]);
//            foreach ($done_works as $dk=>$dv){
//                if(array_key_exists($dk, $arr) == true){
//                    $sum_works = intval($done_works[$dk]) + intval($arr[$dk]);
//                    $str_upd_made .= "<size>" . $dk . "</size>" . "<done>" . $sum_works . "</done>";
//                }
//            }

            $full_detail = $match2[0] . $write_detail;
            $sql = "UPDATE masters SET detail = '{$full_detail}' WHERE id = '{$master_id}'";
            $rs = $conn->Execute($sql);

            $send_txt = $this->current_work . ", количество работ изменено";
            session_start();
            $_SESSION['saveSizeCount'] = $send_txt;
            App::Redirect("\\App\\Pages\\MasterPage");
        }
    }

    public function editDefectModelOnClick($sender){
        $this->tableWorkForm->setVisible(false);
        $this->defectForm->setVisible(true);

        $this->list_defect = [];
        $defects = ["Брак на коже", "Моя вина", "Клей", "Другое, вызвать Администратора"];

        for($i = 0; $i < count($this->list_size); $i++){
            $id = $this->list_size[$i]->getID();
            $sz = $this->list_size[$i]->size;
            $defect = "";
            if(count($defects) > $i) $defect = $defects[$i];
            $this->list_defect[] = new WorksSize($id, $sz, "", "", $defect);
        }
        $this->defectForm->listDefectModel->Reload();
    }

    public function saveDefectModelOnClick($sender)
    {
        $isSelect = false;
        if($sender->id == "saveDefectModel"){
            $listDefectModel = $this->defectForm->listDefectModel->getChildComponents();
            $work = $this->current_work;
            $work_id = $this->current_work_id;
            $emp_id = $this->emp_id;
            $size_defect = "";
            $text = "Описание: ";


            foreach($listDefectModel as $elem){
                $defects = $elem->getChildComponents();
                $sel = false;
                $desc = "";
                foreach($defects as $key=>$value){
                    if(str_starts_with($key, "defectSize") == true){
                        $size_defect = $value->getText();
                    }else if(str_starts_with($key, "defectSelect") == true){
                        $sel = $value->getValue();

                    }else if(str_starts_with($key, "defectDescribe") == true){
                        $desc = $value->getText();
                    }
                }
                if($sel == true){
                    $text .= $desc . ". ";
                    $isSelect = true;
                }
            }
            $defectInfo = $this->defectForm->defectInfo->getText();
            if($isSelect == true){
                $detail = "<master>" . $this->employee_name . "</master>" .
                    "<work>" . $work . "</work>" . "<size>" . $size_defect . "</size>" .
                    "<work_id>" . $work_id . "</work_id>" . "<emp_id>" . $emp_id . "</emp_id>" . "<defect>" . $text . "</defect>";
                $conn = \ZDB\DB::getConnect();
                $sql = "SELECT id as model_id FROM model WHERE pasport_id = " . $this->pasport_id;
                $rs = $conn->Execute($sql);

                $model_id = $rs->fields['model_id'];

                date_default_timezone_set("Europe/Moscow");
                $date = date("Y-m-d H:i:s");
                $sql = "INSERT INTO defect_model(model_id, monitor, detail, created) VALUES(" . "'" . $model_id . "'" .
                    "," . "'" . $defectInfo . "'" . "," . "'" . $detail . "'" . "," . "'" . $date . "'" . ")";
//                $sql = "INSERT INTO defect_model(model_id, monitor, detail, created)
//                        VALUES()";
                $res = $conn->Execute($sql);
            }
        }

        if($isSelect == true){
            $this->defectForm->defectInfo->setText('');
            $this->defectForm->setVisible(false);
            $this->tableWorkForm->setVisible(true);
        }else{

        }

    }

    public function itemEditDefectOnClick($sender)
    {
//        $send = $sender;
        $item = $sender->getOwner()->getDataItem();
        $sz_defect = $item->size;
        $this->list_defect = [];
        $defects = ["Брак на коже", "Моя вина", "Клей", "Другое, вызвать Администратора"];

        foreach ($defects as $key=>$defect){
            $this->list_defect[] = new WorksSize($key + 1, $sz_defect, "", "", $defect);
        }
        $this->defectForm->listDefectModel->Reload();
        $this->tableWorkForm->setVisible(false);
        $this->defectForm->setVisible(true);
    }

    public function listDefectModelOnRow($row)
    {
        $item = $row->getDataItem();
        if($item->id == 1){
            $row->add(new Label('defectSize', $item->size));
        }
        $row->add(new Label('defectDescribe', $item->defect));
        $row->add(new CheckBox('defectSelect'));
    }

    public function saveFinishModelOnClick($sender)
    {
        $text = $this->modelNameSize->getText();
        $sizeRange = explode(",", $text);
        $tmp = $this->masterForm->workTypeMaster;//->getValue();
        $value = $tmp->getValue();
        $typeWorks = $tmp->getOptionList();
        $typeWork = $typeWorks[intval($value)];

        $this->finishModelForm->itemSizeRange->setText($sizeRange[1]);
        $this->finishModelForm->itemConfirm->setText($typeWork . " по данной модели выполнена");
        $this->tableWorkForm->setVisible(false);
        $this->finishModelForm->setVisible(true);
    }

    public function saveConfirmModelOnClick($sender)
    {
        if($sender->id == 'saveConfirmModel'){
            $conn = \ZDB\DB::getConnect();
            $sql = "UPDATE typework SET finished = true WHERE pasport_id = '{$this->pasport_id}' AND type_work = '{$this->current_work}'";
            $rs = $conn->Execute($sql);
            $send_txt = $this->current_work . " данной модели завершена";
            session_start();
            $_SESSION['saveConfirmModel'] = $send_txt;
            App::Redirect("\\App\\Pages\\MasterPage");
        }
        $this->finishModelForm->setVisible(false);
        $this->tableWorkForm->setVisible(true);
    }
}

class WorksMaster implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $model;
    public $typework = [];
//    public $detail= [];
    public $quantity;
    public $master_id = [];
    public $made_work = [];

    public function __construct($id, $model, $typework, $detail, $quantity, $master_id, $made_work)
    {
        $this->id = $id;
        $this->model = $model;
        $this->typework[$typework] = $detail;
//        $this->detail[] = $detail;
        $this->quantity = $quantity;
        $this->master_id[$typework] = $master_id;
        $this->made_work[$typework] = $made_work;
    }

    public function getID() { return $this->id; }
}

class WorksSize implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $size;
    public $total_quantity;
    public $master_quantity;
    public $defect;
    public $select;

    public function __construct($id, $size, $total_quantity, $master_quantity, $defect = "", $select=false)
    {
        $this->id = $id;
        $this->size = $size;
        $this->total_quantity = $total_quantity;
        $this->master_quantity = $master_quantity;
        $this->defect = $defect;
        $this->select = $select;
    }
    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->id . ", " . $this->size . ", " . $this->defect;
    }

    public function getID() { return $this->id; }
}

/*
 * SELECT m.id, m.detail, t.type_work, m.finished FROM masters m, typework t, model md, pasport p WHERE m.emp_id = 4 AND t.id = m.typework_id AND md.pasport_id = t.pasport_id AND md.in_work = true AND p.id = md.pasport_id AND m.finished = false
 * */
/*
 *select m.id,m.pasport_id, m.typework_id,m.emp_id,m.detail FROM masters m WHERE m.emp_id = 4
 *
 * SELECT p.id, p.name, p. size, m.typework_id, m.emp_id, m.detail from pasport p, model md
 * LEFT JOIN masters m ON p.id = m.pasport_id AND m.emp_id=4 AND md.in_work = true

 */