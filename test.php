<?php
echo "test\n";
//$p = "1. Магазин Обувщик на Малыгина    есть в наличии дам в рассрочку цена 20 руб. дм. тел: 79888888888 2. Магазин все для обуви";
//$s2 = "Магазин  Обувщик   на";
//
////$p = "Программа должна подсчитать количество произведенных и количество брака например если за все время производства модели 001 произошло 4 брака определенного размера (41, 43, 43, 45) то по окончанию производства таблица паспорта готовой продукции в размерном ряду будет выглядеть так ";
//
//$p = "Для производства и подсчет расходов необходимо в 1С УНФ (далее 1С) создать паспорт модели, из чего будет состоять наша производимая обувь, какие расходы в себе она содержит.
//Паспорт каждый раз создается техническим специалистом самостоятельно, данные по расходам берутся из номенклатуры.
//Что входит или что из себя представляет паспорт — это документ, в котором находятся:
//";
////$arr1 = str_split($s2);
//$s = preg_replace('/\s\s+/',' ', $p);
//$crr = explode(" ", $s);
//print_r($crr);
//echo "len = " . strlen($s) . "\n";
//echo "mb_len = " . mb_strlen($s) . "\n";
//$sum = 0;
//
//$pos = 0;
//$brr = [];
//$len = count($crr);
//for($i = 0; $i < $len; $i++){
//    $p = mb_strlen($crr[$i]) + 1;
//    $sum += $p;
//    if($sum > 100){
//        $pos = $i - 1;
//        $brr[] = $pos;
//        $sum = mb_strlen($crr[$i]) + 1;
//    }
//}
//
//$brr[] = $len;
//
//print_r($brr);
//
//$str = "";
//$drr = [];
//$j = 0;
//for($i = 0; $i < count($brr); $i++){
//    for($j; $j < $brr[$i]; $j++){
//        $str .= $crr[$j] . " ";
//    }
//    $str .= "\n";
//    $j = $brr[$i];
//}
//
//echo $str;

//$arr = array('40'=>0, '41'=>2, '42'=>8);
//$brr = array('40'=>100, '41'=>200, '42'=>300);
//print_r(array_keys($arr));
//function cb($a, $b){
//    return $a + $b;
//}
//print_r(array_map('cb', $arr, $brr));
//foreach ($arr as $k=>$v){
//    if(array_key_exists($k, $brr) == true){
//        $arr[$k] = $arr[$k] + $brr[$k];
//    }
//}
//print_r($arr);

//$crr = ['90'=>'кройка', '90'=>'кройка', '91'=>'сбивка', '92'=>'фальцовка', '93'=>'сборка', '93'=>'сборка'];
//$crr_unique = array_unique($crr);
//print_r($crr_unique);
//$hrr = [];
//foreach ($crr_unique as $cu){
//    $hrr[] = $cu;
//}
//print_r($hrr);

//$drr = [ ['40'=>2, '41'=>4, '42'=>5], ['40'=>20, '41'=>40, '42'=>50], ['40'=>200, '41'=>400, '42'=>500] ];
//
//for($i = 1; $i < count($drr); $i++){
//    $tmp = $drr[0];
//    foreach ($drr[$i] as $k=>$v){
//        if(array_key_exists($k, $tmp) == true){
//            $tmp[$k] = intval($tmp[$k]) + intval($v);
//        }
//    }
//}

//print_r($tmp);
//$frr = [ ['40'=>20, '41'=>20, '42'=>30], ['40'=>30, '41'=>30, '42'=>50], ['40'=>50, '41'=>50, '42'=>90], ['40'=>60, '41'=>60, '42'=>110] ];
//$grr = [];
//$krr = array_keys($frr[0]);
//for ($kf = 0; $kf < count($krr); $kf++){
//    $grr[$krr[$kf]] = min(array_column($frr, $krr[$kf]));
//}
////$ff = min(array_column($frr, '40'));
//print_r($grr);

//$s1 = "клей (1.5),Клей ATS AQUAGUM  (0.5),кожа (100.255),нитки, м (10),";
//$res = preg_match_all('/([а-яА-Яa-zA-Z,. ].*?)([0-9().]+),/i',$s1, $match);
//print_r($match);


class Test{
    public $id;
    public $wid;

    public function __construct($id, $wid)
    {
        $this->id = $id;
        $this->wid = $wid;
    }

    static function cmp($a, $b){
        return $a->wid - $b->wid;
    }
    static function cmpid($a, $b){
        return $a->id - $b->id;
    }
}

$arr = [];
for($i = 0; $i < 3; $i++){
    $test = new Test(random_int(1, 10), random_int(20, 50));
    $arr[] = $test;
}

print_r($arr);
usort($arr, array("Test", "cmpid"));
print_r($arr);


