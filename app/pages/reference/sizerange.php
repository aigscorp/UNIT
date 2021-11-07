<?php
namespace App\Pages\Reference;

use App\Pages\Base;
use App\Helper;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;

class SizeRange extends Base
{
    public $sizer = [];
    public $_size;
    public $root = [];
    public $edit = false;

    public function __construct($params = null)
    {
        parent::__construct($params);

        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT id, name_size, detail FROM sizesrange";
        $rs = $conn->Execute($sql);

        foreach ($rs as $r){
            $this->sizer[] = new Sizer($r['id'], $r['name_size'], $r['detail']);
        }


        $this->add(new Panel('sizetable'))->setVisible(true);
        $this->sizetable->add(new DataView('sizelist',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"sizer")),$this,'sizelistOnRow'))->Reload();

        $this->sizetable->sizelist->setPageSize(Helper::getPG());
        $this->sizetable->add(new \Zippy\Html\DataList\Paginator('pag', $this->sizetable->sizelist));
        $this->sizetable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->add(new Form('sizedetail'))->setVisible(false);
        $this->sizedetail->add(new TextInput('edit_size'));


        for($i = 0; $i < 9; $i++){
            $this->root[] = new RootSize($i+1, $i+16, $i+25, $i+34, $i+43);
        }
        $this->sizedetail->add(new DataView('rangelist',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"root")), $this, 'rootlistOnRow'));

        $this->sizedetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->sizedetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function rootlistOnRow(\Zippy\Html\DataList\DataRow $row){
        $item = $row->getDataItem();

        $row->add(new Label('range1', "размер " . $item->col_1));
        $row->add(new CheckBox('sizerange1', new \Zippy\Binding\PropertyBinding($item, "select_1")));//->onChange($this, 'sizerangeOnSelect', true);
        if($item->select_1 == true){
            $row->sizerange1->setChecked(true);
        }
        $row->add(new Label('range2', "размер " . $item->col_2));
        $row->add(new CheckBox('sizerange2', new \Zippy\Binding\PropertyBinding($item, "select_2")));//->onChange($this, 'sizerangeOnSelect', true);
        if($item->select_2 == true) $row->sizerange2->setChecked(true);

        $row->add(new Label('range3', "размер " . $item->col_3));
        $row->add(new CheckBox('sizerange3', new \Zippy\Binding\PropertyBinding($item, "select_3")));//->onChange($this, 'sizerangeOnSelect', true);
        if($item->select_3 == true) $row->sizerange3->setChecked(true);

        $row->add(new Label('range4', "размер " . $item->col_4));
        $row->add(new CheckBox('sizerange4', new \Zippy\Binding\PropertyBinding($item, "select_4")));//->onChange($this, 'sizerangeOnSelect', true);
        if($item->select_4 == true) $row->sizerange4->setChecked(true);
    }

    public function sizelistOnRow(\Zippy\Html\DataList\DataRow $row){
        $item = $row->getDataItem();
        $row->add(new Label('sizerange', $item->size));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function addOnClick($sender) {
        $this->sizetable->setVisible(false);
        $this->sizedetail->setVisible(true);
        $this->edit = false;
        // Очищаем  форму
        $this->sizedetail->clean();
        $this->sizedetail->rangelist->Reload();
        $this->_size = new Sizer(0, '', '');
    }

    public function editOnClick($sender) {
        $size = $sender->owner->getDataItem();
        $this->sizetable->setVisible(false);
        $this->sizedetail->setVisible(true);
        $this->edit = true;
        $conn = \ZDB\DB::getConnect();

        $sql = "SELECT id, name_size, detail FROM sizesrange WHERE id = " . $size->id;
        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
            $this->_size->id = $r['id'];
            $this->_size->size = $r['name_size'];
            $this->_size->detail = $r['detail'];
        }


        $xml = @simplexml_load_string($this->_size->detail);
        $arr = $xml->size;
        foreach ($this->root as $rt){
            $rt->reset();
        }
        for($i = 0; $i < count($arr); $i++){
            foreach ($this->root as $root){
                $root->setSelect($arr[$i]);
            }
        }


        if (strlen($this->_size->size) > 0) {
            $this->sizedetail->edit_size->setValue($this->_size->size);

        } else {
            $this->sizedetail->edit_size->setValue('');
        }
        $this->sizedetail->rangelist->Reload();

    }

    public function deleteOnClick($sender) {
        $this->edit = false;
        $id_del = $sender->owner->getDataItem()->getID();
        $conn = \ZDB\DB::getConnect();
        $sql = "DELETE FROM sizesrange WHERE id = " . $id_del;
        foreach ($this->sizer as $k=>$sz){
            if($sz->getID() == $id_del){
                unset($this->sizer[$k]);
                $conn->Execute($sql);
                break;
            }
        }
        $this->sizetable->sizelist->Reload();
    }

    public function saveOnClick($sender){
        $txt_size = trim($this->sizedetail->edit_size->getText());

        $sel_sz = [];
        foreach ($this->root as $root){
            for($i = 1; $i <= 4; $i++){
                $col = $root->getColumn($i);
                if($col != 0){
                    $sel_sz[] = $col;
                }
            }
        }
        sort($sel_sz);

        if(strlen($txt_size) > 0 && count($sel_sz) > 0){
            $conn = \ZDB\DB::getConnect();
            $detail = "<detail>";
            //упаковываем  данные в detail
            for($j = 0; $j < count($sel_sz); $j++){
                $detail .= "<size>{$sel_sz[$j]}</size>";
            }
            $detail .= "</detail>";
            if($this->edit == false){
                $sql = "INSERT INTO sizesrange(name_size, detail) VALUES ('{$txt_size}', '{$detail}')";
                $conn->Execute($sql);
                $id_ins = $conn->_insertid();

                $this->sizer[] = new Sizer($id_ins, $txt_size, $detail);
            }else{
                $id = $this->_size->id;
                for($i = 0; $i < count($this->sizer); $i++){
                    if($id == $this->sizer[$i]->id){
                        $this->sizer[$i]->size = $txt_size;
                        $this->sizer[$i]->detail = $detail;
                        break;
                    }
                }
                $sql = "UPDATE sizesrange SET name_size = '{$txt_size}', detail = '{$detail}' WHERE id = '{$id}'";
                $conn->Execute($sql);

            }

        }

        $this->sizedetail->setVisible(false);
        $this->sizetable->setVisible(true);
        $this->sizetable->sizelist->Reload();
    }
    public function cancelOnClick($sender) {
        $this->sizetable->setVisible(true);
        $this->sizedetail->setVisible(false);
    }
}

