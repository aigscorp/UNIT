<?php

namespace App\Pages;

use App\Application as App;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Link\SubmitLink;

class ControlProd extends \App\Pages\Base
{
    public $modelWorks = [];
    public $models = [];
    public $pasportID = "";
//    public $total = 0;
//    public $list_works = [];


    public function __construct($params = null)
    {
        parent::__construct($params);
        $conn = \ZDB\DB::getConnect();

        $sql = "SELECT p.id as pasport_id, m.id as model_id, p.name, p.size, p.comment as detail, p.quantity as qty
                FROM pasport p, model m WHERE m.in_work = true AND m.pasport_id = p.id AND m.finished = false";
        $rs = $conn->Execute($sql);

        $model_list = [];
        foreach ($rs as $r){
            $matches = [];
            $res = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>,/i', $r['detail'], $matches);
            $sizes = [];
            if($res == true){
                $sizes = array_combine($matches[1], $matches[2]);
            }
            $this->models[] = new ModelWork($r['pasport_id'], $r['model_id'], $r['name'], $sizes,  $r['qty']);
            $model_list[$r['model_id']] = $r['name'] . ", " . $r['size'];
        }

        $this->add(new Label('msg_prod'));
        if($params != null){
            $this->msg_prod->setText($params);
        }

        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);
        $this->add(new Form('modelWorkSelect'));
        $this->modelWorkSelect->add(new DropDownChoice('selectModel'))->onChange($this, 'selectModelOnChange');
        $this->modelWorkSelect->selectModel->setOptionList($model_list);

        $this->add(new Form('totalWorkForm'));
        $this->add(new Form('panelMonitor'));
        $this->panelMonitor->add(new SubmitLink('doneProduction'))->onClick($this, 'doneProductionOnClick');
        $this->panelMonitor->add(new SubmitButton('finishProduction'))->onClick($this, 'finishProductionOnClick', true); //заменить на SubmitButton

