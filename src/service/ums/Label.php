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

    
}