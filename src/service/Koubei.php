<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Koubei as KoubeiModel;
use mia\miagroup\Service\Item as ItemService;
use mia\miagroup\Service\Order as OrderService;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Util\EmojiUtil;

class Koubei extends \FS_Service {
    
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
     */
    public function createKoubei($koubeiData){
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
        $koubeiSetData['order_id'] = $orderInfo['id'];
        $koubeiSetData['created_time'] = date("Y-m-d H:i:s");
        $koubeiSetData['immutable_score'] = $this->calImmutableScore($koubeiSetData);
        $koubeiSetData['rank_score'] = $koubeiSetData['immutable_score'] + 1.2;
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
        }
        //发口碑同时发布蜜芽圈帖子
        //#############start
        $subjectInfo = array();
        $subjectInfo['user_info']['user_id'] = $koubeiSetData['user_id'];
        $subjectInfo['title'] = $koubeiSetData['title'];
        $subjectInfo['text'] = $koubeiSetData['content'];
        $subjectInfo['created'] = $koubeiSetData['created_time'];
        $subjectInfo['extr_info'] = $labels;
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
     * 获取商品口碑列表
     * @param int $itemId 商品id
     * @param int $page 页码
     * @param int $count 每页数量
     * @param int $userId 用户id
     */
    public function getKoubeiList($itemId, $page=1, $count=20, $userId){
        $koubeiRes = array( "total_score"=> 0, "recom_count" => 0 ,"koubei_info" => array());
        //1、获取关联商品id
        $itemService = new ItemService();
        $itemInfo = $itemService->getItemList([$itemId])['data'][$itemId];//获取商品信息
        //（1）如果商品不存在，则返回空
        if(empty($itemInfo)){
            return $this->succ($koubeiRes);
        }
        
        $relateItemArr = array();
        $relateItemIds = array();
        if(!empty($itemInfo['relate_flag'])){
            //（2）如果存在，且关联标识不为空，根据其关联标识获取关联商品id
            $relateItemArr = $itemService->getRelateItemList([$itemInfo['relate_flag']])['data'][$itemInfo['relate_flag']];
            $relateItemIds = array_keys($relateItemArr);
        }else{
            //(3)如果商品存在，且关联标识为空，则直接取商品id
            $relateItemIds = array($itemInfo['id']);
        }
        //2、获取口碑数量,如果口碑小于等于0，直接返回空数组
        $koubeiNums = $this->koubeiModel->getItemKoubeiNums($relateItemIds);
        if($koubeiNums <=0){
            return $this->succ($koubeiRes);
        }
        //3、获取用户评分
        $itemScore = $this->koubeiModel->getItemUserScore($relateItemIds);
        //4、获取蜜粉推荐
        $itemRecNums = $this->koubeiModel->getItemRecNums($relateItemIds);
        //5、根据商品id或者关联商品id批量获取口碑信息
        $offset = $page > 1 ? ($page - 1) * $count : 0;
        $koubeiArr = $this->koubeiModel->getKoubeiList($relateItemIds,$count, $offset);
        
        foreach($koubeiArr as $koubei){
            if(empty($koubei['subject_id'])) continue;
            $subjectId[] = $koubei['subject_id'];
            
            $itemKoubei[$koubei['subject_id']] = array(
                'rank' => $koubei['rank'],
                'score' => $koubei['score'],
                'item_size' => $koubei['item_size']) ;
        }
        //6、根据口碑中帖子id批量获取帖子信息（subject service）
        $subjectRes = $this->subjectService->getBatchSubjectInfos($subjectId, $userId , array('user_info', 'count', 'comment', 'group_labels', 'praise_info'));
        foreach( $itemKoubei as $key => $value)
        {
            if(!empty($subjectRes['data'][$key]))
            {
                $subjectRes['data'][$key]['item_koubei'] = $value;
                $koubeiRes['koubei_info'][] = $subjectRes['data'][$key];
            }
        }
        
        $koubeiRes['total_score'] = $itemScore;//综合评分
        $koubeiRes['recom_count'] = $itemRecNums;//蜜粉推荐
        
        return $this->succ($koubeiRes);
    }
    
    //获取口碑的不变分数
    private function calImmutableScore($data)
    {
        $hasPic = 0;
        if(!empty($data['image_infos'])) {
            $hasPic = 1;
        }
        $content_count = mb_strlen($data['text'],'utf-8');
        $immutable_score = 1 * 0.2;
    
        if($content_count > 100)
        {
            $immutable_score = 10 * 0.2;
        }
        else if($content_count > 50)
        {
            $immutable_score = 8 * 0.2;
        }
        else if($content_count > 30)
        {
            $immutable_score = 5 * 0.2;
        }
        else if($content_count > 10)
        {
            $immutable_score = 3 * 0.2;
        }
    
        $immutable_score += $data['score'] * 0.5 + 0.3 * 10 * $hasPic + $data['rank'] * 6 ;
        return $immutable_score;
    }

}
