<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Koubei as KoubeiModel;
use mia\miagroup\Model\Ums\User as UserModel;
use mia\miagroup\Model\Ums\Item as ItemModel;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Service\Koubei as KoubeiService;
use mia\miagroup\Util\EmojiUtil;

class Koubei extends \mia\miagroup\Lib\Service {
    
    private $koubeiModel;
    private $userModel;
    private $itemModel;
    
    public function __construct() {
        $this->koubeiModel = new KoubeiModel();
        $this->userModel = new UserModel();
        $this->emojiUtil = new EmojiUtil();
        $this->itemModel = new ItemModel();
    }
    
    /**
     * ums口碑列表
     */
    public function getKoubeiList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'id desc'; //默认排序
        $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 50;
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
        
        if (intval($params['id']) > 0) {
            //口碑ID
            $condition['id'] = $params['id'];
        }
        if (intval($params['user_id']) > 0 && intval($condition['id']) <= 0) {
            //用户id
            $condition['user_id'] = $params['user_id'];
        }
        if (!empty($params['user_name']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            //用户名
            $condition['user_id'] = intval($this->userModel->getUidByUserName($params['user_name']));
        }
        if (!empty($params['nick_name']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            //用户昵称
            $condition['user_id'] = intval($this->userModel->getUidByNickName($params['nick_name']));
        }
        if ($params['status'] !== null && $params['status'] !== '' && in_array($params['status'], array(0, 1, 2)) && intval($condition['id']) <= 0) {
            //口碑状态
            $condition['status'] = $params['status'];
        }
        if ($params['rank'] !== null && $params['rank'] !== '' && in_array($params['rank'], array(0, 1)) && intval($condition['id']) <= 0) {
            //是否是精品
            $condition['rank'] = $params['rank'];
        }
        if ($params['score'] !== null && $params['score'] !== '' && in_array($params['score'], array(0, 1, 2, 3, 4, 5)) && intval($condition['id']) <= 0) {
            //用户评分
            $condition['score'] = $params['score'];
        }
        if ($params['score'] == '机选差评' && intval($condition['id']) <= 0) {
            //机器评分
            $condition['machine_score'] = 1;
        }
        if (intval($params['item_id']) > 0 && intval($condition['id']) <= 0) {
            //商品ID
            $condition['item_id'] = $params['item_id'];
            $orderBy = 'rank_score desc'; //按分数排序，与app保持一致
        }
        if (!empty($params['brand']) && intval($condition['item_id']) <= 0 && intval($condition['id']) <= 0) {
            //品牌ID
            $brandId = intval($this->itemModel->getBrandIdByName($params['brand']));
            $itemIds = $this->itemModel->getAllItemByBrandId($brandId);
            if (!empty($itemIds)) {
                $condition['item_id'] = $itemIds;
            }
        }

        if (intval($params['supplier_id']) > 0 && intval($condition['item_id']) <= 0 && intval($condition['id']) <= 0) {
            //供应商ID
            $itemIds = intval($this->itemModel->getAllItemBySupplyId($params['supplier_id']));
            if (!empty($itemIds)) {
                $condition['item_id'] = $itemIds;
            }
        }
        
        if (intval($params['cate_1']) > 0 && intval($params['category_id']) > 0 && empty($params['brand']) && intval($condition['item_id']) <= 0 && intval($condition['id']) <= 0) {
            //类目ID
            $itemIds = $this->itemModel->getAllItemByCategoryId($params['category_id']);
            if (!empty($itemIds)) {
                $condition['item_id'] = $itemIds;
            }
        }
        if (strtotime($params['start_time']) > 0 && intval($condition['id']) <= 0) {
            //起始时间
            $condition['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0 && intval($condition['id']) <= 0) {
            //结束时间
            $condition['end_time'] = $params['end_time'];
        }
        $data = $this->koubeiModel->getKoubeiData($condition, $offset, $limit, $orderBy);
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $koubeiIds = array();
        foreach ($data['list'] as $v) {
            $koubeiIds[] = $v['id'];
        }
        $koubeiService = new KoubeiService();
        $koubeiInfos = $koubeiService->getBatchKoubeiByIds($koubeiIds)['data'];
        foreach ($data['list'] as $v) {
            $tmp = $v;
            $tmp['subject'] = $koubeiInfos[$v['id']];
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
        $limit = (intval($params['limit'] > 0) && intval($params['limit'] < 100)) ? $params['limit'] : 50;
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
}