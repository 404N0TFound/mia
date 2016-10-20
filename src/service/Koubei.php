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
        $labels = array();$labels['label'] = array();$labels['image'] = array();
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
    public function getItemKoubeiList($itemId, $page=1, $count=20, $userId)
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
            }else{
                $itemIds = array($itemId);
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
            }else{
                $itemIds = $itemIdArr;
            }
            //将套装的商品id和所有套装id拼在一起，实现单品和套装互通
            array_push($itemIds, $spuItemId[0]);
        }
        
        //2、获取口碑数量,如果口碑小于等于0，直接返回空数组
        $koubeiNums = $this->koubeiModel->getItemKoubeiNums($itemIds);
        if($koubeiNums <=0){
            return $this->succ($koubeiRes);
        }
        
        //3、获取用户评分
        $itemScore = $this->koubeiModel->getItemUserScore($itemIds);
        //4、获取蜜粉推荐
        $itemRecNums = $this->koubeiModel->getItemRecNums($itemIds);
        
        //通过商品id获取口碑id
        $offset = $page > 1 ? ($page - 1) * $count : 0;
        $koubeiIds = $this->koubeiModel->getKoubeiIds($itemIds,$count,$offset);
        
        //如果综合评分和蜜粉推荐都为0，且当页无口碑，则返回空数组，如果当页有口碑，则返回口碑记录
        //（适用情况，该商品及关联商品无口碑贴，全为蜜芽贴）
        if($itemScore == 0 && $itemRecNums == 0){
            if(empty($koubeiIds)){
                return $this->succ($koubeiRes);
            }
        }else{
            //5、获取口碑信息
            $koubeiInfo = $this->getBatchKoubeiByIds($koubeiIds,$userId);
            $koubeiRes['koubei_info'] = $koubeiInfo;
        }
        
        //如综合评分不为0，蜜粉推荐数为0,蜜粉推荐数不展示，保留综合评分（适用情况，该商品无4&5星评分）
        if($itemScore > 0 && $itemRecNums == 0){
            $koubeiRes['total_score'] = $itemScore;//综合评分
            return $this->succ($koubeiRes);
        }
        //其他情况，都展示
        $koubeiRes['total_score'] = $itemScore;//综合评分
        $koubeiRes['recom_count'] = $itemRecNums;//蜜粉推荐
        #############end
        
        return $this->succ($koubeiRes);
    }
    
    /**
     * 根据口碑ID获取口碑信息
     */
    public function getBatchKoubeiByIds($koubeiIds,$userId) {
        $koubeiInfo = array();
        //批量获取口碑信息
        $koubeiArr = $this->koubeiModel->getBatchKoubeiByIds($koubeiIds,$status = array(2));
        foreach($koubeiArr as $koubei){
            if(empty($koubei['subject_id'])) continue;
            //收集subjectids
            $subjectId[] = $koubei['subject_id'];
        
            $itemKoubei[$koubei['subject_id']] = array(
                'rank' => $koubei['rank'],
                'score' => $koubei['score'],
                'item_size' => $koubei['item_size']) ;
        }
        //3、根据口碑中帖子id批量获取帖子信息（subject service）
        $subjectRes = $this->subjectService->getBatchSubjectInfos($subjectId, $userId , array('user_info', 'count', 'comment', 'group_labels', 'praise_info'));
        foreach( $itemKoubei as $key => $value)
        {
            if(!empty($subjectRes['data'][$key]))
            {
                $subjectRes['data'][$key]['item_koubei'] = $value;
                //口碑信息拼装到帖子
                $koubeiInfo[] = $subjectRes['data'][$key];
            }
        }
        return $koubeiInfo;
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
        
        //口碑评分，权重1
        $immutable_score += ($data['score'] * 1.5);
        
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
    
}
