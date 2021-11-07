<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 28.10.2021
 * Time: 21:12
 */

namespace App\Pages\Reference;

use App\Entity\Passport;
use App\Entity\Category;
use App\Entity\Item;
use \Zippy\Html\DataList\ArrayDataSource;
use App\Entity\Service;
use ZCL\DB\EntityDataSource as EDS;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Helper as H;

class PassportList extends \App\Pages\Base
{
    private $_passport = null;
    public $sizes = [];
    public $razmer = [];

    public function __construct($params = null)
    {
        parent::__construct($params);

//        $conn = \ZDB\DB::getConnect();
//        $sql = "select id, name_size from sizesrange";
//
//        $rs = $conn->Execute($sql);
//        foreach ($rs as $r){
//            $this->sizes[$r['id']] = $r['name_size'];
//        }

        $this->add(new Form('filterpassport'))->onSubmit($this, 'OnFilterPas');
        $this->filterpassport->setVisible(true);
        $this->filterpassport->add(new CheckBox('showdispas'));

        $this->filterpassport->add(new TextInput('searchkeypas'));

        $this->add(new Panel('passporttable'))->setVisible(true);
        $this->passporttable->add(new DataView('passportlist', new ItemDataPassport($this), $this, 'passportListOnRow'))->Reload();

        $this->passporttable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->add(new Form('passportdetail'))->setVisible(false);
        $this->passportdetail->add(new TextInput('editpas_name'));
        $this->passportdetail->add(new Panel('panelNewModelSize'))->setVisible(false);
        $this->passportdetail->add(new DropDownChoice('editsize', null))->onChange($this, "onSize");

        $this->passportdetail->panelNewModelSize->add(new DataView('newSizeModel',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"razmer")),$this,'listSizeModelOnRow'));

        $this->passportdetail->add(new ClickLink('addmaterials'))->onClick($this, 'addmaterialsOnClick');
        $this->passportdetail->add(new TextArea('editmaterial'))->setVisible(false);
        $this->passportdetail->add(new ClickLink('addworks'))->onClick($this, 'addworksOnClick');
        $this->passportdetail->add(new TextArea('editwork'))->setVisible(false);


        $this->passportdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->passportdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');


        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->setVisible(false);
        $this->filter->add(new CheckBox('showdis'));

        $this->filter->add(new TextInput('searchkey'));
        $catlist = array();
        $catlist[-1] = H::l("withoutcat");
        foreach (Category::getList() as $k => $v) {
            $catlist[$k] = $v;
        }
        $this->filter->add(new DropDownChoice('searchcat', $catlist, 0));

        $this->add(new Form('panelMaterial'))->setVisible(false);
        $this->panelMaterial->add(new DataView('material_list', new ItemDataMaterials($this), $this, 'materialListOnRow'));

        $this->panelMaterial->material_list->setPageSize(H::getPG());
        $this->panelMaterial->add(new \Zippy\Html\DataList\Paginator('pag', $this->panelMaterial->material_list));
        $this->panelMaterial->add(new SubmitButton('saveMaterialTotal'))->onClick($this, 'saveMaterialTotalOnClick');
        $this->panelMaterial->add(new Button('cancelMaterialTotal'))->onClick($this, 'saveMaterialTotalOnClick');
        //форма для списка работ

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

        $this->add(new Form('listWorkForm'))->setVisible(false);
        $this->listWorkForm->add(new DataView('listwork', new ItemDataWorks($this), $this, 'listWorkOnRow'));//->Reload();

        $this->listWorkForm->listwork->setPageSize(H::getPG());
        $this->listWorkForm->add(new \Zippy\Html\DataList\Paginator('pag1', $this->listWorkForm->listwork));
        $this->listWorkForm->add(new SubmitButton('saveWork'))->onClick($this, 'saveWorkOnClick');
        $this->listWorkForm->add(new Button('cancelWork'))->onClick($this, 'saveWorkOnClick');
    }

    public function getPassportInst(){
        return $this->_passport;
    }

    public function OnFilterPas($sender) {
        $this->passporttable->passportlist->Reload();
    }
    public function OnFilter($sender) {
        $this->panelMaterial->material_list->Reload();
    }
    public function OnFilterWork($sender){
        $this->listWorkForm->listwork->Reload();
    }

    public function passportListOnRow($row){
        $item = $row->getDataItem();

        $row->add(new Label('pas_name', $item->name));
        $row->add(new Label('pas_size', $item->size_name));
    }

    public function addOnClick($sender) {
        $this->passporttable->setVisible(false);
        $this->passportdetail->setVisible(true);

        $conn = \ZDB\DB::getConnect();
        $sql = "select id, name_size, detail from sizesrange";

        $rs = $conn->Execute($sql);
        $sizes = [];
        foreach ($rs as $r){
            $sizes[$r['id']] = $r['name_size'];
            $this->sizes[] = new SizePass($r['id'], $r['name_size'], $r['detail']);
        }
        $this->passportdetail->editsize->setOptionList($sizes);
        $this->passportdetail->panelNewModelSize->setVisible(false);
        // Очищаем  форму
        $this->passportdetail->clean();
        $this->_passport = new Passport();
//        $this->_passport = Passport::getInstance();

    }

    public function onSize($sender){
        $val = $this->passportdetail->editsize->getValue();
//        $option = $this->passportdetail->editsize->getOptionList();
        if($val != -1){
            $str_detail = "";
            foreach ($this->sizes as $size){
                if($size->getID() == $val){
                    $str_detail = $size->detail;
                    break;
                }
            }

            $xml = @simplexml_load_string($str_detail);
            $arr_size = $xml->size;
            sort($arr_size);
            $this->razmer = [];
            for($i = 0; $i < count($arr_size); $i++){
                $this->razmer[] = new ModelSize($i+1, (string)$arr_size[$i], 0);
            }

            $this->passportdetail->panelNewModelSize->newSizeModel->Reload();
            $this->passportdetail->editsize->setValue($val);
            $this->passportdetail->panelNewModelSize->setVisible(true);
        }else{
            $this->passportdetail->panelNewModelSize->setVisible(false);
            $this->passportdetail->editsize->setValue($val);
        }
    }

    public function listSizeModelOnRow($row){
        $item = $row->getDataItem();
        $text = $this->passportdetail->editpas_name->getText();
        if(strlen($text) == 0) $text = "Модель";

        $row->add(new Label('namemodel', $text));
        $row->add(new Label('sizemodel', $item->size));
        $row->add(new AutocompleteTextInput('countofsize', 0, 100))->onText($this, 'OnAutoCompleteSize');
    }

    public function OnAutoCompleteSize($sender){
        $text = trim($sender->getText());
        if($text != "" && $text != "0"){
            $sz = $sender->getOwner()->getDataItem();
            $this->_passport->qty[$sz->size] = intval($text);
        }
        return $text;
    }

    public function cancelOnClick($sender) {
        $this->passporttable->setVisible(true);
        $this->passportdetail->setVisible(false);
    }

    public function addmaterialsOnClick($sender){
        $this->passporttable->setVisible(false);
        $this->passportdetail->setVisible(false);
        $this->filter->clean();
        $this->filter->setVisible(true);

        $this->_passport->delAllMaterial();
        $this->panelMaterial->material_list->Reload();
        $this->panelMaterial->setVisible(true);
    }

    public function materialListOnRow($row){
        $item = $row->getDataItem();

        $row->add(new Label('material_name', $item->itemname));
        $row->add(new Label('material_msr', $item->msr));
        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue("/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('href', "/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('data-gallery', $item->image_id);
        if ($item->image_id == 0) {
            $row->imagelistitem->setVisible(false);
        }
        $row->add(new AutocompleteTextInput('material_qty', 0, 100))->onText($this, 'OnAutoCompleteMaterial');
        $row->material_qty->setAttribute('item_id', $item->item_id);

        if(is_null($this->_passport) != true){
            $qty = $this->_passport->getMaterial($item->item_id);
            $row->material_qty->setText($qty);
        }
        $row->add(new ClickLink('material_del'))->onClick($this, 'delQuantityOnClick');
    }

    public function OnAutoCompleteMaterial($sender){
        $text = trim($sender->getText());
        if($text != "" && $text != "0"){
            $item_id = $sender->getAttribute('item_id');
            $this->_passport->materials[$item_id] = floatval($text);
        }
        return $text;
    }

    public function addworksOnClick($sender){
        $this->passporttable->setVisible(false);
        $this->passportdetail->setVisible(false);
        $this->filterwork->clean();
        $this->filterwork->setVisible(true);

        $this->_passport->delAllWork();
        $this->listWorkForm->listwork->Reload();
        $this->listWorkForm->setVisible(true);

    }

    public function saveMaterialTotalOnClick($sender){
        $materials = $this->_passport->getAllMaterial();
        if($sender->id == "saveMaterialTotal" && count($materials) > 0){
            $key_items = array_keys($materials);
            $str_item = "('" . implode("','", $key_items) . "')";
            $conn = \ZDB\DB::getConnect();
            $sql = "SELECT item_id, itemname FROM items WHERE item_id IN " . $str_item;
            $rs = $conn->Execute($sql);
            $list_item = [];
            foreach ($rs as $r){
                $list_item[$r['item_id']] = $r['itemname'];
            }
            $str_mat = "";
            foreach ($materials as $k=>$v){
                $str_mat .= $list_item[$k] . " - кол-во: " . $v . ", ";
            }
            $str_mat = trim($str_mat);
            $str_mat = substr($str_mat, 0, -1);
            $this->passportdetail->editmaterial->setText($str_mat);
            $this->passportdetail->editmaterial->setVisible(true);

        }else{
            $this->_passport->delAllMaterial();
            $this->passportdetail->editmaterial->setText("");
            $this->passportdetail->editmaterial->setVisible(false);
        }

        $this->filter->setVisible(false);
        $this->panelMaterial->setVisible(false);
        $this->passportdetail->setVisible(true);
    }

    public function delQuantityOnClick($sender){
        $items = $sender->getOwner()->getDataItem();
        $data = $items->getData();
        $del_qty = $sender->getOwner();
        $key_qty = "material_qty_" . $del_qty->getNumber();
        $comps = $del_qty->getChildComponents();
        if(array_key_exists($key_qty, $comps) == true){
            $comps[$key_qty]->setText("0");
        }
        $item_id = $data['item_id'];
        $this->_passport->delMaterial($item_id);
        $this->panelMaterial->material_list->Reload();
    }

    public function listWorkOnRow(\Zippy\Html\DataList\DataRow $row){
        $item = $row->getDataItem();

        $row->add(new Label('typeWork', $item->service_name));
        $row->add(new Label('price', $item->price));
        $row->add(new CheckBox('checkTypeWork', new \Zippy\Binding\PropertyBinding($item, 'select')))->onChange($this, 'checkOnSelect', true);

        if(is_null($this->_passport) != true){
            $sel = $this->_passport->getWork($item->getID());
            $row->checkTypeWork->setChecked($sel);
        }
    }

    public function checkOnSelect($sender){
        $items = $sender->getOwner()->getDataItem();
        $chk = $sender->isChecked();
        $id = $items->getID();
        if($chk == true){
            $this->_passport->setWork($id);
        }else{
            $this->_passport->delWork($id);
        }
        $this->updateAjax(array('checkTypeWork'));
    }

    public function saveWorkOnClick($sender){
        $works = $this->_passport->getAllWork();
        if($sender->id == "saveWork" && count($works) > 0){
            $key_items = array_keys($works);
            $str_item = "('" . implode("','", $key_items) . "')";
            $conn = \ZDB\DB::getConnect();
            $sql = "SELECT service_id, service_name FROM services WHERE service_id IN " . $str_item;
            $rs = $conn->Execute($sql);
            $list_item = [];
            foreach ($rs as $r){
                $list_item[$r['service_id']] = $r['service_name'];
            }
            $str_work = "";
            foreach ($works as $k=>$v){
                $str_work .= $list_item[$k] . ", ";
            }
            $str_work = trim($str_work);
            $str_work = substr($str_work, 0, -1);
            $this->passportdetail->editwork->setText($str_work);
            $this->passportdetail->editwork->setVisible(true);

        }else{
            $this->_passport->delAllWork();
            $this->passportdetail->editwork->setText("");
            $this->passportdetail->editwork->setVisible(false);
        }

        $this->filterwork->setVisible(false);
        $this->listWorkForm->setVisible(false);
        $this->passportdetail->setVisible(true);

    }

    public function saveOnClick($sender){
        $this->_passport->name = $this->passportdetail->editpas_name->getText();
        $val = $this->passportdetail->editsize->getValue();
        $option = $this->passportdetail->editsize->getOptionList();
        $this->_passport->size_name = $option[$val];

        if ($this->_passport->name == '') {
            $this->setError("entername");
            return;
        }
        $this->_passport->Save();
        $this->passportdetail->setVisible(false);
        $this->passporttable->setVisible(true);
        $this->passporttable->passportlist->Reload();
    }
}



