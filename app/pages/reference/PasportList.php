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

    public function __construct($params = null)
    {
        parent::__construct($params);

        $this->add(new Label('showModal'));
        $this->showModal->setAttribute('data-show', false);
        if($params == true || $params == "true"){
            $this->showModal->setAttribute('data-show', true);
        }
        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addNewOnClick');
        $this->itemtable->add(new Form('listform'));

        $this->itemtable->listform->add(new DataView('itemlist', new MyItemDataSource($this), $this, 'itemlistOnRow'))->Reload();

    }

    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row)
    {
        $item = $row->getDataItem();
        $row->add(new Label('itemname', $item->name));
        $row->add(new Label('itemsize', $item->size));
        $inwork = "Нет";
        $show = false;
        if($item->work == 1){
            $inwork = "Да";
            $show = true;
        }
        $row->add(new Label('inwork', $inwork));
        $row->add(new Label('itemquantity', $item->quantity));
        $row->add(new ClickLink('edit'))->onClick($this, 'editModelOnClick', $show);
        $dis = "false";
        if($item->work == 1) $dis = "true";
        $row->edit->setAttribute('data-disabled', $dis);
        $row->edit->setAttribute('data-pid', $item->id);
    }


    public function editModelOnClick($sender)
    {
        $pasport_id = $sender->getAttribute('data-pid');
        $disabled = $sender->getAttribute('data-disabled');
        if($disabled == "true"){
            $html = "<p>Модель находится в работе, редактирование недоступно.</p>";
            $js = "  $('.modal-body').html('{$html}') ; $('#msg').click()";
            $this->updateAjax(array(), $js);
        }else {

            $conn = \ZDB\DB::getConnect();
            $sql = "SELECT p.name, p.size, p.comment, t.model_item, t.detail FROM pasport p, pasport_tax t 
                WHERE p.id = t.pasport_id AND t.pasport_id=" . $pasport_id;
            $rs = $conn->Execute($sql);
            $items = [];
            $works = [];
            foreach ($rs as $r) {
                $modelName = $r['name'];
                $modelSize = $r['size'];
                $comment = $r['comment'];
//            $bool = preg_match('/\<work\>+/i',$r['detail']);

                if (preg_match('/\<work\>+/i', $r['detail']) == true) {
                    $works[] = $r['model_item'];
                } else {
                    $items[] = $r['detail'];
                }
            }
            $list_work = implode(",", $works);
            $list_work .= ",";

            $res = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>/i', $comment, $all_sizes);

            $sz_quan = "";
            for ($i = 0; $i < count($all_sizes[0]); $i++) {
                $sz_quan .= $all_sizes[1][$i] . ":" . $all_sizes[2][$i] . ",";
            }
            $str_to_items = $modelName . ";" . $modelSize . ";" . $list_work . ";" . $sz_quan . ";";
            $text_material = "";

            for ($i = 0; $i < count($items); $i++) {
                preg_match_all('/\<material\>([а-яА-Яa-zA-Z.,\/ ].*?)\<\/material\>\<quantity\>([0-9.,]+)\<\/quantity\>/i', $items[$i], $all_material);
                $text_material .= $all_material[1][0] . " <" . $all_material[2][0] . ">,";
            }

            $str_edit_text = $text_material . "::" . $str_to_items . "::" . "edit";
            App::Redirect("\\App\\Pages\\Pasport", $str_edit_text);
        }

    }

    public function addNewOnClick()
    {
        if(isset($_SESSION['kindwork']) == true){
            unset($_SESSION['kindwork']);
        }
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
        $sql = "SELECT p.id as id, p.name as name, p.size as size, p.quantity as quantity, m.in_work as work 
        FROM pasport p, model m where p.id = m.pasport_id";
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
