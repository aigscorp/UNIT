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
    public $data = "hello";
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
        $row = count($brr);
        $col = count($brr[0]);
//        $data = "model";
        $this->str = $this->createTable($row, $col, $brr, $model_id, "model");
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

    public function createTable($row, $col, $arr, $mid, $data)
    {
        //table-borderless
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
                    $tpl .= "<td>" . $arr[$i][$j] . "</td>";
                }
            }
            $tpl .= "</tr>";
        }
        $tpl .= "</table>";

        return $tpl;
    }
}