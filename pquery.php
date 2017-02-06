<?php
/**
 * 主要使用jQuery的筛选器语法 获取页面某一块的html内容或属性值
 * 开发者邮箱 1847537660@qq.com
 * 开发者QQ 1847537660 如有问题 请加QQ联系并备注为pQuery 本人将尽快帮你解决
 */
//php版query类实现文件
include_once('./CreateTagTree.php');

class Pquery{
    private $tag_tree = array(); //标签树结构
    private $html = ''; //html内容
    private $selector = ''; //选择器
    private $pq_tags = array(); //符合选择器对应规则的标签
    private $max_pq_tags = array(); //符合选择器对应规则的最大标签
    private $tags_by_selector_obj = null; //通过单线发现属性标签的对象

    public function __construct($html = '',$web_format = true){
        $tags = new CreateTagTree($html,$web_format);
        $this->tag_tree = $tags->getTagTree();
        $this->max_pq_tags = $this->tag_tree;
        $this->html = $tags->getHtml();
        $this->tags_by_selector_obj = new TagsBySelector();
        unset($tags);
    }

    //根据选择器查询后代标签
    public function find($selector = ''){
        $selectors = explode(',', $selector);
        $this->pq_tags = array();
        foreach($selectors as $selector){
            $this->tags_by_selector_obj->setMaxPqTags($this->max_pq_tags);
            $this->pq_tags = array_merge($this->pq_tags,$this->tags_by_selector_obj->getTagsBySelector($selector));
        }
        $this->_setMaxPqTagsByTag();
        return $this;
    }
    
    //设置符合选择的最大标签
    private function _setMaxPqTagsByTag(){
        $this->max_pq_tags = array();

        foreach($this->pq_tags as $tag){
            $is_max = true;
            foreach($this->max_pq_tags as $max_tag){
                if($tag['start_l'] > $max_tag['start_l'] && $tag['end_l'] < $max_tag['end_l']){
                    //不是最大范围
                    $is_max = false;
                    break;
                }
            }
            if($is_max == true){
                array_push($this->max_pq_tags,$tag);
            }
        }
    }

    //重置最大符合选择的标签
    private function _resetMaxpqTags(){
        $this->max_pq_tags = $this->tag_tree;
        $this->pq_tags = array();
    }

    //获取多条内部带标签html内容
    public function fullhtmls($func = null){
        $htmls = array();
        foreach($this->pq_tags as $k => $tag){
            $htmls[$k] = substr($this->html,$tag['start_l'],($tag['end_l'] - $tag['start_l'] + 1));
        }

        if(gettype($func) == 'object'){
            foreach($htmls as $k => $html){
                $htmls[$k] = $func($k,$html);
            }
        }

        //重置
        $this->_resetMaxpqTags();

        return $htmls;
    }

    //获取第一条内部带标签html内容
    public function fullhtml(){
        if(empty($this->pq_tags)){
            return '';
        }
        $tag = $this->pq_tags[0];
        $html = substr($this->html,$tag['start_l'],($tag['end_l'] - $tag['start_l'] + 1));

        //重置
        $this->_resetMaxpqTags();

        return $html;
    }

    //获取多条内部html内容
    public function htmls($func = null){
        $htmls = array();
        foreach($this->pq_tags as $k => $tag){
            $content = substr($this->html,$tag['start_l'],($tag['end_l'] - $tag['start_l'] + 1));
            $pattern = "/<".$tag['tag']."[^>]*>(.*)<\/".$tag['tag'].">/is";
            preg_match($pattern,$content,$match);
            $htmls[$k] = $match[1];
        }

        if(gettype($func) == 'object'){
            foreach($htmls as $k => $html){
                $htmls[$k] = $func($k,$html);
            }
        }

        //重置
        $this->_resetMaxpqTags();

        return $htmls;
    }

    //获取第一条内部html内容
    public function html(){
        if(empty($this->pq_tags)){
            return '';
        }
        $tag = $this->pq_tags[0];
        $content = substr($this->html,$tag['start_l'],($tag['end_l'] - $tag['start_l'] + 1));
        $pattern = "/<".$tag['tag']."[^>]*>(.*)<\/".$tag['tag'].">/is";
        preg_match($pattern,$content,$match);
        $html = $match[1];

        //重置
        $this->_resetMaxpqTags();

        return $html;
    }

    //获取多个html的属性
    public function attrs($func = null){
        $attrs = array();
        foreach($this->pq_tags as $k => $tag){
            $attrs[$k] = $tag['attrs'];
        }

        if(gettype($func) == 'object'){
            foreach($attrs as $k => $attr){
                $attrs[$k] = $func($k,$attr);
            }
        }

        //重置
        $this->_resetMaxpqTags();

        return $attrs;
    }

    //获取第一条html的属性
    public function attr(){
        $attr = array();
        if(!empty($this->pq_tags)){
            $attr = $this->pq_tags[0]['attrs'];
        }

        //重置
        $this->_resetMaxpqTags();
        
        return $attr;
    }

    //类似jquery的each
    public function each($func){
        if(gettype($func) != 'object'){
            return array();
        }

        $rs = array(); //操作存放结果集
        foreach($this->pq_tags as $k => $tag){
            $rs[$k] = $func($k,$tag);
        }

        //重置
        $this->_resetMaxpqTags();

        return $rs;
    }

    //获取已处理过的html
    public function getHtml(){
        return $this->html;
    }

