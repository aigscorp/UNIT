<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 24.08.2021
 * Time: 20:30
 */

namespace App\Pages;

use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
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
    public $list_defect = [];
    public $list_total_work = [];

//    SELECT p.id as pasport_id, m.id, d.detail FROM defect_model d, model m, pasport p WHERE m.in_work = true AND m.id = d.model_id AND m.finished = false AND d.status = false AND p.id = m.pasport_id

    public function __construct($params = null)
    {
        parent::__construct($params);
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT p.id, p.name, t.type_work, m.id as model_id FROM pasport p, model m, typework t 
                WHERE m.in_work = true and p.id = m.pasport_id and t.pasport_id = p.id and m.finished = false";
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
//        <master>Арсен</master><work>Кройка</work><size>40</size><defect>Описание: Брак на коже. Моя вина. </defect>
        sort($brr);
        $sql_defect = "SELECT m.id, d.detail, m.pasport_id FROM defect_model d, model m 
                       WHERE m.in_work = true AND m.id = d.model_id AND m.finished = false AND d.status = false";
        $res = $conn->Execute($sql_defect);

        $tags = ["master","work","size"];
        foreach ($res as $r){
            $obj_defect = new \stdClass();
            $obj_defect->model_id = $r['id'];
            $obj_defect->pasport_id = $r['pasport_id'];
            for($i = 0; $i < count($tags); $i++){
                $elem = $tags[$i];
                $tag = $this->parseTagValue($r['detail'], $tags[$i]);
                $obj_defect->$elem = $tag;
            }
            $this->list_defect[] = $obj_defect;
        }


        $str_id = implode("','", $brr);
        $sql = "SELECT id, comment FROM pasport p WHERE p.id IN('{$str_id}')";
        $rs = $conn->Execute($sql);
        $total_size = [];
        foreach ($rs as $r){
            $total_size[$r['id']] = $r['comment'];
        }

        $res_master = $this->getMastersWork($brr, $total_size);

        for($i = 0; $i < count($brr); $i++){
            $id = $brr[$i];
//            $sql = "SELECT id, comment FROM pasport p WHERE p.id = " . $id;
//            $rs = $conn->Execute($sql);
            $detail = $total_size[$id]; // 'comment';
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
//            $this->list_total_work = $frr;
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
            foreach ($this->list_defect as $defect){
                if($model_ID == $defect->model_id){
                    $tbl->defect[] = $defect;
                }
            }
            $this->list_works[] = $tbl;
        }



        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);

        $this->add(new Form('totalWorkForm'));
        $this->add(new Form('panelMonitor'));
        if($res_master == false){
            $this->totalWorkForm->setVisible(false);
            $this->panelMonitor->setVisible(false);
        }
        $this->panelMonitor->add(new SubmitLink('finishProduction'))->onClick($this, 'finishProductionOnClick', true); //заменить на SubmitButton

        $this->add(new ComponentProd('tableModelComponent'));//->onClick($this, 'testOnClick');
        $this->add(new ComponentMaster('tableMasterComponent'));
        $this->add(new ComponentTotal('tableTotalComponent'));

        $this->add(new ClickLink('next'))->onClick($this, 'nextModelOnClick');
        $this->add(new ClickLink('prev'))->onClick($this, 'prevModelOnClick');


        $this->tableModelComponent->setValue($this->list_works[$this->count]);
        $this->tableMasterComponent->setValue($this->list_masters[$this->count]);
        $this->tableTotalComponent->setValue($this->list_total_work[$this->count]);
    }

    public function getMastersWork(array $arr, array $total)
    {
        if(count($arr) == 0) return false;

        $param = implode(",", $arr);

        $sql = "SELECT wrk.mid as mid, wrk.id, wrk.emp_id, e.emp_name, wrk.type_work, wrk.detail, wrk.name, wrk.init_quantity as init  
                FROM employees e, (SELECT tmp.name, tmp.type_work, m.id as mid, m.emp_id, tmp.id, m.detail, m.init_quantity 
                FROM masters m, ((SELECT pp.id, t.id as tid, t.type_work, pp.name 
                FROM typework t, (SELECT p.id,p.name FROM pasport p WHERE p.id IN(" .$param . ")) as pp 
                WHERE t.pasport_id = pp.id)) AS tmp WHERE m.typework_id = tmp.tid) AS wrk WHERE e.employee_id = wrk.emp_id";

        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute($sql);
        foreach ($rs as $r){
            $this->masters[] = new ListMastersWork($r['mid'], $r['id'], $r['emp_id'], $r['emp_name'],
                                                   $r['type_work'], $r['detail'], $r['name'], $r['init']);
        }

        for($i = 0, $k = 0; $i < count($arr); $i++, $k++){
            $prr = [];

            foreach ($this->masters as $master){
                if($arr[$i] == $master->pasport_id){
                    $str_total = $total[$arr[$i]];

                    $tmp = new \stdClass();
                    $fnd = false;
                    foreach($prr as $pr){
                        if($pr->emp_id == $master->emp_id){
                            //$this->parseTag($str_total, "quantity")
                            $pr->typework[$master->typework] = intval($master->init_quantity) - $this->parseTag($master->detail, "quantity");
                            $fnd = true;
                        }
                    }
                    if($fnd == false){
                        $tmp->emp_name = $master->emp_name;
                        $tmp->emp_id = $master->emp_id;
                        //$this->parseTag($str_total, "quantity")
                        $tmp->typework[$master->typework] = intval($master->init_quantity) - $this->parseTag($master->detail, "quantity");//$master->typework;
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

            $list_all_works = [];
            foreach ($this->modelWork as $mw){
                if($mw->id == $arr[$i]){
                    $list_all_works[$mw->typework] = 0;
                }
            }

            foreach ($prr as $item_prr){
                foreach ($item_prr->typework as $key_work=>$val_qty){
                    $list_all_works[$key_work] += $val_qty;
                }
            }

            $total_quantity = $this->parseTag($str_total, "quantity");

            $obj_total = new \stdClass();
            $obj_total->count = $k;
            $obj_total->total = $list_all_works;
            $obj_total->total_qty = $total_quantity;

            $this->list_total_work[] = $obj_total;
        }

        foreach ($this->list_defect as $defect){
            foreach ($this->list_masters as $master_defect){
                if($defect->pasport_id == $master_defect->pasport_id){
                    $master_defect->defect[] = $defect;
                }else{
//                    $master_defect->defect = null;
                }
            }

        }

        return true;
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

    public function parseTagValue($str_tag, $name){
        $matches = [];
        $pattern = "/\<" . $name . "\>" . "([а-яА-ЯЁёa-zA-Z0-9]+)" . "\<\/" . $name . "\>/u";
        $res = preg_match($pattern, $str_tag, $matches);
        if($res == false){
            echo "Error parsing " . $name . "<br>";
        }
        return $matches[1];
    }

    public function nextModelOnClick($sender)
    {
        $cnt = count($this->list_works);
        if($cnt-1 > $this->count) $this->count++;
        $this->tableModelComponent->setValue($this->list_works[$this->count]);
        $this->tableMasterComponent->setValue($this->list_masters[$this->count]);
        $this->tableTotalComponent->setValue($this->list_total_work[$this->count]);
//        $this->updateAjax(array('tableModelComponent'), $this->tableModelComponent->setValue($this->drr[$this->count]));
    }

    public function prevModelOnClick()
    {
        if($this->count > 0) $this->count--;
        $this->tableModelComponent->setValue($this->list_works[$this->count]);
        $this->tableMasterComponent->setValue($this->list_masters[$this->count]);
        $this->tableTotalComponent->setValue($this->list_total_work[$this->count]);
    }

    public function finishProductionOnClick($sender){
        $s = $sender;
        $count = $this->count;

        $totalComponent = $this->getComponent('tableTotalComponent');
        $arr_total = $totalComponent->value->total;
        $min_work = min($arr_total);
        $size_models = $this->list_works[$count]->elems;
        $pasport_id = $this->list_masters[$count]->pasport_id;
        $model_id = $this->list_works[$count]->model_id;

        $master_ids = [];
        foreach ($this->masters as $all_m){
            if($all_m->pasport_id == $pasport_id){
                $master_ids[] = $all_m->mid;
            }
        }

        $str_master_ids = implode("','", $master_ids);
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT m.emp_id, m.typework_id, m.init_quantity, m.made_work, m.finished, t.type_work as work_name  
                FROM masters m, typework t WHERE m.id IN('{$str_master_ids}') AND m.typework_id = t.id";
        $rs = $conn->Execute($sql);

        $list_works_done = [];
        $typework_ids = [];
        foreach ($rs as $r){
            $works_done = new \stdClass();
            $works_done->emp_id = $r['emp_id'];
            $typework_id = $r['typework_id'];
            $works_done->typework_id = $typework_id;
            $typework_ids[$typework_id] = $r['work_name'];
            $works_done->init_quantity = $r['init_quantity'];
            $works_done->made_work = $r['made_work'];
            $works_done->finished = $r['finished'];
            $works_done->work_name = $r['work_name'];
            $list_works_done[] = $works_done;
        }

        $typework_order = array_unique($typework_ids);
//        $typework_order = [];
//        foreach ($typework_id_unique as $tiu){
//            $typework_order[] = $tiu;
//        }

        $list_swd = [];
        foreach ($typework_order as $kto=>$vto){
            $sort_work_done = new \stdClass();
            $sort_work_done->typework_id = $kto;
            $sort_work_done->work_name = $vto;
            $sort_work_done->init_quantity = 0;
            $fnd = false;
            foreach ($list_works_done as $lwd){
                if($lwd->typework_id == $kto){
                    $sort_work_done->made_work[] = $lwd->made_work;
                    $sort_work_done->init_quantity += intval($lwd->init_quantity);
                    $sort_work_done->emp_id[] = $lwd->emp_id;
                    $sort_work_done->finished[$lwd->emp_id] = $lwd->finished;
                    $fnd = true;
                }
            }
            if($fnd == true){
                $list_swd[] = $sort_work_done;
            }
        }



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
    public $init_quantity;

    public function __construct($mid, $pasport_id, $emp_id, $emp_name, $typework, $detail, $model, $init)
    {
        $this->mid = $mid;
        $this->pasport_id = $pasport_id;
        $this->emp_id = $emp_id;
        $this->emp_name = $emp_name;
        $this->typework = $typework;
        $this->detail = $detail;
        $this->model = $model;
        $this->init_quantity = $init;
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