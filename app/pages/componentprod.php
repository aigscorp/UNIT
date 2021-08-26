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
        $row = count($brr);
        $col = count($brr[0]);
        $this->str = $this->createTable($row, $col, $brr);
        return $this->str;
    }

    public function RequestHandle()
    {
        // TODO: Implement RequestHandle() method.

    }

    public function testContent($param)
    {
//        return "<p>hello</p>";
//        $this->str = "<div>abc</div>";
//        var_dump($param);
//        $this->data = "hello world";
//        return $this->getContent($this->str);
//        $arr = $param->elems;
//        $row = count($arr);
//        $col = count($arr[0]);
//        $this->value = $arr;
//        $this->str = $this->createTable($row, $col, $arr);
//        $this->getContent($param);
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function createTable($row, $col, $arr)
    {
        $tpl = "<table class='table table-borderless table-striped' style='margin: 10px 0;'>";
        for ($i = 0; $i < $row; $i++){
            $tpl .= "<tr>";
            for($j = 0; $j < $col; $j++){
                if($i == 0){
                    $tpl .= "<th>" . $arr[$i][$j] . "</th>";
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