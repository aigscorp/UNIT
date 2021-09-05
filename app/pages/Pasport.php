<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 31.07.2021
 * Time: 19:12
 */

namespace App\Pages;

use App\Application as App;
use App\Entity\Category;
use App\Entity\Item;
use App\Entity\ItemSet;
use App\Helper as H;
use App\System;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\Label;
use \Zippy\Html\Link\SubmitLink;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;


class Pasport extends Base
{
    public $sizes = [];
    public $works = [];
    public $materials = [];
    public $razmer = [];

    public function __construct($params = null)
    {
        parent::__construct($params);

        $cat = 10;
        $conn = \ZDB\DB::getConnect();
        $sql = "select * from items where items.cat_id = " . $cat;

        $rs = $conn->Execute($sql);

        foreach ($rs as $r){
//            $this->sizes[] = $r;
//            $this->razmer[] = new ModelSize($r['item_id'], $r['itemname']);
            $this->sizes[] = $r['itemname'];
        }

        $cat = 11;
        $sql = "select * from items where items.cat_id = " . $cat;

        $rsw = $conn->Execute($sql);
        $matches = [];
        foreach ($rsw as $w){
            $tmp = $w['detail'];
            $res = preg_match('/\<zarp\>([0-9]+)\<\/zarp\>/i', $tmp, $matches);
            $price = 0.00;
            if($res == true) $price = $matches[1];
            $this->works[] = new ListWork($w['item_id'], $w['itemname'], $price, false);
        }
////////////////////////////////////////////////
        $cat = 9;
        $conn = \ZDB\DB::getConnect();
        $sql = "select * from items where items.cat_id = " . $cat;

        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
            $matches = [];
            preg_match('/\<image_id\>([0-9]+)\<\/image_id\>/i', $r['detail'], $matches);
            $this->materials[] = new ListMaterial($this, $r['item_id'], $r['itemname'], 0, $matches[1]);
        }
///////////////////////////////////////////////////////////

        $this->add(new Form('pasportForm'));
        $this->pasportForm->add(new TextInput('modelName'));
//        $this->pasportForm->add(new DataView('list',
//            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"sizes")),$this,'listOnRow'))->Reload();
        $this->pasportForm->add(new DropDownChoice('size', $this->sizes))->onChange($this, "onSize");
        $this->pasportForm->add(new Panel('panelNewModelSize'))->setVisible(false);
        $this->pasportForm->panelNewModelSize->add(new DataView('newSizeModel',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"razmer")),$this,'listSizeModelOnRow'))->Reload();
//        $this->pasportForm->newSizeModel->setVisible(false);
        
        $this->pasportForm->add(new TextArea('editcomment'))->setVisible(false);
        $this->pasportForm->add(new TextArea('editmaterial'))->setVisible(false);
        $this->pasportForm->add(new SubmitLink('saveModel'))->onClick($this, 'saveModelOnClick');
        $this->pasportForm->add(new SubmitLink('cancelModel'))->onClick($this, 'saveModelOnClick');
//        $this->pasportForm->add(new DataView('listwork',new ArrayDataSource(new \Zipp$this->pasportForm->newSizeModel->setVisible(false);y\Binding\PropertyBinding($this,"works")),$this,'listOnRowWork'))->Reload();

        $this->pasportForm->add(new SubmitLink('addworks'))->onClick($this, 'addWorkOnClick');
        $this->pasportForm->add(new SubmitLink('addmaterials'))->onClick($this, 'addMaterialsOnClick');

        $this->add(new Form('listWorkForm'))->setVisible(false);
        $this->listWorkForm->add(new DataView('listwork',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"works")),$this,'listOnRowWork'))->Reload();
        $this->listWorkForm->add(new SubmitLink('saveWork'))->onClick($this, 'saveWorkOnClick');
        $this->listWorkForm->add(new SubmitLink('cancelWork'))->onClick($this, 'saveWorkOnClick'); //cancelWorkOnClick

//        $this->add(new Panel('itemtable'))->setVisible(false);
//        $this->itemtable->add(new Form('listMaterialForm'));
//        $this->itemtable->listMaterialForm->add(new DataView('listmaterial',
//            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "materials")),$this, 'listOnRowMaterial'));//->Reload();
//        $this->itemtable->listMaterialForm->listmaterial->setPageSize(10);
//        $this->itemtable->listMaterialForm->listmaterial->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->listMaterialForm->listmaterial));
//
//        $this->itemtable->listMaterialForm->add(new SubmitLink('saveMaterial'))->onClick($this, 'saveMaterialOnClick');
//        $this->itemtable->listMaterialForm->add(new SubmitLink('cancelMaterial'))->onClick($this, 'saveMaterialOnClick');

        $this->add(new Form('listMaterialForm'))->setVisible(false);
        $this->listMaterialForm->add(new DataView('listmaterial', new ItemDataSource($this), $this, 'listOnRowMaterial'))->Reload();


