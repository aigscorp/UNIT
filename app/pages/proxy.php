<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
session_start();
$args = $_GET;
$param = $_GET["q"];
// echo $param;
$url = "http://localhost/index.php?q=";
$pos = stripos($param, "::quantity");

$page_pag = substr($param, 0, $pos);
$query = substr($param, $pos+2, strlen($param));

$arr = explode("::", $query);
foreach($arr as $ar){
  $brr = explode(":", $ar);
//  if(isset($_SESSION[$brr[0]]) == false){
//    $_SESSION[$brr[0]] = $brr[1];
//  }
  $_SESSION[$brr[0]] = $brr[1];
}

//var_dump($_SESSION);

// echo $page_pag . "<br>";
// echo $s2 . "<br>";

// $url = "http://localhost/index.php?q=p:83::itemtable::listform::pag:2";
 header('Location: ' . $url . $page_pag);
 exit();
// var_dump($args);
