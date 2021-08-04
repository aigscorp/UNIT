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



class Pasport extends Base
{
    public $sizes = [];
    public $works = [];
    public $test = [];


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
//        $this->pasportForm->add(new DataView('listwork',new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"works")),$this,'listOnRowWork'))->Reload();

        $this->pasportForm->add(new SubmitLink('addworks'))->onClick($this, 'addWorkOnClick');

        $this->add(new Form('listWorkForm'))->setVisible(false);
        $this->listWorkForm->add(new DataView('listwork',new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"works")),$this,'listOnRowWork'))->Reload();
        $this->listWorkForm->add(new SubmitLink('saveWork'))->onClick($this, 'saveWorkOnClick');
    }

    public function listOnRow($row){
        $item = $row->getDataItem();

        $row->add(new Label('modelSize',$item->size));
    }

    public function listOnRowWork($row){
        $item = $row->getDataItem();

        $row->add(new Label('typeWork',$item->work));
        $row->add(new Label('price', $item->price));
        $row->add(new CheckBox('checkTypeWork'))->onChange($this, 'checkOnSelect');
    }

    public function typeObject($obj, $lvl)
    {
        $crr = get_object_vars($obj);
        array_walk_recursive($crr, function ($item, $key){
            echo "$key содержит $item<br>";
            if(is_object($item) == true){
                echo "object item" . "<br>";
                $this->typeObject($item, 0);
            }
        });
    }

    public function checkOnSelect($sender)
    {

//        echo "<pre>";
        $d1 = $sender->getOwner();
        $items = $sender->getOwner()->getDataItem();
//        if(($items instanceof \Zippy\Html\DataList\DataRow) == true){
//            var_dump("DATAROW");
//            echo "DATAROW:" . "<br>";
//        }

//        $d2 = $d1->getOwner();
//        $d3 = $d1->getDataItem();
//        var_dump($d1);

//        echo "=======================================<br>";
        $chk = $sender->isChecked();
        $id = $sender->id;
        foreach ($this->works as $work){
            if($work->getID() == $id){
                $work->setSelect($chk);
                break;
            }
        }
//        $items->select = $chk;
//        $this->test[] = $items;
//        var_dump($this->test);
    }
    public function addWorkOnClick($sender)
    {
        $this->pasportForm->setVisible(false);
        $this->listWorkForm->setVisible(true);
//        $id = $this->listWorkForm->works; //->getValue();
//        var_dump($id);
    }

    public function saveWorkOnClick($sender)
    {
        var_dump($sender);
        var_dump($this->works);
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
    public function getID() { return $this->id; }
}