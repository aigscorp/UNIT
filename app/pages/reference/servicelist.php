<?php

namespace App\Pages\Reference;

use App\Entity\Service;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use \Zippy\Html\Form\DropDownChoice;

class ServiceList extends \App\Pages\Base
{

    private $_service;
    public $area;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('ServiceList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new CheckBox('showdis'));
        $this->filter->add(new TextInput('searchkey'));

        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT pa_id, pa_name FROM parealist";
        $rs = $conn->Execute($sql);
        $area = [];
        foreach ($rs as $r){
            $area[$r['pa_id']] = $r['pa_name'];
        }
        $this->area = $area;

        $this->add(new Panel('servicetable'))->setVisible(true);
        $this->servicetable->add(new DataView('servicelist', new ServiceDataSource($this), $this, 'servicelistOnRow'))->Reload();
        $this->servicetable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->servicetable->servicelist->setPageSize(H::getPG());
        $this->servicetable->add(new \Zippy\Html\DataList\Paginator('pag', $this->servicetable->servicelist));


        $this->add(new Form('servicedetail'))->setVisible(false);
        $this->servicedetail->add(new TextInput('editservice_name'));
        $this->servicedetail->add(new TextInput('editprice'));
        $this->servicedetail->add(new DropDownChoice('editarea', $area));//->onChange($this, "onEditArea");
        $this->servicedetail->add(new TextInput('editcost'));
        $this->servicedetail->add(new TextInput('edithours'));
        $this->servicedetail->add(new CheckBox('editdisabled'));

        $this->servicedetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->servicedetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function servicelistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();

        $row->add(new Label('service_name', $item->service_name));
        $row->add(new Label('price', $item->price));
        $row->add(new Label('cost', $item->cost));
        $area_name = $this->area[$item->area];
        $row->add(new Label('area', $area_name));
//        $row->add(new Label('hours', $item->hours));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('ServiceList')) {
            return;
        }

        $service_id = $sender->owner->getDataItem()->service_id;

        $del = Service::delete($service_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->servicetable->servicelist->Reload();
    }

    public function editOnClick($sender) {
        $this->_service = $sender->owner->getDataItem();
        $this->servicetable->setVisible(false);
        $this->servicedetail->setVisible(true);
        $this->servicedetail->editservice_name->setText($this->_service->service_name);
//        $area = $this->servicedetail->editarea->getOptionList();
//        $area_id = $this->servicedetail->editarea->getValue();
        $this->servicedetail->editarea->setValue($this->_service->area);
        $this->servicedetail->editprice->setText($this->_service->price);
        $this->servicedetail->editcost->setText($this->_service->cost);
        $this->servicedetail->edithours->setText($this->_service->hours);
        $this->servicedetail->editdisabled->setChecked($this->_service->disabled);
    }

    public function addOnClick($sender) {
        $this->servicetable->setVisible(false);
        $this->servicedetail->setVisible(true);
        // ??????????????  ??????????
        $this->servicedetail->clean();

        $this->_service = new Service();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('ServiceList')) {
            return;
        }
        /////?????????????? ????????????????????, ?????????????????? ???????? ???????????????????????????????? ??????????????

        $this->_service->service_name = $this->servicedetail->editservice_name->getText();
        $this->_service->price = $this->servicedetail->editprice->getText();
        $this->_service->cost = $this->servicedetail->editcost->getText();
        $this->_service->hours = $this->servicedetail->edithours->getText();
        $this->_service->area = $this->servicedetail->editarea->getValue();
        if ($this->_service->service_name == '') {
            $this->setError("entername");
            return;
        }
        $this->_service->disabled = $this->servicedetail->editdisabled->isChecked() ? 1 : 0;

        $this->_service->Save();
        $this->servicedetail->setVisible(false);
        $this->servicetable->setVisible(true);
        $this->servicetable->servicelist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->servicetable->setVisible(true);
        $this->servicedetail->setVisible(false);
    }

    public function OnFilter($sender) {
        $this->servicetable->servicelist->Reload();
    }

}

class ServiceDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $form = $this->page->filter;
        $where = "1=1";
        $text = trim($form->searchkey->getText());
        $showdis = $form->showdis->isChecked();

        if ($showdis > 0) {

        } else {
            $where = $where . " and disabled <> 1";
        }
        if (strlen($text) > 0) {
            $text = Service::qstr('%' . $text . '%');
            $where = $where . " and service_name like {$text}   ";
        }
        return $where;
    }

    public function getItemCount() {
        return Service::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Service::find($this->getWhere(), "service_name asc", $count, $start);
    }

    public function getItem($id) {
        return Service::load($id);
    }

}
