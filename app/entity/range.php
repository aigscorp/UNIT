<?php
namespace App\Entity;

/**
 * Клас-сущность  размерный ряд
 *
 * @table=sizesrange
 * @keyfield=id
 */

class Range extends \ZCL\DB\Entity
{
    protected function init() {
        $this->id = 0;
        $this->name_size = "";
        $this->size = "";
    }

    protected function afterLoad() {


        $xml = @simplexml_load_string($this->detail);
//        $sz = "<size>38</size><size>39</size><size>40</size><size>41</size><size>42</size><size>43</size><size>44</size><size>45</size>";
//        $this->hours = (string)($xml->hours[0]);
//        $this->price = (string)($xml->price[0]);
//        $this->cost = (string)($xml->cost[0]);
        $arr = $xml->size;
        $str = implode(",", $arr);
        $brr = [];
        for($i = 0; $i < count($arr); $i++){
            $str .= $arr[$i] . ", ";
//            $fnd = false;
//            for($j = 0; $j < count($arr); $j++){
//                if($i == intval($arr[$j])){
//                    $brr[$i] = true;
//                    $fnd = true;
//                    break;
//                }
//            }
//            if($fnd == false) $brr[$i] = false;
        }
//        $this->size = $brr;
        $str = trim($str);
        $str = substr($str, 0, -1);
        $this->size = $str;
//        $this->size = (string)($xml->size[0]);
        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
        //упаковываем  данные в detail
        $this->detail .= "<size>{$this->size}</size>";
//        $this->detail .= "<cost>{$this->cost}</cost>";
//        $this->detail .= "<price>{$this->price}</price>";
//        $this->detail .= "<hours>{$this->hours}</hours>";

        $this->detail .= "</detail>";

        return true;
    }
}