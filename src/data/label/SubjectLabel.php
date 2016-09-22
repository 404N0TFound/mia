<?php
namespace mia\miagroup\Data\Label;

use Ice;

class SubjectLabel extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_label';

    protected $mapping = array();
    // TODO
    
    /**
     * 批量查标签信息
     *
     * @param
     *            s array() $labelIdArr 标签ids
     * @return array() 图片标签信息列表
     */
    public function getBatchLabelInfos($labelIds) {
        if (empty($labelIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'id', $labelIds);
        $where[] = array(':eq', 'status', 1);
        $labelInfos = $this->getRows($where, '`id`, `title`, `is_hot`');
        $labelsRes = array();
        if (!empty($labelInfos)) {
            foreach ($labelInfos as $labelInfo) {
                $labelsRes[$labelInfo['id']] = $labelInfo;
            }
        }
        return $labelsRes;
    }

    /**
     * 判断标签记录是否存在(用于图片发布，避免主辅库不同步，从主库查)
     *
     * @param string $labelTitle
     *            标签标题
     * @return bool
     */
    public function checkIsExistByLabelTitle($labelTitle) {
        $labelTitle = md5($labelTitle);
        
        $where[] = array("title_md5", $labelTitle);
        $LabelRes = $this->getRow($where);
        
        return $LabelRes;
    }

    /**
     * 保存蜜芽圈标签
     *
     * @param array $labelInfo
     *            标签信息
     * @return int 标签id
     */
    public function addLabel($labelTitle) {
        $labelTitleMd5 = md5($labelTitle);
        $setData = ['title' => $labelTitle, 'title_md5' => $labelTitleMd5];
        $LabelId = $this->insert($setData);
        
        return $LabelId;
    }
    
    /**
     * 获取标签ID
     */
    public function getLabelID(){
        $LabelID = array();
        $where = array();
        $where[] = array("status", '1');
        $where[] = array("is_recommend", '1');
        $LabelRes = $this->getRows($where,'id');
        if($LabelRes){
            foreach ($LabelRes as $value){
                    $LabelID[] = $value['id'];
            }
        }
        return $LabelID;
    }

    /**
     * 获取标签列表
     */
    public function getRecommendLables($offset=0,$limit=10,$userType='')
    {
        if(!empty($userType)){
            $where[] = [$userType,1];
        }
        $orderBy = '';
        if(($userType=='is_new')){
            $orderBy = 'new_time desc';
        }elseif($userType=='is_recommend'){
            $orderBy = 'recom_time desc';
        }
        $where[] = ['status',1];
        $data = $this->getRows($where,'*',$limit,$offset,$orderBy);
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = $value['id'];
        }
        return $result;
    }

    /**
     * 获取标签分类信息
     */
    public function getRecommendCategroyLabels($start = 1, $limit = 50)
    {
        $sql = "select a.category_id,l.id,l.title
        from group_subject_label_category_relation as a
        , {$this->table_labels} as l
        where a.label_id = l.id 
        order by a.id desc limit {$start}, {$limit}";
        $result = $this->query($sql);
        return $result;
    }

    /**
     * 更新标签详情页图像宽高
     */
    public function updateLabelImgInfo($labelId,$setData) {
        
        if (!isset($setData['ext_info']) || empty($setData['ext_info']) || empty($labelId)) {
            return false;
        }
        $setDataNew[] = ['ext_info', json_encode($setData['ext_info'])];
        $where = array();
        $where[] = ['id', $labelId];
        $data = $this->update($setDataNew, $where);
        return $data;
    }
}
