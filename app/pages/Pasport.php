<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 20.10.2021
 * Time: 20:31
 */

namespace App\Pages;

use App\Application as App;
use App\Entity\Category;
use App\Entity\Item;
use App\Entity\ItemSet;
use App\Entity\Kind;
use App\System;
use Zippy\Html\Form\Button;
use App\Helper as H;
use function GuzzleHttp\Psr7\str;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;


class Pasport extends \App\Pages\Base
{
    public $quantity = [];
    public $select;
    public $sizes = [];
    public $razmer = [];
    public $works = [];

    public function __construct($params = null)
    {
        parent::__construct($params);

        session_start();
        if(isset($_SESSION['material']) == true){
            unset($_SESSION['material']);
        }
        $_SESSION['material'] = [];
//        if(isset($_SESSION['model']) == true) {
//            unset($_SESSION['model']);
//        }

        $conn = \ZDB\DB::getConnect();
        $sql = "select sizer as itemname from sizesrange";

        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
            $this->sizes[] = $r['itemname'];
        }

        $pasport_model = "Паспорт модели";
        $this->add(new Label('pasportModel', $pasport_model));

        $this->add(new Form('pasportForm'));
        $this->pasportForm->add(new Panel('panelModelSize'));
        $this->pasportForm->panelModelSize->add(new TextInput('modelName'));

        $this->pasportForm->add(new Panel('panelNewModelSize'))->setVisible(false);
        $this->pasportForm->add(new DropDownChoice('size', $this->sizes))->onChange($this, "onSize");

        $this->pasportForm->panelNewModelSize->add(new DataView('newSizeModel',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"razmer")),$this,'listSizeModelOnRow'));

//        $this->pasportForm->panelNewModelSize->add(new DataView('newSizeModel', $this, $this,'listSizeModelOnRow'));//->Reload();

        $this->pasportForm->add(new SubmitLink('addmaterials'))->onClick($this, 'addMaterialsOnClick');
        $this->pasportForm->add(new SubmitLink('addworks'))->onClick($this, 'addWorksOnClick');
        $this->pasportForm->add(new TextArea('editmaterial'))->setVisible(false);
        $this->pasportForm->add(new TextArea('editcomment'))->setVisible(false);
        $this->pasportForm->add(new SubmitButton('savePasport'))->onClick($this, 'savePasportOnClick');
        $this->pasportForm->add(new SubmitButton('cancelPasport'))->onClick($this, 'cancelPasportOnClick');

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->setVisible(false);
        $this->filter->add(new CheckBox('showdis'));
//        $this->filter->add(new TextInput('searchbrand'));
//        $this->filter->searchbrand->setDataList(Item::getManufacturers() ) ;

        $this->filter->add(new TextInput('searchkey'));
        $catlist = array();
        $catlist[-1] = H::l("withoutcat");
        foreach (Category::getList() as $k => $v) {
            $catlist[$k] = $v;
        }
        $this->filter->add(new DropDownChoice('searchcat', $catlist, 0));


        $this->add(new Form('panelMaterial'))->setVisible(false);
        $this->panelMaterial->add(new DataView('material_list', new ItemDataMaterial($this), $this, 'materialListOnRow'));

        $this->panelMaterial->material_list->setPageSize(H::getPG());
        $this->panelMaterial->add(new \Zippy\Html\DataList\Paginator('pag', $this->panelMaterial->material_list));
        $this->panelMaterial->add(new SubmitButton('saveMaterialTotal'))->onClick($this, 'saveMaterialTotalOnClick');
        $this->panelMaterial->add(new SubmitButton('cancelMaterialTotal'))->onClick($this, 'saveMaterialTotalOnClick');

        $this->add(new Form('panelAddQuantity'))->setVisible(false);
        $this->panelAddQuantity->add(new Label('material_name'));
        $this->panelAddQuantity->add(new TextInput('material_qty'));
        $this->panelAddQuantity->add(new SubmitButton('saveMaterial'))->onClick($this, 'saveMaterialOnClick');
        $this->panelAddQuantity->add(new Button('cancelMaterial'))->onClick($this, 'cancelMaterialOnClick');

        /* список работ форма*/

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
        $this->listWorkForm->add(new DataView('listwork', new ItemDataWork($this), $this, 'listWorkOnRow'));//->Reload();

        $this->listWorkForm->listwork->setPageSize(H::getPG());
        $this->listWorkForm->add(new \Zippy\Html\DataList\Paginator('pag1', $this->listWorkForm->listwork));
        $this->listWorkForm->add(new SubmitButton('saveWork'))->onClick($this, 'saveWorkOnClick');
        $this->listWorkForm->add(new Button('cancelWork'))->onClick($this, 'saveWorkOnClick');