    //获取生成的标签树
    public function getTagTree(){
        return $this->tag_tree;
    }
}

//通过单线发现属性标签
class TagsBySelector{
    private $max_pq_tags = array();
    private $pq_tags = array();
    private $tags_by_attr_obj = null;

    public function __construct(){
        $this->tags_by_attr_obj = new TagsByAttr();
    }

    //通过单条选择器发现标签
    public function getTagsBySelector($selector){
        if(!empty(preg_match_all("/\[(.*?)\]/i",$selector,$matches))){
            //属性选择
            $selectors = $matches[1];
            array_unshift($selectors,substr($selector,0,strpos($selector,'[')));
        }else{
            $selectors = array(
                $selector,
            );
        }

        foreach($selectors as $select_level => $selector){
            $r = $this->_getSelectorAttr($select_level,$selector);
            $this->_setTagByAttr($select_level,$r['attr'],$r['value']);
        }

        return $this->pq_tags;
    }

    //设置最大的筛选根节点
    public function setMaxPqTags($max_pq_tags){
        $this->max_pq_tags = $max_pq_tags;
    }

    //通过筛选器简单规则获取属性和值
    private function _getSelectorAttr($select_level,$selector = ''){
        $selector_attr = array();

        if($selector[0] == '#'){
            //#id
            $selector_attr = array(
                'attr' => 'id',
                'value' => substr($selector,1),
            );
        }elseif($selector[0] == '.'){
            //class
            $selector_attr = array(
                'attr' => 'class',
                'value' => substr($selector,1),
            );
        }elseif(!empty(strpos($selector,'='))){
            list($attr,$value) = explode('=',$selector);
            $value = trim($value,'"');
            $selector_attr = array(
                'attr' => $attr,
                'value' => $value,
            );            
        }elseif($select_level == 0){
            //标签
            $selector_attr = array(
                'attr' => '',
                'value' => $selector,
            );
        }else{
            //仅仅要求包含这个属性
            $selector_attr = array(
                'attr' => $selector,
                'value' => '',
            );            
        }

        return $selector_attr;
    }

    //通过属性设置符合的标签
    private function _setTagByAttr($select_level,$attr_key = '',$attr_value = ''){
        if($select_level == 0){
            $this->tags_by_attr_obj->setFindTagTree($this->max_pq_tags);
            $this->pq_tags = $this->tags_by_attr_obj->getTagByAttrRecur($attr_key,$attr_value);
        }else{
            $this->tags_by_attr_obj->setTagTree($this->max_pq_tags);
            $this->pq_tags = $this->tags_by_attr_obj->getTagByAttrFor($attr_key,$attr_value);
        }
        $this->max_pq_tags = $this->pq_tags;
    }
}

//通过属性得到对应标签节点的类
class TagsByAttr{
    private $tag_tree = array(); //用于筛选的标签树

    //设置用于同级筛选的标签树
    public function setTagTree($tag_tree = array()){
        $this->tag_tree = $tag_tree;
    }

    //设置用于find子集筛选的标签树
    public function setFindTagTree($tag_tree = array()){
        $fined_tag_tree = array(); //用于进行筛选的标签树
        foreach($tag_tree as $tag){
            if(!empty($tag['children']) && is_array($tag['children'])){
                $fined_tag_tree = array_merge($fined_tag_tree,$tag['children']);
            }
        }
        
        $this->tag_tree = $fined_tag_tree;
    }

    //通过递归获取符合属性的标签
    public function getTagByAttrRecur($attr_key = '',$attr_value = ''){
        if(empty($this->tag_tree)){
            return array();
        }

        return $this->_getTagByAttr($attr_key,$attr_value,$this->tag_tree,true);
    }

    //通过循环获取符合属性的标签
    public function getTagByAttrFor($attr_key = '',$attr_value = ''){
        if(empty($this->tag_tree)){
            return array();
        }

        return $this->_getTagByAttr($attr_key,$attr_value,$this->tag_tree,false);
    }

    //设置符合的标签
    private function _getTagByAttr($attr_key,$attr_value,&$cur_tag_node,$is_recur = true){
        $tags = array();

        foreach($cur_tag_node as $tag_node){
            $is_tag = false;

            if(empty($attr_key) && !empty($attr_value) && $tag_node['tag'] == $attr_value){
                //标签
                $is_tag = true;
            }else if($attr_key == 'class' && !empty($tag_node['attrs'][$attr_key])){
                //特殊的一个class属性
                $classes = preg_split("/\s/", $tag_node['attrs']['class']);
                if(in_array($attr_value,$classes)){
                    $is_tag = true;
                }
            }else if(!empty($tag_node['attrs'][$attr_key]) && $tag_node['attrs'][$attr_key] == $attr_value){
                //符合的属性 
                $is_tag = true;
            }else if(empty($attr_value) && !empty($tag_node['attrs'][$attr_key])){
                //只要存在这个属性即可
                $is_tag = true;
            }

            if($is_tag == true){
                array_push($tags,$tag_node);

                //只要一个
                if($attr_key == 'id'){
                    return $tags;
                }
            }

            //查看是否存在子标签并且是递归传递
            if(!empty($tag_node['children']) && $is_recur == true){
                $tags = array_merge($tags,(array)$this->_getTagByAttr($attr_key,$attr_value,$tag_node['children'],$is_recur));
            }
        }

        return $tags;
    }
}