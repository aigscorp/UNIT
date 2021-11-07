<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 14.09.2021
 * Time: 12:25
 */
namespace App\Pages;

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

class StockBalance extends \App\Pages\Base
{
    public $company = "";
    public $show = false;

    public function __construct($params = null)
    {
        parent::__construct($params);

        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT firm_id, firm_name, details FROM firms";
        $rs = $conn->Execute($sql);

        foreach ($rs as $r){
            $detail = $r['details'];
            $firm = $r['firm_name'];
        }
        $d = array();
        $res = preg_match('/\<inn\>([0-9]+)\<\/inn\>/i', $detail, $d);
        if($res == true){
            $this->company = $firm . "\\n" . "ИНН " . $d[1] . "\\n";
        }
        $res = preg_match('/\<phone\>([0-9]+)\<\/phone\>/i', $detail, $d);
        $this->company .= "Тел: " . $d[1] . "\\n";


        $this->add(new Form('providerForm'));

        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);
        $this->providerForm->add(new DataView('providerlist', new ProviderData($this), $this, 'providerlistOnRow'))->Reload();
        $this->providerForm->add(new SubmitLink('providersend'))->onClick($this, 'providerSendOnClick');

        $this->add(new Form('companyInfo'));
        $this->companyInfo->add(new TextArea('displayinfo'));
        $this->companyInfo->add(new ClickLink('showinfo'))->onClick($this, 'showInfoOnClick', true);
    }

    public function providerlistOnRow(\Zippy\Html\DataList\DataRow $row){
        $items = $row->getDataItem();

        $row->add(new Label('providername', $items->itemname . ", " . $items->msr));
        $row->add(new Label('providerquantity', $items->qty_store));
        if(intval($items->qty_store) <= intval($items->min_amount)){
            $row->providername->setAttribute("style", "background: #ed2d2d;border-radius: 5px;");
        }
//        $row->add(new SubmitLink('providersend'))->onClick($this, 'providerSendOnClick');
        $row->add(new TextInput('providerorder', new \Zippy\Binding\PropertyBinding($items, 'providerorder')));
    }

    public function showInfoOnClick(){
        if($this->show == false){
            $this->show = true;
            $this->updateAjax(array(), " $('#showinfo').text('Скрыть'); $('#displayinfo').text('{$this->company}') ");
        }else{
            $this->show = false;
            $this->updateAjax(array(), " $('#showinfo').text('Показать'); $('#displayinfo').text('') ");
        }
    }

    public function providerSendOnClick($sender){
//        $cur = $this;
        if($sender->id == "providersend"){
            $providers = $this->providerForm->providerlist->getChildComponents(); //->getDataItem();
            $order_id = 0;
            $conn = \ZDB\DB::getConnect();
            $sql = "SELECT MAX(order_id) as order_id FROM suppliers";
            $rs = $conn->Execute($sql);

            $res = $rs->fields;
            if($res['order_id'] == null || $res['order_id'] == false) $order_id = 1;
            else $order_id = intval($res['order_id']) + 1;
            $arr = [];
            $created = date("Y-m-d H:i:s");

            foreach ($providers as $provider){
                $p = $provider->getDataItem();
                $fields = $p->fields;
                $tmp = new \stdClass();
                foreach ($fields as $key=>$field){
                    if($key == 'item_id') $tmp->item_id = $field;
                    if($key == 'providerorder') {
                        $tmp->quantity = $field;
                    }
                }
                $tmp->order_id = $order_id;
                $tmp->created = $created;
                $arr[] = $tmp;
            }

            for($i = 0; $i < count($arr); $i++){
                if(intval($arr[$i]->quantity) > 0){
                    $sql = "INSERT INTO suppliers(item_id, order_id, quantity, created) 
                        VALUES ('{$arr[$i]->item_id}', '{$arr[$i]->order_id}', '{$arr[$i]->quantity}', '{$arr[$i]->created}')";
                    $conn->Execute($sql);
                }
            }
            $this->providerForm->providerlist->Reload();
        }

    }
}

class ProviderData implements \Zippy\Interfaces\DataSource
{
    private $page;
    public $data = [];

    public function __construct($page) {
        $this->page = $page;
        $sql = "SELECT it.item_id, it.itemname, it.msr, it.minqty as min_amount, ss.partion as price, ss.qty as qty_store 
                FROM items it, store_stock ss 
                WHERE it.item_id = ss.item_id AND it.disabled = false ORDER BY 2";
        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
             $this->data[] = $r;
        }
    }

    private function getWhere($p = false) {
        $where = "1=1";
        return $where;
    }

    public function getItemCount() {
//        return Item::findCnt($this->getWhere());
        return count($this->data);
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
//        $l = Item::find($this->getWhere(true), "itemname asc", $count, $start);
//        $f = Item::find($this->getWhere(), "itemname asc", $count, $start);
//
//        foreach ($f as $k => $v) {
//            $l[$k] = $v;
//        }
        $t = [];
        foreach ($this->data as $k=>$b){
            $id = $b['item_id'];
            $t[$id] = new ProviderItem($b, []);
        }
        return $t;
    }

    public function getItem($id) {
        return Item::load($id);
    }
}
class ProviderItem extends \App\Entity\Item
{
    public $brprice = [];
    public $fields = [];
    public function __construct($fields, $brprice)
    {
        $this->fields = $fields;
        $this->brprice = $brprice;
    }
}

/*
 * SELECT it.item_id as id, it.itemname, it.detail, it.msr, pr.partion, pr.quantity
 * FROM (SELECT ss.item_id as id, ss.partion, ss.qty, el.quantity
 * FROM store_stock ss, entrylist el, documents doc
 * WHERE ss.stock_id = el.stock_id AND el.document_id = doc.document_id AND doc.state = 5) as pr, items it
 * WHERE it.item_id = pr.id
 * */