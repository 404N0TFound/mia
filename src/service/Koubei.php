<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Koubei as KoubeiModel;
use mia\miagroup\Service\Item as ItemService;
use mia\miagroup\Service\Order as OrderService;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Util\EmojiUtil;
use mia\miagroup\Remote\Solr as SolrRemote;
use mia\miagroup\Remote\Coupon as CouponRemote;

class Koubei extends \mia\miagroup\Lib\Service {

    public $koubeiModel;
    public $subjectService;
    public $koubeiConfig;

    public function __construct() {
        $this->koubeiModel = new KoubeiModel();
        $this->subjectService = new SubjectService();
        $this->emojiUtil = new EmojiUtil();
        $this->koubeiConfig = \F_Ice::$ins->workApp->config->get('batchdiff.koubeibatch');
    }

    /**
     * 发布口碑
     * @param $koubeiData array() 口碑发布信息
     * @param $checkOrder 是否验证订单
     */
    public function createKoubei($koubeiData,$checkOrder=true){
        //判断是否需要验证订单，该判断是因为后台发布口碑时不需要订单号
        if($checkOrder === true){
            //获取订单信息，验证是否可以发布口碑（order service）
            $orderService = new OrderService();
            $orderInfo = $orderService->getOrderInfoByOrderCode(array($koubeiData['order_code']))['data'][$koubeiData['order_code']];
            $orderId = $orderInfo['id'];
            $finishTime = strtotime($orderInfo['finish_time']) ;
            if($orderInfo['status'] != 5  || (time()- $finishTime) > 15 * 86400 )
            {
                return $this->error(6102);
            }
            //获取口碑信息，验证是否该口碑已经发布过
            $koubeiInfo = $this->koubeiModel->getItemKoubeiInfo($orderId[0],$koubeiData['item_id']);
            if(!empty($koubeiInfo)){
                return $this->error(6103);
            }
        }

        //保存口碑
        //组装插入口碑信息  ####start
        $koubeiSetData = array();
        $koubeiSetData['status'] = (isset($koubeiData['status'])) ? intval($koubeiData['status']) : 2;
        $koubeiSetData['title'] = (isset($koubeiData['title']) ) ? intval($koubeiData['title']) : "";
        $koubeiSetData['content'] = trim($this->emojiUtil->emoji_unified_to_html($koubeiData['text']));
        $koubeiSetData['score'] = $koubeiData['score'];
        $koubeiSetData['item_id'] = (isset($koubeiData['item_id']) && intval($koubeiData['item_id']) > 0) ? intval($koubeiData['item_id']) : 0;
        $koubeiSetData['item_size'] = $koubeiData['item_size'];
        $koubeiSetData['user_id'] = $koubeiData['user_id'];
        $koubeiSetData['order_id'] = isset($orderInfo['id']) ? $orderInfo['id'] : 0;
        $koubeiSetData['created_time'] = date("Y-m-d H:i:s");
        $koubeiSetData['immutable_score'] = $this->calImmutableScore($koubeiSetData);
        $koubeiSetData['rank_score'] = $koubeiSetData['immutable_score'] + 12 * 0.5;
        //供应商ID获取
        $itemService = new ItemService();
        $itemInfo = $itemService->getItemList(array($koubeiSetData['item_id']))['data'][$koubeiSetData['item_id']];
        $koubeiSetData['supplier_id'] = intval($itemInfo['supplier_id']);
        $labels = array();
        $labels['label'] = array();
        $labels['image'] = array();
        if(!empty($koubeiData['labels']))
        {
            foreach($koubeiData['labels'] as $label)
            {
                $labels['label'][] = $label['title'];
            }
        }
        if(!empty($koubeiData['image_infos']))
        {
            foreach($koubeiData['image_infos'] as $image)
            {
                $labels['image'][] = $image;
            }
        }
        $koubeiSetData['extr_info'] = json_encode($labels);
        //####end
        $koubeiInsertId = $this->koubeiModel->saveKoubei($koubeiSetData);

        if(!$koubeiInsertId)
        {
            return $this->error(6101);
        }

        //发蜜豆
        $mibean = new \mia\miagroup\Remote\MiBean();
        $param['user_id'] = 3782852;//蜜芽兔
        $param['to_user_id'] = $koubeiData['user_id'];
        $param['relation_type'] = "send_koubei";
        $param['relation_id'] = $koubeiInsertId;

        //首评奖励(绑定代金券)
        if((mb_strlen($koubeiSetData['content']) > 20) && !empty($koubeiData['image_infos'])){
            $couponRemote = new CouponRemote();
            $batch_code = $this->koubeiConfig['batch_code']['test'];
            if(!empty($batch_code)){
                $bindCouponRes = $couponRemote->bindCouponByBatchCode($koubeiSetData['user_id'], $batch_code);
                if(is_array($bindCouponRes)){
                    $this->error(500);
                }
            }
        }

        //保存口碑相关图片信息
        if(!empty($koubeiData['image_infos'])){
            foreach ($koubeiData['image_infos'] as $path) {
                $koubeiPicData = array();
                if (!empty($path)) {
                    $ss = explode( ".", $path['url']);
                    $koubeiPicData = array(
                        "koubei_id"	=> $koubeiInsertId,
                        "local_url"	=> $path['url'],
                        "local_url_origin"=> $path['url'],
                        "local_url_big"=> $ss[0]."_big.".$ss[1]);
                    $this->koubeiModel->saveKoubeiPic($koubeiPicData);
                }
            }
            // 发布商品口碑+商品图片，获得10个蜜豆奖励
            $param['mibean'] = 10;
            $mibean->add($param);
        }else{
            //发布商品口碑，获得5个蜜豆奖励
            $param['mibean'] = 5;
            $mibean->add($param);
        }

        //发口碑同时发布蜜芽圈帖子
        //#############start
        $subjectInfo = array();
        $subjectInfo['user_info']['user_id'] = $koubeiSetData['user_id'];
        $subjectInfo['title'] = $koubeiSetData['title'];
        $subjectInfo['text'] = $koubeiSetData['content'];
        $subjectInfo['created'] = $koubeiSetData['created_time'];
        $subjectInfo['extr_info'] = $labels;
        $subjectInfo['source'] = \F_Ice::$ins->workApp->config->get('busconf.subject.source.koubei'); //帖子数据来自口碑标识
        $imageInfos = array();
        $i=0;
        if(!empty($koubeiData['image_infos'])) {
            foreach($koubeiData['image_infos'] as $image){

                $imageInfos[$i]['url'] = $image['url'];
                $size= getimagesize("http://img.miyabaobei.com/".$image['url']);
                $imageInfos[$i]['width'] = $size[0];
                $imageInfos[$i]['height'] = $size[1];
                $i++;
            }

        }
        $subjectInfo['image_infos'] = $imageInfos;
        $labelInfos = array();

        if(!empty($labels['label']))
        {
            $labels = $labels['label'];
            foreach($labels as $label)
            {
                $labelInfos[] = array('title' => $label);
            }
        }

        $pointInfo[0] = array( 'item_id' => $koubeiSetData['item_id']);

        $subjectIssue = $this->subjectService->issue($subjectInfo,$pointInfo,$labelInfos,$koubeiInsertId)['data'];
        //#############end
        //将帖子id回写到口碑表中
        if(!empty($subjectIssue) && $subjectIssue['id'] > 0){
            $this->koubeiModel->addSubjectIdToKoubei($koubeiInsertId,$subjectIssue['id']);
        }

        return $this->succ($koubeiInsertId);
    }

