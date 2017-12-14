<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Koubei as KoubeiModel;
use mia\miagroup\Model\Ums\User as UserModel;
use mia\miagroup\Model\Ums\Item as ItemModel;
use mia\miagroup\Model\Ums\StockWarehouse as StockModel;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Service\Koubei as KoubeiService;
use mia\miagroup\Service\Item as ItemService;
use mia\miagroup\Util\EmojiUtil;

class Koubei extends \mia\miagroup\Lib\Service {
    
    private $koubeiModel;
    private $userModel;
    private $itemModel;
    private $stockModel;
    
    public function __construct() {
        parent::__construct();
        $this->koubeiModel = new KoubeiModel();
        $this->userModel = new UserModel();
        $this->emojiUtil = new EmojiUtil();
        $this->itemModel = new ItemModel();
        $this->stockModel = new StockModel();
    }
    
    /**
     * ums口碑列表
     */
    public function getKoubeiList($params, $isRealtime = true) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        $solrCond = array();
        //初始化入参
        $orderBy = 'id desc'; //默认排序
        if ($params['limit'] === false) {
            $limit = false;
        } else {
            $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 20;
        }
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
        
        if (intval($params['id']) > 0) {
            //口碑ID
            $solrCond['id'] = $params['id'];
        }
        if (intval($params['user_id']) > 0 && intval($solrCond['id']) <= 0) {
            //用户id
            $solrCond['user_id'] = $params['user_id'];
        }
        if (intval($params['supplier_id']) > 0 && intval($solrCond['id']) <= 0) {
            //供应商id
            $solrCond['supplier_id'] = $params['supplier_id'];
        }
        if (!empty($params['user_name']) && intval($solrCond['user_id']) <= 0 && intval($solrCond['id']) <= 0) {
            //用户名
            $solrCond['user_id'] = intval($this->userModel->getUidByUserName($params['user_name']));
        }
        if (!empty($params['nick_name']) && intval($solrCond['user_id']) <= 0 && intval($solrCond['id']) <= 0) {
            //用户昵称
            $solrCond['user_id'] = intval($this->userModel->getUidByNickName($params['nick_name']));
        }
        if ($params['status'] !== null && $params['status'] !== '' && in_array($params['status'], array(0, 1, 2)) && intval($solrCond['id']) <= 0) {
            //口碑状态
            $solrCond['status'] = $params['status'];
        }
        if (isset($params['auto_evaluate']) && $params['auto_evaluate'] !== null && $params['auto_evaluate'] !== '' && in_array($params['auto_evaluate'], array(0, 1)) && intval($solrCond['id']) <= 0) {
            //是否展示默认好评，默认不展示
            $solrCond['auto_evaluate'] = $params['auto_evaluate'] ? 1 : 0;
        } else {
            $solrCond['auto_evaluate'] = 0;
        }
        if ($params['comment_status'] !== null && $params['comment_status'] !== '' && in_array($params['comment_status'], array(0, 1)) && intval($solrCond['id']) <= 0) {
            //口碑回复状态
            $solrCond['comment_status'] = $params['comment_status'];
            $orderBy = 'comment_time desc';
        }
        if ($params['rank'] !== null && $params['rank'] !== '' && in_array($params['rank'], array(0, 1)) && intval($solrCond['id']) <= 0) {
            //是否是精品
            $solrCond['rank'] = $params['rank'];
        }
        if ((is_array($params['score']) || (isset($params['score']) && !is_array($params['score']) && $params['score'] != -1)) && intval($solrCond['id']) <= 0) {
            //用户评分
            $solrCond['score'] = $params['score'];
        }
        if (($params['score'] === '机选差评' || (is_array($params['score']) && in_array('机选差评', $params['score'])))  && intval($solrCond['id']) <= 0) {
            //机器评分
            $solrCond['machine_score'] = 1;
        }
        if ($params['machine_score'] !== null && $params['machine_score'] !== '' && in_array($params['machine_score'], array(1, 2, 3)) && intval($solrCond['id']) <= 0) {
            //机器评分
            $solrCond['machine_score'] = $params['machine_score'];
        }
        if (!empty($params['order_id']) && intval($solrCond['id']) <= 0) {
            //订单ID
            $solrCond['order_id'] = $params['order_id'];
        }
        if (intval($params['item_id']) > 0 && intval($solrCond['id']) <= 0) {
            //商品ID
            $solrCond['item_id'] = $params['item_id'];
        }
        if (!empty($params['brand']) && $isRealtime == false) {
            $solrCond['brand_id'] = $params['brand'];
        }
        if ($params['self_sale'] !== null && $params['self_sale'] !== '' && in_array($params['self_sale'], array(0, 1))) {
            //sku属性
            $solrCond['self_sale'] = $params['self_sale'];
        }
        if (!empty($params['category_ids']) && intval($params['category_id']) <= 0  && $isRealtime == false) {
            //只有一级类目的时候
            $solrCond['category_id'] = $params['category_ids'];
        }
        
