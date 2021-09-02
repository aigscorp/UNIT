<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 28.08.2021
 * Time: 14:20
 */

namespace App\Pages;

class ComponentMaster extends \App\Pages\ComponentProd
{
    public function __construct($id)
    {
        parent::__construct($id);
    }

    public function getContent($attributes)
    {
        $arr = $this->value;
//        var_dump($arr);
        $brr[] = $arr->list_typework;
        $sz = count($arr->list_typework);
        foreach ($arr->masters as $master){
            $crr = array_fill(0, $sz, "");
            $crr[0] = $master->emp_name;

            for($i = 1; $i < $sz; $i++){
                foreach ($master->typework as $key=>$val){
                    if($key == $arr->list_typework[$i]){
                        $crr[$i] = $val;
                    }
                }
            }
            $brr[] = $crr;
        }
        $row = count($brr);
        $col = count($brr[0]);
        $data = "master";
        $this->str = $this->createTable($row, $col, $brr, "0", "master");
        return $this->str;
        //return parent::getContent($attributes); // TODO: Change the autogenerated stub
    }
}