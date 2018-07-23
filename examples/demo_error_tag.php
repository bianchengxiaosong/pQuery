<?php
//pQuery测试文件
header('content-type:text/html;charset=utf-8');
error_reporting(E_ALL ^ E_NOTICE);

//此次demo测试pQuery能够支持非标准化的html标签 即 闭合标签出现错误的问题

//引入vendor
include_once('../../../../vendor/autoload.php');

$str = '<!DOCTYPE html>
<html>
<body>
    <span>a<span></span>b</span>
    <div>a<div></div></div><span><span></div>aaaaaaaaaaaaaaaaaaa</span>
    <span>bbb</span>
    <div>aa</div></div>
</body>
</html>';

$pquery = new \sobc\pquery\Pquery($str);
var_dump($pquery->find('div')->fullhtmls());
var_dump($pquery->find('span')->fullhtmls());