class Sizer implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $size;
    public $detail;
    public function __construct($id, $size, $detail)
    {
        $this->id = $id;
        $this->size = $size;
        $this->detail = $detail;
    }

    public function getID(){ return $this->id; }
}

class RootSize implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $col_1;
    public $col_2;
    public $col_3;
    public $col_4;
    public $select_1;
    public $select_2;
    public $select_3;
    public $select_4;

    public function __construct($id, $col1, $col2, $col3, $col4)
    {
        $this->id = $id;
        $this->col_1 = $col1; //$col1;
        $this->col_2 = $col2; //$col2;
        $this->col_3 = $col3; //$col3;
        $this->col_4 = $col4; //$col4;
        $this->select_1 = false;
        $this->select_2 = false;
        $this->select_3 = false;
        $this->select_4 = false;
    }

    public function getColumn($numb){
        $sel = "select_" . $numb;
        $col = "col_" . $numb;
        if($this->$sel == true){
            return intval($this->$col);
        }
        return 0;
    }

    public function setSelect($numb, $bl = true){
        $col = "col_";
        $sel = "select_";
        for($i = 1; $i <= 4; $i++){
            $coln = $col . $i;
            if($this->$coln == $numb){
                $seln = $sel . $i;
                $this->$seln = $bl;
                break;
            }
        }
    }
    public function reset(){
        $sel = "select_";
        for($i = 1; $i <= 4; $i++){
            $seln = $sel . $i;
            $this->$seln = false;
        }
    }
    public function getID(){ return $this->id; }
}

