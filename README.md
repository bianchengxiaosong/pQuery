本项目是由PHP编写 支持PHP环境为5.5和5.5以上版本
主要使用jQuery的选择器语法，并支持多层级筛选 用于获取页面某一块的html内容或属性值 并支持页面中出现错误的、未闭合的html标签格式
比较适合抓取文章内容 url地址 图片地址等操作

安装方式
	composer require sobc/pquery

引用并实例化方式
	include_once('./vendor/autoload.php');
	$pquery = new \sobc\pquery\Pquery($str);

目前支持筛选调用方法为
	find(选择器语法)

目前支持的jQuery选择器语法为
	基本选择器
		#id
		element
		.class
		selector1,selctor2,selector3
	属性
		[attribute]
		[attribute=value]

获取内容方法为
	$pquery = new sobc\pquery\Pquery($html);
	获取多条内容 htmls()
		$htmls = $pquery->find('.class_p')->find('a')->htmls();
		$htmls = $pquery->find('.class_p')->find('p')->htmls(function($key,$html){
			//$key 代表索引
			//$html 符合选择器的内容
			return strtoupper($html);
		});
	获取第一条内容 html() 不支持传递任何参数
		$html = $pquery->find('.class_p[name="attr_value"]')->html();
	获取含自身标签多条内容 fullhtmls()
		$fullhtmls = $pquery->find('.class_fullhtmls[name="attr_value"]')->fullhtmls();
		$fullhtmls = $pquery->find('div,p')->fullhtmls(function($key,$fullhtml){
			//$key 代表索引
			//$fullhtml 符合选择器并含自身标签的内容
			return strtoupper($fullhtml);
		});
		注意：返回结果会带有自身标签，如<div class="class_fullhtmls" name="attr_value">内容</div>
	获取含自身标签第一条内容 fullhtml() 不支持传递任何参数
		$fullhtml = $pquery->find('#id')->html()
获取属性方法为
	获取多条对应属性 attrs()
		$attrs = $pquery->find('input[name]')->attrs();
		$attrs = $pquery->find('input[name]')->attrs(function($key,$attr){
			//$key 代表索引
			//$attr 符合选择器的标签的属性
			return $attr;
		});
	获取第一条对应 attr()
		$attr = $pquery->find('checkbox[checked]')->attr();

支持jQuery的each方法
	$pquery = new sobc\pquery\Pquery($html);
	$r = $pquery->find('a[href]')->each(function($key,$tag){
		//$key 代表索引
		//$tag 符合的标签节点
		return $tag;
	})

开发者邮箱 1847537660@qq.com
开发者QQ 1847537660 如有问题 请加QQ联系并备注为pQuery 本人将尽快帮你解决
此项目还在开发中 后续会支持更多选择器