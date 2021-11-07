<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 29.07.2021
 * Time: 23:28
 */

namespace App\Pages;

use App\Application as App;
use App\Entity\Kind;
use App\Entity\WorkModel;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use App\Helper;
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
    public $workers = [];
    public $worklists = [];
    public $employeelists = [];
    public $work_id;
    public $size;
    public $add_work = [];
    public $pasportID = "";

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
        $sql = "select p.id as id, p.name as name, p.size as size, m.in_work as in_work 
                from model as m, pasport as p where p.id = m.pasport_id and m.finished = false";

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

        $this->add(new Form('filterwork'))->onSubmit($this, 'OnFilterWork');
        $this->filterwork->setVisible(false);
        $this->filterwork->add(new CheckBox('showdiswork'));

        $this->filterwork->add(new TextInput('searchkeywork'));
        $catlist_work = array();
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT pa_id, pa_name FROM parealist";
        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
            $catlist_work[$r['pa_id']] = $r['pa_name'];
        }
        $this->filterwork->add(new DropDownChoice('searchcatwork', $catlist_work, 0));

        $this->add(new Form('worktable'))->setVisible(false);
//        $this->worktable->add(new DataView('worklist',
//            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"worklists")),$this,'worklistOnRow'));

        $this->worktable->add(new DataView('worklist', new ItemDataWorkModel($this), $this, 'worklistOnRow'));

        $this->worktable->add(new SubmitButton('saveProduct'))->onClick($this, 'saveProductOnClick', true);
        $this->worktable->add(new Button('cancelProduct'))->onClick($this, 'cancelProductOnClick');
        $this->worktable->worklist->setPageSize(Helper::getPG());
        $this->worktable->add(new \Zippy\Html\DataList\Paginator('pagwork', $this->worktable->worklist));


        $this->add(new Form('filteremp'))->onSubmit($this, 'OnFilterEmp');
        $this->filteremp->setVisible(false);
        $this->filteremp->add(new CheckBox('showdisemp'));
        $this->filteremp->add(new TextInput('searchkeyemp'));

        $this->add(new Form('employeetable'))->setVisible(false);
        $this->employeetable->add(new Label('employeework', 'Список работников'));
        $this->employeetable->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->employeetable->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