    public function getItemKoubeiTagList($itemId, $tag_id, $page = 1, $count = 20, $userId = 0)
    {
        $koubei_res = array("koubei_info" => array());
        if(empty($itemId) || empty($tag_id)){
            return $this->succ($koubei_res);
        }
        $koubei_res = $this->getTagsKoubeiList($itemId, $tag_id, $page, $count, $userId);
        if($page == 1){
            $koubei_res['tag_list'] = $this->getItemTagList($itemId, $field = ["normal", "collect"])['data'];
        }
        return $this->succ($koubei_res);
    }

    /**
     * 获取口碑列表
     */
    public function getItemKoubeiList($itemId, $page=1, $count=20, $userId = 0)
    {
        $koubei_res = array("koubei_info" => array());
        //获取商品的关联商品或者套装单品
        $item_service = new ItemService();
        $item_ids = $item_service->getRelateItemById($itemId);
        if (empty($item_ids)) {
            return $this->succ($koubei_res);
        }
        //获取口碑数量
        $koubei_nums = $this->koubeiModel->getItemKoubeiNums($item_ids);
        if($koubei_nums <=0){
            return $this->succ($koubei_res);
        }
        $koubei_res['total_count'] = $koubei_nums;//口碑数量
        $item_info = $item_service->getBatchItemBrandByIds([$itemId]);

        //好评率
        $feedbackRate = intval($item_info['data'][$itemId]['feedback_rate']);
        if(!empty($feedbackRate)){
            $koubei_res['feedback_rate'] = $feedbackRate."%";
        }

        //获取用户评分
        $item_score = $this->koubeiModel->getItemUserScore($item_ids);
        //获取蜜粉推荐
        $item_rec_nums = $this->koubeiModel->getItemRecNums($item_ids);

        //通过商品id获取口碑id
        $offset = $page > 1 ? ($page - 1) * $count : 0;
        $koubei_ids = $this->koubeiModel->getKoubeiIdsByItemIds($item_ids, $count, $offset);
        //获取口碑信息
        $koubei_infos = $this->getBatchKoubeiByIds($koubei_ids, $userId)['data'];
        $koubei_res['koubei_info'] = !empty($koubei_infos) ? array_values($koubei_infos) : array();

        //如果综合评分和蜜粉推荐都为0，且当页无口碑，则返回空数组，如果当页有口碑，则返回口碑记录
        //（适用情况，该商品及关联商品无口碑贴，全为蜜芽贴）
        if($item_score > 0 && $item_rec_nums == 0){
            $koubei_res['total_score'] = $item_score;//综合评分
        } else if ($item_score > 0 && $item_rec_nums > 0) {
            $koubei_res['total_score'] = $item_score;//综合评分
            $koubei_res['recom_count'] = $item_rec_nums;//蜜粉推荐
        }
        if($page == 1){
            $koubei_res['tag_list'] = $this->getItemTagList($itemId, $field = ["normal", "collect"])['data'];
        }
        return $this->succ($koubei_res);
    }
    
    /**
     * 获取优质口碑
     */
    public function getHighQualityKoubei($item_id, $current_uid = 0, $count = 8) {
        $koubei_res = array("koubei_info" => array());
        //获取商品的关联商品或者套装单品
        $item_service = new ItemService();
        $item_ids = $item_service->getRelateItemById($item_id);
        if (empty($item_ids)) {
            return $this->succ($koubei_res);
        }
        //获取口碑数量
        $koubei_nums = $this->koubeiModel->getItemKoubeiNums($item_ids);
        if($koubei_nums <=0){
            return $this->succ($koubei_res);
        }
        $koubei_res['total_count'] = $koubei_nums;//口碑数量
        //获取用户评分
        $item_score = $this->koubeiModel->getItemUserScore($item_ids);
        //获取蜜粉推荐
        $item_rec_nums = $this->koubeiModel->getItemRecNums($item_ids);

        $item_info = $item_service->getBatchItemBrandByIds([$item_id]);

        //好评率
        $feedbackRate = intval($item_info['data'][$item_id]['feedback_rate']);
        if(!empty($feedbackRate)){
            $koubei_res['feedback_rate'] = $feedbackRate."%";//好评率
        }

        //通过商品id获取口碑id
        $condition = array();
        $condition['with_pic'] = true;
        $condition['score'] = array(4, 5);
        $condition['machine_score'] = 3;
        $koubei_ids = $this->koubeiModel->getKoubeiByItemIdsAndCondition($item_ids, $condition, $count);
        if (count($koubei_ids) < $count) {
            $count = $count - count($koubei_ids);
            $condition['with_pic'] = false;
            $koubei_ids = array_merge($koubei_ids, $this->koubeiModel->getKoubeiByItemIdsAndCondition($item_ids, $condition, $count));
        }
        //获取口碑信息
        $koubei_infos = $this->getBatchKoubeiByIds($koubei_ids, $current_uid)['data'];
        $koubei_res['koubei_info'] = !empty($koubei_infos) ? array_values($koubei_infos) : array();

        //如果综合评分和蜜粉推荐都为0，且当页无口碑，则返回空数组，如果当页有口碑，则返回口碑记录
        //（适用情况，该商品及关联商品无口碑贴，全为蜜芽贴）
        if($item_score > 0 && $item_rec_nums == 0){
            $koubei_res['total_score'] = $item_score;//综合评分
        } else if ($item_score > 0 && $item_rec_nums > 0) {
            $koubei_res['total_score'] = $item_score;//综合评分
            $koubei_res['recom_count'] = $item_rec_nums;//蜜粉推荐
        }
        return $this->succ($koubei_res);
    }
    
