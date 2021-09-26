<?php

namespace App\API;

/**
 * Пример  для  использования Json RPC
 * пример  вызова /api/TestJsonRPC
 */
class TestJsonRPC extends \App\API\Base\JsonRPC
{

    //{"jsonrpc": "2.0", "method": "Hello", "params": {"p1": "12345",$p3:"2"}, "id": 1}
    public function Hello($params) {
        return array('answer' => 'Hi');
    }

    public function parseTagValue($str_tag, $name)
    {
        $matches = [];
        $pattern = "/\<" . $name . "\>" . "([а-яА-ЯЁёa-zA-Z0-9 ]+)" . "\<\/" . $name . "\>/u";
/*        $pattern = "|<" . $name ."[^>]*?>(.*?)</" . $name . "title>|si";*/
/*        preg_match('|<title[^>]*?>(.*?)</title>|si', $str_tag, $matches);*/
        $res = preg_match($pattern, $str_tag, $matches);
        if($res == false){
            echo "Error parsing " . $name . "<br>";
        }
        return $matches[1];
    }

    public function GetModelDefect($params){

        $conn = \ZDB\DB::getConnect();
        $model_id = $params['model_id'];
        $sql = "SELECT t.type_work, defect.detail, defect.pasport_id, defect.monitor FROM 
        (SELECT m.pasport_id, d.detail, d.monitor FROM model m, defect_model d 
        WHERE m.in_work = true AND m.id = d.model_id AND d.model_id = " . $model_id . ") AS defect, typework t WHERE t.pasport_id = defect.pasport_id";

        $work = $params['work'];
        $size = $params['size'];
//        $sql = "SELECT id, monitor, detail, created FROM defect_model WHERE model_id = " . "'" . $model_id . "'";
        $rs = $conn->Execute($sql);
//
        $answer = "";
        foreach ($rs as $r){
            $sz = $this->parseTagValue($r['detail'], "size");
            if($r['type_work'] == $work && $sz == $size){
                $master = $this->parseTagValue($r['detail'], "master");
//                $defect = $this->parseTagValue($r['detail'], "defect");
                $defect = [];
                preg_match('|<defect[^>]*?>(.*?)</defect>|si', $r['detail'], $defect);
                $answer = "<p>" . $master . " обнаружил брак. " . $work . ", размер " . $size . ". " . $defect[1] . "</p>";
                if(trim($r['monitor']) != ""){
                    $answer .= "<p>" . $r['monitor'] . "</p>";
                }
            }
        }

        return array('answer' => $answer);
    }


}