//        $this->employeetable->add(new DataView('employeelist',
//            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"employeelists")),$this,'employeelistOnRow'));//->Reload();
        $this->employeetable->add(new DataView('employeelist', new ItemDataWorkEmp($this), $this, 'employeelistOnRow'));
        $this->employeetable->employeelist->setPageSize(Helper::getPG());
        $this->employeetable->add(new \Zippy\Html\DataList\Paginator('pagemp', $this->employeetable->employeelist));
        /****************************************/

        $this->add(new Form('addWorkEmp'))->setVisible(false);
        $this->addWorkEmp->add(new Label('displaymodel'));
        $this->addWorkEmp->add(new DropDownChoice('addwork'));
        $this->addWorkEmp->add(new DropDownChoice('addemployee'));
        $this->addWorkEmp->add(new SubmitButton('savenewwork'))->onClick($this, 'saveNewWorkOnClick', true);
        $this->addWorkEmp->add(new Button('cancelnewwork'))->onClick($this, 'cancelNewWorkOnClick');

        $this->add(new Form('updateDefectForm'))->setVisible(false);
        $this->updateDefectForm->add(new Label('showdefectmodel'));
        $this->updateDefectForm->add(new CheckBox('checkUpdate'));
        $this->updateDefectForm->add(new SubmitButton('saveUpdate'))->onClick($this, 'saveUpdateOnClick');
        $this->updateDefectForm->add(new Button('cancelUpdate'))->onClick($this, 'cancelUpdateOnClick');

        $this->add(new Label('detailProductionDefect'));
        $this->detailProductionDefect->setAttribute('style', 'display: none');
        $this->add(new Label('detailDefect'));
    }

    public function listOnRow($row){
        $item = $row->getDataItem();
//        $row->add(new Label('modelName',$item->modelName . ', ' . $item->size));
        $row->add(new ClickLink('modelName'))->onClick($this, 'modelNameOnClick'); //,$item->modelName . ', ' . $item->size));
        $row->modelName->setValue($item->modelName . ', ' . $item->size);
        $row->add(new ClickLink('modelWork'))->onClick($this, 'modelWorkOnClick');
        if($item->in_work == true){
            $row->modelWork->setAttribute('class', 'btn btn-outline-secondary disabled');
            $row->modelWork->setValue("В работе");
            $row->modelName->setAttribute('class', 'btn btn-outline-success model');
            $row->modelName->setAttribute('data-model_id', $item->id);
        }
        $row->add(new ClickLink('modelUpdate'))->onClick($this, 'modelUpdateOnClick');
        $row->add(new ClickLink('modelCancel'))->onClick($this, 'modelCancelOnClick');
        if($item->in_work == false){
            $row->modelCancel->setAttribute('class', 'btn btn-outline-secondary disabled');
            $row->modelUpdate->setAttribute('class', 'btn btn-outline-secondary disabled');
        }
    }

    public function modelNameOnClick($sender){
        $item = $sender->getOwner()->getDataItem();
        $this->detailProductionDefect->setAttribute('style', 'display: none');
        if($item->in_work == false) return false;

        $this->modelID = $item->id;
        $conn = \ZDB\DB::getConnect();
        $sql_w = "SELECT id, work FROM kindworks ORDER BY work";
        $rs = $conn->Execute($sql_w);
        $works = [];
        foreach ($rs as $r){
            $works[$r['id']] = $r['work'];
        }

        $sql_e = "SELECT employee_id as emp_id, emp_name FROM employees WHERE disabled = false ORDER BY emp_name";
        $rs = $conn->Execute($sql_e);
        $emps = [];
        foreach ($rs as $r){
            $emps[$r['emp_id']] = $r['emp_name'];
        }

        $this->addWorkEmp->addwork->setOptionList($works);
        $this->addWorkEmp->addemployee->setOptionList($emps);

        $this->addWorkEmp->displaymodel->setText($item->modelName . ", " . $item->size);
        $this->detailProduction->setVisible(false);
        $this->addWorkEmp->setVisible(true);
    }

    public function modelWorkOnClick($sender){
        $item = $sender->getOwner()->getDataItem();
        $pasport_id = $item->getID();
        $this->modelID = $pasport_id;
        $this->size = $item->size;

        $this->detailProductionDefect->setAttribute('style', 'display: none');
        session_start();
        if(isset($_SESSION['workemp']) == true){
            unset($_SESSION['workemp']);
        }
        if(isset($_SESSION['kindwork']) == true){
            unset($_SESSION['kindwork']);
        }
        $_SESSION['workemp'] = [];
//        $conn = \ZDB\DB::getConnect();
//        $sql = "SELECT * FROM kindworks k WHERE k.id IN (SELECT ExtractValue(pt.detail,'/work') as id from pasport_tax pt
//        WHERE pt.pasport_id = '{$model_id}' AND pt.qty_material = false)";
//        $rs = $conn->Execute($sql);
//
//        foreach ($rs as $r){
//            $this->worklists[] = new WorkType($r['id'], $r['work']);
//        }

        $this->filterwork->clean();
        $this->filterwork->setVisible(true);
        $this->filterwork->setAttribute('pasport_id', $pasport_id);
        $this->worktable->worklist->Reload();

        $this->detailProduction->setVisible(false);
        $this->worktable->setVisible(true);
    }
    public function OnFilterWork($sender){
        $this->worktable->worklist->Reload();
    }
    public function OnFilterEmp($sender){
        $this->employeetable->employeelist->Reload();
    }
    public function worklistOnRow(\Zippy\Html\DataList\DataRow $row){
        $item = $row->getDataItem();
        $row->add(new Label('work_name', $item->work));
        $row->add(new ClickLink('work_select'))->onClick($this, 'selectWorkerOnClick');
        $row->add(new Label('work_master', new \Zippy\Binding\PropertyBinding($item, 'show')));
        $id = $item->id;
        if(array_key_exists($id, $_SESSION['workemp']) == true){
            $str_w = "";
            foreach ($_SESSION['workemp'][$id] as $w){
                $str_w .= $w . ",";
            }
            $str_w = substr($str_w, 0, -1);
            $row->work_master->setText($str_w);
        }

    }

    public function selectWorkerOnClick($sender){
        $work = $sender->getOwner()->getDataItem();
        $this->work_id = $work->id;
        $_SESSION['workemp'][$work->id] = [];
//        $w_txt = $this->employeetable->employeework->getText();
        $this->employeetable->employeework->setText('Список работников' . ", " . $work->work);
        $this->employeetable->employeework->setAttribute('work_id', $work->id);
//        $this->filterwork->clean();
//        $this->employeetable->employeelist->Reload();
//        $conn = \ZDB\DB::getConnect();
//        $sql = "SELECT e.employee_id as emp_id, e.login, e.emp_name FROM employees e WHERE e.disabled = false";
//        $rs = $conn->Execute($sql);
//
//        $this->employeelists = [];
//
//        foreach ($rs as $r){
//            $this->employeelists[] = new EmpWork($r['emp_id'], $r['login'], $r['emp_name'], false);
//        }
//        foreach ($this->worklists as $wrkl){
//            if($this->work_id == $wrkl->id){
//                $wrkl->show = "";
//            }
//        }

        $this->filteremp->clean();
        $this->employeetable->employeelist->Reload();
        $this->filterwork->setVisible(false);
        $this->filteremp->setVisible(true);

        $this->worktable->setVisible(false);
        $this->employeetable->setVisible(true);
    }

    public function employeelistOnRow(\Zippy\Html\DataList\DataRow $row){
        $item = $row->getDataItem();
        $row->add(new Label('emp_name', $item->emp_name));
        $row->add(new Label('emp_login', $item->login));
        $row->add(new CheckBox('emp_select', new \Zippy\Binding\PropertyBinding($item, 'select')))->onChange($this, 'checkOnSelect', true);
        $work_id = $this->employeetable->employeework->getAttribute('work_id');
        $id = $item->employee_id;
        if(array_key_exists($id, $_SESSION['workemp'][$work_id]) == true){
            $row->emp_select->setChecked(true);
        }
    }

    public function checkOnSelect($sender)
    {
        $items = $sender->getOwner()->getDataItem();
        $chk = $sender->isChecked();
        $emp_id = $items->getID();
        $item = $items->getData();
        $work_id = $this->employeetable->employeework->getAttribute('work_id');
        if($chk == true){
            $_SESSION['workemp'][$work_id][$emp_id] = $item['emp_name'];
        }else{
            if(array_key_exists($emp_id, $_SESSION['workemp'][$work_id]) == true){
                unset($_SESSION['workemp'][$work_id][$emp_id]);
            }
        }

//        foreach ($this->employeelists as $empl){
//            if($empl->emp_id == $emp_id){
//                $empl->select = $chk;
//            }
//        }
//        foreach ($this->worklists as $wrkl){
//
//            if($wrkl->getID() == $this->work_id){
//                $wrkl->emp[$emp_id] = $items->emp_name;
//                $wrkl->show .= $items->emp_name . ", ";
//            }
//        }
        $this->updateAjax(array('emp_select'));
    }

    public function saveOnClick($sender){
        $work_id = $this->work_id;
        $this->worktable->worklist->Reload();
        $this->filterwork->setVisible(true);
        $this->worktable->setVisible(true);
        $this->employeetable->setVisible(false);
        $this->filteremp->setVisible(false);
    }

    public function cancelOnClick($sender) {
        $this->filterwork->setVisible(true);
        $this->worktable->setVisible(true);
        $this->employeetable->setVisible(false);
        $this->filteremp->setVisible(false);
    }

    public function saveProductOnClick($sender){
        $model_id = $this->modelID;
        $size = $this->size;
        $arr_size = explode("-", $size);
        $txt_size = "";
        for($i = intval(trim($arr_size[0])); $i <= intval(trim($arr_size[1])); $i++){
            $rnd = 0;//mt_rand(0, 20);
            $txt_size .= "<size>" . $i . "</size>" . "<quantity>" . $rnd . "</quantity>";
        }
        $conn = \ZDB\DB::getConnect();

        $work_emp = $_SESSION['workemp'];
        $work_id = array_keys($work_emp);
        $count_works = count($work_id);
//        $str_work_in = "'" . implode("','", $work_id) . "'";

        $sql = "SELECT pt.detail as work_id, pt.model_item as workname FROM pasport_tax pt 
                WHERE pt.qty_material = false AND pasport_id = '{$model_id}'"; // AND pt.id IN(" . $str_work_in . ")"
        $rs = $conn->Execute($sql);

        $is_work = true;
        $pasport_work = [];
        foreach ($rs as $r){
            preg_match('/\<work\>([0-9 ]+)\<\/work\>/i', $r['work_id'], $match);
            $pasport_work[$match[1]] = $r['workname'];
        }

        $count_works_pasport = count($pasport_work);
        if($count_works != $count_works_pasport) $is_work = false;

        if($is_work == true){
            $upd = "UPDATE model SET in_work = true WHERE pasport_id = " . $model_id;
            $conn->Execute($upd);
            foreach ($work_emp as $wrk=>$emps){
                foreach ($emps as $empid=>$empname){
//                    $txt_size = "";
//                    for($i = intval(trim($arr_size[0])); $i <= intval(trim($arr_size[1])); $i++){
//                        $rnd = mt_rand(1, 15);
//                        $txt_size .= "<size>" . $i . "</size>" . "<quantity>" . $rnd . "</quantity>";
//                    }
                    $detail = "<master>" . $empname . "</master>" . "<work>" . $pasport_work[$wrk] . "</work>" . $txt_size;
                    $sql = "INSERT INTO masters(typework_id, emp_id, pasport_id, detail)
                            VALUES ('{$wrk}', '{$empid}', '{$model_id}', '{$detail}')";
                    $conn->Execute($sql);
                }
            }
//            foreach ($this->worklists as $wrkl){
//                $emps = $wrkl->emp;
//                foreach ($emps as $key=>$emp){
//                    $txt_size = "";
//                    for($i = intval(trim($arr_size[0])); $i <= intval(trim($arr_size[1])); $i++){
//                        $rnd = mt_rand(1, 15);
//                        $txt_size .= "<size>" . $i . "</size>" . "<quantity>" . $rnd . "</quantity>";
//                    }
//                    $detail = "<master>" . $emp . "</master>" . "<work>" . $wrkl->work . "</work>" . $txt_size;
//                    $sql = "INSERT INTO masters(typework_id, emp_id, pasport_id, detail)
//                            VALUES ('{$wrkl->id}', '{$key}', '{$model_id}', '{$detail}')";
//                    $conn->Execute($sql);
//                }
//            }
            $js2 = "
                 let orig = window.location.origin;
                 window.location = orig + '/index.php?p=/App/Pages/Production'              
                ";
            $this->updateAjax(array(), $js2);
        }else{
            $msg = "Не все работы выбраны.";
            $js = "
                    $('#model').append(\"<div style='margin: 10px 5px'><p style='color: darkred; font-size: 1.5em'>{$msg}</p></div>\");
                    $('#model').children().fadeOut(3000, \"linear\", function(){\$('#model').children().remove()} );
                    ";
            $this->updateAjax(array(), $js);
        }
    }

    public function saveNewWorkOnClick($sender){
        $model_id = $this->modelID;
        $work = $this->addWorkEmp->addwork->getValue();
        $emp = $this->addWorkEmp->addemployee->getValue();
        if(intval($work) == 0 || intval($emp) == 0){
            $msg = "Не указан работник или вид работы."; // style='margin: 10px 5px' style='color: darkred; font-size: 1.5em'
            $js = "
                    $('#model').append(\"<div class='alert alert-primary' role='alert'><p style='font-size: 1.25em'>{$msg}</p></div>\");
                    $('#model').children().fadeOut(3000, \"linear\", function(){\$('#model').children().remove()} );
                    ";
            $this->updateAjax(array(), $js);
        }
        $work_name = $this->addWorkEmp->addwork->getOptionList()[$work];
        $emp_name = $this->addWorkEmp->addemployee->getOptionList()[$emp];
        foreach ($this->items as $item){
            if($item->id == $model_id){
                $size = $item->size;
                break;
            }
        }
        $str_size = explode("-", $size);
        $txt_size = '';
        for($i = intval($str_size[0]); $i <= intval($str_size[1]); $i++){
            $txt_size .= '<size>' . $i . '</size>' . '<quantity>0</quantity>';
        }
        $detail = '<master>' . $emp_name . '</master>' . '<work>' . $work_name . '</work>' . $txt_size;
        $conn = \ZDB\DB::getConnect();
        $sql = "INSERT INTO masters(typework_id, emp_id, pasport_id, detail) 
                VALUES ('{$work}', '{$emp}', '{$model_id}', '{$detail}')";
        $conn->Execute($sql);
        $js2 = "
                 let orig = window.location.origin;
                 window.location = orig + '/index.php?p=/App/Pages/Production'              
                ";
        $this->updateAjax(array(), $js2);
    }

    public function cancelNewWorkOnClick(){
        $this->addWorkEmp->setVisible(false);
        $this->detailProduction->setVisible(true);
    }

    public function cancelProductOnClick($sender){
        $this->worklists = [];
        $this->filterwork->setVisible(false);
        $this->worktable->setVisible(false);
        $this->detailProduction->setVisible(true);
    }

    public function modelUpdateOnClick($sender){
        $item = $sender->getOwner()->getDataItem();
//        $in_work = $item->in_work;
        $this->pasportID = $item->id;
        $this->updateDefectForm->showdefectmodel->setText($item->modelName . ", " . $item->size);

        $this->detailProductionDefect->setAttribute('style', 'display: none');

        $this->detailProduction->setVisible(false);
        $this->updateDefectForm->setVisible(true);
    }
    public function cancelUpdateOnClick(){
        $this->detailProduction->setVisible(true);
        $this->updateDefectForm->setVisible(false);
    }
    public function saveUpdateOnClick($sender){
        $select = $sender->getOwner()->getComponent('checkUpdate')->isChecked();
        $pasport_id = $this->pasportID;

        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT p.comment, d.id as defect_id, d.detail, m.defect 
                FROM pasport p, model m, defect_model d 
                WHERE p.id=m.pasport_id AND d.model_id = m.id AND d.status = false AND p.id = " . $pasport_id;

        $rs = $conn->Execute($sql);
        $msg = " Нет обновлений по браку.";
        if($rs->fields != false) {
            $defect_obj = new \stdClass();
            foreach ($rs as $r) {
                $defect_obj->comment = $r['comment'];
                $defect_obj->detail_defect[] = $r['detail'];
                $defect_obj->defect_id[] = $r['defect_id'];
                $defect_obj->defect_count = $r['defect'];
            }

            $res = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>,/i',$defect_obj->comment,$match);
            $arr_size_qty = [];
            if($res != null && $res != false){
                $arr_size_qty = array_combine($match[1], $match[2]);
            }

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
            if($select == true) {
                $str_comment = "";
                foreach ($arr_size_qty as $k1 => $v1) {
                    $str_comment .= "<size>" . $k1 . "</size>" . "<quantity>" . $v1 . "</quantity>" . ",";
                }
                $sql = "UPDATE pasport SET comment = '{$str_comment}' WHERE pasport.id = " . $pasport_id;
                $conn->Execute($sql);
            }

            $defect_id = implode("','", $defect_obj->defect_id);
            $sql_defect = "UPDATE defect_model SET status = true WHERE id IN('{$defect_id}')";
            $conn->Execute($sql_defect);

            $defect_count = $defect_count_sz;
            if($defect_obj->defect_count != null) $defect_count += $defect_obj->defect_count; // + $defect_count_sz;
            $sql_model_defect = "UPDATE model SET defect = '{$defect_count}' WHERE pasport_id = " . $pasport_id;
            $conn->Execute($sql_model_defect);

            $msg = "Обновлено количество моделей " . $defect_count_sz . ".";

//            $this->detailDefect->setText($msg);
//            $this->detailProductionDefect->setAttribute('style', 'display: block');

        }
        $this->detailDefect->setText($msg);
        $this->detailProductionDefect->setAttribute('style', 'display: block');

        $this->detailProduction->setVisible(true);
        $this->updateDefectForm->setVisible(false);
    }

    public function modelCancelOnClick($sender){
        $pasport_id = $sender->getOwner()->getDataItem()->id;
        //удалить записи в masters, defect_model
        $conn = \ZDB\DB::getConnect();
        $sql_del = "SELECT DISTINCT md.id as model_id FROM masters m, model md 
                    WHERE m.finished = false AND md.pasport_id = m.pasport_id AND m.pasport_id = " . $pasport_id;

        $rs = $conn->Execute($sql_del);

        $model_id = 0;
        foreach ($rs as $r){
            $model_id = intval($r['model_id']);
        }

        $sql = "DELETE FROM masters WHERE pasport_id = " . $pasport_id;
        $conn->Execute($sql);

        $sql_defect = "DELETE FROM defect_model WHERE model_id = " . $model_id;
        $conn->Execute($sql_defect);

        $sql_model = "UPDATE model SET in_work = false WHERE pasport_id = " . $pasport_id;
        $conn->Execute($sql_model);

        App::Redirect("\\App\\Pages\\Production");
    }
}

class ItemDataWorkModel implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

        $form = $this->page->filterwork;
        $where = "1=1";
        $text = trim($form->searchkeywork->getText()); //"";
        $cat = $form->searchcatwork->getValue(); //$cat = 9
        $showdis = $form->showdiswork->isChecked();

        $pasport_id = $form->getAttribute('pasport_id');
        if($pasport_id != null || $pasport_id != ""){
            $conn = \ZDB\DB::getConnect();
            $sql = "SELECT ExtractValue(pt.detail,'/work') as id from pasport_tax pt
            WHERE pt.pasport_id = '{$pasport_id}' AND pt.qty_material = false";
            $rs = $conn->Execute($sql);
            $str_work = "";

            if($showdis == true){
                $works_show = $_SESSION['workemp'];
                if(count($works_show) == 0) $str_work = "'0',";
                foreach ($works_show as $kw=>$vw){
                    $str_work .= "'" . $kw . "'" . ",";
                }
            }else{
                foreach ($rs as $r){
                    $str_work .= "'" . $r['id'] . "'" . ",";
                }
            }

            $str_work = substr($str_work, 0, -1);
            $where = $where . " and id IN(" . $str_work . ") ";
        }

        if ($cat != 0) {
            if ($cat == -1) {
                $where = $where . " and parealist_id=0";
            } else {
                $where = $where . " and parealist_id=" . $cat;
            }
        }

        if ($showdis == true && ($pasport_id == "" || $pasport_id == null)) {
            $kind_arr = $_SESSION['kindwork'];
            $str_id = "";
            foreach ($kind_arr as $key=>$val){
                if($key == 0 || $key == "0") continue;
                $str_id .= "'{$key}'" . ",";
            }
            $str_id = substr($str_id, 0, -1);
            if($str_id == "") $str_id = "'0'";
            $where = $where . " and id IN(" . $str_id . ")";
        } else {
//            $where = $where . " and disabled <> 1";
        }
        if (strlen($text) > 0) {
            if ($p == false) {
                $text = Kind::qstr('%' . $text . '%');
                $where = $where . " and (work like {$text} )  ";
            } else {
                $text = Kind::qstr($text);
                $where = $where . " and (work = {$text} )  ";
            }
        }
        return $where;
    }

    public function getItemCount() {
        return Kind::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
//        $l = Item::find($this->getWhere(true), "itemname asc", $count, $start);
        $l = Kind::find($this->getWhere(true), "work asc", $count, $start);
        $f = Kind::find($this->getWhere(), "work asc", $count, $start);
        foreach ($f as $k => $v) {
            $l[$k] = $v;
        }

        return $l;
    }

    public function getItem($id) {
        return Kind::load($id);
    }

}

class ItemDataWorkEmp implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

        $form = $this->page->filteremp;
        $where = "1=1";
        $text = trim($form->searchkeyemp->getText()); //"";
//        $cat = $form->searchcatemp->getValue();
        $showdis = $form->showdisemp->isChecked();

        if ($showdis == true) {
            $work_id = $this->page->employeetable->employeework->getAttribute('work_id');
            $emp_arr = $_SESSION['workemp'][$work_id];
            $str_id = "";
            foreach ($emp_arr as $ke=>$ve){
                   $str_id .= "'{$ke}'" . ",";
            }
            $str_id = substr($str_id, 0, -1);
            if($str_id == "") $str_id = "'0'";
            $where = $where . " and employee_id IN(" . $str_id . ")";
        } else {
//            $where = $where . " and disabled <> 1";
        }
        if (strlen($text) > 0) {
            if ($p == false) {
                $text = \App\Entity\Employee::qstr('%' . $text . '%');
                $where = $where . " and (emp_name like {$text} )  ";
            } else {
                $text = \App\Entity\Employee::qstr($text);
                $where = $where . " and (emp_name = {$text} )  ";
            }
        }
        return $where;
    }

    public function getItemCount() {
        return \App\Entity\Employee::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $l = \App\Entity\Employee::find($this->getWhere(true), "emp_name asc", $count, $start);
        $f = \App\Entity\Employee::find($this->getWhere(), "emp_name asc", $count, $start);
        foreach ($f as $k => $v) {
            $l[$k] = $v;
        }

        return $l;
    }

    public function getItem($id) {
        return \App\Entity\Employee::load($id);
    }

}


class EmpWork implements \Zippy\Interfaces\DataItem{
    public $emp_id;
    public $login;
    public $emp_name;
    public $select;

    public function __construct($emp_id, $login, $emp_name, $select)
    {
        $this->emp_id = $emp_id;
        $this->login = $login;
        $this->emp_name = $emp_name;
        $this->select = $select;
    }

    public function getID() { return $this->emp_id;}
}

class WorkType implements \Zippy\Interfaces\DataItem{
    public $id;
    public $work;
    public $emp;
    public $show;

    public function __construct($id, $work, $emp=null, $show="")
    {
        $this->id = $id;
        $this->work = $work;
        $this->emp = $emp;
        $this->show = $show;
    }

    public function getID() { return $this->id;}
}

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
