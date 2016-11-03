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
     * 选择标签，添加关联关系
     * 从后台给帖子添加关联标签
     */
    public function addSubjectLabelRelation($subject_id,$label_id,$user_id){
        $labelRelationModel = new \mia\miagroup\Model\Ums\LabelRelation();
        //判断数量是否已经达到 6 个
        $relation_info = $labelRelationModel->getExistsLabel($subject_id);
        if(count($relation_info) >= 6){
            return $this->error(90000,'关联标签数已达上限');
        }
        //判断是否已经存在关联关系
        $relation_res = $labelRelationModel->getLabelRelation($subject_id,$label_id);
        if(!empty($relation_res)){
            return $this->error(90001,'对应关系已经存在');
        }
        $res = $labelRelationModel->addLabelRelation($subject_id, $label_id, 0, $user_id);
        return $this->succ($res);
    }
    
    /**
     * 输入标签添加关联关系
     */
    public function addSubjectLabelRelationInput($subject_id,$label_title){
        if (mb_strlen($label_title,'utf-8') > 20 || strlen($label_title) <= 0) {
            return $this->error(90002,'标签名字长度不符合要求');
        }
        //判断数量是否已经达到 6 个
        $labelRelationModel = new \mia\miagroup\Model\Ums\LabelRelation();
        $relation_res = $labelRelationModel->getExistsLabel($subject_id);
        if (count($relation_res) >= 6) {
            return $this->error(90000,'关联标签数已达上限');
        }
        //这里的 input 新添加有两种情况
        //1、已经存在 label，如果没有对应关系，则添加关系。
        //2、label 不存在，则添加
        $labelRelationModelNoUms = new \mia\miagroup\Model\Label();
        $label_info = $labelRelationModelNoUms->checkIsExistByLabelTitle($label_title);
        if (!empty($label_info)) {
            $label_id = $label_info['id'];
            
            //存在 label ,不需要进行添加 label 操作
            //已经存在图片与标签的关联（label）
            if (!empty($labelRelationModel->getLabelRelation($subject_id, $label_id))) {
                return $this->error(90001,'对应关系已经存在');
            }
        } else {
            //不存在 label, 需要进行添加 label 操作
            $label_id = $this->labelModel->addLabel($label_title, $this->userdata['user_id']);
            if (!$label_id) {
                return $this->error(90004,'添加标签名称失败');
            }
            if (!empty($labelRelationModel->getLabelRelation($subject_id, $label_id))) {
                return $this->error(90001,'对应关系已经存在');
            }
        }
        //添加数据
        $relation_id = $labelRelationModel->addLabelRelation($subject_id, $label_id, 0, $this->userdata['user_id']);
        if ($relation_id) {
            return $this->succ($relation_id);
        } else {
            return $this->error(90005,'添加失败');
        }
    }
    
    /**
     * 给标签下的帖子加精
     */
    public function changeLabelRelationRecommend($id,$recommend,$user_id){
        $labelRelationModel = new \mia\miagroup\Model\Ums\LabelRelation();
        $affect = $labelRelationModel->setLabelRelationRecommend($id, $recommend, $user_id);
        return $this->succ($affect);
    }
    
    /**
     * 获取帖子关联的标签信息
     */
    public function getExistsLabelInfo($subject_id){
        $labelRelationModel = new \mia\miagroup\Model\Ums\LabelRelation();
        $result = $labelRelationModel->getExistsLabel($subject_id);
        return $this->succ($result);
    }
    
    /**
     * 取消标签帖子关联关系
     */
    public function cancleSelectedTag($subject_id,$label_id,$from_input=0){
        
        //如果是 input 新添加的标签，该标签要被删除。
        if ($from_input == 1) {
            $labelModel = new \mia\miagroup\Model\Ums\Label();
            $del_label_res = $labelModel->removeLabelByLabelId($label_id);
        }
        $labelRelationModel = new \mia\miagroup\Model\Ums\LabelRelation();
        //删除关联
        $affect = $labelRelationModel->removeRelation($subject_id,$label_id);
        $this->succ($affect);
    }
    
}