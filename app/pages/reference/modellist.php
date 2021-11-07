<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 02.11.2021
 * Time: 16:04
 */

namespace App\Pages\Reference;

use App\Entity\Customer;
use App\Entity\Employee;
use App\Entity\Model;
use App\Entity\Category;
use App\Entity\Master;
use App\Entity\Passport;
use App\Entity\ProdArea;
use function GuzzleHttp\Psr7\try_fopen;
use Zippy\Html\DataList\Column;
use \Zippy\Html\DataList\ArrayDataSource;
use App\Entity\Service;
use ZCL\DB\EntityDataSource as EDS;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\DataTable;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Panel;
use App\Helper;

class ModelList extends \App\Pages\Base
{
    private $_model = null;
    public $_list = array();

    public function __construct($params = null)
    {
        parent::__construct($params);


        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);

        $this->add(new Form('filtermodel'))->onSubmit($this, 'OnFilterModel');
        $this->filtermodel->setVisible(true);
        $this->filtermodel->add(new CheckBox('showdismodel'));
        $this->filtermodel->add(new TextInput('searchkeymodel'));

        $this->add(new Panel('modeltable'))->setVisible(true);
        $this->modeltable->add(new DataView('modellist', new ItemDataModel($this), $this, 'modelListOnRow'))->Reload();

        $this->modeltable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->add(new Form('modeldetail'))->setVisible(false);
        $this->modeldetail->add(new DropDownChoice('detail_model_pas', Passport::getList()))->onChange($this, "onPassport", true);
        $this->modeldetail->add(new AutocompleteTextInput('detail_model_qty'));
        $this->modeldetail->add(new DropDownChoice('detail_model_order', Customer::getList()))->onChange($this, "onOrder", true);
        $this->modeldetail->add(new ClickLink('addworks'))->onClick($this, 'addWorksOnClick');
        $this->modeldetail->add(new Panel('modelPanel'))->setVisible(false);
        $this->modeldetail->modelPanel->add(new DropDownChoice('model_work', ''))->onChange($this, "onModelWork", true);
        $this->modeldetail->modelPanel->add(new DropDownChoice('model_emp', ''));
        $this->modeldetail->modelPanel->add(new SubmitLink('add_workemp'))->onClick($this, 'addWorkEmpOnClick');
        $this->modeldetail->modelPanel->add(new SubmitLink('del_workemp'))->onClick($this, 'addWorkEmpOnClick');

        $ds = new ArrayDataSource($this, '_list');
        $this->add(new DataTable("report", $ds, true, true));
        $this->report->addColumn(new Column('service_name', 'Вид работы', true));
        $this->report->addColumn(new Column('work_name', 'Работник', true ));
        $this->report->setPageSize(Helper::getPG());
        $this->report->setVisible(false);
        $this->modeldetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->modeldetail->add(new SubmitButton('saveas'))->onClick($this, 'saveOnClick');
        $this->modeldetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');


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
        $this->worktable->add(new DataView('worklist', new DataSourceWork($this), $this, 'workListOnRow'));

        $this->worktable->worklist->setPageSize(Helper::getPG());
        $this->worktable->add(new SubmitButton('saveWork'))->onClick($this, 'saveWorkOnClick');
        $this->worktable->add(new Button('cancelWork'))->onClick($this, 'cancelWorkOnClick');
        $this->worktable->add(new \Zippy\Html\DataList\Paginator('pagwork', $this->worktable->worklist));

        //workers table
        $this->add(new Form('filteremp'))->onSubmit($this, 'OnFilterEmp');
        $this->filteremp->setVisible(false);
        $this->filteremp->add(new CheckBox('showdisemp'));
        $this->filteremp->add(new TextInput('searchkeyemp'));

        $this->add(new Form('employeetable'))->setVisible(false);
        $this->employeetable->add(new Label('employeework', 'Список работников'));
        $this->employeetable->add(new SubmitButton('saveEmp'))->onClick($this, 'saveEmpOnClick');
        $this->employeetable->add(new Button('cancelEmp'))->onClick($this, 'cancelEmpOnClick');

