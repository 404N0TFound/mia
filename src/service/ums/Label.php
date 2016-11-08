<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Lib\Service;
use mia\miagroup\Model\Ums\Label as LabelModel;

class Label extends Service{
    
    private $labelModel;
    
    public function __construct(){
        $this->labelModel = new LabelModel();
    }
    
    /**
     * 获取推荐和热门标签
     */
    public function getLabelInfoByPic($num = 20){
        $data = $this->labelModel->getLabelInfoByPic($num);
        return $this->succ($data);
    }
    
    /**
     * 获取关联信息
     * @param unknown $label_id
     * @param unknown $subject_id
     * @return unknown
     */
    public function getLabelRelation($subject_id){
        $label_id = array();
        $label_data = array();
        $labelRelation = new \mia\miagroup\Model\Ums\LabelRelation();
        $data = $labelRelation->getLabelRelation($subject_id);
        foreach($data as $k=>$v){
            $label_ids[] = $v['label_id'];
        }
        $label_info = $this->labelModel->getLabelInfo($label_ids);
        foreach($label_info as $k=>$v){
            $label_data[$v['id']] = $v['title'];
        }
        foreach($data as $k=>$v){
            $data[$k]['title'] = $label_data[$v['label_id']];
        }
        
        return $this->succ($data);
    }

    
}