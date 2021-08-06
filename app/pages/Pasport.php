<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 31.07.2021
 * Time: 19:12
 */

namespace App\Pages;


use Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\Label;
use \Zippy\Html\Link\SubmitLink;
use \Zippy\Html\Form\TextArea;


class Pasport extends Base
{
    public $sizes = [];
    public $works = [];
    public $materials = [];


    public function __construct($params = null)
    {
        parent::__construct($params);

        $cat = 10;
        $conn = \ZDB\DB::getConnect();
        $sql = "select * from items where items.cat_id = " . $cat;

        $rs = $conn->Execute($sql);
//        var_dump($rs);
        foreach ($rs as $r){
//            $this->sizes[] = $r;
            $this->sizes[] = new ModelSize($r->id, $r['itemname']);
        }

        $cat = 11;
        $sql = "select * from items where items.cat_id = " . $cat;

        $rsw = $conn->Execute($sql);
        $matches = [];
        foreach ($rsw as $w){
            $tmp = $w['detail'];
            $res = preg_match('/\<zarp\>([0-9]+)\<\/zarp\>/i', $tmp, $matches);
//            var_dump($matches);
            $price = 0.00;
            if($res == true) $price = $matches[1];
//            var_dump($res);
//            echo 'res => ' . $res . "<br>";
//            echo "ID: $w->item_id, id: " . $w['item_id'] . "<br>";
            $this->works[] = new ListWork($w['item_id'], $w['itemname'], $price, false);
        }
//        var_dump($this->sizes);

        $this->add(new Form('pasportForm'));
        $this->pasportForm->add(new TextInput('modelName'));
        $this->pasportForm->add(new DataView('list',new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"sizes")),$this,'listOnRow'))->Reload();
        $this->pasportForm->add(new TextArea('editcomment'))->setVisible(false);
        $this->pasportForm->add(new TextArea('editmaterial'))->setVisible(false);
        $this->pasportForm->add(new SubmitLink('saveModel'))->onClick($this, 'saveModelOnClick');
        $this->pasportForm->add(new SubmitLink('cancelModel'))->onClick($this, 'saveModelOnClick');
//        $this->pasportForm->add(new DataView('listwork',new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"works")),$this,'listOnRowWork'))->Reload();

        $this->pasportForm->add(new SubmitLink('addworks'))->onClick($this, 'addWorkOnClick');
        $this->pasportForm->add(new SubmitLink('addmaterials'))->onClick($this, 'addMaterialsOnClick');

        $this->add(new Form('listWorkForm'))->setVisible(false);
        $this->listWorkForm->add(new DataView('listwork',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"works")),$this,'listOnRowWork'))->Reload();
        $this->listWorkForm->add(new SubmitLink('saveWork'))->onClick($this, 'saveWorkOnClick');
        $this->listWorkForm->add(new SubmitLink('cancelWork'))->onClick($this, 'saveWorkOnClick'); //cancelWorkOnClick

        $this->add(new Form('listMaterialForm'))->setVisible(false);
        $this->listMaterialForm->add(new DataView('listmaterial',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, "materials")),$this, 'listOnRowMaterial'))->Reload();

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

    public function listOnRowMaterial($row)
    {
        $item = $row->getDataItem();

        $row->add(new Label('typeMaterial',$item->material));
        $row->add(new Label('quantity', $item->quantity));
        $row->add(new CheckBox('checkTypeMaterial'));
    }

    public function addMaterialsOnClick()
    {
        $cat = 9;
        $conn = \ZDB\DB::getConnect();
        $sql = "select * from items where items.cat_id = " . $cat;

        $rs = $conn->Execute($sql);
//        var_dump($rs);
        foreach ($rs as $r){
//            $this->sizes[] = $r;
            $this->materials[] = new ListMaterial($r['item_id'], $r['itemname'], 0);
        }

//        var_dump($this->materials);

        $this->listMaterialForm->listmaterial->Reload();
        $this->pasportForm->setVisible(false);
        $this->listWorkForm->setVisible(false);
        $this->listMaterialForm->setVisible(true);


    }

    public function checkOnSelect($sender)
    {
        $items = $sender->getOwner()->getDataItem();
//        $this->listWorkForm->saveWork->setAttribute("disabled", true);
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
        $compon = $this->getComponent('listWorkForm');
        $arr_comp = $compon->getChildComponents();

//        $this->listWorkForm->listWork->checkTypeWork->getValue();
        $this->updateAjax(array('checkTypeWork'));
    }
    public function addWorkOnClick()
    {
        $this->pasportForm->editcomment->clean();
        $this->listWorkForm->listwork->Reload();
        $this->pasportForm->setVisible(false);
        $this->listWorkForm->setVisible(true);
//        $id = $this->listWorkForm->works; //->getValue();
//        var_dump($id);
    }

    public function saveWorkOnClick($sender)
    {
        $id = $sender->id;
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

        foreach ($this->works as $work){
            $work->resetSelect();
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

    public function __construct($id, $size)
    {
        $this->id = $id;
        $this->size = $size;
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

    public function resetSelect($select=false)
    {
        $this->select = $select;
    }
    public function getID() { return $this->id; }
}

class ListMaterial implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $material;
    public $quantity;
    public $select;

    public function __construct($id, $material, $quantity, $select=false)
    {
        $this->id = $id;
        $this->material = $material;
        $this->quantity = $quantity;
        $this->select = $select;
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

    public function getID() { return $this->id; }
}