    /**
     * 根据口碑ID获取口碑信息
     */
    public function getBatchKoubeiByIds($koubeiIds, $userId = 0, $field = array('user_info', 'count', 'comment', 'group_labels', 'praise_info', 'item' ,'order_info'), $status = array(2)) {
        if (empty($koubeiIds)) {
            return array();
        }
        $koubeiInfo = array();
        $orderIds = array();
        //批量获取口碑信息
        $koubeiArr = $this->koubeiModel->getBatchKoubeiByIds($koubeiIds,$status);
        foreach($koubeiArr as $koubei){
            if(empty($koubei['subject_id'])) continue;
            //收集subjectids
            $subjectId[] = $koubei['subject_id'];
            
            $itemKoubei[$koubei['subject_id']] = array(
                'id' => $koubei['id'],
                'rank' => $koubei['rank'],
                'score' => $koubei['score'],
                'item_id' => $koubei['item_id'],
                'item_size' => $koubei['item_size'],
                'status' => $koubei['status'],
                'order_id' => $koubei['order_id'],
            );
           // 获取口碑订单id，用于获取订单编号(order_code)
            if(!empty($koubei['order_id'])){
                $orderIds[] = $koubei['order_id'];
            }
            
        }
        
        if(in_array('order_info', $field) || !empty($orderIds)){
            //获取订单信息，验证是否可以发布口碑（order service）
            $orderService = new OrderService();
            $orderInfos = $orderService->getOrderInfoByIds($orderIds)['data'];
        }
        
        //3、根据口碑中帖子id批量获取帖子信息（subject service）
        $subjectRes = $this->subjectService->getBatchSubjectInfos($subjectId, $userId , $field);
        foreach ($itemKoubei as $key => $value) {
            if (!empty($subjectRes['data'][$key])) {
                foreach ($subjectRes['data'][$key]['items'] as $item) {
                    if ($item['item_id'] == $value['item_id']) {
                        $value['item_info'] = $item;
                        break;
                    }
                }
                $subjectRes['data'][$key]['item_koubei'] = $value;
                //把口碑的订单编号（order_code）拼到口碑信息中
                if($value['order_id']){
                    $subjectRes['data'][$key]['item_koubei']['order_code'] = $orderInfos[$value['order_id']]['order_code'];
                }
                // 口碑信息拼装到帖子
                $koubeiInfo[$value['id']] = $subjectRes['data'][$key];
            }
        }
        return $this->succ($koubeiInfo);
    }

    /**
     * 蜜芽贴同步到口碑
     * @param $subjectData array() 蜜芽贴信息
     */
    public function setSubjectToKoubei($subjectId, $itemId) {
        //判断该蜜芽贴是否同步到口碑贴
        $koubeiInfo = $this->koubeiModel->getKoubeiBySubjectId($subjectId);
        if (!empty($koubeiInfo)) {
            //如果同步过，则直接返回同步过的口碑id
            return $this->succ($koubeiInfo['id']);
        }
        $subjectService = new SubjectService();
        $subjectData = $subjectService->getSingleSubjectById($subjectId, 0 , array('group_labels', 'album'))['data'];
        if (!empty($subjectData['album_article']) || !empty($subjectData['video_info'])) {
            //如果是专栏或者视频贴
            return $this->error(500, '该类型贴不能同步到口碑');
        }
        //如果没有同步过，则同步为口碑贴
        $koubeiSetData = array();
        $koubeiSetData['status'] = 2;
        $koubeiSetData['title'] = (isset($subjectData['title'])) ? trim($subjectData['title']) : "";
        $koubeiSetData['content'] = trim($this->emojiUtil->emoji_unified_to_html($subjectData['text']));
        $koubeiSetData['item_id'] = $itemId;
        $koubeiSetData['user_id'] = $subjectData['user_id'];
        $koubeiSetData['subject_id'] = (isset($subjectData['id'])) ? trim($subjectData['id']) : '';
        $koubeiSetData['created_time'] = date('Y-m-d H:i:s', time());
        $koubeiSetData['immutable_score'] = $this->calImmutableScore($subjectData);
        $koubeiSetData['rank_score'] = $koubeiSetData['immutable_score'] + 12 * 0.5;
        //供应商ID获取
        $itemService = new ItemService();
        $itemInfo = $itemService->getItemList(array($koubeiSetData['item_id']))['data'][$koubeiSetData['item_id']];
        $koubeiSetData['supplier_id'] = intval($itemInfo['supplier_id']);
        
        $extInfo = array();
        $extInfo['label'] = array();
        $extInfo['image'] = array();
        if (!empty($subjectData['group_labels'])) {
            foreach ($subjectData['group_labels'] as $label) {
                $extInfo['label'][] = $label['title'];
            }
        }
        if (!empty($subjectData['image_infos'])) {
            foreach ($subjectData['image_infos'] as $image) {
                $imageInfo = array();
                $url = parse_url($image['url']);
                $imageInfo['url'] = ltrim($url['path'], '/');
                $imageInfo['width'] = $image['width'];
                $imageInfo['height'] = $image['height'];

                $extInfo['image'][] = $imageInfo;
            }
        }
        $koubeiSetData['extr_info'] = json_encode($extInfo);
        $mKoubei = new KoubeiModel();
        $koubeiInsertId = $mKoubei->saveKoubei($koubeiSetData);

        if (!$koubeiInsertId) {
            return $this->error(6101);
        }

        // 3、如果蜜芽贴图片不为空，则同步到口碑图片中
        if (!empty($subjectData['image_infos'])) {
            foreach ($subjectData['image_infos'] as $path) {
                $koubeiPicData = array();
                if (!empty($path)) {
                    $url = parse_url($path['url']);
                    $url = ltrim($url['path'], '/');
                    $ss = explode(".", $url);
                    $koubeiPicData = array("koubei_id" => $koubeiInsertId, "local_url" => $url, "local_url_origin" => $url, "local_url_big" => $ss[0] . "_big." . $ss[1]);
                    $mKoubei->saveKoubeiPic($koubeiPicData);
                }
            }
        }

        //口碑ID回写到蜜芽帖
        if ($koubeiInsertId > 0 && $subjectData['id'] > 0) {
            $subjectService = new SubjectService();
            $subjectInfo['ext_info']['koubei']['id'] = $koubeiInsertId;
            $subjectService->updateSubject($subjectData['id'], $subjectInfo);
        }

        //更新口碑蜜芽贴中的通过状态为通过
        $this->koubeiModel->updateKoubeiSubjectStatus($subjectId, 1);

        return $this->succ($koubeiInsertId);
    }

