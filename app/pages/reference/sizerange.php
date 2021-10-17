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

class SizeRange extends Base
{
    public $sizer = [];
    public $_size;

    public function __construct($params = null)
    {
        parent::__construct($params);

        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT id, sizer FROM sizesrange";
        $rs = $conn->Execute($sql);

        foreach ($rs as $r){
            $this->sizer[] = new Sizer($r['id'], $r['sizer']);
        }

        $this->add(new Panel('sizetable'))->setVisible(true);
        $this->sizetable->add(new DataView('sizelist',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"sizer")),$this,'sizelistOnRow'))->Reload();

        $this->sizetable->sizelist->setPageSize(Helper::getPG());
        $this->sizetable->add(new \Zippy\Html\DataList\Paginator('pag', $this->sizetable->sizelist));
        $this->sizetable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->add(new Form('sizedetail'))->setVisible(false);
        $this->sizedetail->add(new TextInput('edit_size'));
        $this->sizedetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->sizedetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
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
        // Очищаем  форму
        $this->sizedetail->clean();
        $this->_size = new Sizer(0, '');
    }

    public function editOnClick($sender) {
        $this->_size = $sender->owner->getDataItem();
        $this->sizetable->setVisible(false);
        $this->sizedetail->setVisible(true);

        if (strlen($this->_size->size) > 0) {
            $this->sizedetail->edit_size->setValue($this->_size->size);
        } else {
            $this->sizedetail->edit_size->setValue('');
        }

    }

    public function deleteOnClick($sender) {
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
        $is_size = false;
        foreach ($this->sizer as $sz){
            if(trim($sz->size) == $txt_size){
                $is_size = true;
                break;
            }
        }
        $conn = \ZDB\DB::getConnect();
        if(strlen($txt_size) > 0 && $is_size == false){
            $sql = "INSERT INTO sizesrange(sizer) VALUES ('{$txt_size}')";
            $id_ins = $conn->_insertid();
            $conn->Execute($sql);

            $this->sizer[] = new Sizer($id_ins, $txt_size);
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

class Sizer implements \Zippy\Interfaces\DataItem{
    public $id;
    public $size;

    public function __construct($id, $size)
    {
        $this->id = $id;
        $this->size = $size;
    }

    public function getID(){ return $this->id; }
}