class ItemDataMaterials implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

        $form = $this->page->filter;
        $where = "1=1";
        $text = trim($form->searchkey->getText()); //"";
        $brand = ""; //trim($form->searchbrand->getText());
        $cat = $form->searchcat->getValue(); //$cat = 9
        $showdis = $form->showdis->isChecked();

        if ($cat != 0) {
            if ($cat == -1) {
                $where = $where . " and cat_id=0";
            } else {
                $where = $where . " and cat_id=" . $cat;
            }
        }

        if ($showdis == true) {
            $passport = $this->page->getPassportInst();
            $materials = $passport->getAllMaterial();
            $str_id = "";
            foreach ($materials as $key=>$val){
                if($key == 0 || $key == "0") continue;
                $str_id .= "'{$key}'" . ",";
            }
            $str_id = substr($str_id, 0, -1);
            if($str_id == "") $str_id = "'0'";
            $where = $where . " and item_id IN(" . $str_id . ")";
        } else {
            $where = $where . " and disabled <> 1";
        }
        if (strlen($text) > 0) {
            if ($p == false) {
                $text = Item::qstr('%' . $text . '%');
                $where = $where . " and (itemname like {$text} or item_code like {$text}  or bar_code like {$text} )  ";
            } else {
                $text = Item::qstr($text);
                $where = $where . " and (itemname = {$text} or item_code = {$text}  or bar_code like {$text} )  ";
            }
        }
        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $l = Item::find($this->getWhere(true), "itemname asc", $count, $start);
        $f = Item::find($this->getWhere(), "itemname asc", $count, $start);
        foreach ($f as $k => $v) {
            $l[$k] = $v;
        }
        return $l;
    }

    public function getItem($id) {
        return Item::load($id);
    }

}