//        $this->listMaterialForm->add(new DataView('listmaterial',
//            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "materials")),$this, 'listOnRowMaterial'));//->Reload();

        $this->listMaterialForm->listmaterial->setPageSize(8);//H::getPG()
        $this->listMaterialForm->listmaterial->add(new \Zippy\Html\DataList\Paginator('pag', $this->listMaterialForm->listmaterial));
        $this->listMaterialForm->listmaterial->setSelectedClass('table-success');
        $this->listMaterialForm->add(new SubmitLink('saveMaterial'))->onClick($this, 'saveMaterialOnClick');
        $this->listMaterialForm->add(new SubmitLink('cancelMaterial'))->onClick($this, 'saveMaterialOnClick');
    }

    public function onSize($sender)
    {
//        var_dump($sender);
        $val = $this->pasportForm->size->getValue();
        $sz = $this->pasportForm->size;
        $option = $sz->getOptionList();
        $select = $option[$val];

        $arr = explode("-", $select);
        $this->razmer = [];
        for($i = intval(trim($arr[0])), $k = 1; $i <= intval(trim($arr[1])); $i++, $k++){
            $this->razmer[] = new ModelSize($k, $i);
        }

//        $this->pasportForm->size->setValue($select);
        $this->pasportForm->panelNewModelSize->setVisible(true);
        $this->pasportForm->panelNewModelSize->newSizeModel->Reload();
        $this->pasportForm->size->setValue($val);
        $this->updateAjax(array('size'));
    }
    public function listSizeModelOnRow($row)
    {
        $item = $row->getDataItem();
        $text = $this->pasportForm->modelName->getText();
        if(strlen($text) == 0) $text = "Модель";

        $row->add(new Label('namemodel', $text));
        $row->add(new Label('sizemodel', $item->size));
        $row->add(new TextInput('countofsize', $item->quantity));
    }

    public function listOnRow($row){
        $item = $row->getDataItem();
        $row->add(new Label('modelSize',$item->size));
    }

    public function listOnRowWork($row){
        $item = $row->getDataItem();

        $row->add(new Label('typeWork',$item->work));
        $row->add(new Label('price', $item->price));
        $row->add(new CheckBox('checkTypeWork'));//->onChange($this, 'checkOnSelect');
    }

    public function listOnRowMaterial(\Zippy\Html\DataList\DataRow $row)
    {
        $item = $row->getDataItem();

        $row->add(new Label('typeMaterial',$item->itemname));//$item->material
//        $row->add(new Label('quantity', $item->quantity));
        $row->add(new TextInput('quantity'));
        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue("/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('href', "/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('data-gallery', $item->image_id);
        if ($item->image_id == 0) {
            $row->imagelistitem->setVisible(false);
        }
//        $row->add(new CheckBox('checkTypeMaterial'));
    }

    public function addMaterialsOnClick()
    {
        $cat = 9;
        $conn = \ZDB\DB::getConnect();
//        $sql_img = "SELECT image_id FROM images WHERE 1";
//        $rs_img = $conn->Execute($sql_img);

//        $images = $rs_img->fields;

//        $sql = "select * from items where items.cat_id = " . $cat;
//
//        $rs = $conn->Execute($sql);
//        foreach ($rs as $r){
//            $matches = [];
//            preg_match('/\<image_id\>([0-9]+)\<\/image_id\>/i', $r['detail'], $matches);
//            $this->materials[] = new ListMaterial($this, $r['item_id'], $r['itemname'], 0, $matches[1]);
//        }

//        $this->listMaterialForm->listmaterial->Reload();
//        $this->itemtable->listMaterialForm->listmaterial->Reload();
        $this->pasportForm->setVisible(false);
        $this->listWorkForm->setVisible(false);

        $this->listMaterialForm->setVisible(true);
//        $this->listMaterialForm->listmaterial->setPageSize(8);//H::getPG()
//        $this->listMaterialForm->listmaterial->add(new \Zippy\Html\DataList\Paginator('pag', $this->listMaterialForm->listmaterial));
//        $this->listMaterialForm->listmaterial->setSelectedClass('table-success');
//        $this->listMaterialForm->listmaterial->Reload();


    }

    public function checkOnSelect($sender)
    {
        $items = $sender->getOwner()->getDataItem();
        $arr_send = $sender->getOwner()->getChildComponents();
//        if(($items instanceof \Zippy\Html\DataList\DataRow) == true){
//            var_dump("DATAROW");
//            echo "DATAROW:" . "<br>";
//        }
        $chk = $sender->isChecked();
        $id = $items->getID();
        foreach ($this->works as $work){
            if($work->getID() == $id){
                $work->setSelect($chk);
//                $this->listWorkForm->saveWork->setAttribute("disabled", false);
                break;
            }
        }
//        $compon = $this->getComponent('listWorkForm');
//        $arr_comp = $compon->getChildComponents();

//        $this->listWorkForm->listWork->checkTypeWork->getValue();
        $this->updateAjax(array('checkTypeWork'));
    }
    public function addWorkOnClick()
    {
        $this->pasportForm->editcomment->clean();
        $this->listWorkForm->listwork->Reload();
        $this->pasportForm->setVisible(false);
        $this->listWorkForm->setVisible(true);
    }

    public function _str($val) { return "'" . $val . "'"; }
    public function saveModelOnClick($sender)
    {
        $id = $sender->id;
        if($id == "saveModel") {
            $conn = \ZDB\DB::getConnect();
            $name_model = $this->pasportForm->modelName->getText();
            $val = $this->pasportForm->size->getValue();
//        $sz = $this->pasportForm->size;
            $option = $this->pasportForm->size->getOptionList();
            $size = $option[$val];
//        INSERT INTO `pasport`(`name`, `size`, `comment`) VALUES ("test","30-45","")
            $sql = "INSERT INTO pasport(name, size) VALUES";  //('model m001', '30-50')"; //({$name_model},{$size})

            $comment = [];
            $model_size = $this->pasportForm->panelNewModelSize->newSizeModel->getChildComponents();
            foreach ($model_size as $ms){
                $childs = $ms->getChildComponents();
                foreach ($childs as $key=>$child){
                    if(str_starts_with($key, "sizemodel") == true){
                        $sz = $child->getText();
                    }else if(str_starts_with($key, "countofsize") == true){
                        $qnt = $child->getText();
                    }
                }
                $comment[$sz] = $qnt;
            }
            $suite = 0;
            $sizeRange = "";
            foreach ($comment as $s=>$q){
                $sizeRange .= "<size>" . $s . "</size>" . "<quantity>" . $q . "</quantity>" . ",";
                $suite += intval($q);
            }

            $sql = "INSERT INTO pasport(name, size, comment, quantity) 
                    VALUES({$this->_str($name_model)}, {$this->_str($size)}, {$this->_str($sizeRange)}, {$this->_str($suite)})";
//        $sql .= "( " . "'" . $name_model . "', " . "'" . $size . "')";
            $conn->Execute($sql);
            $id_ins = $conn->_insertid();
            print_r($id_ins);

            foreach ($this->works as $work) {
                if ($work->getSelect() == true) {
                    $w = $work->getWork();
                    $detail = "<work>" . $work->getWork() . "</work>";
                    $sql_w = "INSERT INTO pasport_tax(pasport_id, model_item, detail) 
                      VALUES({$this->_str($id_ins)}, {$this->_str($w)}, {$this->_str($detail)})";
                    $conn->Execute($sql_w);
                }
            }
            foreach ($this->materials as $material){
                if($material->getSelect() == true){
                    $m = $material->getMaterial();
                    $q = $material->getQuantity();
                    $detail = "<material>" . $m . "</material>" . "<quantity>" . $q . "</quantity>";
                    $sql_m = "INSERT INTO pasport_tax(pasport_id, model_item, detail) 
                      VALUES ({$this->_str($id_ins)}, {$this->_str($m)}, {$this->_str($detail)})";
                    $conn->Execute($sql_m);
                }
            }
            $quan = 0;
            $sql = "INSERT INTO model(pasport_id, quantity) VALUES ({$this->_str($id_ins)}, {$this->_str($quan)})";
            $conn->Execute($sql);
        }
        App::Redirect("\\App\\Pages\\Reference\\PasportList");

    }

    public function saveMaterialOnClick($sender)
    {
        $id = $sender->id;
        foreach ($this->materials as $material){
            $material->resetQuantity();
        }
        if($id == 'saveMaterial'){
//            $this->materials = [];
            $str_mat = "";
            $listmaterials = $this->listMaterialForm->listmaterial->getChildComponents();

            foreach ($listmaterials as $listmaterial){
                $childs = $listmaterial->getChildComponents();
                $id = $listmaterial->getItemId();
                foreach ($childs as $k=>$v){
                    if(str_starts_with($k, "quantity") == true){
                        $res = $v->getValue();
                        if($res != "" && $res != "0"){
                            foreach ($this->materials as $material){
                                if($material->getID() == $id){
                                    $material->setQuantity($res);
                                    $material->setSelect(true);
                                    $str_mat .= $material->getMaterial() . " (" . $res . ")" . ", ";
                                    break;
                                }
                            }
                        }
                    }
                }
            }
//            var_dump($str_mat);
            if(strlen($str_mat) != 0){
                $this->pasportForm->editmaterial->setText($str_mat);
                $this->pasportForm->editmaterial->setVisible(true);
            }
        }else{
            $this->pasportForm->editmaterial->setText("");
            $this->pasportForm->editmaterial->setVisible(false);
            $this->materials = [];
        }
        $this->pasportForm->setVisible(true);
        $this->listMaterialForm->setVisible(false);
    }

    public function saveWorkOnClick($sender)
    {
        $id = $sender->id;
        foreach ($this->works as $work){
            $work->resetSelect();
        }
        if($id == 'saveWork'){
            $str_works = "";
            $listworks = $this->listWorkForm->listwork->getChildComponents();
            foreach ($listworks as $listwork){
                $childs = $listwork->getChildComponents();
                foreach ($childs as $k=>$v){
                    if(str_starts_with($k, "checkTypeWork") == true){
                        $res = $v->getValue();
                        $id = $listwork->getDataItem()->getID();
                        foreach ($this->works as $work){
                            if($work->getID() == $id){
                                $work->setSelect($res);
                                if($res == true) $str_works .= $work->work . ", ";
                                break;
                            }
                        }
                    }
                }
            }

            if(strlen($str_works) != 0){
                $this->pasportForm->editcomment->setText("$str_works");
                $this->pasportForm->editcomment->setVisible(true);
            }
        }else{
            $this->pasportForm->editcomment->setVisible(false);
        }


        $this->pasportForm->setVisible(true);
        $this->listWorkForm->setVisible(false);
    }

    public function cancelWorkOnClick()
    {
        foreach ($this->works as $work){
            $work->resetSelect();
        }
        $this->listWorkForm->listwork->Reload();
        $this->pasportForm->editcomment->setVisible(false);
        $this->pasportForm->setVisible(true);
        $this->listWorkForm->setVisible(false);
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

class ListMaterial implements \Zippy\Interfaces\DataItem
{
    private $page;
    public $id;
    public $material;
    public $quantity;
    public $image_id;
//    public $select;


    public function __construct($page, $id, $material, $quantity, $image_id=0)
    {
        $this->page = $page;
        $this->id = $id;
        $this->material = $material;
        $this->quantity = $quantity;
        $this->image_id = $image_id;
    }

    public function getMaterial() { return $this->material; }
    public function getQuantity(){ return $this->quantity; }
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }
    public function setSelect($select)
    {
        $this->select = $select;
    }

    public function getSelect() { return $this->select; }

    public function resetSelect($select=false)
    {
        $this->select = $select;
    }
    public function resetQuantity($quantity=0)
    {
        $this->quantity = $quantity;
    }

    public function getID() { return $this->id; }
    public function getItemCount() {
        return count($this->material);
    }
    public function getItems($start=null, $count=null, $sortfield = null, $asc = null) {
        return $this->material;
    }
}

class ItemDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

//        $form = $this->page->filter;
        $where = "1=1";
//        $text = trim($form->searchkey->getText());
//        $brand = trim($form->searchbrand->getText());
//        $cat = $form->searchcat->getValue();
        $cat = 9;
//        $showdis = $form->showdis->isChecked();

        if ($cat != 0) {
            if ($cat == -1) {
                $where = $where . " and cat_id=0";
            } else {
                $where = $where . " and cat_id=" . $cat;
            }
        }

//        if (strlen($brand) > 0) {
//
//            $brand = Item::qstr($brand);
//            $where = $where . " and  manufacturer like {$brand}      ";
//        }
//
//
//        if ($showdis == true) {
//
//        } else {
//            $where = $where . " and disabled <> 1";
//        }
//        if (strlen($text) > 0) {
//            if ($p == false) {
//                $text = Item::qstr('%' . $text . '%');
//                $where = $where . " and (itemname like {$text} or item_code like {$text}  or bar_code like {$text} )  ";
//            } else {
//                $text = Item::qstr($text);
//                $where = $where . " and (itemname = {$text} or item_code = {$text}  or bar_code like {$text} )  ";
//            }
//        }
        return $where;
    }

    public function getItemCount() {
//        var_dump($this->getWhere());
//        $ent = Item::findCnt($this->getWhere());
//        var_dump($ent);
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $l = Item::find($this->getWhere(true), "itemname asc", $count, $start);
        $f = Item::find($this->getWhere(), "itemname asc", $count, $start);

//        var_dump($f);

        foreach ($f as $k => $v) {
            $l[$k] = $v;
        }
//        var_dump($l);
        return $l;
    }

    public function getItem($id) {
        return Item::load($id);
    }

}