        $this->employeetable->add(new DataView('employeelist', new DataSourceEmp($this), $this, 'employeelistOnRow'));
        $this->employeetable->employeelist->setPageSize(Helper::getPG());
        $this->employeetable->add(new \Zippy\Html\DataList\Paginator('pagemp', $this->employeetable->employeelist));

    }

    public function getModel(){
        return $this->_model;
    }

    public function OnFilterModel($sender){
        $this->modeltable->modellist->Reload();
    }
    public function OnFilterWork($sender){
        $this->worktable->worklist->Reload();
    }
    public function OnFilterEmp($sender){
        $this->employeetable->employeelist->Reload();
    }
    public function onPassport($sender){
        $val = $this->modeldetail->detail_model_pas->getValue();

        if($val != -1){
            $this->modeldetail->detail_model_pas->setValue($val);
        }
        $this->updateAjax(array());
    }
    public function onOrder($sender){
        $val = $this->modeldetail->detail_model_order->getValue();

        if($val != -1){
        }
        $this->updateAjax(array());
    }

    public function addWorksOnClick($sender){
        $val = $this->modeldetail->detail_model_pas->getValue();
        $this->_model = new Model();

        $this->modeldetail->setVisible(false);
        $this->filterwork->setVisible(true);
        $this->filterwork->setAttribute('data_pas', $val);

        $this->worktable->worklist->Reload();
        $this->worktable->setVisible(true);
    }

    public function modelListOnRow($row){
        $item = $row->getDataItem();
        $row->add(new ClickLink('model_name'))->onClick($this, 'modelNameOnClick');
        $row->model_name->setValue($item->name_model);

        $row->add(new Label('model_qty', $item->quantity));
        $row->add(new Label('model_order', Customer::getOne('customer_name', " customer_id = '{$item->order_id}'")));
        $row->add(new Label('model_order_num', $item->order_num));
        $in_work = "Нет";
        if($item->finished == true){
            $in_work = "Завершено";
        }else{
            if($item->in_work == true) $in_work = "Да";
        }
        $row->add(new Label('model_inwork', $in_work));
//        $row->add(new ClickLink('show'))->onClick($this, 'showModelOnClick');
//        $row->add(new ClickLink('edit'))->onClick($this, 'showModelOnClick');
//        $row->add(new ClickLink('delete'))->onClick($this, 'deleteModelOnClick');
        if($item->in_work == true){
            $row->add(new ClickLink('show'))->onClick($this, 'showModelOnClick');
        }
        if($item->in_work == false){
            $row->add(new ClickLink('delete'))->onClick($this, 'deleteModelOnClick');
            $row->add(new ClickLink('edit'))->onClick($this, 'showModelOnClick');
        }
    }

    public function deleteModelOnClick($sender){
        $model = $sender->getOwner()->getDataItem();
        $this->_model = $model;
        $data = $model->getData();
        if($data['disabled'] == false){
            $model->setDisabled(true);
        }
        else{
            $model->setDisabled(false);
        }
        $model->Save();
        $this->filtermodel->clean();
        $this->modeltable->modellist->Reload();
    }

    public function showModelOnClick($sender){
        $model = $sender->getOwner()->getDataItem();
        $this->_model = $model;
        $workemp = $model->workemp;
        $data = $model->getData();

        $this->modeltable->setVisible(false);
        $this->modeldetail->setVisible(true);
        $this->modeldetail->addworks->setVisible(false);
        $this->modeldetail->modelPanel->setVisible(true);

        $this->modeldetail->modelPanel->model_emp->setOptionList(array());

        $pas_id = $data['passport_id'];
        $detail_work = Passport::getOne("detail_work", " id = {$pas_id} ");
        $xml = @simplexml_load_string($detail_work);
        $key_work = [];
        for($i = 0; $i < count($xml->work); $i++){
            $key_work[] = (string)$xml->work[$i];
        }
        $str_work = "'" . implode("','", $key_work) . "'";
        $list_work = Service::findArray("service_name"," service_id IN(" . $str_work . ")", " service_name ");

        $this->report->setVisible(true);
        $this->modeldetail->detail_model_pas->setValue($data['passport_id']);
        $this->modeldetail->detail_model_qty->setText($data['quantity']);
        $this->modeldetail->detail_model_order->setValue($data['order_id']);

        $this->modeldetail->detail_model_pas->setAttribute('disabled', '');
        $this->modeldetail->detail_model_qty->setAttribute('readonly', '');
        $this->modeldetail->detail_model_order->setAttribute('disabled', '');

        if(str_starts_with($sender->id, "show")){
            $this->modeldetail->detail_model_pas->setAttribute('disabled', 'disabled');
            $this->modeldetail->detail_model_qty->setAttribute('readonly', 'readonly');
            $this->modeldetail->detail_model_order->setAttribute('disabled', 'disabled');
        }

        $this->modeldetail->modelPanel->model_work->setOptionList($list_work);

        $this->ReloadData($workemp);
        $this->report->Reload($workemp);
    }

    public function onModelWork(){
        $val = $this->modeldetail->modelPanel->model_work->getValue();
        if($val != -1){
            $list_emps = Employee::findArray('emp_name', " 1=1 ");
            $list_emps["-1"] = " Не указан";
            asort($list_emps);
            $this->modeldetail->modelPanel->model_emp->setValue(0);
            $this->modeldetail->modelPanel->model_emp->setOptionList($list_emps);
        }
        $this->updateAjax(array('model_emp'));
    }

    public function addWorkEmpOnClick($sender){
        $model = $this->getModel();
        $val_w = $this->modeldetail->modelPanel->model_work->getValue();
        $val_e = $this->modeldetail->modelPanel->model_emp->getValue();

        $data = $model->getData();
        $this->modeldetail->detail_model_pas->setValue($data['passport_id']);
        $this->modeldetail->detail_model_order->setValue($data['order_id']);

        $res = $model->isWorkEmp($val_e, $val_w);

        $opt_w = $this->modeldetail->modelPanel->model_work->getOptionList();
        $w = $opt_w[$val_w];
        $opt_e = $this->modeldetail->modelPanel->model_emp->getOptionList();
        $e = $opt_e[$val_e];
        if($sender->id == 'add_workemp'){
            if($res == true){
                $this->setError("У работника " . $e . ", уже есть работа " . $w);
                return;
            }else{
                if($val_e == -1){
                    $this->setError("Не выбран работник для работы " . $w);
                    return;
                }
                $model->setWorkEmps($val_w, $val_e, $e);
                $we = $model->getWorkEmps();
                $this->ReloadData($we);
                $this->report->Reload();
            }
        }
        if($sender->id == 'del_workemp'){
            if($res == true){
                //проверить в таблице мастеров кол-во выполненых работ перед удалением
                $model_id = $model->getID();

                $inworkemp = Master::getMasterInWork($model_id);
                if(array_key_exists($val_e, $inworkemp[$val_w]) == true){
                    $price = $inworkemp[$val_w][$val_e]['price'];
                    if($price != 0){
                        $this->setError("Невозможно удалить работника " . $e . ", у которого есть выполненые работы ");
                        return;
                    }
                }
                $master_id = $inworkemp[$val_w][$val_e]['master_id'];
                Master::delete($master_id);
                $model->delWorkEmps($val_w, $val_e);
                $wed = $model->getWorkEmps();
                $this->ReloadData($wed);
                $this->report->Reload();
            }
        }
    }

    public function modelNameOnClick($sender){
        $workemp = $sender->getOwner()->getDataItem();

    }

    public function workListOnRow($row){
        $md = $this->getModel();
        $item = $row->getDataItem();
        $row->add(new Label('work_name', $item->service_name));
        $row->add(new ClickLink('work_select'))->onClick($this, 'selectWorkerOnClick');
        $row->add(new Label('work_master', new \Zippy\Binding\PropertyBinding($item, 'show')));
        $wid = $item->getID();
        if($md->isWork($wid) == true){
            $arr = $md->getWorkEmps();
            $emps = $arr[$wid];
            $str_emp = "";
            foreach ($emps as $k=>$v){
                $str_emp .= $v . ", ";
            }
            $str_emp = trim($str_emp);
            $str_emp = substr($str_emp, 0, -1);
            $row->work_master->setText($str_emp);
        }

    }

    public function selectWorkerOnClick($sender){
        $work = $sender->getOwner()->getDataItem();
        $work_id = $work->getID();
        $data = $work->getData();

        $this->_model->setWork($work_id);

        $this->employeetable->employeework->setText('Список работников' . ", " . mb_strtolower($data['service_name']));
        $this->employeetable->employeework->setAttribute('work_id', $work_id);

        $this->filteremp->clean();
        $this->employeetable->employeelist->Reload();
        $this->filterwork->setVisible(false);
        $this->filteremp->setVisible(true);

        $this->worktable->setVisible(false);
        $this->employeetable->setVisible(true);
    }

    public function employeelistOnRow(\Zippy\Html\DataList\DataRow $row){
        $md = $this->getModel();
        $item = $row->getDataItem();
        $row->add(new Label('emp_name', $item->emp_name));
        $row->add(new Label('emp_login', $item->login));
        $row->add(new CheckBox('emp_select', new \Zippy\Binding\PropertyBinding($item, 'select')))->onChange($this, 'checkOnSelect', true);
        $work_id = $this->employeetable->employeework->getAttribute('work_id');
        $id = $item->employee_id;
        if($md->isWorkEmp($id, $work_id) == true){
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
            $this->_model->setWorkEmps($work_id, $emp_id, $item['emp_name']);
        }else{
            $this->_model->delWorkEmps($work_id, $emp_id);
        }
        $this->updateAjax(array('emp_select'));
    }

    public function addOnClick($sender){
        $this->filtermodel->setVisible(false);
        $this->modeltable->setVisible(false);
        $this->modeldetail->addworks->setVisible(true);
        $this->modeldetail->modelPanel->setVisible(false);

        $this->modeldetail->detail_model_pas->setAttribute('disabled', '');
        $this->modeldetail->detail_model_qty->setAttribute('readonly', '');
        $this->modeldetail->detail_model_order->setAttribute('disabled', '');

        $this->modeldetail->clean();
        $this->_list = [];
        $this->report->Reload();
        $this->report->setVisible(false);
        $this->modeldetail->setVisible(true);
    }

    public function cancelOnClick($sender){
        $this->filtermodel->setVisible(true);
        $this->modeltable->setVisible(true);
        $this->modeldetail->setVisible(false);
        if($this->_model != null){
//            $this->_model = null;
            $this->modeltable->modellist->Reload();
        }
    }
    public function cancelWorkOnClick($sender){
        $this->modeldetail->setVisible(true);
        $this->filterwork->setVisible(false);
        $this->worktable->setVisible(false);
    }

    public function cancelEmpOnClick($sender) {
        $this->filterwork->setVisible(true);
        $this->worktable->setVisible(true);
        $this->employeetable->setVisible(false);
        $this->filteremp->setVisible(false);
    }

    public function saveEmpOnClick($sender){
        $this->worktable->worklist->Reload();
        $this->filterwork->setVisible(true);
        $this->worktable->setVisible(true);
        $this->employeetable->setVisible(false);
        $this->filteremp->setVisible(false);
    }

    public function ReloadData($workemp){
//        $arr_work_emp = $workemp;
        $key_work = array_keys($workemp);
        $str_work = "'" . implode("','", $key_work) . "'";
        $where = "1=1";
        $where = $where . " and service_id IN (" . $str_work . ") ";
        $list = Service::find($where);

        $area_list = ProdArea::find();

        $this->_list = array();

        foreach ($list as $item) {
            foreach ($workemp as $k=>$v){
                if($item->getID() == $k){
                    $item->work_name = implode(", ", $v);
                    foreach ($area_list as $elem){
                        if($item->area != ''){
                            if($elem->getID() == $item->area){
                                $item->area_name = $elem->pa_name;
                            }
                        }else{
                            $item->area_name = '';
                        }
                    }
                }
            }
            $this->_list[] = $item;
        }
        if($area_list != null){
            $this->report->addColumn(new Column('area_name', 'Производственный участок', true ));
        }
    }

    public function saveWorkOnClick($sender){
        $md = $this->getModel();
        $arr_work_emp = $md->getWorkEmps();
        $this->ReloadData($arr_work_emp);
//        $key_work = array_keys($arr_work_emp);
//        $str_work = "'" . implode("','", $key_work) . "'";
//        $where = "1=1";
//        $where = $where . " and service_id IN (" . $str_work . ") ";
//        $list = Service::find($where);
//
//        $area_list = ProdArea::find();
//
//        $this->_list = array();
//
//        foreach ($list as $item) {
//              foreach ($arr_work_emp as $k=>$v){
//                  if($item->getID() == $k){
//                      $item->work_name = implode(", ", $v);
//                      foreach ($area_list as $elem){
//                          if($item->area != ''){
//                              if($elem->getID() == $item->area){
//                                  $item->area_name = $elem->pa_name;
//                              }
//                          }else{
//                              $item->area_name = '';
//                          }
//                      }
//                  }
//              }
//            $this->_list[] = $item;
//        }
//        if($area_list != null){
//            $this->report->addColumn(new Column('area_name', 'Производственный участок', true ));
//        }

        $this->report->Reload();
        $this->modeldetail->setVisible(true);
        $this->report->setVisible(true);
        $this->filterwork->setVisible(false);
        $this->worktable->setVisible(false);
    }

    public function saveOnClick($sender){
        $md = $this->getModel();
        if($md->getID() == 0){

            $pas_id = $this->modeldetail->detail_model_pas->getValue();
            $option = $this->modeldetail->detail_model_pas->getOptionList();
            $name_model = $option[$pas_id];
            $order_id = $this->modeldetail->detail_model_order->getValue();
            if($pas_id == -1){
                $this->setError("Не указан паспорт.");
                return;
            }
            $md->passport_id = $pas_id;
            $this->_model->name_model = $name_model;
            $this->_model->quantity = $this->modeldetail->detail_model_qty->getText();
            if($order_id != -1){
                $this->_model->order_id = $order_id;
            }
            if(strlen($this->_model->order_num) == 0){
                $this->_model->order_num = Model::getNextOrder();
            }

        }
//        $opt = $this->modeldetail->model_->getOptionList();
        if ($md == null || count($this->_list) == 0) {
            $this->setError("Не выбраны работники для производства модели.");
            return;
        }

        if($sender->id == "save"){//в производство
            $this->_model->in_work = true;
        }

        $this->_model->Save();
        if($this->_model->in_work == true){
            $detail_size = Passport::getOne('detail_size', " id = " . $this->_model->passport_id);
            $xml = @simplexml_load_string($detail_size);
            $size = [];
            for($i = 0; $i < count($xml->size); $i++){
                $size[(string)$xml->size[$i]] = 0;
            }

            $workemp = $this->_model->getWorkEmps();
            $model_id = $this->_model->getID();
            $inworkemp = Master::getMasterInWork($model_id);

            foreach ($workemp as $kwe=>$vwe){
                foreach ($vwe as $ke=>$ve){
                    if(array_key_exists($ke, $inworkemp[$kwe]) == false){
                        $master = new Master();
                        $master->model_id = $model_id;
                        $master->work_id = $kwe;
                        $master->order_num = $this->_model->order_num;
                        $master->sz_qty = $size;
                        $master->emp_id = $ke;
                        $master->Save();
                    }
                }
            }
        }

        $this->modeldetail->setVisible(false);
        $this->_list = [];
        $this->report->setVisible(false);
        $this->modeltable->modellist->Reload();
        $this->modeltable->setVisible(true);
    }
}

