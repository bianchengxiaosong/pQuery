<?php
namespace sobc\pquery;

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