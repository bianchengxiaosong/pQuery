<?php
//pQuery测试文件
header('content-type:text/html;charset=utf-8');
error_reporting(E_ALL ^ E_NOTICE);

function curlGet($url,$options = array()){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // 要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_HEADER, 0); // 不要http header 加快效率
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if(!empty($options['proxy'])){
        curl_setopt($ch,CURLOPT_PROXY,$options['proxy']);
        curl_setopt($ch, CURLOPT_PROXYPORT, $options['proxy_port']);
    }

    if(substr($url,0,5) == 'https'){
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);    // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    }

    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

$str = '<!DOCTYPE html>
<html>
<head>
    <title></title>
</head>
<body>
    <span id="div_id" class="div_class">
        <p>htmls</p>
        <div>pQuery测试文件1</div>
        <div>pQuery测试文件2</div>
        <a class="class_a" href="cccc">aaaaaaaaaaaaa</a>
    </span>
    <div id="div_id2" class="div_class">
        <p>htmls</p>
        <div class="class_a">pQuery测试文件</div>
        <div>pQuery测试文件2</div>
        <a class="class_a" href="cccc">aaaaaaaaaaaaa</a>
    </div>
    <span id="span_id">
        <b>#id</b>
        <i>element</i>
        <i>.class</i>
        <p>selector1,selctor2,selector3</p>
    </span>
    <form action="http://localhost/" method="post" id="formid">
        <input type="checkbox" checked="">
        <input type="checkbox" checked="">
    </form>
    <img src="http://localhost/a.jpg" class="aa bb cc">
    <ul>
        <li>列表1</li>
        <li>列表2</li>
        <li>列表3</li>
    </ul>
    <img src="aaa">
    <textarea>aaa&ltscript&gtaa   aaa<textarea>bbb</textarea></textarea>
</body>
</html>';

//引入pqury的类文件
include_once('../src/Pquery.php');

//实例化
$pquery = new Pquery($str);

var_dump($pquery->find('textarea')->fullhtmls());

//htmls 和 html 方法使用
var_dump($pquery->find('#div_id')->find('div')->htmls());

var_dump($pquery->find('.div_class')->htmls(function($key,$html){
    return strtoupper($html);
}));

var_dump($pquery->find('#span_id')->find('p')->html());

//fullhtmls 和 fullhtml 方法使用
var_dump($pquery->find('#span_id,a,.div_class')->fullhtmls());

var_dump($pquery->find('ul')->find('li')->fullhtmls(function($key,$fullhtml){
    return $fullhtml;
}));

var_dump($pquery->find('ul')->fullhtml());

//attrs 和 attr 方法使用
var_dump($pquery->find('#formid')->attrs());

var_dump($pquery->find('input[type="checkbox"]')->attrs(function($key,$attr){
    return $attr;
}));

var_dump($pquery->find('img')->attr());

//each 方法使用
var_dump($pquery->find('li')->each(function($key,$tag){
    var_dump($tag);
    // 'tag' 代表标签名
    // start_l 此标签在已处理过的html中的开始的位置
    // end_l 此标签在已处理过的html中的结束的位置
    // attrs 此标签的属性
    // children 如果存在 为此标签下面的子标签
    return $tag;
}));

//获取已处理过的html
var_dump($pquery->getHtml());

//获取生成的标签树
var_dump($pquery->getTagTree());