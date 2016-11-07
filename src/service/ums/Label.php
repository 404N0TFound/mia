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
    public function getLabelRelation($subject_id,$label_ids){
        $labelRelation = new \mia\miagroup\Model\Ums\LabelRelation();
        $data = $labelRelation->getLabelRelation($subject_id, $label_ids);
        return $data;
    }

    
}