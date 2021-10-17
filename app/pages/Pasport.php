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
use App\System;
use App\Helper;
use function GuzzleHttp\Psr7\str;
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
use Zippy\Html\Link\RedirectLink;
use App\Entity\Event;

//function setGlob(){
//    global $materials;
//}

//$GLOBALS["material"] = "this is test";

class Pasport extends Base
{
    public $sizes = [];
    public $works = [];
    public $razmer = [];
    public $str_params = "";
    public $edit = false;
    public $from_pasport_item = [];
    public $item_qty = [];
    public $_work;

    public function __construct($params = null)
    {
        parent::__construct($params);

//        $cat = 10;
        session_start();

        $conn = \ZDB\DB::getConnect();
        $sql = "select sizer as itemname from sizesrange";

        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
            $this->sizes[] = $r['itemname'];
        }

//        $cat = 11;
//        $sql = "select * from items where items.cat_id = " . $cat . " ORDER BY itemname";
        $sql = "SELECT * FROM kindworks ORDER BY work";
        $rsw = $conn->Execute($sql);

        $matches = [];


        foreach ($rsw as $w){
//            $tmp = $w['detail'];
//            $res = preg_match('/\<zarp\>([0-9]+)\<\/zarp\>/i', $tmp, $matches);
//            $price = 0.00;
//            if($res == true) $price = $matches[1];
//            $this->works[] = new ListWork($w['item_id'], $w['itemname'], $price, false);
            $this->works[] = new ListWork($w['id'], $w['work'], $w['price'], false);
        }

        if(isset($_SESSION['kindwork']) == false){
            foreach ($this->works as $wr){
                $_SESSION['kindwork'][] = array($wr->id=>$wr->select);
            }
        }

        if($params != null) {
            $this->str_params = $params;

            $from_pasportitem = explode("::", $params);
            $txt_model = "";
            $txt_material_item = "";
            switch (count($from_pasportitem)) {
                case 2:
                    $txt_material_item = $from_pasportitem[0];
                    $txt_model = $from_pasportitem[1];
                    break;
                case 3:
                    $txt_material_item = $from_pasportitem[0];
                    $txt_model = $from_pasportitem[1];
                    $is_edit = $from_pasportitem[2];
                    if ($is_edit == "edit") $this->edit = true;
                    break;
                default:
//                echo "Ошибка при передаче параметров в params= " . $params;
                    $txt_material_item = trim($params);
            }

            $list_mat = explode("|", $txt_material_item);
            $refactor_list_mat = explode("|,", $txt_material_item);
            $refactor_array = [];
            for($j = 0; $j < count($refactor_list_mat); $j++){
                $match = array();
                if(strlen(trim($refactor_list_mat[$j])) > 0){
                    preg_match_all('/<([0-9,.]+)>|\(([0-9,.]+)\)/i', $refactor_list_mat[$j], $match);
                    $qty = str_replace(["<", ">"], "", $match[0][0]);
                    $item_id = str_replace(["(", ")"], "", $match[0][1]);
                    $refactor_array[$item_id] = $qty;
                }
            }
            $this->item_qty = $refactor_array;
            $refactor_array = [];
            $txt_material = "";
            $txt_item_id = "";
            for ($k = 0; $k < count($list_mat); $k++) {
                if ($k % 2 == 0) $txt_material .= $list_mat[$k];
                else $txt_item_id .= str_replace(["(", ")"], "", $list_mat[$k]) . ",";
            }

            if(str_ends_with($txt_item_id, ",") == true){
                $txt_item_id = substr($txt_item_id, 0, -1);
            }
            $this->from_pasport_item = explode(",", $txt_item_id);

            $rekv = [];
            $md = "";
            $sz = "";
            $raz = "";
            $works = "";
            if (strlen(trim($txt_model)) > 0) {
                $rekv = explode(";", $txt_model);
                $md = $rekv[0];
                $sz = $rekv[1];
                if ($this->edit == true) {
                    foreach ($this->sizes as $k => $s) {
                        if ($s == $rekv[1]) {
                            $sz = $k;
                            break;
                        }
                    }
                }
                $works = $rekv[2];
                $raz = $rekv[3];
            }
        }

        $pasport_model = "Паспорт модели";
        if($this->edit == true) $pasport_model .= " редактирование";
        $this->add(new Label('pasportModel', $pasport_model));
        $this->add(new Form('pasportForm'));
        $this->pasportForm->add(new TextInput('modelName'));
        if(strlen($md) > 0){
            $this->pasportForm->modelName->setText($md);
        }

