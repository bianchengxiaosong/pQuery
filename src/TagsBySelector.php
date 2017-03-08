<?php
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