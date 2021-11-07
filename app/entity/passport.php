<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 28.10.2021
 * Time: 19:32
 */

namespace App\Entity;

/**
 * Клас-сущность  Паспорт модели
 *
 * @table=passport
 * @view=passport
 * @keyfield=id
 */

class Passport extends \ZCL\DB\Entity
{
    public $materials = [];
    public $works = [];
    public $qty = [];

    protected function init() {
        $this->id = 0;
        $this->passport_code = "";
        $this->name = '';
        $this->size_name = '';
        $this->detail_size = '';
        $this->detail_work = '';
        $this->detail_material = '';
        $this->disabled = false;
        $this->created = new \DateTime();
    }

    protected function beforeSave()
    {
        parent::beforeSave(); // TODO: Change the autogenerated stub

        $this->detail_size = "<detail>";
        //упаковываем  данные в detail
        foreach ($this->qty as $sk=>$sv){
            $this->detail_size .= "<size>{$sk}</size><qty>{$sv}</qty>";
        }

        $this->detail_size .= "</detail>";

        $this->detail_work = "<detail>";
        foreach ($this->works as $wk=>$wv){
            $this->detail_work .= "<work>{$wk}</work>";
        }
        $this->detail_work .= "</detail>";

        $this->detail_material = "<detail>";
        foreach ($this->materials as $mk=>$mv){
            $this->detail_material .= "<material>{$mk}</material><quantity>{$mv}</quantity>";
        }

        $this->detail_material .= "</detail>";

        return true;
    }

//    public static function getSizes($id){
//
//    }

    public function saveMaterial($val){
        $this->materials[] = $val;
    }

    public function getMaterial($itemid){
        if(array_key_exists($itemid, $this->materials) == true){
            return $this->materials[$itemid];
        }
        return 0;
    }

    public function getAllMaterial(){
        return $this->materials;
    }

    public function delMaterial($itemid){
        if(array_key_exists($itemid, $this->materials) == true){
            unset($this->materials[$itemid]);
            return true;
        }
        return false;
    }

    public function delAllMaterial(){
        $this->materials = [];
    }

    public function delAllWork(){
        $this->works = [];
    }

    public function getWork($itemid){
        if(array_key_exists($itemid, $this->works) == true){
            return $this->works[$itemid];
        }
        return 0;
    }

    public function getAllWork(){
        return $this->works;
    }
    public function setWork($itemid, $bool=true){
        $this->works[$itemid] = $bool;
    }
    public function delWork($itemid){
        if(array_key_exists($itemid, $this->works) == true){
            unset($this->works[$itemid]);
            return true;
        }
        return false;
    }

    public static function getList($dis = false){
        $conn = \ZDB\DB::getConnect();
        $where = "";
        if($dis == true){
            $where = " where disabled = true ";
        }
        $sql = "select id, name, size_name from passport " . $where . " order by name ";
        $rs = $conn->Execute($sql);

        $list = [];
        foreach ($rs as $r){
            $list[$r['id']] = $r['name'] . ", " . $r['size_name'];
        }
        return $list;
    }

}