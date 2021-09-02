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
    public $list_works = [];
    public $masters = [];
    public $list_masters = [];

    public function __construct($params = null)
    {
        parent::__construct($params);
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT p.id, p.name, t.type_work, m.id as model_id FROM pasport p, model m, typework t 
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
            $this->modelWork[] = new ListModelWork($r['id'], $r['name'], $r['type_work'], $r['model_id']);
        }
//        $crr = array_unique($brr, SORT_NUMERIC);
        sort($brr);
        $this->getMastersWork($brr);

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
            $model_ID = null;
            foreach ($this->modelWork as $mw){
                if($mw->getID() == $id){
                    $hrr[] = $mw->typework;
                    $modelName = $mw->model;
                    $model_ID = $mw->getModelID();
                }
            }
            $trr[] = $hrr;
            foreach ($this->list_masters as $list_master){
                if($list_master->pasport_id == $id){
//                    array_shift($hrr);
                    $hrr[0] = "Мастер ФИО";
                    $list_master->list_typework = $hrr;
                    break;
                }
            }

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
            $tbl->model_id = $model_ID;
            $this->list_works[] = $tbl;

        }

        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);

        $this->add(new Form('totalWorkForm'));
        $this->add(new ComponentProd('tableModelComponent'));//->onClick($this, 'testOnClick');
        $this->add(new ComponentMaster('tableMasterComponent'));
        $this->add(new ClickLink('next'))->onClick($this, 'nextModelOnClick');
        $this->add(new ClickLink('prev'))->onClick($this, 'prevModelOnClick');

        $this->tableModelComponent->setValue($this->list_works[$this->count]);
        $this->tableMasterComponent->setValue($this->list_masters[$this->count]);

    }

    public function getMastersWork(array $arr)
    {
        if(is_array($arr) == false) return false;

        $param = implode(",", $arr);

        $sql = "SELECT wrk.mid as mid, wrk.id, wrk.emp_id, e.emp_name, wrk.type_work, wrk.detail, wrk.name  
                FROM employees e, (SELECT tmp.name, tmp.type_work, m.id as mid, m.emp_id, tmp.id, m.detail 
                FROM masters m, ((SELECT pp.id, t.id as tid, t.type_work, pp.name 
                FROM typework t, (SELECT p.id,p.name FROM pasport p WHERE p.id IN(" .$param . ")) as pp 
                WHERE t.pasport_id = pp.id)) AS tmp WHERE m.typework_id = tmp.tid) AS wrk WHERE e.employee_id = wrk.emp_id";

        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
            $this->masters[] = new ListMastersWork($r['mid'], $r['id'], $r['emp_id'], $r['emp_name'], $r['type_work'], $r['detail'], $r['name']);
        }

        for($i = 0, $k = 0; $i < count($arr); $i++, $k++){
            $prr = [];
            foreach ($this->masters as $master){
                if($arr[$i] == $master->pasport_id){
                    $tmp = new \stdClass();
                    $fnd = false;
                    foreach($prr as $pr){
                        if($pr->emp_id == $master->emp_id){
                            $pr->typework[$master->typework] = $this->parseTag($master->detail, "quantity");
                            $fnd = true;
                        }
                    }
                    if($fnd == false){
                        $tmp->emp_name = $master->emp_name;
                        $tmp->emp_id = $master->emp_id;
                        $tmp->typework[$master->typework] = $this->parseTag($master->detail, "quantity");//$master->typework;
                        $tmp->model = $master->model;
                        $prr[] = $tmp;
                    }
                }
            }
            $obj_master = new \stdClass();
            $obj_master->count = $k;
            $obj_master->pasport_id = $arr[$i];
            $obj_master->masters = $prr;
            $this->list_masters[] = $obj_master;
        }

        $a = 1;
        $b = $a + 2;

    }

    public function parseTag($str_tag, $name)
    {
        $matches = [];
        $pattern = "/\<" . $name . "\>" . "([0-9]+)" . "\<\/" . $name . "\>/i";
        $res = preg_match_all($pattern, $str_tag, $matches);
        if($res == false){
            echo "Error parsing " . $name . "<br>";
        }

        $sum = 0;
        for($i = 0; $i < count($matches[1]); $i++){
            $sum += intval($matches[1][$i]);
        }
        return $sum;

    }

    public function nextModelOnClick($sender)
    {
        $cnt = count($this->list_works);
        if($cnt-1 > $this->count) $this->count++;
        $this->tableModelComponent->setValue($this->list_works[$this->count]);
        $this->tableMasterComponent->setValue($this->list_masters[$this->count]);
//        $this->updateAjax(array('tableModelComponent'), $this->tableModelComponent->setValue($this->drr[$this->count]));
    }

    public function prevModelOnClick()
    {
        if($this->count > 0) $this->count--;
        $this->tableModelComponent->setValue($this->list_works[$this->count]);
        $this->tableMasterComponent->setValue($this->list_masters[$this->count]);
    }
}

class ListModelWork
{
    public $id;
    public $model;
    public $typework;
    public $model_id;

    public function __construct($id, $model, $typework ,$model_id)
    {
        $this->id = $id;
        $this->model = $model;
        $this->typework = $typework;
        $this->model_id = $model_id;
    }

    public function getID() { return $this->id; }
    public function getModelID() { return $this->model_id; }
}

class ListMastersWork
{
    public $mid;
    public $pasport_id;
    public $emp_id;
    public $typework;
    public $emp_name;
    public $detail;
    public $model;

    public function __construct($mid, $pasport_id, $emp_id, $emp_name, $typework, $detail, $model)
    {
        $this->mid = $mid;
        $this->pasport_id = $pasport_id;
        $this->emp_id = $emp_id;
        $this->emp_name = $emp_name;
        $this->typework = $typework;
        $this->detail = $detail;
        $this->model = $model;
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
 *
 *
 * select t.type_work, m.emp_id,m.detail from typework t, masters m where t.pasport_id=31 and t.id=m.typework_id
 *
 *
 */