        $this->pasportForm->add(new DropDownChoice('size', $this->sizes))->onChange($this, "onSize");
        $this->pasportForm->add(new Panel('panelNewModelSize'))->setVisible(false);
        $this->pasportForm->panelNewModelSize->add(new DataView('newSizeModel',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"razmer")),$this,'listSizeModelOnRow'))->Reload();

        if(strlen($sz) > 0){
            $this->pasportForm->size->setValue($sz);
            $this->onSize($raz);
        }

        $this->pasportForm->add(new TextArea('editcomment'))->setVisible(false);
        if(strlen($works) > 0){
            $this->pasportForm->editcomment->setText($works);
            $this->pasportForm->editcomment->setVisible(true);
        }
        $this->pasportForm->add(new TextArea('editmaterial'))->setVisible(false);
        if(strlen(trim($txt_material)) > 0){
            $this->pasportForm->editmaterial->setText($txt_material);
            $this->pasportForm->editmaterial->setVisible(true);
        }
        $this->pasportForm->add(new SubmitLink('saveModel'))->onClick($this, 'saveModelOnClick');
        $this->pasportForm->add(new SubmitLink('cancelModel'))->onClick($this, 'saveModelOnClick');
        $this->pasportForm->add(new SubmitLink('addworks'))->onClick($this, 'addWorkOnClick');
        $this->pasportForm->add(new SubmitLink('addmaterials'))->onClick($this, 'addMaterialsOnClick');

        $this->add(new Form('listWorkForm'))->setVisible(false);
        $this->listWorkForm->add(new DataView('listwork',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"works")),$this,'listOnRowWork'))->Reload();
        $this->listWorkForm->listwork->setPageSize(Helper::getPG());
        $this->listWorkForm->add(new \Zippy\Html\DataList\Paginator('pag', $this->listWorkForm->listwork));
        $this->listWorkForm->add(new SubmitLink('saveWork'))->onClick($this, 'saveWorkOnClick');
        $this->listWorkForm->add(new SubmitLink('cancelWork'))->onClick($this, 'saveWorkOnClick'); //cancelWorkOnClick
    }

    public function onSize($list_sz=""){
        $val = $this->pasportForm->size->getValue();
        if($val != -1) {
            $sz = $this->pasportForm->size;
            $option = $sz->getOptionList();
            $select = $option[$val];

            $arr = explode("-", $select);
            $this->razmer = [];
            $quantity = [];
            if (strlen($list_sz) > 0) {
                $quantity = explode(",", $list_sz);
            }

            for ($i = intval(trim($arr[0])), $k = 1; $i <= intval(trim($arr[1])); $i++, $k++) {
                $quan = 0;
                if (count($quantity) != 0) $quan = explode(":", $quantity[$k - 1]);
                $this->razmer[] = new ModelSize($k, $i, $quan[1]);
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
        $row->add(new CheckBox('checkTypeWork', new \Zippy\Binding\PropertyBinding($item, 'select')))->onChange($this, 'checkOnSelect', true);
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
    }

    public function addMaterialsOnClick()
    {
//        $this->listMaterialForm->setVisible(true);

        $name_model = $this->pasportForm->modelName->getText();
        $size = $this->pasportForm->size->getValue();
        $list_work = $this->pasportForm->editcomment->getText();
        $razm = $this->pasportForm->panelNewModelSize->newSizeModel->getChildComponents();

        $str_to_items = $name_model . ";" . $size . ";" . $list_work . ";";
        foreach ($razm as $ra){
            $brr = $ra->getChildComponents();
            foreach ($brr as $b=>$v){
                if(str_starts_with($b, "sizemodel") == true){
                    $s = explode("_", $b);
                    $kol = "countofsize_" . $s[1];
                    $str_to_items .= $v->getText() . ":" . $brr[$kol]->getValue() . ",";
                }
            }
        }

        foreach ($_SESSION as $key=>$sess){
            if(str_starts_with($key, 'quantity') == true){
                unset($_SESSION[$key]);
            }
        }

        App::Redirect("\\App\\Pages\\PasportItem", $str_to_items);
    }

    public function checkOnSelect($sender)
    {
        $items = $sender->getOwner()->getDataItem();
        $chk = $sender->isChecked();
        $id = $items->getID();
//        $session_kw = $_SESSION['kindwork'];
        for($i = 0; $i < count($_SESSION['kindwork']); $i++){
            if(array_key_exists($id, $_SESSION['kindwork'][$i]) == true){
                $_SESSION['kindwork'][$i][$id] = $chk;
            }
        }
//        foreach ($_SESSION['kindwork'] as $sess){
//            if(array_key_exists($id, $sess) == true){
//                $sess[$id] = $chk;
//                break;
//            }
//        }
//        foreach ($this->works as $work){
//            if($work->getID() == $id){
//                $work->setSelect($chk);
////                $this->listWorkForm->saveWork->setAttribute("disabled", false);
//                break;
//            }
//        }
        $this->updateAjax(array('checkTypeWork'));
    }
    public function addWorkOnClick()
    {
        for($i = 0; $i < count($_SESSION['kindwork']); $i++){
            foreach ($_SESSION['kindwork'][$i] as $k=>$v){
                $_SESSION['kindwork'][$i][$k] = false;
            }
        }
        foreach ($this->works as $wrk){
            $wrk->resetSelect();
        }
        $this->pasportForm->editcomment->clean();
        $this->listWorkForm->listwork->Reload();
        $this->pasportForm->setVisible(false);
        $this->listWorkForm->setVisible(true);


    }

    public function _str($val) { return "'" . $val . "'"; }
    public function saveModelOnClick($sender)
    {
        $id = $sender->id;
        $conn = \ZDB\DB::getConnect();
        if($id == "saveModel" && $this->edit == false) {
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
            $conn->Execute($sql);
            $id_ins = $conn->_insertid();

            foreach ($this->works as $work) {
                if ($work->getSelect() == true) {
                    $w = $work->getWork();
                    $detail = "<work>" . $work->getID() . "</work>"; //было $work->getWork()
                    $sql_w = "INSERT INTO pasport_tax(pasport_id, model_item, detail)
                      VALUES({$this->_str($id_ins)}, {$this->_str($w)}, {$this->_str($detail)})";
                    $conn->Execute($sql_w);
                }
            }
            $tetxarea_material = $this->pasportForm->editmaterial->getText();
//            $par = $this->str_params;
//            $str_material = explode("::", $par);
//            $materials = $str_material[0];

            $match = array();
            // preg_match_all('/([а-яА-Яa-zA-Z, ].*?)([0-9()]+),/i',$tetxarea_material, $match);
            preg_match_all('/([а-яА-Яa-zA-Z0-9.,\/ ].*?)<([0-9<>.,]+)>,/i',$tetxarea_material, $match);
            $crr_materials = array_combine($match[1], $match[2]);
            foreach($crr_materials as $m=>$q){
                $detail = "<material>" . trim($m) . "</material>" . "<quantity>" . $q . "</quantity>";
                $sql_m = "INSERT INTO pasport_tax(pasport_id, model_item, detail, qty_material)
                      VALUES ({$this->_str($id_ins)}, {$this->_str($m)}, {$this->_str($detail)}, true)";
                $conn->Execute($sql_m);
            }

            //здесь добавить
            $detail = "";
            foreach ($this->from_pasport_item as $fpi){
                $detail .= "<item_id>" . $fpi . "</item_id>";
            }
            //{$this->_str($id_ins)}, {$this->_str($detail)}
            $today = date("Y-m-d H:i:s");
            $sql = "INSERT INTO model(pasport_id, detail, created) VALUES ('{$id_ins}','{$detail}', '{$today}')";
            $conn->Execute($sql);
        }else {
            //UPDATE MODEL ОБНОВИТЬ ДАННЫЕ МОДЕЛИ
            $this->edit = false;

        }
        if(isset($_SESSION['kindwork']) == true){
            unset($_SESSION['kindwork']);
        }
        App::Redirect("\\App\\Pages\\Reference\\PasportList");
    }

    public function saveWorkOnClick($sender)
    {
        $id = $sender->id;

        if($id == 'saveWork'){
            $str_works = "";
            $sess = $_SESSION['kindwork'];
            for($i = 0; $i < count($sess); $i++){
                foreach ($sess[$i] as $k=>$v){
                    if($v == true){
                        foreach ($this->works as $work){
                            if($work->id == $k){
                                $work->select = $v;
                                $str_works .= $work->work . ", ";
                            }

                        }
                    }
                }
            }
//            $listworks = $this->listWorkForm->listwork->getChildComponents();
//            foreach ($listworks as $listwork){
//                $childs = $listwork->getChildComponents();
//                foreach ($childs as $k=>$v){
//                    if(str_starts_with($k, "checkTypeWork") == true){
//                        $res = $v->getValue();
//                        $id = $listwork->getDataItem()->getID();
//                        foreach ($this->works as $work){
//                            if($work->getID() == $id){
//                                $work->setSelect($res);
//                                if($res == true) $str_works .= $work->work . ", ";
//                                break;
//                            }
//                        }
//                    }
//                }
//            }
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
    }

class ItemDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {
        $where = "1=1";
        $cat = 9;

        if ($cat != 0) {
            if ($cat == -1) {
                $where = $where . " and cat_id=0";
            } else {
                $where = $where . " and cat_id=" . $cat;
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