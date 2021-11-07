<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 06.11.2021
 * Time: 13:30
 */

namespace App\Entity;
/**
 * Клас-сущность мастер, работа
 *
 * @table=master_work
 * @view=master_work
 * @keyfield=master_id
 */



class Master extends \ZCL\DB\Entity
{
    protected function init() {
        $this->master_id = 0;
        $this->model_id = 0;
        $this->work_id = 0;
        $this->emp_id = 0;
        $this->detail = '';
        $this->order_num = '';
        $this->price = 0;
        $this->finished = false;
        $this->ended = new \DateTime();
        $this->sz_qty = [];
    }

    protected function afterLoad() {
        $xml = @simplexml_load_string($this->detail);

//        $this->master = (string)($xml->master[0]);
//        $this->work = (string)($xml->work[0]);
        for($i = 0; $i < count($xml->size); $i++){
            $size[] = (string)$xml->size[$i]; //(string)($xml->quantity[$i]);
            $qty[] = (string)($xml->quantity[$i]);
        }
        $this->sz_qty = array_combine($size, $qty);
        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();
        $this->detail = "<detail>";
        //упаковываем  данные в detail
//        $this->detail .= "<master>{$this->master}</master>";
//        $this->detail .= "<work>{$this->work}</work>";
        foreach ($this->sz_qty as $ks=>$vs){
            $this->detail .= "<size>{$ks}</size><quantity>{$vs}</quantity>";
        }

        $this->detail .= "</detail>";
        return true;
    }

    public static function getMasterInWork($model_id){
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT work_id, emp_id, price, master_id, detail FROM master_work WHERE model_id = " . $model_id;
        $rs = $conn->Execute($sql);

        $workemp = [];
        foreach ($rs as $r){
            $workemp[$r['work_id']][$r['emp_id']] = array('price'=>$r['price'], 'master_id'=>$r['master_id']);
        }
        return $workemp;
    }

    public function getModelWorkEmp($model_id){
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT work_id, emp_id, master_id, detail FROM master_work WHERE model_id = " . $model_id;
        $rs = $conn->Execute($sql);

        $workemp = [];
        foreach ($rs as $r){
            $sz = $this->getDetail($r['detail']);
            $workemp[$r['work_id']][$r['emp_id']] = array('master_id'=>$r['master_id'], 'sz_qty'=>$sz);
        }
        return $workemp;
    }

    public function getDetail($detail){
        $xml = @simplexml_load_string($detail);
        for($i = 0; $i < count($xml->size); $i++){
            $size[] = (string)$xml->size[$i]; //(string)($xml->quantity[$i]);
            $qty[] = (string)($xml->quantity[$i]);
        }
        $sz_qty = array_combine($size, $qty);
        return $sz_qty;
    }
}