class ItemDataWorks implements \Zippy\Interfaces\DataSource{
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

        if ($cat != 0) {
            if ($cat == -1) {
//                $where = $where . " and parealist_id=0";
            } else {
                $cat_txt = Service::qstr('%' . '<area>' . $cat . '</area>' . '%');
                $where = $where . " and ( detail like {$cat_txt} ) ";
            }
        }

        if ($showdis == true) {
            $passport = $this->page->getPassportInst();
            $works = $passport->getAllWork();
            $str_id = "";
            foreach ($works as $key=>$val){
                $str_id .= "'{$key}'" . ",";
            }
            $str_id = substr($str_id, 0, -1);
            if($str_id == "") $str_id = "'0'";
            $where = $where . " and service_id IN(" . $str_id . ")";
        } else {
//            $where = $where . " and disabled <> 1";
        }
        if (strlen($text) > 0) {
            if ($p == false) {
                $text = Service::qstr('%' . $text . '%');
                $where = $where . " and (service_name like {$text} )  ";
            } else {
                $text = Service::qstr($text);
                $where = $where . " and (service_name = {$text} )  ";
            }
        }
        return $where;
    }

    public function getItemCount() {
        return Service::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $l = Service::find($this->getWhere(true), "service_name asc", $count, $start);
        $f = Service::find($this->getWhere(), "service_name asc", $count, $start);
        foreach ($f as $k => $v) {
            $l[$k] = $v;
        }
        return $l;
    }

    public function getItem($id) {
        return Service::load($id);
    }
}