    /**
     * 新增口碑待审核记录
     */
    public function addKoubeiSubject($data) {
        $result = $this->koubeiModel->addKoubeiSubject($data);
        return $this->succ($result);
    }

    //获取口碑的不变分数
    private function calImmutableScore($data)
    {
        //初始分，时间月份 * 权重
        $immutable_score = 0;

        //图片分，有图10分，权重0.3
        $hasPic = 0;
        if(!empty($data['image_infos'])) {
            $hasPic = 1;
        }
        $immutable_score += (0.3 * 10 * $hasPic);

        //文本长度分，100字以上10分，50字以上8分，30字以上5分，10字以上3分，10字以下1分，权重0.2
        $content_count = mb_strlen($data['text'],'utf-8');
        if($content_count > 100) {
            $immutable_score += (10 * 0.2);
        }
        else if($content_count > 50) {
            $immutable_score += (8 * 0.2);
        }
        else if($content_count > 30) {
            $immutable_score += (5 * 0.2);
        }
        else if($content_count > 10) {
            $immutable_score += (3 * 0.2);
        }
        
        //口碑评分，权重1.5
        $immutable_score += (intval($data['score']) * 1.5);
        
        //蜜芽圈同步8分，权重1
        $immutable_score += (($data['source'] == 1 ? 8 : 0) * 1);
        
        return $immutable_score;
    }

    /**
     * 删除口碑
     */
    public function delete($id, $userId){
        //删除对应口碑
        $res = $this->koubeiModel->delete($id, $userId);
        return $this->succ($res);
    }

    //查出某用户的所有口碑帖子
    public function getKoubeis($userId){
        if(!is_numeric($userId) || intval($userId) <= 0){
            return $this->error(500);
        }
        $arrKoubeis = $this->koubeiModel->getKoubeisByUid($userId);
        return $this->succ($arrKoubeis);
    }

    /**
     * 批量删除口碑
     */
    public function deleteKoubeis($koubeiIds){
        //删除对应口碑
        $res = $this->koubeiModel->deleteKoubeis($koubeiIds);
        return $this->succ($res);
    }

    /**
     * 口碑加精
     */
    public function setKoubeiRank($koubeiIds, $rank)
    {
        if (empty($koubeiIds) || !in_array($rank, array(0, 1))) {
            return $this->error(500);
        }
        if (is_string($koubeiIds)) {
            $koubeiIds = array($koubeiIds);
        }
        $res = $this->koubeiModel->setKoubeiRank($koubeiIds, $rank);
        return $this->succ($res);
    }

    /**
     * 修改口碑审核通过状态
     */
    public function setKoubeiStatus($koubeiId, $status)
    {
        if(!is_numeric($koubeiId) || intval($koubeiId) <= 0){
            return $this->error(500);
        }
        //更新口碑状态
        $koubeUpData = array('status'=>$status);
        $res = $this->koubeiModel->setKoubeiStatus($koubeiId, $koubeUpData);
        //如果是修改为不通过，不需要同步蜜芽圈
        if($status== 0){
            return $this->succ($res);
        }
        //查出口碑信息，用户提供同步数据
        $koubeInfo = $this->koubeiModel->getBatchKoubeiByIds(array($koubeiId), array(2))[$koubeiId];
        if($koubeInfo['subject_id'] > 0){
            //如果同步过，直接返回通过状态
            return $this->succ($res);
        }
        if(!empty($koubeInfo['extr_info'])){
            $extInfo = json_decode($koubeInfo['extr_info'],true);
            $imageInfos = $extInfo['image'];
            $labels = $extInfo['label'];
        }
        //同步蜜芽圈帖子
        //#############start
        $subjectInfo = array();
        $subjectInfo['user_info']['user_id'] = $koubeInfo['user_id'];
        $subjectInfo['title'] = $koubeInfo['title'];
        $subjectInfo['text'] = $koubeInfo['content'];
        $subjectInfo['created'] = $koubeInfo['created_time'];
        $subjectInfo['source'] = \F_Ice::$ins->workApp->config->get('busconf.subject.source.koubei'); //帖子数据来自口碑标识
        $subjectInfo['image_infos'] = isset($imageInfos) ? $imageInfos : array();
        $labelInfos = array();

        if(isset($labels) && !empty($labels))
        {
            foreach($labels as $label)
            {
                $labelInfos[] = array('title' => $label);
            }
        }
        $pointInfo[0] = array( 'item_id' => $koubeInfo['item_id']);
        $subjectIssue = $this->subjectService->issue($subjectInfo,$pointInfo,$labelInfos,$koubeiId)['data'];
        //#############end
        //将帖子id回写到口碑表中
        if(!empty($subjectIssue) && $subjectIssue['id'] > 0){
            $this->koubeiModel->addSubjectIdToKoubei($koubeiId,$subjectIssue['id']);
        }
        return $this->succ($res);
    }
    
