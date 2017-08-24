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
        if (!empty($params['id'])) {
            //帖子ID
            $condition['id'] = $koubeiCondtion['subject_id'] = $params['id'];
        }
        if (intval($params['user_id']) > 0 && empty($condition['id'])) {
            //用户id
            $condition['user_id'] = $koubeiCondtion['user_id'] = $params['user_id'];
        }
        if (!empty($params['user_name']) && intval($condition['user_id']) <= 0 && empty($condition['id'])) {
            //用户名
            $condition['user_id'] = $koubeiCondtion['user_id'] = intval($this->userModel->getUidByUserName($params['user_name']));
        }
        if (!empty($params['nick_name']) && intval($condition['user_id']) <= 0 && empty($condition['id'])) {
            //用户昵称
            $condition['user_id'] = $koubeiCondtion['user_id'] = $this->userModel->getUidByNickName($params['nick_name']);
        }
        if (!empty($params['title'])) {
            //标题搜索
            $condition['title'] = $params['title'];
        }
        if (!empty($params['uid_list']) && is_array($params['uid_list']) && intval($condition['user_id']) <= 0 && empty($condition['id'])) {
            $condition['user_id'] = $params['uid_list'];
        }
        if (is_array($params['status']) || (!is_array($params['status']) && $params['status'] !== null && $params['status'] !== '' && in_array($params['status'], array(0, 1, -1, 4))) && empty($condition['id'])) {
            //帖子状态
            $condition['status'] = $params['status'];
        }
        
        if ($params['source'] !== null && $params['source'] !== '' && in_array($params['source'], array(0, 1, 2, 4)) && empty($condition['id'])) {
            //帖子来源
            if($params['source'] == 0){
                $params['source'] = array(1, 2, 4);
            }
            $condition['source'] = $params['source'];
        }
        
        if ($params['is_fine'] !== null && $params['is_fine'] !== '' && in_array($params['is_fine'], array(0, 1)) && empty($condition['id'])) {
            //是否是推荐
            $condition['is_fine'] = $params['is_fine'];
        }
        if ($params['is_top'] !== null && $params['is_top'] !== '' && in_array($params['is_top'], array(0, 1)) && empty($condition['id'])) {
            //是否是置顶
            $condition['is_top'] = $params['is_top'];
        }
        if ($params['is_audited'] !== null && $params['is_audited'] !== '' && in_array($params['is_audited'], array(0, 1))) {
            //是否已同步口碑
            $koubeiCondtion['is_audited'] = $params['is_audited'];
        }
        
        if (intval($params['item_id']) > 0 && empty($condition['id'])) {
            //商品ID
            $koubeiCondtion['item_id'] = $params['item_id'];
        }
        if (strtotime($params['start_time']) > 0 && empty($condition['id'])) {
            //起始时间
            $condition['start_time'] = $koubeiCondtion['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0 && empty($condition['id'])) {
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
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('item', 'album','group_labels','count', 'share_info'), array())['data'];
        $userIds = array();
        foreach ($subjectInfos as $v) {
            if (intval($v['user_id']) > 0) {
                $userIds[] = $v['user_id'];
            }
        }
        $userService = new UserService();
        $userInfos = $userService->getUserInfoByUids($userIds, 0, ['count', 'cell_phone', 'user_group'])['data'];
        foreach ($data['list'] as $v) {
            $tmp = $v;
            $tmp['subject'] = $subjectInfos[$v['subject_id']];
            $tmp['subject']['user_info'] = $userInfos[$v['user_id']];
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
    
    /**
     * 获取长文列表
     */
    public function getBlogList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'id desc'; //默认排序
        if ($params['limit'] === false) {
            $limit = false;
        } else {
            $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 20;
        }
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
        if (intval($params['user_id']) > 0 && empty($condition['id'])) {
            //用户id
            $condition['user_id'] = $params['user_id'];
        }
        if (is_array($params['status']) || (!is_array($params['status']) && $params['status'] !== null && $params['status'] !== '' && in_array($params['status'], array(0, 1, -1, 3))) && empty($condition['id'])) {
            //状态
            $condition['status'] = $params['status'];
        }
        if (strtotime($params['start_time']) > 0 && empty($condition['id'])) {
            //起始时间
            $condition['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0 && empty($condition['id'])) {
            //结束时间
            $condition['end_time'] = $params['end_time'];
        }
        $data = $this->subjectModel->getBlogData($condition, $offset, $limit, $orderBy);
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $subjectIds = array_keys($data['list']);
        $subjectService = new SubjectService();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('user_info', 'item', 'album','group_labels','count','content_format', 'share_info'), array())['data'];
        foreach ($data['list'] as $subject_id => $v) {
            if (!empty($subjectInfos[$subject_id])) {
                $subject = $subjectInfos[$subject_id];
                //非官方账号发布的长文，仅在审核状态下可编辑、删除
                if ($subject['type'] == 'blog') {
                    if (in_array($subject["user_id"], \F_Ice::$ins->workApp->config->get('busconf.user.blog_audit_white_list')) || in_array($subject['status'], [\F_Ice::$ins->workApp->config->get('busconf.subject.status.koubei_hidden'), \F_Ice::$ins->workApp->config->get('busconf.subject.status.to_audit'), \F_Ice::$ins->workApp->config->get('busconf.subject.status.audit_failed')])) {
                        $subject['allow_operate'] = 1;
                    } else {
                        $subject['allow_operate'] = 0;
                    }
                }
                if (!empty($v['index_cover_image'])) {
                    $subject['cover_image'] = \mia\miagroup\Util\NormalUtil::buildImgUrl($v['index_cover_image']['url'], 'normal', $v['index_cover_image']['width'], $v['index_cover_image']['height']);
                }
                $result['list'][] = $subject;
            }
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
        if (is_array($data['status']) || (!is_array($data['status']) && $data['status'] !== null && $data['status'] !== '' && in_array($data['status'], array(0, 1, -1, 4))) && intval($data['id']) <= 0) {
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
            $solrParams['label'] = $label['id'];
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
        
        if (!empty($data['subject_type'])) {
            //查询帖子类型
            $solrParams['subject_type'] = $data['subject_type'];
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
        $result['list'] = $this->getSubjectList(['id' => $subject_ids, 'limit' => $limit])['data']['list'];
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
    public function getOperationNoteList($tabId= array(), $page = 1)
    {
        //发现列表，增加运营广告位
        $res = array();
        $operationNoteData = $this->subjectModel->getOperationNoteData($tabId, $page, 'all');
        $operationNoteIds = array_keys($operationNoteData);
        $res['content_lists'] = $this->formatNoteData($operationNoteIds,$operationNoteData);
        return $this->succ($res);
    }
    
    /**
     * 根据展示id，运营数据，拼凑出数据
     * @param array $ids
     * @param array $operationNoteData
     * @return array
     */
    public function formatNoteData(array $ids, $operationNoteData = [])
    {
        $subjectIds = array();
        $doozerIds = array();
        foreach ($ids as $key => $value) {
            list($id, $relation_id, $relation_type) = explode('_', $value, 3);
            if($relation_type == 'link'){
                continue;
            }
            //帖子
            if ($relation_type == 'subject') {
                $subjectIds[] = $relation_id;
                //达人
            } elseif ($relation_type == 'doozer') {
                $doozerIds[] = $relation_id;
            }
        }
        //批量获取帖子信息
        if(!empty($subjectIds)){
            $subjectService = new SubjectService();
            $subjects = $subjectService->getBatchSubjectInfos($subjectIds, 0, ['user_info', 'count', 'content_format'])['data'];
        }
    
        //批量获取达人信息
        if (!empty($doozerIds)) {
            $userService = new UserService();
            $doozerInfo = $userService->getUserInfoByUids($doozerIds, 0, ['count'])['data'];
        }
    
        $return = [];
        foreach ($ids as $value) {
            $count = substr_count($value, "_");
            list($id, $relation_id, $relation_type) = explode('_', $value, 3);
    
            //使用运营配置信息
            $is_opearation = 0;
            if (array_key_exists($value, $operationNoteData)) {
                $relation_desc = $operationNoteData[$value]['ext_info']['desc'] ? $operationNoteData[$value]['ext_info']['desc'] : '';
                $relation_title = $operationNoteData[$value]['ext_info']['title'] ? $operationNoteData[$value]['ext_info']['title'] : '';
                $relation_cover_image = !empty($operationNoteData[$value]['ext_info']['cover_image']) ? $operationNoteData[$value]['ext_info']['cover_image'] : '';
                $is_opearation = 1;
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
                        $tmpData['is_opearation'] = $is_opearation;
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
                        $tmpData['is_opearation'] = $is_opearation;
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
                    $link['image'] = $relation_cover_image;
                    $link['url'] = $linkInfo['ext_info']['url'];
                    $link['desc'] = $relation_desc;
                    $tmpData['link'] = $link;
                    $tmpData['is_opearation'] = $is_opearation;
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
    
    /**
     * 首页导航分类标签
     * @return mixed
     */
    public function indexTabLists()
    {
        $config = \F_Ice::$ins->workApp->config->get('busconf.subject');
        //起始固定位，“发现”，“关注”
        $headTabs = $config['group_fixed_tab_first'];
        //一级频道分类
        $middleTabs = $config['first_level'];
        //结尾固定位，“育儿”
        $endTabs = $config['group_fixed_tab_last'];
    
        //将首页固定频道和一级频道格式统一
        $fixTabs = array_merge($headTabs,$endTabs);
        $newTab = array();
        foreach ($fixTabs as $v) {
            $newTab[$v['extend_id']] = $v['name'];
        }
    
        $tabResult = $newTab+$middleTabs;
        return $this->succ($tabResult);
    }
}