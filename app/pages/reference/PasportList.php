<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 08.08.2021
 * Time: 23:27
 */

namespace App\Pages\Reference;

use App\Application as App;
use App\Entity\Category;
use App\Entity\Item;
use App\Entity\ItemSet;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
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
use Zippy\Html\Link\SubmitLink;
//use \App\Pages\Pasport;

class PasportList extends \App\Pages\Base
{
    private $_item;
    public $brr = [1=>"apple", 2=>"orange"];

    public function __construct($params = null)
    {
        parent::__construct($params);

        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addNewOnClick');
        $this->itemtable->add(new Form('listform'));

//        $ids = new ItemDataSource($this); $this->brr

        $this->itemtable->listform->add(new DataView('itemlist', new MyItemDataSource($this), $this, 'itemlistOnRow'))->Reload();
        $a = 1;
        $b = $a + 2;
    }

    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row)
    {
        $item = $row->getDataItem();
        $row->add(new Label('itemname', $item->name));
        $row->add(new Label('itemsize', $item->size));
        $row->add(new ClickLink('edit'))->onClick($this, 'editModelOnClick');
    }

    public function editModelOnClick($sender)
    {
//        var_dump($sender);


    }

    public function addNewOnClick()
    {
        App::Redirect("\\App\\Pages\\Pasport");
    }
}


class MyItemDataSource1 implements \Zippy\Interfaces\DataSource
{
    private $page;
    public $brr = [];

    public function __construct($page)
    {
        $this->page = $page;
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT * FROM pasport";
        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
            $this->brr[] = $r;
        }
    }

    public function getItemCount() {
        return 1;
    }
    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return $this->brr;
    }
}

class MyItemDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;
    public $brr = [];

    public function __construct($page)
    {
        $this->page = $page;
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT * FROM pasport";
        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
            $this->brr[] = $r;
        }
    }

    public function getItem($id) {
        return Item::load($id);
    }

    public function getItemCount() {
        return 10;
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $l = [];
        foreach ($this->brr as $b){
            $l[] = new MyItem($b, []);
        }
        return $l;
    }
}

class MyItem extends \App\Entity\Item
{
    public $brprice = [];
    public $fields = [];
    public function __construct($fields, $brprice)
    {
        $this->fields = $fields;
        $this->brprice = $brprice;
    }
}