    /**
     * 商家评论
     */
    public function koubeiComment($supplierId, $subjectId, $comment, $fid = 0) {
        if (empty($comment) || intval($subjectId) <= 0) {
            return $this->error(500);
        }
        $comment = trim($this->emojiUtil->emoji_unified_to_html($comment));
        if ($comment == "") {
            return $this->error(500);
        }
        //过滤敏感词
        $audit = new \mia\miagroup\Service\Audit();
        $sensitive_res = $audit->checkSensitiveWords($comment)['data'];
        if(!empty($sensitive_res['sensitive_words'])){
            return $this->error(1112, '有敏感内容 "' . implode('","', $sensitive_res['sensitive_words']) . '"，发布失败');
        }
        //判断用户是否是商家
        if ($supplierId > 0) {
            $itemService = new ItemService();
            $supplierUserRelation = $itemService->getMappingBySupplierId($supplierId)['data'];
            if (empty($supplierUserRelation) || $supplierUserRelation['status'] != 1) {
                return $this->error(6107);
            }
            $userId = $supplierUserRelation['user_id'];
        } else {
            //如果没有指定商家，默认蜜芽兔
            $userId = \F_Ice::$ins->workApp->config->get('busconf.user.miaTuUid');
        }
        //判断是否是口碑
        $subjectService = new SubjectService();
        $subjectInfoData = $subjectService->getBatchSubjectInfos(array($subjectId), 0, array())['data'];
        $subjectInfo = $subjectInfoData[$subjectId];
        if (intval($subjectInfo['koubei_id']) <=0) {
            return $this->error(6106);
        }
        $koubeiId = $subjectInfo['koubei_id'];
        //判断是否有父评论
        $commentService = new \mia\miagroup\Service\Comment();
        if (intval($fid) > 0) {
            $parentInfo = $commentService->getBatchComments([$fid])['data'][$fid];
            if(!empty($parentInfo)) {
                if($parentInfo['status'] != 1) {
                    //父ID 无效
                    return $this->error(1108);
                }
            }
        }
    
        // 评论信息入库
        $commentInfo['subject_id'] = $subjectId;
        $commentInfo['comment'] = $comment;
        $commentInfo['user_id'] = $userId;
        $commentInfo['fid'] = $fid;
        $commentInfo['is_expert'] = 1;
        $commentInfo['id'] = $commentService->addComment($commentInfo);
    
        //更新口碑表口碑回复状态
        $koubeiInfo['reply'] = $comment;
        $koubeiInfo['comment_status'] = 1;
        $koubeiInfo['comment_supplier_id'] = $supplierId;
        $koubeiInfo['comment_time'] = date('Y-m-d H:i:s');
        $this->koubeiModel->updateKoubeiReplyStatus($koubeiId, $koubeiInfo);
    
        // 给被评论的口碑发消息
        $newService = new \mia\miagroup\Service\News();
        if ($userId != $subjectInfo['user_id']) {
            $newService->addNews('single', 'group', 'img_comment', $userId, $subjectInfo['user_id'], $commentInfo['id']);
        }
        // 给口碑的评论回复发消息
        if ($parentInfo['comment_user'] && $parentInfo['comment_user']['user_id'] != $userId) {
            $newService->addNews('single', 'group', 'img_comment', $userId, $parentInfo['comment_user']['user_id'], $commentInfo['id'])['data'];
        }
    
        return $this->succ($commentInfo);
    }
    
    /**
     * 商家申诉口碑
     */
    public function supplierKoubeiAppeal($supplier_id, $koubei_id, $koubei_comment_id = 0, $appeal_reason = '', $supplier_name = '')
    {
        //检查是否是商家
        $itemService = new ItemService();
        $supplierUserRelation = $itemService->getMappingBySupplierId($supplier_id)['data'];
        if (empty($supplierUserRelation) || $supplierUserRelation['status'] != 1) {
            return $this->error(6107);
        }
        //检查是否是有效口碑
        $koubei_info = $this->koubeiModel->getBatchKoubeiByIds(array($koubei_id))[$koubei_id];
        if (empty($koubei_info)) {
            return $this->error(500);
        }
        //检查是否已申诉过
        $is_exist = $this->koubeiModel->checkAppealInfoExist($koubei_id, $koubei_comment_id);
        if (!empty($is_exist)) {
            return $this->error(6108);
        }
        
        //申诉信息记录
        $appeal_info['supplier_id'] = $supplier_id;
        $appeal_info['koubei_id'] = $koubei_id;
        $appeal_info['subject_id'] = $koubei_info['subject_id'];
        $appeal_info['appeal_time'] = date('Y-m-d H:i:s');
        if (intval($koubei_comment_id) > 0) {
            $appeal_info['koubei_comment_id'] = $koubei_comment_id;
        }
        if (!empty($appeal_reason)) {
            $appeal_info['appeal_reason'] = $appeal_reason;
        }
        if (!empty($supplier_name)) {
            $appeal_info['supplier_name'] = $supplier_name;
        }
        $appeal_info['id'] = $this->koubeiModel->addKoubeiAppeal($appeal_info);
        return $this->succ($appeal_info);;
    }
    
