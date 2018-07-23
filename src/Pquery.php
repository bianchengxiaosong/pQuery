<?php
namespace sobc\pquery;
/**
 * 主要使用jQuery的筛选器语法 获取页面某一块的html内容或属性值
 * 开发者邮箱 1847537660@qq.com
 * 开发者QQ 1847537660 如有问题 请加QQ联系并备注为pQuery 本人将尽快帮你解决
 */

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