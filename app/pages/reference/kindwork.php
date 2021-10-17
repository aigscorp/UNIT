<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 04.10.2021
 * Time: 19:17
 */
namespace App\Pages\Reference;

use App\Pages\Base;
use App\Helper;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\TextInput;


class KindWork extends Base
{
    public $works = [];
    public $_work;
    public $kindshop = [];
    public $is_shop = false;
    public $edit = false;


    public function __construct($params = null)
    {
        parent::__construct($params);

        $conn = \ZDB\DB::getConnect();
        $sql_shop = "SELECT * FROM parealist";
        $rs = $conn->Execute($sql_shop);
        if($rs->fields == false){
            $sql = "SELECT * FROM kindworks k";
        }else{
            $this->is_shop = true;
            foreach ($rs as $r){
                $this->kindshop[$r['pa_id']] = $r['pa_name'];
            }
            $sql = "SELECT k.id, k.work, p.pa_name as shop, k.price, p.pa_id, k.short 
                    FROM kindworks k, parealist p WHERE k.parealist_id = p.pa_id";
        }
        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
            if($this->is_shop == true){
                $pa_id = $r['pa_id'];
            }else{
                $pa_id = 0;
            }
            $this->works[] = new Works($r['id'], $r['work'], $r['short'], $r['price'], $pa_id);
        }

//        $rs = $conn->Execute($sql);


        $this->add(new Panel('kindworktable'))->setVisible(true);
        $this->kindworktable->add(new DataView('kindworklist',
            new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this,"works")),$this,'kindworklistOnRow'))->Reload();
        $this->kindworktable->kindworklist->setPageSize(Helper::getPG());
        $this->kindworktable->add(new \Zippy\Html\DataList\Paginator('pag', $this->kindworktable->kindworklist));

        $this->kindworktable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->add(new Form('kindworkdetail'))->setVisible(false);
        $this->kindworkdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->kindworkdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        $this->kindworkdetail->add(new TextInput('kind_name'));
        $this->kindworkdetail->add(new TextInput('kind_short'));
        $this->kindworkdetail->add(new DropDownChoice('kind_shop'));
        $this->kindworkdetail->add(new TextInput('kind_price'));
    }

    public function addOnClick($sender) {
        $this->kindworktable->setVisible(false);
        $this->kindworkdetail->setVisible(true);
        // Очищаем  форму
        $this->edit = false;
        $this->kindworkdetail->clean();
        $this->kindworkdetail->kind_shop->setOptionList($this->kindshop);
        $this->_work = new Works(0, '', '', 0.00, 0);
    }

    public function kindworklistOnRow(\Zippy\Html\DataList\DataRow $row){
        $item = $row->getDataItem();
        $row->add(new Label('work_name', $item->work));
        $row->add(new Label('work_shop', $this->kindshop[$item->pa_id]));
        $row->add(new Label('work_price', $item->price));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $this->edit = true;

        $this->_work = $sender->owner->getDataItem();
        $this->kindworktable->setVisible(false);
        $this->kindworkdetail->setVisible(true);

        if (strlen($this->_work->work) > 0) {
            $this->kindworkdetail->kind_name->setValue($this->_work->work);
        } else {
            $this->kindworkdetail->kind_name->setValue('');
        }
        $this->kindworkdetail->kind_short->setText($this->_work->short);
        $this->kindworkdetail->kind_price->setText($this->_work->price);
        if(intval($this->_work->pa_id) > 0){
            $this->kindworkdetail->kind_shop->setOptionList($this->kindshop);
            $this->kindworkdetail->kind_shop->setValue($this->_work->pa_id);
        }

    }

    public function deleteOnClick($sender) {
        $id_del = $sender->owner->getDataItem()->getID();
        $conn = \ZDB\DB::getConnect();
        $sql = "DELETE FROM kindworks WHERE id = " . $id_del;
        foreach ($this->works as $k=>$wrk){
            if($wrk->getID() == $id_del){
                unset($this->works[$k]);
                $conn->Execute($sql);
                break;
            }
        }
        $this->kindworktable->kindworklist->Reload();
    }

    public function saveOnClick($sender) {
        $work = trim($this->kindworkdetail->kind_name->getText());

        $this->_work->work = $work;
        $this->_work->short = trim($this->kindworkdetail->kind_short->getText());
        if($this->is_shop == false){
            $this->_work->pa_id = 0;
        }else{
            $this->_work->pa_id = trim($this->kindworkdetail->kind_shop->getValue());
        }
        $this->_work->price = (float)trim($this->kindworkdetail->kind_price->getText());
        $w = $this->_work;

        $conn = \ZDB\DB::getConnect();
        if($this->edit == true){
            $sql = "UPDATE kindworks SET work = '{$w->work}', parealist_id = '{$w->pa_id}', short = '{$w->short}', price = '{$w->price}' 
                    WHERE id = " . $this->_work->id;
        }else{
            $sql = "INSERT INTO kindworks(work, parealist_id, short, price) 
                VALUES ('{$w->work}','{$w->pa_id}','{$w->short}','{$w->price}')";
            $id_ins = $conn->_insertid();
            $this->_work->id = $id_ins;
            $this->works[] = $this->_work;
        }
        $conn->Execute($sql);

        $this->kindworkdetail->setVisible(false);
        $this->kindworktable->setVisible(true);
        $this->kindworktable->kindworklist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->kindworktable->setVisible(true);
        $this->kindworkdetail->setVisible(false);
    }

}


class Works implements \Zippy\Interfaces\DataItem
{
    public $id;
    public $work;
    public $short;
    public $price;
    public $pa_id;

    public function __construct($id, $work, $short, $price, $pa_id = 0)
    {
        $this->id = $id;
        $this->work = $work;
        $this->short = $short;
        $this->price = $price;
        $this->pa_id = $pa_id;
    }

    public function getID()
    {
        return $this->id;
    }
}