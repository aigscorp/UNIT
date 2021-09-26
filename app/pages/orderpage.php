<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 17.09.2021
 * Time: 11:44
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

class OrderPage extends \App\Pages\Base
{
    public function __construct($params = null)
    {
        parent::__construct($params);

        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);

        $this->add(new Form('orderForm'));
        $this->orderForm->add(new DataView('orderlist', new OrderData($this), $this, 'orderlistOnRow'))->Reload();

    }

    public function orderlistOnRow(\Zippy\Html\DataList\DataRow $row){
        $items = $row->getDataItem();

        $row->add(new Label('ordername', trim($items->itemname . ", " . $items->msr) ));
        $row->add(new Label('orderquantity', $items->order_q));
        $row->add(new Label('ordermsg', $items->message));
    }


}

class OrderData implements \Zippy\Interfaces\DataSource{
    private $page;
    public $data = [];
    public $num;

    public function __construct($page) {
        $this->page = $page;
        $this->num = 100;

        $sql = "SELECT * FROM orders WHERE order_id = (SELECT MAX(order_id) as order_id FROM orders)";
        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute($sql);

        $order_id = 0;
        $tmp = [];
        $ord = [];
        $ind = 0;
        foreach ($rs as $r){
            $ord[] = $r;
            $order_id = $r['order_id'];
            $res = preg_match_all('/\<itemid\>([0-9]+)\<\/itemid\>/i', $r['detail_item'], $match);
            $ord[$ind++]['list_item'] = $match[1];
            $tmp = array_merge($tmp, $match[1]);
        }

        $arr_item_id = array_unique($tmp);
        $txt_in = implode("','", $arr_item_id);
        $sql = "SELECT it.item_id, it.itemname, it.msr, s.quantity as order_q 
                FROM items it, suppliers s 
                WHERE it.item_id IN ('{$txt_in}') AND s.item_id=it.item_id AND s.order_id = " . $order_id;
        $rs = $conn->Execute($sql);
        $i = 0;

        foreach ($rs as $r){
            $item_id = $r['item_id'];
            $msgs = [];
            foreach ($ord as $d){
                $res = array_search($item_id, $d['list_item']);
                if($res !== false) $msgs[] = $d['message'];
            }
            $this->data[] = $r;
            $this->data[$i++]['message'] = $this->arrayToStr($msgs); //implode("\n", $msgs); //$msgs;
        }
    }

    public function arrayToStr($arr){
        $drr = [];
        foreach ($arr as $ar){
            $s = preg_replace('/\s\s+/',' ', $ar);
            $crr = explode(" ", $s);
            $sum = 0;
            $pos = 0;
            $brr = [];
            $len = count($crr);
            for($i = 0; $i < $len; $i++){
                $p = mb_strlen($crr[$i]) + 1;
                $sum += $p;
                if($sum > $this->num){
                    $pos = $i - 1;
                    $brr[] = $pos;
                    $sum = mb_strlen($crr[$i]) + 1;
                }
            }

            $brr[] = $len;

            $str = "";
            $j = 0;
            for($i = 0; $i < count($brr); $i++){
                for($j; $j < $brr[$i]; $j++){
                    $str .= $crr[$j] . " ";
                }
                $str .= "\n";
                $j = $brr[$i];
            }

//            echo $str;
            $drr[] = $str;
        }
        $txt = implode("|", $drr);
        return $txt;
    }
    private function getWhere($p = false) {
        $where = "1=1";
        return $where;
    }

    public function getItemCount() {
        return count($this->data);
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $t = [];
        foreach ($this->data as $k=>$b){
            $id = $b['item_id'];
            $t[$id] = new OrderItem($b, []);
        }
        return $t;
    }
}
class OrderItem extends \App\Entity\Item
{
    public $brprice = [];
    public $fields = [];
    public function __construct($fields, $brprice)
    {
        $this->fields = $fields;
        $this->brprice = $brprice;
    }
}