<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 29.07.2021
 * Time: 23:28
 */

namespace App\Pages;


use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;

class Production extends Base
{
    public $items = array();

    public function __construct($params = null)
    {
        parent::__construct($params);

        $conn = \ZDB\DB::getConnect();
        $sql = "select * from model";

        $rs = $conn->Execute($sql);
        foreach($rs as $r){
            $this->items[] = new Model($r->id, $r['name'], $r['size']);
        }
        $this->add(new Panel('panelButton'))->setVisible(true);
        $this->panelButton->add(new ClickLink('showProduction'));
        $this->panelButton->add(new ClickLink('showWork'));
        $this->panelButton->add(new ClickLink('showStore'));
        $this->panelButton->add(new ClickLink('showCustomer'));
        $this->panelButton->add(new ClickLink('showDirector'));

        $this->add(new Panel('detailProduction'))->setVisible(true);
        $this->detailProduction->add(new DataView('list',new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"items")),$this,'listOnRow'))->Reload();
//        $this->detailProduction->add(new ClickLink('modelName'));
//        $this->detailProduction->add(new ClickLink('modelWork'));
//        $this->detailProduction->add(new ClickLink('modelUpdate'));
//        $this->detailProduction->add(new ClickLink('modelCancel'));
    }

    public function listOnRow($row){
        $item = $row->getDataItem();

        $row->add(new Label('modelName',$item->modelName . ', ' . $item->size));
        $row->add(new ClickLink('modelWork'));
        $row->add(new ClickLink('modelUpdate'));
        $row->add(new ClickLink('modelCancel'));
//    $row->add(new ClickLink('edit'))->onClick($this,'editOnClick');
    }
}




//class  User implements \Zippy\Interfaces\DataItem
//{
//    public $id;
//    public $fio;
//    public $age;
//
//    public function __construct($id,$fio,$age)
//    {
//        $this->id=$id;
//        $this->fio=$fio;
//        $this->age=$age;
//    }
//    //требование  интерфейса
//    public function getID() { return $this->id;}
//}

class Model implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $modelName;
    public $size;

    public function __construct($id, $modelName, $size)
    {
        $this->id = $id;
        $this->modelName = $modelName;
        $this->size = $size;
    }

    public function getID()
    {
        return $this->id;
    }
}