class ItemDataModel implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {
        $form = $this->page->filtermodel;
        $where = "1=1";
        $text = trim($form->searchkeymodel->getText()); //"";
        $showdis = $form->showdismodel->isChecked();

        if ($showdis == true) {

        }else{
            $where = $where . " and disabled <> 1";
        }
//
//        if ($showdis == true) {
//            $passport = $this->page->getPassportInst();
//            $materials = $passport->getAllMaterial();
//            $str_id = "";
//            foreach ($materials as $key=>$val){
//                if($key == 0 || $key == "0") continue;
//                $str_id .= "'{$key}'" . ",";
//            }
//            $str_id = substr($str_id, 0, -1);
//            if($str_id == "") $str_id = "'0'";
//            $where = $where . " and item_id IN(" . $str_id . ")";
//        } else {
//            $where = $where . " and disabled <> 1";
//        }
        if (strlen($text) > 0) {
            if ($p == false) {
                $text = Model::qstr('%' . $text . '%');
                $where = $where . " and (name_model like {$text} )  ";
            } else {
                $text = Model::qstr($text);
                $where = $where . " and (name_model = {$text} )  ";
            }
        }
        return $where;
    }

    public function getItemCount() {
        return Model::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $l = Model::find($this->getWhere(true), "name_model asc", $count, $start);
        $f = Model::find($this->getWhere(), "name_model asc", $count, $start);
        foreach ($f as $k => $v) {
            $l[$k] = $v;
        }
        return $l;
    }

    public function getItem($id) {
        return Model::load($id);
    }

}