//        $this->listWorkForm->add(new Panel('panelListWork'))->setVisible(false);
//        $this->listWorkForm->panelListWork->add(new DataView('listwork',
//            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"works")),$this,'listOnRowWork'));
    }

    public function onSize($list_sz=""){
        $val = $this->pasportForm->size->getValue();
        if($val != -1) {
            $sz = $this->pasportForm->size;
            $option = $sz->getOptionList();
            $select = $option[$val];

            $arr = explode("-", $select);
            $this->razmer = [];

            for ($i = intval(trim($arr[0])), $k = 1; $i <= intval(trim($arr[1])); $i++, $k++) {
                $this->razmer[] = new ModelSize($k, $i, 0);
            }

            $this->pasportForm->panelNewModelSize->setVisible(true);
            $this->pasportForm->panelNewModelSize->newSizeModel->Reload();
            $this->pasportForm->size->setValue($val);
        }else{
            $this->pasportForm->panelNewModelSize->setVisible(false);
//            $this->pasportForm->panelNewModelSize->newSizeModel->Reload();
            $this->pasportForm->size->setValue($val);
        }
        $this->updateAjax(array('size'));
    }
    public function listSizeModelOnRow($row){
        $item = $row->getDataItem();
        $text = $this->pasportForm->panelModelSize->modelName->getText();
        if(strlen($text) == 0) $text = "Модель";

        $row->add(new Label('namemodel', $text));
        $row->add(new Label('sizemodel', $item->size));
//        $row->add(new TextInput('countofsize', $item->quantity));
        $row->add(new TextInput('countofsize', new \Zippy\Binding\PropertyBinding($item, 'quantity')));
    }

    public function OnFilter($sender) {
        $this->panelMaterial->material_list->Reload();
    }
    public function OnFilterWork($sender){
        $this->listWorkForm->listwork->Reload();
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

        if(isset($_SESSION['material']) == true){
            $id = $item->getID();
            $materials = $_SESSION['material'];
            if(array_key_exists($id, $materials) == true){
                $qty = $materials[$id];
                $row->add(new Label('material_qty', $qty));
            }else{
                $row->add(new Label('material_qty', 0));
            }
        }
        $row->add(new ClickLink('material_add'))->onClick($this, 'addQuantityOnClick'); //new \Zippy\Binding\PropertyBinding($item, 'select')))
        $row->add(new ClickLink('material_del'))->onClick($this, 'delQuantityOnClick');
    }

    public function addMaterialsOnClick($sender){
//        if(isset($_SESSION['material']) == true){
//            unset($_SESSION['material']);
//        }
//        $_SESSION['material'] = [];

        $this->filter->setVisible(true);
        $this->panelMaterial->setVisible(true);
        $this->pasportForm->panelModelSize->setVisible(false);
//        $this->pasportForm->addmaterials->setVisible(false);
        $this->pasportForm->setVisible(false);
        $this->panelMaterial->material_list->Reload();
    }

    public function listWorkOnRow($row){
        $item = $row->getDataItem();
        $row->add(new Label('typeWork', $item->work));
        $row->add(new Label('price', $item->price));
        $row->add(new CheckBox('checkTypeWork', new \Zippy\Binding\PropertyBinding($item, 'select')))->onChange($this, 'checkOnSelect', true);

        if(isset($_SESSION['kindwork']) == true){
            $id = $item->getID();
            $kindworks = $_SESSION['kindwork'];
            if(array_key_exists($id, $kindworks) == true){
                $chk = $kindworks[$id];
                $row->checkTypeWork->setChecked($chk);
            }
        }
    }

    public function addWorksOnClick($sender){
        if($_SESSION['kindwork'] == true){
            unset($_SESSION['kindwork']);
        }
        $_SESSION['kindwork'] = [];


        $this->pasportForm->setVisible(false);
        $this->listWorkForm->setVisible(true);
        $this->listWorkForm->listwork->Reload();
        $this->filterwork->setVisible(true);
    }

    public function checkOnSelect($sender)
    {
        $items = $sender->getOwner()->getDataItem();
        $item = $items->getData();

        $chk = $sender->isChecked();
        $id = $items->getID();
        if($chk == true){
            $_SESSION['kindwork'][$id] = $item['work'];
        }else{
            unset($_SESSION['kindwork'][$id]);
        }

        $this->updateAjax(array('checkTypeWork'));
    }

    public function listOnRowWork($row){
        $item = $row->getDataItem();

        $row->add(new Label('typeWork',$item->work));
        $row->add(new Label('price', $item->price));
        $row->add(new CheckBox('checkTypeWork', new \Zippy\Binding\PropertyBinding($item, 'select')))->onChange($this, 'checkOnSelect', true);
    }

    public function addQuantityOnClick($sender){
        $items = $sender->getOwner()->getDataItem();
        $data = $items->getData();
        $item_id = $data['item_id'];
        $material_name = $data['itemname'];

        $pag = $this->panelMaterial->pag;
        $comp = $pag->getOwner()->getComponent('pag');
        $num_page = $this->panelMaterial->material_list->getCurrentPage();
        $mat_comp_id = $sender->getOwner()->getNumber();

        $this->panelAddQuantity->clean();
        $this->panelAddQuantity->material_name->setText($material_name);
        $this->panelAddQuantity->material_name->setAttribute('item_id', $item_id);
        $this->panelAddQuantity->material_name->setAttribute('num_pag', $num_page);
        $this->panelAddQuantity->material_name->setAttribute('mat_id', $mat_comp_id);
        $this->filter->setVisible(false);
        $this->panelMaterial->setVisible(false);
        $this->panelAddQuantity->setVisible(true);

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
        unset($_SESSION['material'][$item_id]);
    }
    public function saveMaterialOnClick($sender){

//        $sess = $_SESSION['material'];
//        $s = $sender;
//        $sess[$item_id] = $qty;

        $num_pag = $this->panelAddQuantity->material_name->getAttribute('num_pag');
        $qty = $this->panelAddQuantity->material_qty->getText();
        $item_id = $this->panelAddQuantity->material_name->getAttribute('item_id');
        $mat_id = $this->panelAddQuantity->material_name->getAttribute('mat_id');

        $mat_qty = "material_qty_" . $mat_id;
        $mat_list = "material_list_" . $mat_id;
        $_SESSION['material'][$item_id] = $qty;
//        $this->panelMaterial->material_list->Reload();

        $this->panelMaterial->material_list->$mat_list->$mat_qty->setText($qty);
        $this->panelMaterial->material_list->setCurrentPage($num_pag);

        $this->filter->setVisible(true);
        $this->panelMaterial->setVisible(true);
        $this->panelAddQuantity->setVisible(false);
    }

    public function cancelMaterialOnClick($sender){
        $this->filter->setVisible(true);
        $this->panelMaterial->setVisible(true);
        $this->panelAddQuantity->setVisible(false);
    }

    public function saveMaterialTotalOnClick($sender){
        foreach ($_SESSION['material'] as $key=>$val){
            if($_SESSION['material'][$val] == '0'){
                unset($_SESSION['material'][$key]);
            }
        }

        $sess = $_SESSION['material'];
        if($sender->id == "saveMaterialTotal") {
            if (count($sess) != 0) {
                $str_id = "";
                foreach ($sess as $k => $v) {
                    $str_id .= "'{$k}'" . ",";
                }
                $str_id = substr($str_id, 0, -1);
                $sql = "SELECT item_id, itemname FROM items WHERE item_id IN(" . $str_id . ")";
                $conn = \ZDB\DB::getConnect();
                $rs = $conn->Execute($sql);

                $str_txt = "";
                foreach ($rs as $r) {
                    $str_txt .= trim($r['itemname']) . ", кол-во: " . $sess[$r['item_id']] . ", ";
                }
                $str_txt = substr($str_txt, 0, -2);
                $this->pasportForm->editmaterial->setText($str_txt);
                $this->pasportForm->editmaterial->setVisible(true);
            }
        }else{
//            if(isset($_SESSION['material']) == true){
//                unset($_SESSION['material']);
//            }
        }
        $this->filter->setVisible(false);
        $this->panelMaterial->setVisible(false);
        $this->pasportForm->setVisible(true);
        $this->pasportForm->panelModelSize->setVisible(true);

    }

    public function saveWorkOnClick($sender){
        if($sender->id == "saveWork"){
            $works = $_SESSION['kindwork'];
            $str_work = "";
            foreach ($works as $work){
                $str_work .= $work . ", ";
            }
            $this->pasportForm->editcomment->setText($str_work);
            $this->pasportForm->editcomment->setVisible(true);
        }
        $this->filterwork->setVisible(false);
        $this->listWorkForm->setVisible(false);
        $this->pasportForm->setVisible(true);
    }

    public function savePasportOnClick($sender){

        $model_name = $this->pasportForm->panelModelSize->modelName->getText();
        $str_mat = $this->pasportForm->editmaterial->getText();
        $str_work = $this->pasportForm->editcomment->getText();
        $err = [];
        if(strlen(trim($model_name)) == 0){
            $err[] = "Не указано наименование модели";
        }
        if(strlen(trim($str_mat)) == 0){
            $err[] = "Не выбраны материалы";
        }
        if(strlen(trim($str_work)) == 0){
            $err[] = "Не указаны работы";
        }

        $sizes = $this->pasportForm->panelNewModelSize->newSizeModel->getDataRows();
        $str_size = "";
        $suite = 0;
        $str_qty = false;
        foreach ($sizes as $size){
            $sz_items = $size->getDataItem();
            if($sz_items->quantity != 0) $str_qty = true;
            $str_size .= "<size>" . $sz_items->size . "</size>" . "<quantity>" . $sz_items->quantity . "</quantity>" . ",";
            $suite += intval($sz_items->quantity);
        }
        if($str_qty == false){
            $err[] = "all zero";
        }

        if(count($err) == 0){
            $works = $_SESSION['kindwork'];
            $materials = $_SESSION['material'];
            $val = $this->pasportForm->size->getValue();
            $option = $this->pasportForm->size->getOptionList();
//            $option = $sz->getOptionList();
            $size = $option[$val];
            $conn = \ZDB\DB::getConnect();
            $sql = "INSERT INTO pasport(name, size, quantity, comment) 
                    VALUES ('{$model_name}', '{$size}', '{$suite}', '{$str_size}')";

            $conn->Execute($sql);
            $id_ins = $conn->_insertid();

            foreach ($works as $kw=>$vw){
                $detail = "<work>" . $kw . "</work>";
                $sql_w = "INSERT INTO pasport_tax(pasport_id, model_item, detail) 
                          VALUES ('{$id_ins}', '{$vw}', '{$detail}')";
                $conn->Execute($sql_w);
            }
            $mat_name = [];

            $brr = array_keys($materials);
            $str_key_in = "";
            for($i = 0; $i < count($brr); $i++){
                $str_key_in .= "'" . $brr[$i] . "'" . ",";
            }
            $str_key_in = substr($str_key_in, 0, -1);
            $sql_mat = "SELECT item_id, itemname FROM items WHERE item_id IN(" . $str_key_in . ")";
            $rs = $conn->Execute($sql_mat);
            foreach ($rs as $r){
                $mat_name[$r['item_id']] = $r['itemname'];
            }
            $model_detail = "";
            foreach ($materials as $km=>$vm){
                $model_detail .= "<item_id>" . $km . "</item_id>";
                $detail = "<material>" . $km . "</material>" . "<quantity>" . trim($vm) . "</quantity>";
                $sql_m = "INSERT INTO pasport_tax(pasport_id, model_item, detail, qty_material)
                          VALUES ('{$id_ins}', '{$mat_name[$km]}', '{$detail}', true)";
                $conn->Execute($sql_m);
            }

            $today = date("Y-m-d H:i:s");
            $sql = "INSERT INTO model(pasport_id, detail, created) VALUES ('{$id_ins}','{$model_detail}', '{$today}')";
            $conn->Execute($sql);

            if(isset($_SESSION['kindwork']) == true){
                unset($_SESSION['kindwork']);
            }
            if(isset($_SESSION['material']) == true){
                unset($_SESSION['material']);
            }
            App::Redirect("\\App\\Pages\\Reference\\PasportList");
        }else{

        }
    }

    public function cancelPasportOnClick($sender){
        if(isset($_SESSION['kindwork']) == true){
            unset($_SESSION['kindwork']);
        }
        if(isset($_SESSION['material']) == true){
            unset($_SESSION['material']);
        }
        App::Redirect("\\App\\Pages\\Reference\\PasportList");
    }
}





class ItemDataMaterial implements \Zippy\Interfaces\DataSource
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
            $mat_arr = $_SESSION['material'];
            $str_id = "";
            foreach ($mat_arr as $key=>$val){
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

class ItemDataWork implements \Zippy\Interfaces\DataSource
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

        if ($cat != 0) {
            if ($cat == -1) {
                $where = $where . " and parealist_id=0";
            } else {
                $where = $where . " and parealist_id=" . $cat;
            }
        }

        if ($showdis == true) {
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


class ModelSize implements \Zippy\Interfaces\DataItem
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

    public function getID() { return $this->id; }
}

class ListWork implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $work;
    public $price;
    public $select;

    public function __construct($id, $work, $price, $select)
    {
        $this->id = $id;
        $this->work = $work;
        $this->price = $price;
        $this->select = $select;
    }

    public function setSelect($select)
    {
        $this->select = $select;
    }

    public function getSelect() { return $this->select; }
    public function getWork() { return $this->work; }

    public function resetSelect($select=false)
    {
        $this->select = $select;
    }
    public function getID() { return $this->id; }
}