        $this->add(new ComponentProd('tableModelComponent'));//->onClick($this, 'testOnClick');

    }

    public function getModelDefect($pid){
        $conn = \ZDB\DB::getConnect();

        $sql = "SELECT m.id, d.detail FROM defect_model d, model m, pasport p 
                WHERE d.status = false AND m.in_work = true AND m.finished = false 
                AND m.pasport_id = '{$pid}' AND p.id=m.pasport_id AND d.model_id=m.id";
        $rs = $conn->Execute($sql);

        $defects = [];
        foreach ($rs as $r){
            $model_defect = new \stdClass();
            $model_defect->model_id = $r['id'];
            $detail = $r['detail'];
            $pattern = '/\<master\>([а-яА-ЯЁёa-zA-Z0-9 ()-.,]+)\<\/master\>\<work\>([а-яА-ЯЁёa-zA-Z0-9 ()-.,]+)\<\/work\>\<size\>([0-9 ]+)\<\/size\>\<work_id\>([0-9 ]+)\<\/work_id\>\<emp_id\>([0-9 ]+)\<\/emp_id\>/u';
//            $pattern = '/\<work\>([а-яА-ЯЁёa-zA-Z0-9 ()-.,]+)\<\/work\>\<size\>([0-9 ]+)\<\/size\>\<work_id\>([0-9 ]+)\<\/work_id\>\<emp_id\>([0-9 ]+)\<\/emp_id\>/u';
            $res = preg_match($pattern, $detail, $match);
            $model_defect->emp_name = $match[1];
            $model_defect->work = $match[2];
            $model_defect->size = $match[3];
            $model_defect->work_id = $match[4];
            $model_defect->emp_id = $match[5];
            $defects[] = $model_defect;
        }
        return $defects;
    }

    public function getModelWorks($pid, $area = true){
        $this->pasportID = $pid;
        $pasport_id = $pid;
        $list_defects = $this->getModelDefect($pasport_id);

        if($area == true){
            $sql = "SELECT m.id, m.typework_id, m.emp_id, m.detail, e.emp_name, k.work, k.parealist_id, p.pa_name as area_name 
                    FROM masters m, employees e, kindworks k, parealist p 
                WHERE k.parealist_id = p.pa_id AND e.employee_id = m.emp_id AND k.id = m.typework_id AND m.pasport_id = " . $pasport_id;
        }else{
            $sql = "SELECT m.id, m.typework_id, m.emp_id, m.detail, e.emp_name, k.work FROM masters m, employees e, kindworks k 
                WHERE e.employee_id = m.emp_id AND k.id = m.typework_id AND m.pasport_id = " . $pasport_id;
        }

        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute($sql);
        foreach ($this->models as $model){
            if($model->pasport_id == $pid){
                $total_sizes = $model->size;
                $model_name = $model->name;
                $model_id = $model->model_id;
                $model_qty = intval($model->quantity);
                break;
            }
        }

        $list_work_emp = [];
        $area_names = [];
        foreach ($rs as $r){
            $matches = [];
            $res = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<quantity\>([0-9]+)\<\/quantity\>/i', $r['detail'], $matches);
            $size_qty = [];
            if($res != false){
                $size_qty = array_combine($matches[1], $matches[2]);
            }
            $typeid = intval($r['typework_id']);
            $empid = intval($r['emp_id']);
            $area_id = $area == true ? intval($r['parealist_id']) : 0;
            $txt_area_name = "";
            if($area == true) $txt_area_name = $r['area_name'];
            $list_work_emp[] = new ListMastersWork(intval($r['id']), intval($typeid), intval($empid),
                $size_qty, $r['emp_name'], $r['work'], intval($area_id), $txt_area_name);

            if(array_key_exists($area_id, $area_names) == false){
                $area_names[$area_id] = $r['area_name'];
            }
        }
        $this->modelWorks = $list_work_emp;

        ksort($area_names);

        ListMastersWork::sortByEmpID($list_work_emp, 'area_id');
        $modelTableSize = new \stdClass();
        $modelTableSize->model_id = $model_id;
        $modelTableSize->list_work_size = [];
        $modelTableSize->list_emp_work = [];
        $modelTableSize->list_work_defect = [];
        $modelTableSize->list_emp_defect = [];
        $modelTableSize->list_total_work = [];

        foreach ($area_names as $a_key=>$v_key){
            $modelArea = new \stdClass();
            $modelArea->area_id = $a_key;
            $modelArea->area_name = $v_key;
            $works = ListMastersWork::getWorkNameByAreaID($list_work_emp, $a_key);
            $modelArea->works[] = $works;
            $count_work = count($modelArea->works[0]) - 1;
            foreach ($total_sizes as $kts=>$vts){
                $trr = [];
                $trr[] = $model_name . ", " . $kts;
                $trr1 = array_fill(1, $count_work, $vts);
                $res_trr = array_merge($trr, $trr1);
                $modelArea->works[] = $res_trr;
            }
            $modelTableSize->list_work_size[] = $modelArea;
            $modelAreaEmp = new \stdClass();
            $modelAreaEmp->area_id = $a_key;
            $modelAreaEmp->area_name = $v_key;
            $modelAreaEmp->emps = ListMastersWork::getEmpNameByAreaID($list_work_emp, $works, $a_key);
            $modelTableSize->list_emp_work[] = $modelAreaEmp;

            $modelWorkDefect = new \stdClass();
            $modelWorkDefect->emp_id = [];
            $modelWorkDefect->work_id = [];
            $modelWorkDefect->size = [];
            $modelWorkDefect->defects = [];

            $modelEmpDefect = new \stdClass();
            $modelEmpDefect->emp_id = [];
            $modelEmpDefect->work_id = [];
            $modelEmpDefect->size = [];
            $modelEmpDefect->defects = [];

            foreach ($list_defects as $ls){
                $wn = $ls->work;
                for($j = 1; $j < count($works); $j++){
                    if($works[$j] == $wn){
                        $y = $j;
                        $size = $ls->size;
                        for($k = 1; $k < count($modelArea->works); $k++){
                            $arr_sz = explode(",", $modelArea->works[$k][0]);
                            if(trim($arr_sz[1]) == $size){
                                $x = $k;
                                $modelWorkDefect->defects[] = [$x, $y];
                                $modelWorkDefect->work_id[] = $ls->work_id;
                                $modelWorkDefect->size[] = $size;
                                $modelWorkDefect->emp_id[] = $ls->emp_id;
                                break;
                            }
                        }
                        for($n = 1; $n < count($modelAreaEmp->emps); $n++){
                            if($modelAreaEmp->emps[$n][0] == $ls->emp_name){
                                $ex = $n;
                                $modelEmpDefect->defects[] = [$ex, $y];
                                $modelEmpDefect->work_id[] = $ls->work_id;
                                $modelEmpDefect->size[] = $size;
                                $modelEmpDefect->emp_id[] = $ls->emp_id;
                                break;
                            }
                        }
                    }
                }
            }
            $modelTableSize->list_work_defect[] = $modelWorkDefect;
            $modelTableSize->list_emp_defect[] = $modelEmpDefect;
            $totals = $modelAreaEmp->emps;
            $modelTotalWork = new \stdClass();
            $modelTotalWork->total = [];

            $count_col = count($totals[0]);
            $t1 = array_fill(0, $count_col, "");
            $t1[0] = "всего выполнено";
            $t2 = array_fill(0, $count_col, "");
            $t2[0] = "нужно выполнить";
            $tz = $works;
            $tz[0] = "";
            for($m = 1; $m < $count_col; $m++){
                $sum = 0;
                for($p = 1; $p < count($totals); $p++){
                    $sum += $totals[$p][$m];
                }

                $t1[$m] = $sum;
                $t2[$m] = $model_qty - $sum;
            }
            $modelTotalWork->total[] = $tz;
            $modelTotalWork->total[] = $t1;
            $modelTotalWork->total[] = $t2;
            $modelTableSize->list_total_work[] = $modelTotalWork;
        }
        $this->tableModelComponent->setValue($modelTableSize);
    }

    public function selectModelOnChange($sender){
        $value = $this->modelWorkSelect->selectModel->getValue();
        if($value == 0){
            $this->tableModelComponent->setValue(null);
            $this->pasportID = "";
            return false;
        }
        foreach ($this->models as $model){
            if($model->model_id == $value){
                $pid = $model->pasport_id;
                break;
            }
        }
//        $this->getModelWorks($pid, false);
        $this->getModelWorks($pid);

    }

    public function doneProductionOnClick($sender){
        $count = $this->count;


    }
    public function finishProductionOnClick($sender){
        if($this->pasportID == "") return false;
        $sizes = [];
        $model_id = "";
        foreach ($this->models as $mod){
            if($mod->pasport_id == $this->pasportID){
                $sizes = $mod->size;
                $model_id = $mod->model_id;
                break;
            }
        }

        $typeworks = [];
        foreach ($this->modelWorks as $mw){
            $work_id = $mw->typework_id;
            if(array_key_exists($work_id, $typeworks) == false){
                $typeworks[$work_id] = $mw->size_qty;
            }else{
                $sz1 = $mw->size_qty;
                $sz2 = $typeworks[$work_id];
                foreach ($sz1 as $k=>$v){
                    $sz2[$k] += $v;
                }
                $typeworks[$work_id] = $sz2;
            }
        }
        foreach ($sizes as $sk=>$sv){
            foreach ($typeworks as $type){
                foreach ($type as $tk=>$tv){
                    if($sk == $tk){
                        if(intval($sizes[$sk]) > $tv){
                            $sizes[$sk] = $tv;
                        }
                    }
                }
            }
        }

        $min = current($sizes);
        foreach ($sizes as $sz){
            if($min > $sz) $min = $sz;
        }
        $modelComplect = floatval($min);

        $sql = "SELECT detail FROM pasport_tax WHERE qty_material = true AND pasport_id = " . $this->pasportID;
        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute($sql);
        $material_qty = [];
        $stock_str = "";
        foreach ($rs as $r){
            $detail = preg_match('/\<material\>([0-9]+)\<\/material\>\<quantity\>([0-9,. ]+)\<\/quantity\>/i', $r['detail'], $matches);
            $material_qty[$matches[1]] = floatval($matches[2] * $modelComplect);
            $stock_str .= "'{$matches[1]}'" . ",";
//            $stock_str .= "ss.item_id = '{$matches[1]}' AND ";
        }
        $stock_str = substr($stock_str, 0, -1);

        $sql = "SELECT count(*) as cnt FROM store_stock WHERE item_id IN(" . $stock_str . ")";
        $rscnt = $conn->getOne($sql);
        if(intval($rscnt) != count($material_qty)){
            $sql = "SELECT ss.stock_id, ss.item_id, ss.partion, ss.qty FROM store_stock ss WHERE ss.item_id IN(" . $stock_str . ")";
            $rs = $conn->Execute($sql);
            $stocks = [];
            foreach ($rs as $r){
                $stock_store = new \stdClass();
                $stock_store->stock_id = $r['stock_id'];
                $stock_store->item_id = $r['item_id'];
                $stock_store->price = $r['partion'];
                $stock_store->qty = floatval($r['qty']);
                $stocks[] = $stock_store;
            }
            foreach ($material_qty as $km=>$vm){
                foreach ($stocks as $stock){
                    if($stock->item_id == $km){
                        $stock->qty -= $vm;
                    }
                }
            }
            foreach ($stocks as $stock){
                $sql = "UPDATE store_stock SET qty = '{$stock->qty}' WHERE stock_id = " . $stock->stock_id;
                $conn->Execute($sql);
            }
            $sql = "UPDATE model SET in_work = false, finished = true WHERE pasport_id = " . $this->pasportID;
            $conn->Execute($sql);
            $js2 = "
                 let orig = window.location.origin;
                 window.location = orig + '/index.php?p=/App/Pages/Production'              
                ";
            $this->updateAjax(array(), $js2);
        }else{
            $msg = "На складе не оприходованы материалы, которые используются в модели";
//            App::Redirect("\\App\\Pages\\ControlProd", $msg);
//            $js2 = "
//                 let orig = window.location.origin;
//                 window.location = orig + '/index.php?p=/App/Pages/ControlProd'
//                ";
            $js = "
                    $('#msg_prod').append(\"<div style='margin: 10px 5px'><p style='color: darkred; font-size: 1.5em'>{$msg}</p></div>\");
                    $('#msg_prod').children().fadeOut(5000, \"linear\", function(){\$('#msg_prod').children().remove()} );
                    ";
            $this->updateAjax(array(), $js);
//            $this->updateAjax(array(), $js2);
        }


    }
    public function finishProductionOnClick_old($sender){
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


        $list_sizes_done = [];
        foreach ($list_swd as $ls){
            $count_work = [];
            $sizes_done = [];
            foreach ($ls->made_work as $mw){
                $match = [];
                $res = preg_match_all('/\<size\>([0-9]+)\<\/size\>\<done\>([0-9]+)\<\/done\>/i', $mw, $match);
                if(count($sizes_done) == 0){
                    for($k = 0; $k < count($match[1]); $k++){
                        $sizes_done[$match[1][$k]] = 0;
                    }
                }
                $count_work[] = array_combine($match[1], $match[2]);
            }
            for($j = 0; $j < count($count_work); $j++){
                foreach ($count_work[$j] as $k1=>$v1){
                    if(array_key_exists($k1, $sizes_done) == true){
                        $sizes_done[$k1] = intval($sizes_done[$k1]) + intval($v1);
                    }
                }
            }
//            $ls->size_done = $sizes_done;
            $list_sizes_done[] = $sizes_done;
        }

        $srr = [];
        $keyrr = array_keys($list_sizes_done[0]);
        for($i = 0; $i < count($keyrr); $i++){
            $srr[$keyrr[$i]] = min(array_column($list_sizes_done, $keyrr[$i]));
        }

    }
}


class ModelWork
{
    public $pasport_id;
    public $model_id;
    public $name;
    public $size;
    public $quantity;

    public function __construct($pid, $mid, $name, $size, $qty)
    {
        $this->pasport_id = $pid;
        $this->model_id = $mid;
        $this->name = $name;
        $this->size = $size;
        $this->quantity = $qty;
    }
}

class ListMastersWork
{
    public $id;
    public $emp_id;
    public $typework_id;
    public $emp_name;
    public $size_qty;
    public $work_name;
    public $area_id;
    public $area_name;

    static $sortKey;

    public function __construct($id, $typework_id, $emp_id, $size_qty, $emp_name, $work_name, $area_id=0, $area_name = "")
    {
        $this->id = $id;
        $this->typework_id = $typework_id;
        $this->emp_id = $emp_id;
        $this->size_qty = $size_qty;
        $this->emp_name = $emp_name;
        $this->work_name = $work_name;
        $this->area_id = $area_id;
        $this->area_name = $area_name;
    }
    public function getSizeQty(){
        return $this->size_qty;
    }
    public function getWorkName(){
        return $this->work_name;
    }

    public function getID() { return $this->id; }

    static function cmp($a, $b){
        return $a->{self::$sortKey} - $b->{self::$sortKey};
    }

    public static function sortByEmpID(&$collection, $prop){
        self::$sortKey = $prop;
        usort($collection, array(__CLASS__, 'cmp'));
    }

    public static function getWorkNameByAreaID($collection, $area_id = 0){
        $tmp = [];
        foreach ($collection as $coll){
            if($coll->area_id == $area_id || $area_id == 0){
                $tmp[] = $coll->work_name;
            }
        }
        $t = array_unique($tmp);
        $tmp = [];
        $tmp[] = "Модель";
        foreach ($t as $p){
            $tmp[] = $p;
        }
        return $tmp;
    }
    public static function getEmpNameByAreaID($collection, $works, $area_id = 0){
        $tmp = [];
        $count_work = count($works);
        $works[0] = "Мастер ФИО";
        $arr_m = [];
        $arr_m[] = $works;

        foreach ($collection as $coll){
            if($area_id == $coll->area_id || $area_id == 0){
                $ind = 0;
                for($i = 0; $i < count($works); $i++){
                    if($works[$i] == $coll->work_name){
                        $ind = $i;
                        break;
                    }
                }

                $fnd = false;
                foreach ($arr_m as $k=>$v){
                    if($k == $coll->emp_id){
                        $fnd = true;
                        break;
                    }
                }
                if($fnd == false){
                    $arr_m[$coll->emp_id] = array_fill(0, $count_work, "");
                    $arr_m[$coll->emp_id][0] = $coll->emp_name;
                }
                $arr_m[$coll->emp_id][$ind] = $coll->sumWork();
            }
        }

        foreach ($arr_m as $item){
            $tmp[] = $item;
        }
        return $tmp;
    }

    public function sumWork(){
        $sum = 0;
        foreach ($this->size_qty as $sz){
            $sum += $sz;
        }
        return $sum;
    }
}

//class ListModelWork
//{
//    public $id;
//    public $model;
//    public $typework;
//    public $model_id;
//
//    public function __construct($id, $model, $typework ,$model_id)
//    {
//        $this->id = $id;
//        $this->model = $model;
//        $this->typework = $typework;
//        $this->model_id = $model_id;
//    }
//
//    public function getID() { return $this->id; }
//    public function getModelID() { return $this->model_id; }
//}