        if (intval($params['category_id']) > 0 && $isRealtime == false) {
            //类目ID
            $solrCond['category_id'] = $params['category_id'];
        }
        //只选择了仓库一级类型，且为自营，则需要包括其下面的所有业务类型（1：自营,6：跨境购）
        if (!empty($params['warehouse_type']) && $params['warehouse_type'] == 1 && empty($params['service_type']) && $isRealtime == false) {
            $solrCond['warehouse_type'] = array(1,6);
        }
        //只选择了仓库一级类型，且为pop，则需要包括其下面的所有业务类型（3：POP国内,5：直邮,7：虚拟跨境购,8：自营直邮,10：保税区,11：直邮一件代销,12：国内一件代销,13：一件代销海外站）
        if (!empty($params['warehouse_type']) && $params['warehouse_type'] == 2 && empty($params['service_type']) && $isRealtime == false) {
            $solrCond['warehouse_type'] = array(3,5,7,8,10,11,12,13);
        }
        //选择了仓库二级类型
        if (!empty($params['service_type']) && $isRealtime == false) {
            $solrCond['warehouse_type'] = $params['service_type'];
        }
        if (!empty($params['pop_type']) && $isRealtime == false && in_array($params['service_type'], [3])) {
            //拼团类型
            $solrCond['pop_type'] = $params['pop_type'];
        }
        if (!empty($params['stock_id']) && $isRealtime == false) {
            //仓库类型
            $solrCond['stock_id'] = $params['stock_id'];
        }
        if (strtotime($params['start_time']) > 0 && intval($solrCond['id']) <= 0) {
            //起始时间
            $solrCond['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0 && intval($solrCond['id']) <= 0) {
            //结束时间
            $solrCond['end_time'] = $params['end_time'];
        }
        if (strtotime($params['comment_start_time']) > 0 && intval($solrCond['id']) <= 0) {
            //回复起始时间
            $solrCond['comment_start_time'] = $params['comment_start_time'];
            $orderBy = 'comment_time desc';
        }
        if (strtotime($params['comment_end_time']) > 0 && intval($solrCond['id']) <= 0) {
            //回复结束时间
            $solrCond['comment_end_time'] = $params['comment_end_time'];
            $orderBy = 'comment_time desc';
        }
        //回复方
        if (isset($params['comment_style']) && in_array($params['comment_style'],array(0,1)) && intval($solrCond['id']) <= 0) {
            $solrCond['comment_style'] = intval($params['comment_style']);
        }
        //回复人(回复商家id)
        if (intval($params['comment_supplier_id']) && intval($solrCond['id']) <= 0) {
            $solrCond['comment_supplier_id'] = intval($params['comment_supplier_id']);
        }
        //商家回复监控操作
        if($params['op']){
            $solrCond['op'] = $params['op'];
        }
        
        //是否是甄选
        if (isset($params['selections']) && in_array($params['selections'],array(0,1)) && $isRealtime == false) {
            $solrCond['selections'] = intval($params['selections']);
        }

        // 思源商家处理
        if (!empty(intval($params['siyuan_group']))) {
            //思源组
            $solrCond['siyuan_group'] = $params['siyuan_group'];
        }
        if (!empty(intval($params['siyuan_son_supplier_id']))) {
            //思源二级商家id
            $solrCond['siyuan_son_supplier_id'] = $params['siyuan_son_supplier_id'];
        }

        // 商家开通回复权限时间限制
        if(!empty($solrCond['supplier_id'])) {
            $itemService = new \mia\miagroup\Service\Item();
            $supplier = $itemService->getMappingBySupplierId($solrCond['supplier_id'])['data'];
            if($supplier['status'] == 1 && !empty($supplier['create_time'])) {
                if(!empty($solrCond['end_time']) && ($supplier['create_time'] > $solrCond['end_time'])) {
                    return $this->succ($result);
                }
                if(!empty($solrCond['start_time']) && $supplier['create_time'] > $solrCond['start_time']) {
                    $solrCond['start_time'] = $supplier['create_time'];
                }
            }
            if(empty($solrCond['start_time']) && !empty($supplier['create_time'])) {
                $solrCond['start_time'] = $supplier['create_time'];
            }
        }
        if($isRealtime == false){
            $solr = new \mia\miagroup\Remote\Solr('koubei');
            if(empty($limit)) {
                // 导出数据
                $solrCountData = $solr->getKoubeiList($solrCond, 'id', $params['page'], 1, $orderBy);
                if(!empty($solrCountData['count'])) {
                    $limit = $solrCountData['count'];
                }
            }
            
            // 查询列表
            $solrData = $solr->getKoubeiList($solrCond, 'id', $params['page'], $limit, $orderBy);
            //print_r($solrData);exit;
            if(!empty($solrData['list'])){
                foreach ($solrData['list'] as $v) {
                    $koubeiIds[] = $v['id'];
                }
                $condition['id'] = $koubeiIds;
                $data = $this->koubeiModel->getKoubeiData($condition, 0, $limit, $orderBy);
                $data['count'] = $solrData['count'];
            }
        }else{
            $data = $this->koubeiModel->getKoubeiData($solrCond, $offset, $limit, $orderBy);
        }
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $koubeiIds = array();
        $supplierIds = array();
        foreach ($data['list'] as $v) {
            $koubeiIds[] = $v['id'];
            if($v['supplier_id'] > 0){
                $supplierIds[] = $v['supplier_id'];
            }
        }
        //根据商家id获取仓库名
        $wearhouseInfo = $this->stockModel->getBatchNameBySupplyIds($supplierIds);

        $koubeiService = new KoubeiService();
        $maxKoubeiCount = 20000;
        $pieceCount = 100;
        $koubeiIds = count($koubeiIds) > $maxKoubeiCount ? array_splice($koubeiIds, 0, $maxKoubeiCount) : $koubeiIds;
        $koubeiInfos = array();
        $appealStatus = array();
        ini_set('memory_limit', '1024M');
        do {
            $pieceIds = count($koubeiIds) > $pieceCount ? array_splice($koubeiIds, 0, $pieceCount) : array_splice($koubeiIds, 0, count($koubeiIds));
            //获取口碑信息
            $tmpKoubeiInfos = $koubeiService->getBatchKoubeiByIds($pieceIds, 0, array('user_info', 'count', 'item', 'order_info', 'koubei_reply', 'content_format', 'filter_ums'), array())['data'];
            if (!empty($tmpKoubeiInfos)) {
                foreach ($tmpKoubeiInfos as $koubeiId => $koubei) {
                    $koubeiInfos[$koubeiId] = $koubei;
                }
            }
            //获取口碑申诉信息
            $koubeiAppealInfos = $this->koubeiModel->getKoubeiAppealData(array('koubei_id' => $pieceIds), 0, false)['list'];
            if (!empty($koubeiAppealInfos)) {
                foreach ($koubeiAppealInfos as $appeal) {
                    $appealStatus[$appeal['koubei_id']] = array('appeal_id' => $appeal['id'], 'status' => $appeal['status']);
                }
            }
        } while (!empty($koubeiIds));
        
        foreach ($data['list'] as $v) {
            $tmp = $v;
            if(!empty($koubeiInfos[$v['id']])){
                $tmp['subject'] = $koubeiInfos[$v['id']];
                if (isset($appealStatus[$v['id']])) {
                    $tmp['subject']['item_koubei']['appeal_status'] = $appealStatus[$v['id']]['status'];
                    $tmp['subject']['item_koubei']['appeal_id'] = $appealStatus[$v['id']]['appeal_id'];
                }
                if($v['supplier_id'] > 0){
                    //获取仓库信息
                    if(!empty($wearhouseInfo[$v['supplier_id']]['id'])){
                        $tmp['subject']['item_koubei']['wearhouse'] = $wearhouseInfo[$v['supplier_id']];
                    }
                }
            }
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
    
    /**
     * ums口碑申诉列表
     */
    public function getKoubeiAppealList($params) {
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
        
        if (intval($params['id']) > 0) {
            //口碑申诉ID
            $condition['id'] = $params['id'];
        }
        if ($params['status'] !== null && $params['status'] !== '' && in_array($params['status'], array(0, 1, 2)) && intval($condition['id']) <= 0) {
            //申诉状态
            $condition['status'] = $params['status'];
        }
        if (intval($params['supplier_id']) > 0 && intval($condition['id']) <= 0) {
            //供应商id
            $condition['supplier_id'] = $params['supplier_id'];
        }
        if (intval($params['koubei_id']) > 0 && intval($condition['id']) <= 0) {
            //口碑id
            $condition['koubei_id'] = $params['koubei_id'];
        }
        if (strtotime($params['start_time']) > 0 && intval($condition['id']) <= 0) {
            //起始时间
            $condition['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0 && intval($condition['id']) <= 0) {
            //结束时间
            $condition['end_time'] = $params['end_time'];
        }
        $data = $this->koubeiModel->getKoubeiAppealData($condition, $offset, $limit, $orderBy);
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $koubeiIds = array();
        $commentIds = array();
        foreach ($data['list'] as $v) {
            $koubeiIds[] = $v['koubei_id'];
            if (intval($v['koubei_comment_id']) > 0) {
                $commentIds[] = $v['koubei_comment_id'];
            }
        }
        $koubeiService = new KoubeiService();
        $koubeiInfos = $koubeiService->getBatchKoubeiByIds($koubeiIds, 0, array('user_info', 'share_info'))['data'];
        $commentService = new \mia\miagroup\Service\Comment();
        $commentInfos = $commentService->getBatchComments($commentIds, array('user_info', 'parent_comment'), array())['data'];
        foreach ($data['list'] as $v) {
            $tmp = $v;
            $tmp['appeal_koubei'] = $koubeiInfos[$v['koubei_id']];
            if (!empty($commentInfos[$v['koubei_comment_id']])) {
                $tmp['appeal_comment'] = $commentInfos[$v['koubei_comment_id']];
            }
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
    
    /**
     * ums口碑相关蜜芽贴列表
     */
    public function getKoubeiSubjectList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'create_time desc'; //默认排序
        if ($params['limit'] === false) {
            $limit = false;
        } else {
            $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 20;
        }
        $offset = intval($params['page'] > 1) ? (($params['page'] - 1) * limit) : 0;
    
        if (intval($params['subject_id']) > 0) {
            //帖子ID
            $condition['subject_id'] = $params['subject_id'];
        }
        if (intval($params['user_id']) > 0) {
            //用户id
            $condition['user_id'] = $params['user_id'];
        }
        if (!empty($params['user_name']) && intval($condition['user_id']) <= 0) {
            //用户名
            $condition['user_id'] = intval($this->userModel->getUidByUserName($params['user_name']));
        }
        if (!empty($params['nick_name']) && intval($condition['user_id']) <= 0) {
            //用户昵称
            $condition['user_id'] = intval($this->userModel->getUidByNickName($params['nick_name']));
        }
        if ($params['is_audited'] !== null && $params['is_audited'] !== '') {
            //帖子审核状态
            $condition['is_audited'] = $params['is_audited'];
        }
        if (intval($params['item_id']) > 0) {
            //商品ID
            $condition['item_id'] = $params['item_id'];
        }
        if (strtotime($params['start_time']) > 0) {
            //起始时间
            $condition['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0) {
            //结束时间
            $condition['end_time'] = $params['end_time'];
        }
        
        $data = $this->koubeiModel->getSubjectData($condition, $offset, $limit, $orderBy);
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $subjectIds = array();
        foreach ($data['list'] as $v) {
            $subjectIds[] = $v['subject_id'];
        }
        $subjectService = new SubjectService();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('user_info', 'item', 'album'), array())['data'];
        foreach ($data['list'] as $v) {
            $tmp = $v;
            $tmp['subject'] = $subjectInfos[$v['subject_id']];
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
    
    /**
     * ums口碑商家自主回复监控
     */
    public function getSupplierKoubeiMonitorList($params) {
        @ini_set('memory_limit', '512M');
        $result = array();
        $condition = array();
        //初始化入参
        $orderBy = 'supplier_id desc'; //默认排序
    
        //获取所有自主商家id
        $supplyInfo= $this->userModel->getAllKoubeiSupplier();
        if(empty($supplyInfo)){
            return $result;
        }
        
        $supplierIds = array_keys($supplyInfo);

        //起始时间
        $condition['start_time'] = $params['start_time'];
        //结束时间
        $condition['end_time'] = $params['end_time'];

        //如果无某商家查询，则默为所有自主商家
        if (intval($params['supplier_id']) > 0) {
            //商家ID
            $condition['supplier_id'] = $params['supplier_id'];
            $itemService = new \mia\miagroup\Service\Item();
            $supplier = $itemService->getMappingBySupplierId($params['supplier_id'])['data'];
            if($supplier['status'] == 1 && !empty($supplier['create_time'])) {
                if(!empty($condition['end_time']) && ($supplier['create_time'] > $condition['end_time'])) {
                    return $this->succ($result);
                }
                if(!empty($condition['start_time']) && $supplier['create_time'] > $condition['start_time']) {
                    $condition['start_time'] = $supplier['create_time'];
                }
            }
            if(empty($condition['start_time']) && !empty($supplier['create_time'])) {
                $condition['start_time'] = $supplier['create_time'];
            }
        }else{
            //默认为所有的自主商家id
            $condition['supplier_id'] = $supplierIds;
        }
    
        //查询商家的口碑信息，用来过滤掉无口碑的商家id ######start
        $data = $this->koubeiModel->getSupplierKoubeiData($condition, $orderBy);
        if (empty($data)) {
            return $this->succ($result);
        }
        $supplyIds = array();
        $koubeiArr = array();
        foreach ($data as $v) {
            $koubeiArr[$v['supplier_id']][] = $v['id'];
        }
    
        //最终统计的是有口碑的自主商家的数据
        $supplyIds = array_keys($koubeiArr);
        $condition['supplier_id'] = $supplyIds;
        #####end
        
        //根据口碑id获取各商家生成口碑数、差评数、机选差评数、被评论过的口碑数及评论比例
        $koubeiStatistics = $this->koubeiModel->supplierKoubeiStatistics($condition);
        //根据商家id获取仓库名
        $wearhouseInfo = $this->stockModel->getBatchNameBySupplyIds($supplyIds);
        //拼接商家各计数及商家名
        foreach ($supplyIds as $supplyId) {
            $supplierKoubei = array();
            $dealLowscoreNums = $koubeiStatistics['deal_lowscore'][$supplyId] ? $koubeiStatistics['deal_lowscore'][$supplyId] : 0;
            $dealMscoreNums = $koubeiStatistics['deal_mscore'][$supplyId] ? $koubeiStatistics['deal_mscore'][$supplyId] : 0;
            $supplierKoubei['supplier_id'] = $supplyId;
            $supplierKoubei['wearhouse_name'] = trim($wearhouseInfo[$supplyId]['name']);
            $supplierKoubei['koubei_nums'] = $koubeiStatistics['koubei'][$supplyId] ? $koubeiStatistics['koubei'][$supplyId] : 0;
            $supplierKoubei['lscore_nums'] = $koubeiStatistics['lowscore'][$supplyId] ? $koubeiStatistics['lowscore'][$supplyId] : 0;
            $supplierKoubei['mscore_nums'] = $koubeiStatistics['mscore'][$supplyId] ? $koubeiStatistics['mscore'][$supplyId] : 0;
            //处理数（回复差评数+回复机选差评数）
            $supplierKoubei['deal_nums'] = $dealLowscoreNums + $dealMscoreNums;
            //总差评数（差评数+机选差评数）
            $badFeedback = $supplierKoubei['lscore_nums'] + $supplierKoubei['mscore_nums'];
            //分母不能为0，所以需要判断总差评是否为0，如果总差评数为0，则处理数，默认为0
            if($badFeedback > 0){
                //处理数为处理差评数/总差评数
                $dealRate = $supplierKoubei['deal_nums'] /$badFeedback > 0  ? $supplierKoubei['deal_nums'] /$badFeedback : 0;
            }else{
                //如果没有总差评数，则处理差评数默认为0
                $dealRate = 0;
            }
            
            $supplierKoubei['deal_rate'] = (round($dealRate, 2) * 100)."%";
            $supplierKoubei['appeal_nums'] = $koubeiStatistics['appeal'][$supplyId] ? $koubeiStatistics['appeal'][$supplyId] : 0;
            $supplierKoubei['pass_nums'] = $koubeiStatistics['pass'][$supplyId] ? $koubeiStatistics['pass'][$supplyId] : 0;
            $supplierKoubei['reject_nums'] = $koubeiStatistics['reject'][$supplyId] ? $koubeiStatistics['reject'][$supplyId] : 0;
            $result[] = $supplierKoubei;
        }
        return $this->succ($result);
    }

    /*
    * UMS 商品各分值统计
    * */
    public function getItemStatisticsScore($item_ids)
    {
        $return = array('koubei_scores' => array());
        if(empty($item_ids) || !is_array($item_ids)) {
            return $this->succ($return);
        }
        $return_score = [];
        $conditions['status'] = 2;
        $conditions['auto_evaluate'] = 1;
        $config = \F_Ice::$ins->workApp->config->get('busconf.koubei.koubei_score');
        // 默认好评
        $default = $this->koubeiModel->itemKoubeiStatistics($item_ids, $conditions);
        foreach($default as $default_score) {
            $item_id = $default_score['item_id'];
            $count = $default_score['count'];
            $return_score[$item_id]['default'] = $count;
        }
        $conditions['subject_id'] = 0;
        $conditions['auto_evaluate'] = 0;

        foreach($config as $conf_score) {
            $conditions['score'] = $conf_score;
            $res = $this->koubeiModel->itemKoubeiStatistics($item_ids, $conditions);
            if(empty($res)) {
                continue;
            }
            foreach($res as $info) {
                $item_id = $info['item_id'];
                $count = $info['count'];
                $score = $info['score'];
                switch ($score) {
                    case 1 :
                        $return_score[$item_id]['one'] = $count;
                        break;
                    case 2 :
                        $return_score[$item_id]['two'] = $count;
                        break;
                    case 3 :
                        $return_score[$item_id]['three'] = $count;
                        break;
                    case 4 :
                        $return_score[$item_id]['four'] = $count;
                        break;
                    default:
                        $return_score[$item_id]['five'] = $count;
                        break;
                }
            }
        }
        $return['koubei_scores'] = $return_score;
        return $this->succ($return);
    }

    
    /**
     * 根据商家id获取仓库信息
     */
    public function getBatchWarehouse($supplyIds){
        if(empty($supplyIds)) {
            return $this->succ(array());
        }
        $wearhouseInfo = $this->stockModel->getBatchNameBySupplyIds($supplyIds);
        return $this->succ($wearhouseInfo);
    }

    /*
     * 批量迁移口碑
     * */
    public function batchKoubeiTransfer($params)
    {
        $return = ['status' => false];
        if (empty($params)) {
            return $this->succ($return);
        }

        $item = new ItemService();

        foreach ($params as $ori => $new) {
            if (empty($ori) || empty($new) || !is_numeric($ori) || !is_numeric($new)) {
                continue;
            }
            $conditions = [];
            $updateItemData['item_id'] = $new;
            $tableRelation = \F_Ice::$ins->workApp->config->get('busconf.koubei');
            $tableParam = $tableRelation['koubei_transf_relation'];
            foreach($tableParam as $table) {
                $conditions['table'] = $table;
                if($table === 'tag') {
                    $conditions['type'] = 'sku';
                }
                // 更新口碑表，帖子标签关联表，口碑印象关联表
                $this->koubeiModel->updateKoubeiTransfer($updateItemData, $ori, $conditions);
            }
            // 更新商品好评率
            $updateData[':literal'] = 'feedback_rate = null';
            $item->updateItem($ori, $updateData);
        }
        $return['status'] = true;
        return $this->succ($return);
    }
}