<?php
namespace sobc\pquery;

//解析标签获取标签树的类
class CreateTagTree{
    private $html = '';
    private $queue_tags = array();
    private $check_queue_tags = array();
    public function __construct($html = '',$web_format = true){
        if($web_format == false){
            //非web格式 为html代码
            $this->html = trim($html);
        }else{
            //web格式 要进行web格式过滤
            $this->setWebHtml($html,$web_format);
        }
        $this->setQueueTags();
    }

    //设置html内容 用于过滤注释 script style
    private function setWebHtml($html = ''){
        $html = trim($html); //去空格
        if(empty(stripos('</html>',$html))){
            $html .= '</html>';
        }
        preg_match("/<html.*?>(.*?)<\/html>/is",$html,$match);
        $html = $match[0];
        $clear_tags = array("/<script(.*?)>.*?<\/script>/is","/<\!--.*?-->/is",'/<style(.*?)>.*?<\/style>/is');
        $null_tags = array('<script\\1></script>','','<style\\1></style>','');
        $html = trim(preg_replace($clear_tags,$null_tags,$html));
        $html = preg_replace_callback("/<textarea>(.*?)<\/textarea>/is", function($match){
            return '<textarea>'.htmlspecialchars($match[1]).'</textarea>';
        }, $html);
        $this->html = $html;
    }

    //设置队列式标签
    private function setQueueTags(){
        if(!preg_match_all("/<(.+?)>/is",$this->html,$matches)){
            return array();
        }

        //队列数组
        $queue_tags = array();

        $find_start_l = 0;
        foreach($matches[1] as $k => $tag){
            $r = preg_split("/\s/",trim($tag));

            $full_tag = $matches[0][$k];
            $start_l = strpos($this->html,$full_tag,$find_start_l);
            $end_l = $start_l + strlen($full_tag) - 1;
            $find_start_l = $end_l + 1;
            $tag_name = strtolower(array_shift($r));

            $queue_tag = array(
                'tag' => $tag_name,
                'start_l' => $start_l,
                'end_l' => $end_l,
            );

            //属性不为空设置
            if(!empty($r)){
                if(preg_match("/class=(?:\"|\')(.*?)(?:\"|\')/is",$tag,$match)){
                    $tag = str_replace($match[0],'',$tag);
                    $r = preg_split("/\s/",trim($tag));
                    unset($r[0]);

                    array_push($r,$match[0]);
                }
                $attrs = $this->getAttrs($r);
            }else{
                $attrs = null;
            }

            $queue_tag['attrs'] = empty($attrs) ? array() : $attrs ;

            array_push($queue_tags,$queue_tag);
        }

        $this->queue_tags = $queue_tags;

        //赋值核对对列标签树
        $this->check_queue_tags = $this->queue_tags;
    }

    //将属性数组字符串转换成键值对
    private function getAttrs($attr = array()){
        $r = array();
        foreach($attr as $v){
            if(empty($v)){
                continue;
            }
            list($key,$val) = explode('=',$v,2);
            $r[$key] = empty($val) ? 'true' : trim($val,'"\'');
        }
        return $r;
    }

    //获取标签树结构
    public function getTagTree(){
        //标签树结构
        $tag_tree = array();

        //单个标签
        $single_tags = array('img', 'input', 'br', 'hr', 'col', 'area', 'link', 'meta', 'frame', 'input', 'param', 'base');

        //插入根树
        $root_node = array(
            'tag' => 'root',
            'children' => array(),
        );
        array_push($tag_tree,$root_node);

        foreach($this->queue_tags as $key => $tag){
            $tag_name = $tag['tag'];
            if($tag_name[0] != '/'){
                //普通标签
                $tag_node = array(
                    'tag' => $tag_name,
                    'children' => array(),
                );
                $tag_node = array_merge($tag,$tag_node);

                if(in_array($tag_name,$single_tags)){
                    //单个标签
                    array_push($tag_tree[count($tag_tree)-1]['children'],$tag_node);
                    continue;
                }

                array_push($tag_tree,$tag_node);
            }else{
                //结束标签
                $prev_end_tag_name = '/'.$tag_tree[count($tag_tree)-1]['tag'];
                if($prev_end_tag_name == $tag_name){
                    //符合对应标签
                    $tag_node = array_pop($tag_tree);
                    $tag_node['end_l'] = $tag['end_l']; //重置结束为止为结束标签的位置
                    array_push($tag_tree[count($tag_tree)-1]['children'],$tag_node);
                    $prev_key = $this->getPrevKeyCheckQueueTags($key);
                    unset($this->check_queue_tags[$prev_key]);
                }else{
                    //不能对应标签
                    $is_false_tag = true;
                    for($i = $key - 1;$i > 0;$i--){
                        if(empty($this->check_queue_tags[$i])){
                            continue;
                        }
                        if('/'.$this->check_queue_tags[$i]['tag'] == $tag_name){
                            $is_false_tag = false;
                            break;
                        }
                    }
                    if($is_false_tag == false){
                        //不是废标签 而是错误格式的标签
                        while(count($tag_tree) > 1){
                            $tag_node = array_pop($tag_tree);
                            $tag_node['end_l'] = ('/'.$tag_node['tag'] == $tag_name) ? $tag['end_l'] : $tag['start_l'] - 1;
                            array_push($tag_tree[count($tag_tree)-1]['children'],$tag_node);
                            $prev_key = $this->getPrevKeyCheckQueueTags($key);
                            unset($this->check_queue_tags[$prev_key]);

                            if('/'.$tag_node['tag'] == $tag_name){
                                break;
                            }
                        }
                    }
                }

                unset($this->check_queue_tags[$key]);
            }
        }

        return $tag_tree;
    }

    //获取最靠近某一个key的上一个key
    private function getPrevKeyCheckQueueTags($key){
        for($i = $key - 1;$i > 0;$i--){
            if(!empty($this->check_queue_tags[$i])){
                return $i;
            }
        }
    }

    //获取html
    public function getHtml(){
        return $this->html;
    }

    //获取队列标签
    public function getQueueTag(){
        return $this->queue_tags;
    }

    public function getCheckQueueTag(){
        return $this->check_queue_tags;
    }
}