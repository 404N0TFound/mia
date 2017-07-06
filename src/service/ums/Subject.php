<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Koubei as KoubeiModel;
use mia\miagroup\Model\Ums\User as UserModel;
use mia\miagroup\Model\Ums\Subject as SubjectModel;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Service\User as UserService;
use \mia\miagroup\Remote\Solr;

class Subject extends \mia\miagroup\Lib\Service {
    
    public $koubeiModel;
    public $userModel;
    public $subjectModel;
    
    public function __construct() {
        parent::__construct();
        $this->koubeiModel = new KoubeiModel();
        $this->userModel = new UserModel();
        $this->subjectModel = new SubjectModel();
    }
    
    public function getSubjectList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        $koubeiCondtion = array();
        //初始化入参
        $orderBy = 'id desc'; //默认排序
        if ($params['limit'] === false) {
            $limit = false;
        } else {
            $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 20;
        }
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
        if (intval($params['id']) > 0) {
            //帖子ID
            $condition['id'] = $koubeiCondtion['subject_id'] = $params['id'];
        }
        if (intval($params['user_id']) > 0 && intval($condition['id']) <= 0) {
            //用户id
            $condition['user_id'] = $koubeiCondtion['user_id'] = $params['user_id'];
        }
        if (!empty($params['user_name']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            //用户名
            $condition['user_id'] = $koubeiCondtion['user_id'] = intval($this->userModel->getUidByUserName($params['user_name']));
        }
        if (!empty($params['nick_name']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            //用户昵称
            $condition['user_id'] = $koubeiCondtion['user_id'] = $this->userModel->getUidByNickName($params['nick_name']);
        }
        if (!empty($params['title'])) {
            //标题搜索
            $condition['title'] = $params['title'];
        }
        if (!empty($params['uid_list']) && is_array($params['uid_list']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            $condition['user_id'] = $params['uid_list'];
        }
        if (is_array($params['status']) || (!is_array($params['status']) && $params['status'] !== null && $params['status'] !== '' && in_array($params['status'], array(0, 1, -1))) && intval($condition['id']) <= 0) {
            //帖子状态
            $condition['status'] = $params['status'];
        }
        
        if ($params['source'] !== null && $params['source'] !== '' && in_array($params['source'], array(0, 1, 2, 4)) && intval($condition['id']) <= 0) {
            //帖子来源
            if($params['source'] == 0){
                $params['source'] = array(1, 2, 4);
            }
            $condition['source'] = $params['source'];
        }
        
        if ($params['is_fine'] !== null && $params['is_fine'] !== '' && in_array($params['is_fine'], array(0, 1)) && intval($condition['id']) <= 0) {
            //是否是推荐
            $condition['is_fine'] = $params['is_fine'];
        }
        if ($params['is_top'] !== null && $params['is_top'] !== '' && in_array($params['is_top'], array(0, 1)) && intval($condition['id']) <= 0) {
            //是否是置顶
            $condition['is_top'] = $params['is_top'];
        }
        if ($params['is_audited'] !== null && $params['is_audited'] !== '' && in_array($params['is_audited'], array(0, 1))) {
            //是否已同步口碑
            $koubeiCondtion['is_audited'] = $params['is_audited'];
        }
        
        if (intval($params['item_id']) > 0 && intval($condition['id']) <= 0) {
            //商品ID
            $koubeiCondtion['item_id'] = $params['item_id'];
        }
        if (strtotime($params['start_time']) > 0 && intval($condition['id']) <= 0) {
            //起始时间
            $condition['start_time'] = $koubeiCondtion['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0 && intval($condition['id']) <= 0) {
            //结束时间
            $condition['end_time'] = $koubeiCondtion['end_time'] = $params['end_time'];
        }
        if (isset($koubeiCondtion['is_audited']) || isset($koubeiCondtion['item_id'])) {
            $orderBy = 'koubei_subjects.id desc';
            $data = $this->koubeiModel->getSubjectData($koubeiCondtion, $offset, $limit, $orderBy);
        } else {
            $data = $this->subjectModel->getSubjectData($condition, $offset, $limit, $orderBy);
        }
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $subjectIds = array();
        foreach ($data['list'] as $v) {
            if(empty($v['subject_id'])){
                continue;
            }
            $subjectIds[] = $v['subject_id'];
        }
        $subjectService = new SubjectService();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('user_info', 'item', 'album','group_labels','count','content_format', 'share_info'), array())['data'];
        foreach ($data['list'] as $v) {
            $tmp = $v;
            $tmp['subject'] = $subjectInfos[$v['subject_id']];
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }

    /*
     * 帖子综合搜索
     * */
    public function getComSubjectList($data)
    {
        $subject_ids = array();
        $solrParams = array();
        $subjectInfos = array();

        if(empty($data)) {
            return $this->succ($subjectInfos);
        }

        $page = intval($data['page']) > 1 ?  intval($data['page']): 1;
        $limit = intval($data['limit']) > 0 ?  intval($data['limit']): 0;

        if (intval($data['id']) > 0) {
            //帖子ID
            $solrParams['id'] = intval(trim($data['id']));
        }
        if (!empty($data['title'])) {
            //标题搜索
            $solrParams['title_like'] = trim($data['title']);
        }
        if (!empty($data['content'])) {
            //内容搜索
            $solrParams['text_like'] = trim($data['content']);
        }
        if (is_array($data['status']) || (!is_array($data['status']) && $data['status'] !== null && $data['status'] !== '' && in_array($data['status'], array(0, 1, -1))) && intval($data['id']) <= 0) {
            //帖子状态
            $solrParams['status'] = $data['status'];
        }
        if ($data['source'] !== null && $data['source'] !== '' && in_array($data['source'], array(0, 1, 2, 4)) && intval($data['id']) <= 0) {
            //帖子来源
            if($data['source'] == 0){
                $data['source'] = array(1, 2, 4);
            }
            $solrParams['source'] = $data['source'];
        }
        if ($data['is_fine'] !== null && $data['is_fine'] !== '' && in_array($data['is_fine'], array(0, 1)) && intval($data['id']) <= 0) {
            //是否是推荐
            $solrParams['is_fine'] = $data['is_fine'];
        }
        if (intval($data['item_id']) > 0 && intval($data['id']) <= 0) {
            //商品ID
            $solrParams['item_id'] = $data['item_id'];
        }
        if (strtotime($data['start_time']) > 0 && intval($data['id']) <= 0) {
            //起始时间
            $solrParams['start_time']  = $data['start_time'];
        }
        if (strtotime($data['end_time']) > 0 && intval($data['id']) <= 0) {
            //结束时间
            $solrParams['end_time'] = $data['end_time'];
        }
        if (!empty($data['user_id'])) {
            //用户ID
            $solrParams['user_id'] = $data['user_id'];
        }
        if (!empty($data['uid_list']) && is_array($data['uid_list']) && intval($data['user_id']) <= 0 && intval($data['id']) <= 0) {
            // 用户列表
            foreach($data['uid_list'] as $k => $user_id) {
                if(empty(trim($user_id))) {
                    unset($data['uid_list'][$k]);
                }
            }
            $solrParams['user_id'] = $data['uid_list'];
        }
        if (!empty($data['user_type'])) {
            //用户分类
            $solrParams['c_type'] = $data['user_type'];
        }
        if (!empty($data['role_id'])) {
            //用户分组
            $solrParams['role_id'] = $data['role_id'];
        }
        if (!empty($data['active_id'])) {
            //活动
            $solrParams['active_id'] = $data['active_id'];
        }
        if (in_array($data['semantic_analys'], [1,2,3])){
            //内容分析
            $solrParams['semantic_analys'] = $data['semantic_analys'];
        }
        if (!empty($data['supplier_id'])) {
            //商家ID
            $solrParams['supplier_id'] = $data['supplier_id'];
        }
        if (!empty($data['label_name'])) {
            //标签名称
            $labelService = new \mia\miagroup\Service\Label();
            $label = $labelService->getLabelInfoByTitle($data['label_name'])['data'];
            $solrParams['label'] = $label['label_id'];
        }
        if ($data['is_pic'] == 1) {
            //带图
            $solrParams['after_image'] = 1;
        }
        if ($data['is_pic'] == 0) {
            //不带图
            $solrParams['before_image'] = 0;
        }
        if($data['is_title'] == 1) {
            //有标题
            $solrParams['have_title'] = $data['is_title'];
        }
        if($data['is_title'] == 0) {
            //没有标题
            $solrParams['no_title'] = $data['is_title'];
        }
        if (!empty($data['picnum'])) {
            //查询图片数
            $solrParams['after_pic_count'] = $data['picnum'];
        }
        if (!empty($data['contentnum'])) {
            //查询文字内容数
            $solrParams['after_text_count'] = $data['contentnum'];
        }
        $solr = new Solr('pic_search', 'group_search_solr');

        // 总用户数查询
        $solrData = $solr->getSeniorSolrSearch($solrParams, '', '', '',  [], ['count' =>'user_id'])['data'];
        $total_users = $solrData['facets']['count'];

        // 总评论数
        $solrData = $solr->getSeniorSolrSearch($solrParams, '', '', '',  [], ['sum' =>'comment_num'])['data'];
        $total_comment_num = $solrData['facets']['sum'];

        // 总点赞数
        $solrData = $solr->getSeniorSolrSearch($solrParams, '', '', '',  [], ['sum' =>'praise_num'])['data'];
        $total_praise_num = $solrData['facets']['sum'];

        // 总数据查询
        $solrData = $solr->getSeniorSolrSearch($solrParams, 'id', $page, $limit)['data'];
        $success = $solrData['responseHeader']['status'];
        $success_data = $solrData['response']['docs'];
        $total_count = $solrData['response']['numFound'];
        if($success != 0 || empty($total_count)) {
            return $this->succ($subjectInfos);
        }
        $subject_ids = array_column($success_data, 'id');
        $subjectService = new SubjectService();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subject_ids, 0, array('user_info', 'item', 'album','group_labels','count','content_format', 'share_info'), array())['data'];
        foreach ($subjectInfos as $v) {
            $v['subject'] = $v;
            $result['list'][] = $v;
        }
        $result['count'] = !empty($total_count) ? $total_count : 0;
        $result['total_users'] = !empty($total_users) ? $total_users: 0;
        $result['total_comment_num'] = !empty($total_comment_num) ? $total_comment_num: 0;
        $result['total_praise_num'] = !empty($total_praise_num) ? $total_praise_num: 0;
        return $this->succ($result);
    }
    
    /**
     * 获取蜜芽圈笔记推广位列表
     * @param $tabId
     * @param $page
     */
    public function getOperationNoteList($tabId=1, $page = 1)
    {
        //发现列表，增加运营广告位
        $res = array();
        $operationNoteData = $this->subjectModel->getOperationNoteData($tabId, $page, 'all');
        $res['content_lists'] = $this->formatNoteData($operationNoteData);
        return $this->succ($res);
    }
    
    /**
     * 根据展示id，运营数据，拼凑出数据
     * @param array $operationNoteData
     * @return array
     */
    public function formatNoteData($operationNoteData = [])
    {
        if(empty($operationNoteData)){
            return array();
        }
        $subjectIds = array();
        $doozerIds = array();
        $operationNoteIds = array_keys($operationNoteData);
        foreach ($operationNoteIds as $key => $value) {
            list($relation_id, $relation_type) = explode('_', $value, 2);
            if($relation_type == 'link'){
                continue;
            }
            //帖子
            if ($relation_type == 'subject') {
                $subjectIds[] = $relation_id;
            }
            //专题
            if ($relation_type == 'doozer') {
                $doozerIds[] = $relation_id;
            }
        }
        //批量获取帖子信息
        if(!empty($subjectIds)){
            $subjectService = new SubjectService();
            $subjects = $subjectService->getBatchSubjectInfos($subjectIds)['data'];
        }
        
        //批量获取达人信息
        if (!empty($doozerIds)) {
            $userService = new UserService();
            $doozerInfo = $userService->getUserInfoByUids($doozerIds, 0, ['count'])['data'];
        }
    
        $return = [];
        foreach ($operationNoteIds as $value) {
            list($relation_id, $relation_type) = explode('_', $value, 2);
            //使用运营配置信息
            if (array_key_exists($value, $operationNoteData)) {
                $relation_id = $operationNoteData[$value]['relation_id'];
                $relation_type = $operationNoteData[$value]['relation_type'];
                $relation_desc = $operationNoteData[$value]['ext_info']['desc'] ? $operationNoteData[$value]['ext_info']['desc'] : '';
                $relation_title = $operationNoteData[$value]['ext_info']['title'] ? $operationNoteData[$value]['ext_info']['title'] : '';
                $relation_cover_image = $operationNoteData[$value]['ext_info']['cover_image'] ? $operationNoteData[$value]['ext_info']['cover_image'] : '';
                $tmpData['config_data'] = $operationNoteData[$value];
            }
            switch ($relation_type) {
                //目前只有口碑帖子，蜜芽圈帖子。
                case 'subject':
                    if (isset($subjects[$relation_id]) && !empty($subjects[$relation_id])) {
                        //有无视频不区分，video_info有值代表有视频
                        $subject = $subjects[$relation_id];
                        $tmpData['id'] = $subject['id'] . '_subject';
                        $tmpData['type'] = 'subject';
                        $tmpData['type_name'] = '口碑';
    
                        $subject['title'] = $relation_title ? $relation_title : $subject['title'];
                        if(!empty($relation_cover_image)){
                            $subject['cover_image'] = $relation_cover_image;
                        }
                        $tmpData['subject'] = $subject;
                    }
                    break;
                case 'doozer':
                    if (isset($doozerInfo[$relation_id]) && !empty($doozerInfo[$relation_id])) {
                        $user = $doozerInfo[$relation_id];
                        $tmpData['id'] = $user['user_id'] . '_doozer';
                        $tmpData['type'] = 'doozer';
                        $tmpData['type_name'] = '达人';
    
                        //配置了用配置的title，否则用group_doozer里的intro
                        if(!empty($relation_title)){
                            $user['doozer_intro'] = $relation_title ? $relation_title : $doozerInfo[$relation_id]['intro'];
                        }
                        if(!empty($relation_cover_image)){
                            $user['doozer_recimage'] = $relation_cover_image;
                        }
                        $tmpData['doozer'] = $user;
                    }
                    break;
                case 'link':
                    //链接跳转，只是运营配置的
                    $linkInfo = $operationNoteData[$relation_id.'_'.$relation_type];
                    $tmpData['id'] = $relation_id;
                    $tmpData['type'] = 'link';
                    $tmpData['type_name'] = '链接';
    
                    $link['title'] = $relation_title ? $relation_title : '';
                    $link['id'] = $relation_id;
                    if(!empty($relation_cover_image)){
                        $link['image'] = $relation_cover_image;
                    }
                    $link['url'] = $linkInfo['ext_info']['url'];
                    if(!empty($relation_desc)){
                        $link['desc'] = $relation_desc;
                    }
                    $tmpData['link'] = $link;
                    break;
            }
            if (!empty($tmpData)) {
                $return[] = $tmpData;
            }
            unset($subject);
            unset($tmpData);
            unset($relation_title);
            unset($relation_cover_image);
        }
        return $return;
    }
}