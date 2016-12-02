<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Koubei as KoubeiModel;
use mia\miagroup\Service\Item as ItemService;
use mia\miagroup\Service\Order as OrderService;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Util\EmojiUtil;

class Koubei extends \mia\miagroup\Lib\Service {
    
    public $koubeiModel;
    public $subjectService;
    
    public function __construct() {
        $this->koubeiModel = new KoubeiModel();
        $this->subjectService = new SubjectService();
        $this->emojiUtil = new EmojiUtil();
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
            $orderInfo = $orderService->getOrderInfo($koubeiData['order_code'])['data'];
            $finishTime = strtotime($orderInfo['finish_time']) ;
            if($orderInfo['status'] != 5  || (time()- $finishTime) > 15 * 86400 )
            {
                return $this->error(6102);
            }
            //获取口碑信息，验证是否该口碑已经发布过
            $koubeiInfo = $this->koubeiModel->getItemKoubeiInfo($orderInfo['id'],$koubeiData['item_id']);
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
    
    /**
     * 获取口碑列表
     */
    public function getItemKoubeiList($itemId, $page=1, $count=20, $userId = 0, $onlyPic = false)
    {
        $koubeiRes = array("koubei_info" => array());
        if(!$itemId){
            return $this->succ($koubeiRes);
        }
        //1、检查该商品是否是套装
        $itemService = new ItemService();
        $itemInfo = $itemService->getItemList([$itemId])['data'][$itemId];
        if(empty($itemInfo)){
            return $this->succ($koubeiRes);
        }
        $itemIds = array();
        //如果是单品，直接取商品口碑
        if($itemInfo['is_spu'] == 0){
            if(!empty($itemInfo['relate_flag'])){
                $relatedItems = $itemService->getRelateItemList([$itemInfo['relate_flag']])['data'];
                if(!empty($relatedItems)){
                    foreach($relatedItems as $rItem){
                        $itemIds[] = $rItem['id'];
                    }
                }
            }
        }elseif($itemInfo['is_spu'] == 1 && $itemInfo['spu_type'] == 1){//是单品套装的情况
            //根据套装id获取套装的商品
            $spuItemId = $itemService->getItemRelateSpu($itemId)['data'];
            //根据套装的商品，获取商品的所有套装，实现套装和套装的互通
            $itemIdArr = $itemService->getSpuRelateItem($spuItemId[0])['data'];
            //如果该商品还有其他套装
            if(count($itemIdArr) >1){
                //过滤掉其他套装中为多品套装的
                $itemArr = $itemService->getItemList($itemIdArr)['data'];
                foreach($itemArr as $item){
                    if($item['is_spu'] == 1 && $item['spu_type'] == 1){
                        $itemIds[] = $item['id'];
                    }
                }
            }
            //将套装的商品id和所有套装id拼在一起，实现单品和套装互通
            array_push($itemIds, $spuItemId[0]);
        }
        $itemIds[] = $itemId;
        
        //2、获取口碑数量,如果口碑小于等于0，直接返回空数组
        $koubeiNums = $this->koubeiModel->getItemKoubeiNums($itemIds);
        if($koubeiNums <=0){
            return $this->succ($koubeiRes);
        }
        $koubeiRes['total_count'] = $koubeiNums;//口碑数量
        //3、获取用户评分
        $itemScore = $this->koubeiModel->getItemUserScore($itemIds);
        //4、获取蜜粉推荐
        $itemRecNums = $this->koubeiModel->getItemRecNums($itemIds);
        
        //通过商品id获取口碑id
        $offset = $page > 1 ? ($page - 1) * $count : 0;
        if ($onlyPic == false) {
            $koubeiIds = $this->koubeiModel->getKoubeiIds($itemIds,$count,$offset);
        } else {
            $koubeiIds = $this->koubeiModel->getKoubeiWithPicByItemIds($itemIds, $count, $offset);
        }
        //5、获取口碑信息
        $koubeiInfo = $this->getBatchKoubeiByIds($koubeiIds,$userId)['data'];
        $koubeiRes['koubei_info'] = !empty($koubeiInfo) ? array_values($koubeiInfo) : array();
        
        //如果综合评分和蜜粉推荐都为0，且当页无口碑，则返回空数组，如果当页有口碑，则返回口碑记录
        //（适用情况，该商品及关联商品无口碑贴，全为蜜芽贴）
        if($itemScore > 0 && $itemRecNums == 0){
            $koubeiRes['total_score'] = $itemScore;//综合评分
        } else if ($itemScore > 0 && $itemRecNums > 0) {
            $koubeiRes['total_score'] = $itemScore;//综合评分
            $koubeiRes['recom_count'] = $itemRecNums;//蜜粉推荐
        }
        
        return $this->succ($koubeiRes);
    }
    
    /**
     * 根据口碑ID获取口碑信息
     */
    public function getBatchKoubeiByIds($koubeiIds, $userId = 0) {
        if (empty($koubeiIds)) {
            return array();
        }
        $koubeiInfo = array();
        //批量获取口碑信息
        $koubeiArr = $this->koubeiModel->getBatchKoubeiByIds($koubeiIds,$status = array(2));
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
                'status' => $koubei['status']
            );
        }
        //3、根据口碑中帖子id批量获取帖子信息（subject service）
        $subjectRes = $this->subjectService->getBatchSubjectInfos($subjectId, $userId , array('user_info', 'count', 'comment', 'group_labels', 'praise_info', 'item'));
        foreach ($itemKoubei as $key => $value) {
            if (!empty($subjectRes['data'][$key])) {
                foreach ($subjectRes['data'][$key]['items'] as $item) {
                    if ($item['item_id'] == $value['item_id']) {
                        $value['item_info'] = $item;
                        break;
                    }
                }
                $subjectRes['data'][$key]['item_koubei'] = $value;
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
        return $appeal_info;
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
}
