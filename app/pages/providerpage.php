<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 15.09.2021
 * Time: 14:16
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
class ProviderPage extends \App\Pages\Base
{

    public function __construct($params = null)
    {
        parent::__construct($params);

        $this->add(new Form('supplierForm'));
        $this->supplierForm->add(new DataView('supplierlist', new SupplierData($this), $this, 'supplierlistOnRow'))->Reload();
        $this->supplierForm->add(new SubmitLink('supplierinfo'))->onClick($this, 'supplierInfoOnClick');
        $this->supplierForm->add(new SubmitLink('suppliermsg'))->onClick($this, 'supplierMsgOnClick');

        $this->add(new Panel('panelInfo'))->setVisible(false);
        $this->panelInfo->add(new TextArea('displayInfo'));

        $this->add(new Panel('panelData'))->setVisible(false);
        $this->panelData->add(new ClickLink('suppliertemplate'))->onClick($this, 'supplierTemplateOnClick');
        $this->panelData->add(new Form('supplierProps'));
        $this->panelData->supplierProps->setVisible(false);
        $this->panelData->supplierProps->add(new TextArea('supplieranswer'));
        $this->panelData->supplierProps->add(new SubmitLink('suppliersend'))->onClick($this, 'supplierSendOnClick');
    }

    public function supplierlistOnRow(\Zippy\Html\DataList\DataRow $row){
        $items = $row->getDataItem();
        $row->add(new Label('suppliername', trim($items->itemname) . ", " . $items->msr));
        $row->add(new Label('supplierquantity', $items->quantity));
        $row->add(new CheckBox('seldel', new \Zippy\Binding\PropertyBinding($items, 'seldel')));
    }

    public function supplierInfoOnClick($sender){
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT firm_id, firm_name, details FROM firms";
        $rs = $conn->Execute($sql);

        foreach ($rs as $r){
            $detail = $r['details'];
            $firm = $r['firm_name'];
        }
        $d = array();
        $res = preg_match('/<\inn\>([0-9]+)\<\/inn\>/i', $detail, $d);
        if($res == true){
            $company = $firm . "\n" . "ИНН " . $d[1] . "\n";
        }
        $res = preg_match('/<\phone\>([0-9]+)\<\/phone\>/i', $detail, $d);
        $company .= "Тел: " . $d[1] . "\n";

//        $items = $this->supplierForm->supplierlist->getChildComponents();


        if($this->panelInfo->isVisible() == false){
            $this->panelInfo->displayInfo->setText($company);
            $this->panelInfo->setVisible(true);
        }else{
            $this->panelInfo->setVisible(false);
        }

    }

    public function supplierMsgOnClick($sender){
        $this->supplierForm->setVisible(false);
        $this->panelInfo->setVisible(false);

        $items = $this->supplierForm->supplierlist->getChildComponents();
        $check = 'false';
        foreach ($items as $item){
            $elem = $item->getDataItem();
            $fields = $elem->fields;
            foreach ($fields as $key=>$field){
                if($key == "seldel" && $field == true){
                    $check = 'true';
                    break;
                }
            }
            if($check == 'true') break;
        }

        $this->panelData->supplierProps->supplieranswer->setAttribute('data-check', $check);
        $this->panelData->setVisible(true);
    }

    public function supplierTemplateOnClick(){

        $this->panelData->supplierProps->setVisible(true);
    }

    public function supplierSendOnClick($sender){
        $items = $this->supplierForm->supplierlist->getChildComponents();
        $user_id = $_SESSION['user_id'];
        $created = date("Y-m-d H:i:s");

        $detail_item = "";
        $order_id = 0;
        foreach ($items as $item){
            $elem = $item->getDataItem();
            $fields = $elem->fields;
            foreach ($fields as $key=>$field){
                if($key == "seldel" && $field == true){
                    $detail_item .= "<itemid>" . $fields['item_id'] . "</itemid>" . ",";
                }
                if($key == 'order_id') $order_id = $field;
            }
        }
        $attr = false;
        if(strlen($detail_item) > 0) $attr = true;
        $this->panelData->supplierProps->supplieranswer->setAttribute('data-check', $attr);
        $msg = $this->panelData->supplierProps->supplieranswer->getText();

        if(strlen($msg) > 0 && strlen($detail_item) > 0){
            $conn = \ZDB\DB::getConnect();
            $sql = "INSERT INTO orders(message, detail_item, user_id, order_id, created) 
                VALUES ('{$msg}', '{$detail_item}', '{$user_id}', '{$order_id}', '{$created}')";
            $rs = $conn->Execute($sql);
//            $this->panelData->supplierProps->supplieranswer->setText('');
//            $this->supplierForm->supplierlist->Reload();
            App::Redirect("\\App\\Pages\\Providerpage");
        }



    }
}

class SupplierData implements \Zippy\Interfaces\DataSource
{
    private $page;
    public $data = [];

    public function __construct($page) {
        $this->page = $page;
        //SELECT s.id, s.item_id, s.order_id, s.quantity, s.created, it.msr, it.itemname FROM suppliers s, items it WHERE order_id = (SELECT MAX(s.order_id) as id FROM suppliers s) AND s.item_id = it.item_id
        $sql = "SELECT s.id, s.item_id, it.itemname, it.msr, s.quantity, s.created, s.order_id 
                FROM suppliers s, items it 
                WHERE order_id = (SELECT MAX(s.order_id) as id FROM suppliers s) AND s.item_id = it.item_id ORDER BY 3";
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
        $t = [];
        foreach ($this->data as $k=>$b){
            $id = $b['item_id'];
            $t[$id] = new SupplierItem($b, []);
        }
        return $t;
    }

    public function getItem($id) {
        return Item::load($id);
    }
}
class SupplierItem extends \App\Entity\Item
{
    public $brprice = [];
    public $fields = [];
    public function __construct($fields, $brprice)
    {
        $this->fields = $fields;
        $this->brprice = $brprice;
    }
}