class SizePass implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $name_size;
    public $detail;

    public function __construct($id, $n, $d)
    {
        $this->id = $id;
        $this->name_size = $n;
        $this->detail =$d;
    }

    public function getID(){
        return $this->id;
    }
}

class ModelSize implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $size;
    public $quantity;

    public function __construct($id, $sz, $qty)
    {
        $this->id = $id;
        $this->size = $sz;
        $this->quantity =$qty;
    }

    public function getID(){
        return $this->id;
    }
}

class ItemDataPassport implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

        $form = $this->page->filterpassport;
        $where = "1=1";
        $text = trim($form->searchkeypas->getText()); //"";
//        $brand = ""; //trim($form->searchbrand->getText());
//        $cat = $form->searchcat->getValue(); //$cat = 9
        $showdis = $form->showdispas->isChecked();
//

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
                $text = Passport::qstr('%' . $text . '%');
                $where = $where . " and (name like {$text} )  ";
            } else {
                $text = Passport::qstr($text);
                $where = $where . " and (name = {$text} )  ";
            }
        }
        return $where;
    }

    public function getItemCount() {
        return Passport::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $l = Passport::find($this->getWhere(true), "name asc", $count, $start);
        $f = Passport::find($this->getWhere(), "name asc", $count, $start);
        foreach ($f as $k => $v) {
            $l[$k] = $v;
        }
        return $l;
    }

    public function getItem($id) {
        return Passport::load($id);
    }
}