<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 24.08.2021
 * Time: 20:30
 */

namespace App\Pages;

use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Link\SubmitLink;

class ControlProd extends \App\Pages\Base
{
    public $count = 0;
    public $modelWork = [];
    public $total = 0;
    public $drr = [];

    public function __construct($params = null)
    {
        parent::__construct($params);
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT p.id, p.name, t.type_work FROM pasport p, model m, typework t 
                WHERE m.in_work = true and p.id = m.pasport_id and t.pasport_id = p.id";

        $rs = $conn->Execute($sql);

        $brr = [];
        foreach ($rs as $r){
            $id = $r['id'];
            if(count($brr) != 0){
                $fnd = false;
                for($k = 0; $k < count($brr); $k++){
                    if($brr[$k] == $id){
                        $fnd = true;
                        break;
                    }
                }
                if($fnd == false) $brr[] = $id;
            }else{
                $brr[] = $r['id'];
            }

                $this->modelWork[] = new ListModelWork($r['id'], $r['name'], $r['type_work']);

        }

//        $crr = array_unique($brr, SORT_NUMERIC);

        sort($brr);

        for($i = 0; $i < count($brr); $i++){
            $id = $brr[$i];
            $sql = "SELECT id, comment FROM pasport p WHERE p.id = " . $id;
            $rs = $conn->Execute($sql);
            $detail = $rs->fields['comment'];
            if($detail == "") continue;
            if(str_ends_with($detail, ",") == true){
                $detail = substr($detail, 0, -1);
            }

            $crr = explode(",", $detail);
            $matches = [];
            $frr = [];

            for($j = 0; $j < count($crr); $j++){
                $pm = preg_match('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>/i', $crr[$j], $matches);
                if($pm == true){
                    $frr[$matches[1]] = $matches[2];
                }
            }

            $trr = [];
            $hrr = ["Модель"];
            $modelName = "";
            foreach ($this->modelWork as $mw){
                if($mw->getID() == $id){
                    $hrr[] = $mw->typework;
                    $modelName = $mw->model;
                }
            }
            $trr[] = $hrr;

            foreach ($frr as $key=>$val){
                $vrr = [];
                $vrr[] = $modelName . ", " . $key;
                for($k = 0; $k < count($hrr)-1; $k++){
                    $vrr[] = $val;
                }
                $trr[] = $vrr;
            }

            $tbl = new \stdClass();
            $tbl->id = $id;
            $tbl->elems = $trr;
            $tbl->count = $i;
            $this->drr[] = $tbl;

        }

        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);

        $this->add(new Form('totalWorkForm'));
        $this->add(new ComponentProd('testComponent'));//->onClick($this, 'testOnClick');
        $this->add(new ClickLink('next'))->onClick($this, 'nextModelOnClick');
        $this->add(new ClickLink('prev'))->onClick($this, 'prevModelOnClick');

        $this->testComponent->setValue($this->drr[$this->count]);
    }

    public function nextModelOnClick($sender)
    {
        $cnt = count($this->drr);
        if($cnt-1 > $this->count) $this->count++;
        $this->testComponent->setValue($this->drr[$this->count]);
//        $this->updateAjax(array('testComponent'), $this->testComponent->setValue($this->drr[$this->count]));
    }

    public function prevModelOnClick()
    {
        if($this->count > 0) $this->count--;
        $this->testComponent->setValue($this->drr[$this->count]);
    }
}

class ListModelWork
{
    public $id;
    public $model;
    public $typework;
//    public $detail;

    public function __construct($id, $model, $typework /*,$detail*/)
    {
        $this->id = $id;
        $this->model = $model;
        $this->typework = $typework;
//        $this->detail = $detail;
    }

    public function getID() { return $this->id; }
}

/*
 * SELECT
    p.id,
    p.name,
    p.comment,
    t.type_work
FROM
    pasport p,
    model m,
    typework t
WHERE
    m.in_work = TRUE AND p.id = m.pasport_id AND t.pasport_id = p.id
 *
 *
 * select t.type_work, t.pasport_id, m.detail from typework t, masters m where t.id=m.typework_id
 */