    /**
     * 处理口碑申诉
     */
    public function setKoubeiAppealStatus($appeal_id, $status, $refuse_reason = '', $operator_id = 0)
    {
        //检查是否是有效申诉
        $appeal_info = $this->koubeiModel->getAppealInfoByIds(array($appeal_id))[$appeal_id];
        if (empty($appeal_info)) {
            return $this->error(500);
        }
        switch ($status) {
            case 1:
                if (intval($appeal_info['koubei_comment_id']) > 0) {
                    //执行口碑评论屏蔽操作
                    $commentService = new \mia\miagroup\Service\Comment();
                    $commentService->delComments(array($appeal_info['koubei_comment_id']), -1, $refuse_reason);
                } else {
                    //执行口碑屏蔽操作
                    $subjectService = new \mia\miagroup\Service\Subject();
                    $subjectService->delSubjects(array($appeal_info['subject_id']), -1, $refuse_reason);
                }
                $this->koubeiModel->passKoubeiAppeal($appeal_id, $operator_id);
                break;
            case 2:
                $this->koubeiModel->refuseKoubeiAppeal($appeal_id, $refuse_reason, $operator_id);
                break;
        }
        return $this->succ(true);
    }

    /**
     * solr 分类检索口碑列表
     * @param $brand_id       品牌id          非必填
     * @param $category_id    分类id          非必填
     * @param $count          每页数量        必填
     * @param $page           当前页          必填
     * @ return array()
     */
    public function categorySearch($brand_id = 0, $category_id = 0, $count = 20, $page = 1,$userId = 0){

        $solr        = new SolrRemote('koubei');
        $koubei      = array();
        $brand_list  = array();
        if(!empty($category_id) && empty($brand_id)){
            // 类目下口碑去重分页列表
            $koubei_info = $solr->getHighQualityKoubeiByCategoryId($category_id, $page);
            $brand_list  = $solr->brandList($category_id);
        }else{
            // 品牌口碑去重分页列表
            $koubei_info = $solr->getHighQualityKoubeiByBrandId($category_id, $brand_id, $page);
        }
        if(!empty($koubei_info)){
            $koubei['count'] = $koubei_info['count'];
            $koubei['list']  = array_values($this->getBatchKoubeiByIds($koubei_info['list'], $userId)['data']);
        }

        /*$koubei_info = $solr->koubeiList($brand_id, $category_id, $count, $page);
        if(!empty($category_id)){
            $brand_list  = $solr->brandList($category_id);
        }
        if(!empty($koubei_info)){
            $totalCount  = $koubei_info['numFound'];
            $koubei_ids  = array_column($koubei_info['docs'],'id');
            $koubei['count'] = $totalCount;
            $koubei['list']  = array_values($this->getBatchKoubeiByIds($koubei_ids, $userId)['data']);

        }*/
        $res = array('koubei_list' => $koubei, 'brand_list' => $brand_list);
        return $this->succ($res);
    }


    /**
     * solr 分类检索口碑列表
     * @param $order_code     订单编号
     * @param $item_id        商品SKU
     * @ return issue_reward  口碑发布奖励
     * @ return issue_tip_url 口碑发布图片提升
     */
    public function issueinit($order_code = 0, $item_id = 0){
        //$order_code = 1;
        //$item_id = 1005598;
        // 验证是否为首评
        if(empty($item_id)){
            return $this->error(500);
        }
        $check_res = $this->koubeiModel->getCheckFirstComment($order_code, $item_id);
        if(empty($check_res)){
            $batch_info = $this->koubeiConfig['shouping'];
            $shouping_Info = $this->koubeiModel->getBatchKoubeiByDefaultInfo($batch_info);
            return $this->succ($shouping_Info);
        }
    }

    /**
     * 商品最优口碑
     * @param item_id   商品SKU
     * @ return | array(group_subject)
     */
    public function itemBatchBestKoubei($itemIds = array()){
        // 获取口碑最优id
        //$itemIds = array('1005598','1003113');
        if(empty($itemIds)){
            return $this->succ(array());
        }
        $transfer_koubei = array();
        $item_service = new ItemService();
        //通过商品id获取口碑id
        foreach ($itemIds as $value) {
            $item_ids = $item_service->getRelateItemById($value);
            if(!empty($item_ids)){
                $koubei_ids = $this->koubeiModel->getKoubeiIdsByItemIds($item_ids, 20, 0);
                $res = $this->getBatchKoubeiByIds(array($koubei_ids[0]));
                foreach($res['data'] as $v){
                    $transfer_koubei[$value] = $v;
                }

           }
        }
        return $this->succ($transfer_koubei);
    }
    
    /**
     * 将工单号更新到口碑表
     * @param int $koubeiId
     * @param int $workOrder
     */
    public function setWorkorderToKoubei($koubeiId,$workOrder) {
        if (empty($koubeiId) || empty($workOrder)) {
            return $this->error(500);
        }
        $koubeUpData = array('work_order'=>$workOrder);
        $res = $this->koubeiModel->setKoubeiStatus($koubeiId,$koubeUpData);
        return $this->succ($res);
    }


    /**
     * 导入口碑印象标签信息
     * @param $tagName
     */
    public function syncTags($tagName, $positive)
    {
        if (empty($tagName) || empty($positive)) {
            return $this->error(500);
        }
        //检查标签是否存在
        $tagInfo = $this->koubeiModel->getTagInfo($tagName);

        if(!empty($tagInfo)){
            return $this->error(500,"标签存在");
        }
        //标签信息入库
        $insertData['tag_name'] = $tagName;
        $insertData['parent_id'] = 0;
        $insertData['positive'] = $positive;

        $tagId = $this->koubeiModel->addTag($insertData);

        if (!$tagId) {
            return $this->error(500,"添加失败");
        } else {
            return $this->succ($tagId);
        }
    }


