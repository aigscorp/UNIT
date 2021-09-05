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
//        var_dump($this->value);
        $arr = $this->value;
        $brr = $arr->elems;
        $model_id = $arr->model_id;
        $colors = [];
        $defect = $arr->defect;
        foreach ($defect as $d){
            $work = $d->work;
            $size = $d->size;
            $x = 0; $y = 0;
            for($j = 1; $j < count($brr[0]); $j++){
                if($brr[0][$j] == $work) $y = $j;
            }
            for($i = 1; $i < count($brr); $i++){
                $txt = explode(",", $brr[$i][0]);
                $sz = $txt[1].trim();
                if($sz == $size) $x = $i;
            }
            $colors[] = [$x, $y];
        }
        $row = count($brr);
        $col = count($brr[0]);
//        $data = "model";
        $this->str = $this->createTable($row, $col, $brr, $model_id, "model", $colors);
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

    public function createTable($row, $col, $arr, $mid, $data, $color=[])
    {

        $model = "model_" . $mid;
        $tpl = "<table id=" . $model . " " . "data-model=" . $data . "  class='table table-striped table-sm table-bordered' style='margin: 10px 0;'>";
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
                    for($p = 0; $p < count($color); $p++){
                        if($color[$p][0] == $i && $color[$p][1] == $j){
                            $fnd = true;
                            break;
                        }
                    }
                    if($fnd == true){
                        $tpl .= "<td style='border-radius: 5px;background: #ed2d2d;' data-color='1';>" . $arr[$i][$j] . "</td>";
                    }else{
                        $tpl .= "<td style='border-radius: 5px;'>" . $arr[$i][$j] . "</td>";
                    }
                }
            }
            $tpl .= "</tr>";
        }
        $tpl .= "</table>";

        return $tpl;
    }
}