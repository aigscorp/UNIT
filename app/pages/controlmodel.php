<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 07.11.2021
 * Time: 13:29
 */
namespace App\Pages;

use App\Entity\Customer;
use App\Entity\Employee;
use App\Entity\Model;
use App\Entity\Category;
use App\Entity\Master;
use App\Entity\Passport;
use App\Entity\ProdArea;
use function GuzzleHttp\Psr7\try_fopen;
use Zippy\Html\DataList\Column;
use \Zippy\Html\DataList\ArrayDataSource;
use App\Entity\Service;
use ZCL\DB\EntityDataSource as EDS;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\DataTable;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Panel;
use App\Helper;


class ControlModel extends \App\Pages\Base
{
    public function __construct($params = null)
    {
        parent::__construct($params);

        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);

        $this->getModelWork(11);

    }

    public function getDetail($detail){
        $xml = @simplexml_load_string($detail);
        for($i = 0; $i < count($xml->size); $i++){
            $size[] = (string)$xml->size[$i];
            $qty[] = (string)($xml->quantity[$i]);
        }
        $sz_qty = array_combine($size, $qty);
        return $sz_qty;
    }

    public function getModelWork($model_id, $quantity=100){
      $conn = \ZDB\DB::getConnect();
      //SELECT s.service_id, p.pa_id, p.pa_name FROM services s, parealist p WHERE s.area_id = p.pa_id
      $sql_area = "SELECT s.service_id, p.pa_id, p.pa_name FROM services s LEFT JOIN parealist p ON s.area_id = p.pa_id";
      $rs_area = $conn->Execute($sql_area);
      $list_area = [];
      foreach ($rs_area as $ra){
          $pa_id = $ra['pa_id'];
          $pa_name = $ra['pa_name'];
          if($ra['pa_id'] == null){
            $pa_id = 0;
            $pa_name = '';
          }
          $list_area[$ra['service_id']][$pa_id] = $pa_name;
      }

      $size_qty_passport = Model::getPassportItem($model_id, 'detail_size');

      $sql = "SELECT m.master_id, m.detail, m.work_id, s.service_name, m.emp_id, e.emp_name FROM master_work m, employees e, services s 
      WHERE m.work_id = s.service_id AND m.emp_id = e.employee_id AND m.model_id = " . $model_id;
      $rs = $conn->Execute($sql);

      $list_area_work = [];
      $list_defect = [];

      foreach ($rs as $r){
          $list_defect[] = $r['master_id'];
          $emp = new \stdClass();
          $emp->master_id = $r['master_id'];
          $emp->emp_id = $r['emp_id'];
          $emp->emp_name = $r['emp_name'];
          $emp->work_name = $r['service_name'];
          $emp->sz_qty = $this->getDetail($r['detail']);
          $area = $list_area[$r['work_id']];
          $area_key = array_key_first($area);
          $emp->area_id = $area_key;
          $emp->area_name = $area[$area_key];

//              $list[$r['work_id']][] = $emp;
          $list_area_work[$area_key][$r['work_id']][] = $emp;
      }

//      uasort($list, function ($a, $b) { return $a[0]->area_id < $b[0]->area_id; });

//      $brr = new \SplFixedArray(3);
//      for($i = 0; $i < count($brr); $i++){
//          $brr[$i] = new \SplFixedArray(5);
//      }

      $list_tbl_work = [];
      $list_tbl_emp = [];
      foreach ($list_area_work as $list_works){
          $tbl_work['model'] = [];
          $tbl_emp['FIO'] = [];
          $work_count = count($list_works);
          $work_arr = array_fill(0, $work_count, '');

          $cnt = 0;
          foreach ($list_works as $list_emp){
            for($i = 0; $i < count($list_emp); $i++){
                $tmp = $tbl_work['model'];
                if($this->isWorkArr($tmp, $list_emp[$i]->work_name) == false){
                    $tbl_work['model'][] = $list_emp[$i]->work_name;
                }
                $sum = $this->calcSum($list_emp[$i]->sz_qty);
                if(array_key_exists($list_emp[$i]->emp_name, $tbl_emp) == true){
                    $tbl_emp[$list_emp[$i]->emp_name][$cnt] = $sum;
                }else{
                    $tbl_emp[$list_emp[$i]->emp_name] = $work_arr;
                    $tbl_emp[$list_emp[$i]->emp_name][$cnt] = $sum;
                }
            }
            $cnt++;
          }

          $tbl_emp['FIO'] = $tbl_work['model'];
          foreach ($size_qty_passport as $ks=>$vs){
              $key = 'model'. ', ' . $ks;
              $tbl[$key] = [];
              for($j = 0; $j < count($tbl['model']); $j++){
                  $tbl[$key][] = $quantity * $vs;
              }
          }

          $list_tbl_work[] = $tbl_work;
          $list_tbl_emp[] = $tbl_emp;
      }
      $a=1;
      $b=2;

    }

    public function calcSum($arr){
        $sum = 0;
        foreach ($arr as $a){
            $sum += $a;
        }
        return $sum;
    }

    public function isWorkArr($arr, $work){
        for($j = 0; $j < count($arr); $j++){
            if($arr[$j] == $work){
                return true;
            }
        }
        return false;
    }
}

//          $sql_p = "SELECT p.detail_size FROM models m, passport p WHERE p.id=m.passport_id AND m.id=" . $model_id;