    /**
     * 导入印象标签父子关系
     * @param $tagName
     * @param $parentName
     */
    public function syncTagsRelation($tagName, $parentName)
    {
        if(empty($tagName) || empty($parentName)){
            return $this->error(500);
        }
        //检查父子标签名是否存在
        $childTagInfo = $this->koubeiModel->getTagInfo($tagName);
        $parentTagInfo = $this->koubeiModel->getTagInfo($parentName);

        if (empty($childTagInfo) || empty($parentTagInfo)) {
            return $this->error(500, "标签不存在");
        }
        //检查父类标签是否是以前是子标签,是的话则报错
        if ($parentTagInfo['parent_id'] != 0) {
            return $this->error(500, "父标签错误");
        }
        //检查子标签是否有子类，有的话不可以修改
        $childArr = $this->koubeiModel->getChildTags($childTagInfo['id']);
        if (!empty($childArr)) {
            return $this->error(500, "子标签错误");
        }
        //更新子标签parent_id
        $SetData[] = ['parent_id', $parentTagInfo['id']];
        $res = $this->koubeiModel->updateTags($SetData, $childTagInfo['id']);
        if (!$res) {
            return $this->error(500, "添加失败");
        } else {
            return $this->succ($res);
        }
    }

    /**
     * 导入口碑和标签关系
     * @param $relationInfo tag_name（子标签） koubei_id
     */
    public function syncKoubeiTags($relationInfo)
    {
        $tag_name = $relationInfo["tag_name"];
        $koubei_id = $relationInfo["koubei_id"];

        if (empty($tag_name) || empty($koubei_id)) {
            return $this->error(500, "参数错误");
        }

        $item_id = $this->koubeiModel->getBatchKoubeiByIds([$koubei_id])[$koubei_id]['item_id'];
        if(empty($item_id)){
            return $this->error(500, "口碑不存在");
        }
        //根据标签名，检查标签是否存在
        $tagInfo = $this->koubeiModel->getTagInfo($tag_name);
        if (empty($tagInfo)) {
            return $this->error(500, "标签不存在");
        }
        //判断是否是子标签
        if ($tagInfo['parent_id'] == 0) {
            //是父标签
            $insertData["tag_id_1"] = $tagInfo['id'];
            $insertData["tag_id_2"] = 0;
        } else {
            //是子标签
            $insertData["tag_id_1"] = $tagInfo['parent_id'];
            $insertData["tag_id_2"] = $tagInfo['id'];
        }

        //防止重复数据
        $res = $this->koubeiModel->getItemTags([[':eq', 'koubei_id', $koubei_id],[':eq', 'item_id', $item_id],[':eq', 'tag_id_1', $insertData["tag_id_1"]],[':eq', 'tag_id_2', $insertData["tag_id_2"]]]);
        if(!empty($res)){
            return $this->error(500, "数据重复");
        }
        //关系数据入库
        $insertData["koubei_id"] = $koubei_id;
        $insertData["item_id"] = $item_id;

        $id = $this->koubeiModel->addTagsRelation($insertData);

        if (!$id) {
            return $this->error(500,"添加失败");
        } else {
            return $this->succ($id);
        }
    }

    /**
     * 获取指定商品，指定标签的口碑列表
     * @param $item_id
     * @param $tag_id（父标签）
     */
    public function getTagsKoubeiList($item_id, $tag_id, $page = 1, $limit = 20, $userId = 0)
    {
        if (empty($item_id) || empty($tag_id)) {
            return $this->error(500, "参数错误");
        }

        //判断标签类型
        $normalTagArr = \F_Ice::$ins->workApp->config->get('busconf.koubei.normalTagArr');
        if (array_key_exists($tag_id, $normalTagArr)) {
            $type = "normal";
        } else {
            $type = "collect";
        }

        //获取商品的关联商品或者套装单品
        $item_service = new ItemService();
        $item_ids = $item_service->getRelateItemById($item_id);
        $item_info = $item_service->getBatchItemBrandByIds([$item_id]);

        //好评率
        $feedbackRate = intval($item_info['data'][$item_id]['feedback_rate']);

        //获取用户评分
        $item_score = $this->koubeiModel->getItemUserScore($item_ids);
        //获取蜜粉推荐
        $item_rec_nums = $this->koubeiModel->getItemRecNums($item_ids);
        $offset = $page > 1 ? ($page - 1) * $limit : 0;

        if ($type == "collect") {
            //聚合印象
            //查询口碑id列表
            $koubeiIds = $this->koubeiModel->getItemKoubeiIds($item_ids, $tag_id, $limit, $offset);
            $koubei_res = array("koubei_info" => array());
            //通过商品id获取口碑id
            $cond['koubei_id'] = $koubeiIds;
            $koubei_ids = $this->koubeiModel->getKoubeiIdsByItemIds($item_ids, $limit, $offset, $cond);
        }
        if ($type == "normal") {
            //普通印象
            switch ($tag_id) {
                case 1 ://全部
                    //通过商品id获取口碑id
                    $koubei_ids = $this->koubeiModel->getKoubeiIdsByItemIds($item_ids, $limit, $offset);
                case 2 ://有图
                    //通过商品id获取口碑id
                    $condition = [];
                    $condition['with_pic'] = true;
                    $condition['score'] = array(4, 5);
                    $condition['machine_score'] = 3;
                    $koubei_ids = $this->koubeiModel->getKoubeiByItemIdsAndCondition($item_ids, $condition, $limit, $offset);
                case 3 ://好评
                    $koubei_ids = $this->koubeiModel->getKoubeiPraisedList($item_ids, $limit, $offset);
            }
        }
        //获取口碑信息
        $koubei_infos = $this->getBatchKoubeiByIds($koubei_ids, $userId)['data'];
        $koubei_res['koubei_info'] = !empty($koubei_infos) ? array_values($koubei_infos) : array();
        //如果综合评分和蜜粉推荐都为0，且当页无口碑，则返回空数组，如果当页有口碑，则返回口碑记录
        //（适用情况，该商品及关联商品无口碑贴，全为蜜芽贴）
        if ($item_score > 0 && $item_rec_nums == 0) {
            $koubei_res['total_score'] = $item_score;//综合评分
        } else if ($item_score > 0 && $item_rec_nums > 0) {
            $koubei_res['total_score'] = $item_score;//综合评分
            $koubei_res['recom_count'] = $item_rec_nums;//蜜粉推荐
        }
        if(!empty($feedbackRate)){
            $koubei_res['feedback_rate'] = $feedbackRate."%";//好评率
        }
        return $koubei_res;
    }

