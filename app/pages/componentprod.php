<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 24.08.2021
 * Time: 21:26
 */

namespace App\Pages;


class ComponentProd extends \Zippy\Html\CustomComponent implements \Zippy\Interfaces\Requestable
{
    public $str = "";
    public $value = null;

    public function __construct($id)
    {
        parent::__construct($id);

    }
    public function getContent($attributes)
    {
        // TODO: Implement getContent() method.
        $arr = $this->value;
        if($arr == null){
            $this->str = "";
            return $this->str;
        }
        $work_size = $arr->list_work_size;
        $emp_work = $arr->list_emp_work;
        $work_defect = $arr->list_work_defect;
        $emp_defect = $arr->list_emp_defect;
        $total_work = $arr->list_total_work;

        $this->str = "";
        $model_id = $arr->model_id;
        for($i = 0; $i < count($work_size); $i++){
            $w = $work_size[$i]->works;
            $row = count($w);
            $col = count($w[0]);
            $this->str .= $this->createTable($row, $col, $w, $model_id, "model", $work_size[$i], $work_defect[$i]);

            $e = $emp_work[$i]->emps;
            $row1 = count($e);
            $col1 = count($e[0]);
            $this->str .= $this->createTable($row1, $col1, $e, $model_id,"0", $emp_work[$i], $emp_defect[$i]);

            $t = $total_work[$i]->total;
            $row2 = count($t);
            $col2 = count($t[0]);

            $this->str .= $this->createTable($row2, $col2, $t, $model_id, "1", $emp_work[$i]);

        }

        return $this->str;
    }

    public function RequestHandle()
    {
        // TODO: Implement RequestHandle() method.

    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function createTable($row, $col, $arr, $mid, $data, $area = "", $color=[])
    {
        $model = "";
        if(strlen($mid) > 0) $model = "model_" . $mid . "_" . $area->area_id . "_" .$data;
//        if(strlen($area) > 0) $area = ", " . $area;
        if($data == "model"){
            $tpl = "<h2 class='area-panel' style='background: #000; color: #fff; padding-left: 5px; border-radius: 5px;'>{$area->area_name}</h2>";
            $tpl .= "<div class='show-panel'>";
            $tpl .= "<p class='h5 text-muted' style='padding: 5px 0;font-weight: 600;'>Общее количество работ</p>";
        }else{
            if($data == "0"){
                $tpl = "<p class='h5 text-muted' style='padding: 5px 0;font-weight: 600;'>Список сотрудников в работе данной модели</p>";
            }

        }

        $tpl .= "<table id=" . $model . " " . "data-model=" . $data . "  class='table table-striped table-sm table-bordered' style='margin: 10px 0;'>";
        for ($i = 0; $i < $row; $i++){
            $tpl .= "<tr>";
            for($j = 0; $j < $col; $j++){
                if($i == 0){
                    if($j == 0){
                        $tpl .= "<th style='width: 180px;'>" . $arr[$i][$j] . "</th>";
                    }else{
                        $tpl .= "<th>" . $arr[$i][$j] . "</th>";
                    }

                }else{
                    $fnd = false;
                    $color1 = $color->defects;
                    $sz = "";
                    for($p = 0; $p < count($color1); $p++){
                        if($color1[$p][0] == $i && $color1[$p][1] == $j){
                            $fnd = true;
                            $sz = $color->size[$p];
                            $emp_id = $color->emp_id[$p];
                            $work_id = $color->work_id[$p];
                            break;
                        }
                    }
                    if($fnd == true){
                        $emp_work = $mid . "-" . $sz . "-" . $emp_id . "-" . $work_id;
                        $tpl .= "<td style='border-radius: 5px;background: #ed2d2d;' data-color='{$emp_work}';>" . $arr[$i][$j] . "</td>";
                    }else{
                        $tpl .= "<td style='border-radius: 5px;'>" . $arr[$i][$j] . "</td>";
                    }
                }
            }
            $tpl .= "</tr>";
        }
        $tpl .= "</table>";
        if($data == "1"){
            $tpl .= "</div>";
        }
        return $tpl;
    }
}