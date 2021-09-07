<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 06.09.2021
 * Time: 20:46
 */

namespace App\Pages;

use App\Application as App;
use App\Entity\Item;
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


class PasportItem extends \App\Pages\Base
{
    public $pasport;
    public function __construct($params = null)
    {
        parent::__construct($params);

//        if (false == \App\ACL::checkShowRef('ItemList')) {
//            return;
//        }


        $this->pasport = $_POST;

        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new Form('listform')) ;

        $this->itemtable->listform->add(new DataView('itemlist', new ItemDataTest($this), $this, 'itemlistOnRow'))->Reload();
        $this->itemtable->listform->itemlist->setPageSize(8);//H::getPG()
        $this->itemtable->listform->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->listform->itemlist, 8, true));
//        $this->itemtable->listform->itemlist->setSelectedClass('table-success');
        $this->itemtable->listform->add(new SubmitLink('saveMaterial'))->onClick($this, 'saveMaterialOnClick');
        $this->itemtable->listform->add(new SubmitLink('cancelMaterial'))->onClick($this, 'saveMaterialOnClick');

    }

    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();

        $row->add(new Label('typeMaterial', $item->itemname));
        $row->add(new TextInput('quantity'));//->onChange($this, 'state');
        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue("/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('href', "/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('data-gallery', $item->image_id);
        if ($item->image_id == 0) {
            $row->imagelistitem->setVisible(false);
        }
    }

    public function state($sender){
        var_dump($sender);
    }

    public function saveMaterialOnClick($sender){
        $tmp = [];
        foreach ($this->pasport as $key=>$p){
            if($key == 'modelName'){
                $_SESSION[$key] = $p;
            }
            if($key == 'size') $_SESSION[$key] = $p;
            if(str_starts_with($key, 'countofsize_') == true){
                $tmp[$key] = $p;
            }
        }
        $_SESSION["countofsize"] = $tmp;
//        $sess = $_SESSION;
        if($sender->id == 'saveMaterial'){
            $listmaterial = $this->itemtable->listform->itemlist->getChildComponents();
            $text = "";
            foreach ($listmaterial as $material){
                $itemmaterial = $material->getChildComponents();
                foreach ($itemmaterial as $k=>$v){
                    if(str_starts_with($k, "typeMaterial") == true){
                       $text .= $v->getText();
                    }
                    if(str_starts_with($k, "quantity") == true){
                       $text .= " (" . $v->getText() . "),";
                    }
                }
            }
            $a = 1;
            $b = $a + 9;
            var_dump($text);
        }
        App::Redirect("\\App\\Pages\\Pasport");
    }
}

class ItemDataTest implements \Zippy\Interfaces\DataSource
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