    /**
     * 获取指定商品的印象标签列表(父标签列表)
     * @param $item_id
     * @param array $field  normal普通标签 collect聚合标签
     * @return
     */
    public function getItemTagList($item_id, $field = ["normal", "collect"])
    {
        $tagOpen = \F_Ice::$ins->workApp->config->get('busconf.koubei.tagOpen');
        if (!$tagOpen) {
            return $this->succ([]);
        }
        if (empty($item_id)) {
            return $this->error(500, "参数错误");
        }
        //获取商品的关联商品或者套装单品
        $item_service = new ItemService();
        $item_ids = $item_service->getRelateItemById($item_id);
        $tagList = [];
        $normalTags = [];
        //聚合印象，内容数量小于3则不显示
        if (in_array('collect', $field)) {
            //查询商品所有父标签
            $tagsIds = $this->koubeiModel->getItemKoubeiTags($item_ids);

            //查询标签信息
            $tagInfos = $this->koubeiModel->getTags(array_keys($tagsIds));

            //根据商品id查询个标签数量
            $tagCount = $this->getTagsCount($item_ids, array_keys($tagsIds));

            foreach ($tagInfos as &$tag) {
                if (array_key_exists($tag['id'], $tagCount)) {
                    $tag["count"] = $tagCount[$tag['id']];
                }
            }
            //调整展示数量和顺序,按大小排序,正向最多10个，负向最多1个
            $good = 0;
            $bad = 0;
            foreach ($tagInfos as $k => $v) {
                if ($v["count"] >= 2 && $v['positive'] == 1 && $good < 10) {
                    $tagList[$k]['type'] = "collect";
                    $tagList[$k]['tag_id'] = $v["id"];
                    $tagList[$k]['tag_name'] = $v["tag_name"];
                    $tagList[$k]['count'] = intval($v["count"]);
                    $tagList[$k]['positive'] = 1;
                    $good++;
                } elseif ($v["count"] >= 2 && $v['positive'] == 2 && $bad < 1) {
                    $tagList[$k]['type'] = "collect";
                    $tagList[$k]['tag_id'] = $v["id"];
                    $tagList[$k]['tag_name'] = $v["tag_name"];
                    $tagList[$k]['count'] = intval($v["count"]);
                    $tagList[$k]['positive'] = 2;
                    $bad++;
                }
            }
            usort($tagList, function ($left, $right) {
                return $left['count'] < $right['count'];
            });
        }

        //常规印象为：全部，有图，好评，内容数量小于3则不显示
        if (in_array('normal', $field)) {
            //全部
            $totalNum = $this->koubeiModel->getItemKoubeiNums($item_ids);
            $picNum = $this->koubeiModel->getItemKoubeiNums($item_ids, 1);
            $praiseNum = $this->koubeiModel->getItemRecNums($item_ids);
            if ($totalNum >= 3) {
                $normalTags[] = ["type" => "normal", "tag_id" => "1", "tag_name" => "全部", "count" => intval($totalNum), 'positive' => 1];
            }
            if ($picNum >= 3) {
                $normalTags[] = ["type" => "normal", "tag_id" => "2", "tag_name" => "有图", "count" => intval($picNum), 'positive' => 1];
            }
            if ($praiseNum >= 3) {
                $normalTags[] = ["type" => "normal", "tag_id" => "3", "tag_name" => "好评", "count" => intval($praiseNum), 'positive' => 1];
            }
        }
        $result = array_merge($normalTags,$tagList);
        return $this->succ($result);
    }

    /**
     * 批量获取商品的标签，总数
     * @param $item_ids
     * @param $tagsIds
     * @return array
     */
    public function getTagsCount($item_ids, $tagsIds)
    {
        $tagInfos = $this->koubeiModel->getItemKoubeiTags($item_ids, $tagsIds, 1);
        $res = [];
        if (!empty($tagInfos)) {
            foreach ($tagInfos as $v) {
                $res[$v["tag_id_1"]] = $v["num"];
            }
        }
        return $res;
    }

    /*
     * 批量获取供应商口碑评分数量
     * */
    public function getSupplierKoubeiScore($suppliers = '',$search_time = ''){
        if(empty($suppliers) || empty($search_time)){
            return $this->succ(array());
        }
        $supplier_list = array();
        $solr = new SolrRemote('koubei');
        $solr_supplier = new SolrRemote('supplier');
        $supplier_info = $solr->getSupplierGoodsScore($suppliers, $search_time);
        // 获取默认5分好评
        $default_info = $solr_supplier->getDefaultScoreFive($suppliers, $search_time);
        $supplier_info['count']['num_default'] = 0;
        if(!empty($default_info)){
            $supplier_info['count']['num_default'] = count(array_diff($default_info,$supplier_info['order_ids']));
        }
        // 统计今日得分
        $numerator = (
                5*$supplier_info['count']['num_five']+
                4*$supplier_info['count']['num_four']+
                3*$supplier_info['count']['num_three']+
                2*$supplier_info['count']['num_two']+
                1*$supplier_info['count']['num_one'])*100
            +5*$supplier_info['count']['num_default'];
        $denominator = array_sum($supplier_info['count'])*100+$supplier_info['count']['num_default'];
        $supplier_info['count']['score_today'] = 0;
        if(!empty($denominator)){
            $supplier_info['count']['score_today'] = round($numerator/$denominator, 3);
        }
        $supplier_list[$suppliers] = $supplier_info['count'];
        return $this->succ($supplier_list);
    }
}