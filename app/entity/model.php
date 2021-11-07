<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 02.11.2021
 * Time: 15:59
 */

namespace App\Entity;

/**
 * Клас-сущность  модель
 *
 * @table=models
 * @view=models
 * @keyfield=id
 */

class Model extends \ZCL\DB\Entity
{
    public $workemp = [];

    protected function init() {
        $this->id = 0;
        $this->passport_id = 0;
        $this->name_model = '';
        $this->in_work = false;
        $this->quantity = 0;
        $this->order_id = 0;
        $this->order_num = '';
        $this->defect = 0;
        $this->detail = '';
        $this->finished = false;
        $this->disabled = false;
        $this->created = new \DateTime();
    }

    protected function afterLoad() {

        $xml = @simplexml_load_string($this->detail);
        $work = $xml->work;
        $emp = $xml->emp;
        for($i = 0; $i < count($work); $i++){
            $key_w = (string)$work[$i];
            $key_e = (string)$emp[$i];
            $name = Employee::getOne('emp_name', " employee_id = {$key_e}");
            $this->workemp[$key_w][$key_e] = $name; //(string)$emp[$i];
        }
        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
        //упаковываем  данные в detail
        foreach ($this->workemp as $kw=>$works){
            foreach ($works as $ke=>$emp){
                $this->detail .= "<work>{$kw}</work><emp>{$ke}</emp>";
            }
        }

        $this->detail .= "</detail>";

        return true;
    }

    public function setWork($id){
        $this->workemp[$id] = [];
    }

    public function setWorkEmps($wid, $eid, $name){
        $this->workemp[$wid][$eid] = $name;
    }
    public function delWorkEmps($wid, $eid){
        unset($this->workemp[$wid][$eid]);
    }

    public function getWorkEmps(){
        return $this->workemp;
    }

    public function isWorkEmp($wid, $eid){
        if(array_key_exists($wid, $this->workemp[$eid]) == true){
            return true;
        }
        return false;
    }

    public function isWork($wid){
        if(array_key_exists($wid, $this->workemp) == true){
            return true;
        }
        return false;
    }

    public function getListWork(){
        return array_keys($this->workemp);
    }

    public function getEmps($work){
        return $this->workemp[$work];
    }

    public static function getNextOrder() {
        $conn = \ZDB\DB::getConnect();

        $sql = "  select max(id)  from  models ";
        $id = $conn->GetOne($sql);

        return "ZK" . sprintf("%06d", ++$id);
    }

    public function setDisabled($dis = true){
        $this->disabled = $dis;
    }
    public function setInWork($inwork){
        $this->disabled = $inwork;
    }
    public static function getPassportItem($model_id, $field){
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT {$field} FROM models m, passport p WHERE p.id=m.passport_id AND m.id=" . $model_id;

        $str = $conn->GetOne($sql);
        $xml = @simplexml_load_string($str);
        for($i = 0; $i < count($xml->size); $i++){
            $size[] = (string)($xml->size[$i]);
            $qty[] = (string)($xml->qty[$i]);
        }
        $sz_qty = array_combine($size, $qty);
        return $sz_qty;
    }
}