class DataSourceWork implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

        $form = $this->page->filterwork;
        $where = "1=1";
        $text = trim($form->searchkeywork->getText());
        $passport_id = $form->getAttribute('data_pas');
        $work_detail = Passport::getOne("detail_work", " id = '{$passport_id}' ");
        $showdis = $form->showdiswork->isChecked();

        $cat = $form->searchcatwork->getValue();
        if ($cat != 0) {
            if ($cat == -1) {
//                $where = $where . " and area=0";
            } else {
                $area = \App\Entity\Service::qstr('%' . '<area>' . $cat . '</area>' . '%');
                $where = $where . " and (detail like {$area} ) ";
            }
        }

        if ($showdis == true) {
            $md = $this->page->getModel();
            $emp_arr = $md->getWorkEmps();
            $str_id = "";
            foreach ($emp_arr as $ke=>$ve){
                $str_id .= "'{$ke}'" . ",";
            }
            $str_id = substr($str_id, 0, -1);
            if($str_id == "") $str_id = "'0'";
            $where = $where . " and service_id IN(" . $str_id . ")";
        } else {
            if($work_detail != null){
                $xml = @simplexml_load_string($work_detail);
                $works = $xml->work;
                $wrr = [];
                for($i = 0; $i < count($works); $i++){
                    $wrr[] = (string)$works[$i];
                }
                $str_id = "'" . implode("','", $wrr) . "'";
                $where .= " and service_id IN (" . $str_id . ")";
            }else{
                $where .= " and service_id = 0 ";
            }
        }
        if (strlen($text) > 0) {
            if ($p == false) {
                $text = \App\Entity\Service::qstr('%' . $text . '%');
                $where = $where . " and (service_name like {$text} )  ";
            } else {
                $text = \App\Entity\Service::qstr($text);
                $where = $where . " and (service_name = {$text} )  ";
            }
        }
        return $where;
    }

    public function getItemCount() {
        return \App\Entity\Service::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $l = \App\Entity\Service::find($this->getWhere(true), "service_name asc", $count, $start);
        $f = \App\Entity\Service::find($this->getWhere(), "service_name asc", $count, $start);
        foreach ($f as $k => $v) {
            $l[$k] = $v;
        }

        return $l;
    }

    public function getItem($id) {
        return \App\Entity\Service::load($id);
    }

}

class DataSourceEmp implements \Zippy\Interfaces\DataSource
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
            $md = $this->page->getModel();
            $all_arr = $md->getWorkEmps();
            $emp_arr = $all_arr[$work_id];
//            $emp_arr = $_SESSION['workemp'][$work_id];
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


