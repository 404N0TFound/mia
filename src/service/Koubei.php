<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Koubei as KoubeiModel;
use mia\miagroup\Service\Item as ItemService;
use mia\miagroup\Service\Order as OrderService;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Service\Label as LabelService;
use mia\miagroup\Util\EmojiUtil;
use mia\miagroup\Remote\Solr as SolrRemote;
use mia\miagroup\Remote\Coupon as CouponRemote;

class Koubei extends \mia\miagroup\Lib\Service {

    public $koubeiModel;
    public $subjectService;
    public $koubeiConfig;
    public $version;

    public function __construct() {
        parent::__construct();
        $this->koubeiModel = new KoubeiModel();
        $this->subjectService = new SubjectService();
        $this->emojiUtil = new EmojiUtil();
        $this->version = $this->ext_params['version'];
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
            //退货订单没有发口碑权限
            $orderCode = $orderInfo['order_code'];
            $return = $orderService->getReturnByOrderCode($orderCode, $koubeiData['item_id'])['data'];
            if($orderInfo['status'] != 5  || (time()- $finishTime) > 16 * 86400 || count($return) > 0)
            {
                return $this->error(6102);
            }
            //获取口碑信息，验证是否该口碑已经发布过
            $koubeiInfo = $this->koubeiModel->getItemKoubeiInfo($orderId, $koubeiData['item_id'], strval($koubeiData['item_size']));
            if(!empty($koubeiInfo)){
                return $this->error(6103);
            }
        }

        //保存口碑
        //组装插入口碑信息  ####start
        $koubeiSetData = array();
        $koubeiSetData['status'] = (isset($koubeiData['status'])) ? intval($koubeiData['status']) : 2;
        $koubeiSetData['title'] = (isset($koubeiData['title']) ) ? trim($this->emojiUtil->emoji_unified_to_html($koubeiData['title'])) : "";
        $koubeiSetData['title'] = strip_tags($koubeiSetData['title'], '<span><p>');
        $koubeiSetData['content'] = trim($this->emojiUtil->emoji_unified_to_html($koubeiData['text']));
        $koubeiSetData['content'] = strip_tags($koubeiSetData['content'], '<span><p>');
        $koubeiSetData['score'] = $koubeiData['score'];
        $koubeiSetData['item_id'] = (isset($koubeiData['item_id']) && intval($koubeiData['item_id']) > 0) ? intval($koubeiData['item_id']) : 0;
        $koubeiSetData['item_size'] = $koubeiData['item_size'];
        $koubeiSetData['user_id'] = $koubeiData['user_id'];
        $koubeiSetData['order_id'] = isset($orderInfo['id']) ? $orderInfo['id'] : 0;
        $koubeiSetData['created_time'] = date("Y-m-d H:i:s");
        $labels = array();
        $labels['label'] = array();
        $labels['image'] = array();
        // 5.3 新增发布类型（针对封测报告）
        if(isset($koubeiData['type']) && $koubeiData['type'] == 'pick') {
            // 封测报告默认是没有评分的
            $koubeiSetData['score'] = 0;
            // 封测报告标识
            $koubeiSetData['type']  = 1;
        }
        // 封测报告推荐标识
        $labels['selection'] = "1";

        if(!empty($koubeiData['labels'])) {
            foreach($koubeiData['labels'] as $label) {
                $labels['label'][] = $label['title'];
            }
        }
        if(!empty($koubeiData['image_infos'])) {
            foreach($koubeiData['image_infos'] as $image) {
                $labels['image'][] = $image;
            }
        }

        // 5.3 口碑新增 甄选商品印象标签(三个维度)
        $labels['selection_label'] = array();
        $noRecommend_arr = \F_Ice::$ins->workApp->config->get('busconf.koubei.norecommend_flag');
        if(!empty($koubeiData['selection_labels'])) {
            $no_recommend_ident = 0;
            foreach($koubeiData['selection_labels'] as $selection_label) {
                $labels['selection_label'][] = $selection_label['tag_name'];
                // 1:推荐 2:不推荐
                if($selection_label['positive'] == 2 && in_array($selection_label['tag_name'], $noRecommend_arr)) {
                    $no_recommend_ident += 1;
                }
            }
            // 甄选商品三个维度，不推荐个取一个
            if($no_recommend_ident == 3) {
                $labels['selection'] = "0";
            }
            // 默认蜜芽圈封测标签
            $labels['label'][] = '封测报告';
        }

        // 排序权重新增封测报告逻辑(新增封测报告逻辑)
        $koubeiSetData['extr_info'] = json_encode($labels);
        $koubeiSetData['immutable_score'] = $this->calImmutableScore($koubeiSetData);
        $koubeiSetData['rank_score'] = $koubeiSetData['immutable_score'] + 12 * 0.5;
        //供应商ID获取
        $itemService = new ItemService();
        $itemInfo = $itemService->getItemList(array($koubeiSetData['item_id']))['data'][$koubeiSetData['item_id']];
        $koubeiSetData['supplier_id'] = intval($itemInfo['supplier_id']);
        //####end
        $koubeiInsertId = $this->koubeiModel->saveKoubei($koubeiSetData);
        if(!$koubeiInsertId) {
            return $this->error(6101);
        }

        //发口碑同时发布蜜芽圈帖子
        //#############start
        $subjectInfo = array();
        $subjectInfo['user_info']['user_id'] = $koubeiSetData['user_id'];
        $subjectInfo['title'] = $koubeiSetData['title'];
        $subjectInfo['text'] = $koubeiSetData['content'];
        $subjectInfo['created'] = $koubeiSetData['created_time'];
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
        if(!empty($labels['label'])) {
            //$labels = $labels['label'];
            foreach($labels['label'] as $label) {
                $labelInfos[] = array('title' => $label);
            }
        }
        // 5.3 封测报告标签
        $selectionLabelInfo = array();
        if(!empty($labels['selection_label'])) {
            foreach($labels['selection_label'] as $label) {
                $selectionLabelInfo[] = $label;
            }
        }

        // 5.3 封测报告是否推荐
        $selection = array();
        if(!empty($labels['selection'])) {
            $selection['selection'] = $labels['selection'];
        }

        $pointInfo[0] = array( 'item_id' => $koubeiSetData['item_id']);
        
        $subjectIssue = $this->subjectService->issue($subjectInfo,$pointInfo,$labelInfos,$koubeiInsertId,0,$selectionLabelInfo,$selection)['data'];
        //#############end
        //将帖子id回写到口碑表中
        if(!empty($subjectIssue) && $subjectIssue['id'] > 0){
            $this->koubeiModel->addSubjectIdToKoubei($koubeiInsertId,$subjectIssue['id']);
        }

        // 首评代金券(封测报告不作首评数据)
        if(!empty($koubeiData['issue_reward']) && $koubeiSetData['type'] != 1) {
            if ((mb_strlen($koubeiSetData['content']) > 20) && !empty($koubeiData['image_infos'])) {
                $couponRemote = new CouponRemote();
                $batch_code = $this->koubeiConfig['batch_code']['test'];
                if (!empty($batch_code)) {
                    $bindCouponRes = $couponRemote->bindCouponByBatchCode($koubeiSetData['user_id'], $batch_code);
                    if (!$bindCouponRes) {
                        $bindCouponRes = $couponRemote->bindCouponByBatchCode($koubeiSetData['user_id'], $batch_code);
                    }
                }
            }
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

        return $this->succ($koubeiInsertId);
    }
    
    /**
     * 默认好评
     */
    public function autoEvaluateKoubei($koubeiData) {
        if (empty($koubeiData['order_code']) || empty($koubeiData['items'])) {
            return $this->error(500);
        }
        //获取订单信息，验证是否可以发布口碑
        $orderService = new OrderService();
        $orderInfo = $orderService->getOrderInfoByOrderCode(array($koubeiData['order_code']))['data'][$koubeiData['order_code']];
        if(empty($orderInfo) || $orderInfo['status'] != 5) {
            return $this->error(6102);
        }
        $items = $koubeiData['items'];
        
        foreach ($items as $item) {
            $itemId = $item['item_id'];
            $itemSize = $item['item_size'];
            //获取口碑信息，验证是否该口碑已经发布过
            $koubeInfo = $this->koubeiModel->getItemKoubeiInfo($orderInfo['id'], $itemId, $itemSize);
            if(!empty($koubeInfo)) {
                continue;
            }
            //保存口碑
            $koubeiSetData = array();
            $koubeiSetData['status'] = 2;
            $koubeiSetData['auto_evaluate'] = 1;
            $koubeiSetData['score'] = 5;
            $koubeiSetData['item_id'] = $itemId;
            $koubeiSetData['item_size'] = $itemSize;
            $koubeiSetData['user_id'] = $orderInfo['user_id'];
            $koubeiSetData['order_id'] = $orderInfo['id'];
            $koubeiSetData['created_time'] = date("Y-m-d H:i:s");
            $koubeiSetData['immutable_score'] = 0;
            $koubeiSetData['rank_score'] = 0;
            //供应商ID获取
            $itemService = new ItemService();
            $itemInfo = $itemService->getItemList(array($koubeiSetData['item_id']))['data'][$koubeiSetData['item_id']];
            $koubeiSetData['supplier_id'] = intval($itemInfo['supplier_id']);
            $koubeiInsertId = $this->koubeiModel->saveKoubei($koubeiSetData);
            if(!$koubeiInsertId) {
                return $this->error(6101);
            }
            
            //发口碑同时发布蜜芽圈帖子
            $subjectInfo = array();
            $subjectInfo['user_info']['user_id'] = $koubeiSetData['user_id'];
            $subjectInfo['title'] = '';
            $subjectInfo['text'] = '';
            $subjectInfo['status'] = 3; //不展示
            $subjectInfo['created'] = $koubeiSetData['created_time'];
            $subjectInfo['source'] = \F_Ice::$ins->workApp->config->get('busconf.subject.source.koubei'); //帖子数据来自口碑标识
            $subjectIssue = $this->subjectService->issue($subjectInfo, array('item_id'=>$koubeiSetData['item_id']), array(), $koubeiInsertId)['data'];
            
            //将帖子id回写到口碑表中
            if(!empty($subjectIssue) && $subjectIssue['id'] > 0){
                $this->koubeiModel->addSubjectIdToKoubei($koubeiInsertId, $subjectIssue['id']);
            }
        }
        return $this->succ();
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

        // 获取商品是否为甄选商品
        $item_info = $item_service->getItemList([$itemId])['data'];
        if(!empty($item_info[$itemId]['is_pick'])) {
            $is_pick = $item_info[$itemId]['is_pick'];
        }

        $conditions = array();
        if(!empty($is_pick) && $is_pick == 1) {
            // 封测报告列表不展示默认好评(甄选商品)
            $conditions = array("auto_evaluate" => 0);
        }

        $koubei_nums = $this->koubeiModel->getItemKoubeiNums($item_ids, 0, $conditions);

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
        $koubei_ids = $this->koubeiModel->getKoubeiIdsByItemIds($item_ids, $count, $offset, $conditions);
        //获取口碑信息
        $koubei_infos = $this->getBatchKoubeiByIds($koubei_ids, $userId)['data'];

        // 封测报告推荐展示三条(排序靠前的3条不符合，不展示)
        if($page == 1 && $count == 3) {
            foreach($koubei_infos as $k => $koubei) {
                $extr_info = $koubei['item_koubei']['extr_info'];
                if(!empty($extr_info)) {
                    $extr_info = json_decode($extr_info, true);
                    if(empty($extr_info['selection'])) {
                        unset($koubei_infos[$k]);
                    }
                }
            }
        }

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
        // 甄选商品推荐率
        //$selection_info = $this->getSelectionKoubeiInfo([$itemId])['data'];
        //$koubei_res['selection_rate'] = $selection_info[$itemId]['rate'];
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
        $condition['score'] = array(0, 4, 5);
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
    public function getBatchKoubeiByIds($koubeiIds, $userId = 0, $field = array('user_info', 'count', 'koubei_reply', 'group_labels', 'praise_info', 'item' , 'order_info'), $status = array(2)) {
        if (empty($koubeiIds)) {
            return $this->succ(array());
        }
        $koubeiInfo = array();
        $orderIds = array();
        $commentIds = array();
        //批量获取口碑信息
        $koubeiArr = $this->koubeiModel->getBatchKoubeiByIds($koubeiIds,$status);
        if (empty($koubeiArr)) {
            return $this->succ();
        }
        foreach($koubeiArr as $koubei){
            if(empty($koubei['subject_id'])) {
                continue;
            }            
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
                'comment_id' => $koubei['comment_id'],
                'auto_evaluate' => $koubei['auto_evaluate'],
                'extr_info' => $koubei['extr_info'],
                'type' => $koubei['type']
            );
            //获取口碑订单id，用于获取订单编号(order_code)
            if(!empty($koubei['order_id'])){
                $orderIds[] = $koubei['order_id'];
            }
            if (intval($koubei['comment_id']) > 0) {
                $commentIds[] = $koubei['comment_id'];
            }
        }
        if (in_array('order_info', $field) && !empty($orderIds)){
            //获取订单信息
            $orderService = new OrderService();
            $orderInfos = $orderService->getOrderInfoByIds($orderIds)['data'];
        }
        if (in_array('koubei_reply', $field)) {
            //获取口碑官方回复信息
            $commentService = new \mia\miagroup\Service\Comment();
            $commentInfos = $commentService->getBatchComments($commentIds, array('user_info'))['data'];
        }

        // 封测报告，包含帖子删除，也获取数据
        $subject_status = [1, 3];
        if(in_array(0, $status)) {
            // 口碑包含删除的逻辑，帖子也要包含
            $subject_status[] = 0;
        }

        //3、根据口碑中帖子id批量获取帖子信息（subject service）
        $subjectRes = $this->subjectService->getBatchSubjectInfos($subjectId, $userId , $field, $subject_status);
        foreach ($itemKoubei as $key => $value) {
            if (!empty($subjectRes['data'][$key])) {
                if (!empty($subjectRes['data'][$key]['items'])) {
                    foreach ($subjectRes['data'][$key]['items'] as $item) {
                        if ($item['item_id'] == $value['item_id']) {
                            $value['item_info'] = $item;
                            break;
                        }
                    }
                }
                if ($subjectRes['data'][$key]['status'] == 3) {
                    $subjectRes['data'][$key]['text'] = \F_Ice::$ins->workApp->config->get('busconf.koubei.autoEvaluateText');
                }
                $subjectRes['data'][$key]['item_koubei'] = $value;
                //把口碑的订单编号（order_code）拼到口碑信息中
                if (in_array('order_info', $field) && intval($value['order_id']) > 0) {
                    $subjectRes['data'][$key]['item_koubei']['order_code'] = $orderInfos[$value['order_id']]['order_code'];
                }
                $selection_label = array();
                // 封测报告标签拼到口碑信息中
                if(!empty($value['extr_info'])) {
                    $extr_arr = json_decode($value['extr_info'], true);
                    if(!empty($extr_arr['selection_label'])) {
                        $selection_label = $extr_arr['selection_label'];
                    }
                }
                $subjectRes['data'][$key]['item_koubei']['selection_label'] = $selection_label;
                // 是否为封测报告（0：不是，1：是）
                $subjectRes['data'][$key]['item_koubei']['closed_report'] = $value['type'];

                //拼口碑官方回复信息
                if (in_array('koubei_reply', $field) && intval($value['comment_id']) > 0) {
                    $subjectRes['data'][$key]['koubei_reply'] = $commentInfos[$value['comment_id']];
                    if (!empty($commentInfos[$value['comment_id']])) {
                        $subjectRes['data'][$key]['comment_info'] = array($commentInfos[$value['comment_id']]);
                    }
                }
                //口碑信息拼装到帖子
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
        if (empty($subjectData)) {
            return $this->error(500, '帖子不存在或者已被删除');
        }
        if (!empty($subjectData['album_article']) || !empty($subjectData['video_info'])) {
            //如果是专栏或者视频贴
            return $this->error(500, '该类型贴不能同步到口碑');
        }
        //如果没有同步过，则同步为口碑贴
        $koubeiSetData = array();
        $koubeiSetData['status'] = 2;
        $koubeiSetData['machine_score'] = 3;
        $koubeiSetData['title'] = (isset($subjectData['title'])) ? trim($subjectData['title']) : "";
        $koubeiSetData['content'] = trim($this->emojiUtil->emoji_unified_to_html($subjectData['text']));
        $koubeiSetData['item_id'] = $itemId;
        $koubeiSetData['user_id'] = $subjectData['user_id'];
        $koubeiSetData['subject_id'] = (isset($subjectData['id'])) ? trim($subjectData['id']) : '';
        $koubeiSetData['created_time'] = $subjectData['created'];
        $koubeiSetData['immutable_score'] = $this->calImmutableScore($subjectData);
        $koubeiSetData['rank_score'] = $koubeiSetData['immutable_score'] + $this->calTimeScore($subjectData['created']);
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
        $mKoubei = new KoubeiModel();
        $koubeiInsertId = $mKoubei->saveKoubei($koubeiSetData);

        if (!$koubeiInsertId) {
            return $this->error(6101);
        }
        
        //更新口碑扩展字段
        $this->setKoubeiExtr($koubeiInsertId, $extInfo);

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

        //封测报告，权重（上下浮动2分）
        if(!empty($data['extr_info'])) {
            $extr_arr = json_decode($data['extr_info'], true);
            if(!empty($extr_arr['selection']) && $extr_arr['selection'] == 1) {
                $immutable_score += 3;
            }else {
                $immutable_score -= 3;
            }
        }
        return $immutable_score;
    }
    
    /**
     * 计算时间加权分，每月递减0.5分
     */
    private function calTimeScore($createdDate) {
        $timeScore = (12 - (time() - strtotime($createdDate)) / 86400 / 30) * 0.5; //时间加权分
        $timeScore = $timeScore > 0 ? number_format($timeScore, 1) : 0;
        return $timeScore;
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
    public function setKoubeiRank($koubeiIds, $rank, $adminId=0)
    {
        if (empty($koubeiIds) || !in_array($rank, array(0, 1))) {
            return $this->error(500);
        }
        if (is_string($koubeiIds)) {
            $koubeiIds = array($koubeiIds);
        }
        $res = $this->koubeiModel->setKoubeiRank($koubeiIds, $rank, $adminId);
        return $this->succ($res);
    }

    /**
     * 修改口碑审核通过状态
     */
    public function setKoubeiStatus($koubeiId, $status, $adminId)
    {
        if(!is_numeric($koubeiId) || intval($koubeiId) <= 0){
            return $this->error(500);
        }
        //更新口碑状态
        $koubeUpData = array('status'=>$status,'admin_id'=>$adminId,'verify_time'=>date('Y-m-d H:i:s'));
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
        $commentInfo['id'] = $commentService->addComment($commentInfo)['id'];

        //更新口碑表口碑回复状态
        $koubeiInfo['comment_id'] = $commentInfo['id'];
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

        // 5.1 版本控制
        $category_name = "category_id";
        $version = explode('_', $this->ext_params['version'], 3);
        array_shift($version);
        $version = intval(implode($version));
        //$version = 51;
        if ($version >= 51) {
            $category_name = "category_id_ng";
        }

        $item_service = new ItemService();

        if(!empty($category_id) && empty($brand_id)){

            // 类目（三级）
            $relation_ids = $item_service->getCategoryFourIds($category_id, 'cid')['data'];

            if(!empty($relation_ids)){
                // 类目下口碑去重分页列表
                $koubei_info = $solr->getHighQualityKoubeiByCategoryId($relation_ids, $page, $category_name);
                $brand_ids  = $solr->brandList($relation_ids, $category_name);
            }
        }
        if(empty($category_id) && !empty($brand_id)){

            // 品牌（三级）
            $relation_ids = $item_service->getCategoryFourIds($brand_id, 'bid')['data'];

            // 品牌口碑去重分页列表
            if(!empty($relation_ids)){
                $koubei_info = $solr->getHighQualityKoubeiByBrandId('', $relation_ids, $page, $category_name);
            }
        }else{
            // 类目及品牌 (默认为四级)

            $relation_ids = $item_service->getCategoryFourIds($category_id, 'cid')['data'];
            if(!empty($brand_id)){
                $koubei_info = $solr->getHighQualityKoubeiByBrandId($relation_ids, $brand_id, $page, $category_name);
            }
        }

        // 获取品牌名称
        if(!empty($brand_ids) && is_array($brand_ids)){
            $brand_list = array_values($item_service->getRelationBrandName($brand_ids)['data']);
        }

        if(!empty($koubei_info) && is_array($koubei_info)){
            $koubei['count'] = $koubei_info['count'];
            $koubei['list']  = array_values($this->getBatchKoubeiByIds($koubei_info['list'], $userId)['data']);
        }

        $res = array('koubei_list' => $koubei, 'brand_list' => $brand_list);
        return $this->succ($res);
    }


    /**
     * solr 口碑发布初始化
     * @param $order_id       可选
     * @param $item_id        可选
     * @param $issue_type[default:koubei]  发布类型
     */
    public function issueinit($order_id = 0, $item_id = 0, $issue_type = 'koubei'){

        // 发布口碑传入，帖子不需要传
        if($issue_type == 'koubei') {
            if(empty($order_id) || empty($item_id)) {
                return $this->error(500);
            }
        }
        $return_Info = array();
        switch ($issue_type) {
            case 'subject':
                # 帖子
                $issue_info = \F_Ice::$ins->workApp->config->get('busconf.subject')['subject_issue']['issue'];
                $return_Info = $this->koubeiModel->getBatchKoubeiByDefaultInfo($issue_info, $issue_type);
                $label_service = new LabelService();
                $labels = $label_service->getRecommendLabels()['data'];
                $return_Info['labels'] = $labels;
                break;

            default:
                # 口碑
                $item_service = new ItemService();
                $item_info = $item_service->getBatchItemBrandByIds([$item_id])['data'];
                $return_Info['item_info'] = $item_info[$item_id];
                $check_res = $this->koubeiModel->getCheckFirstComment(0, $item_id, 0);
                if(empty($check_res)) {
                    // 首评
                    $batch_info = $this->koubeiConfig['shouping'];
                }else {
                    // 代金券发放规则
                    $data = $this->getCouponRule([$item_id], 1, 1)['data'];
                    $coupon_info = $data[$item_id];
                    if(!empty($coupon_info)) {
                        $ext_info = json_decode($coupon_info['ext_info'],true);
                        $batch_info['issue_img'] = $ext_info['image']['url'];
                        $batch_info['issue_img_width'] = $ext_info['image']['width'];
                        $batch_info['issue_img_height'] = $ext_info['image']['height'];
                        $batch_info['char_count'] = $ext_info['chat_count'];
                        $batch_info['image_count'] = $ext_info['image_count'];
                    }
                }
                if(!empty($batch_info)) {
                    $return_Info = $this->koubeiModel->getBatchKoubeiByDefaultInfo($batch_info, $issue_type);
                }

                // 甄选商品
                $parent_cate_id = $item_service->getRelationCateId($item_id, 1)['data'];
                $selection_labels = \F_Ice::$ins->workApp->config->get('busconf.koubei.selection_cate');
                $relation = $selection_labels[$parent_cate_id];
                $q_labels = \F_Ice::$ins->workApp->config->get('busconf.koubei.selection_quality_labels_'.$relation);
                $p_labels = \F_Ice::$ins->workApp->config->get('busconf.koubei.selection_price_labels');
                $e_labels = \F_Ice::$ins->workApp->config->get('busconf.koubei.selection_exper_labels');
                $return_Info['selection_labels'][] = $q_labels;
                $return_Info['selection_labels'][] = $p_labels;
                $return_Info['selection_labels'][] = $e_labels;

                break;
        }
        return $this->succ($return_Info);
    }

    /**
     * 商品最优口碑
     * @param item_id   商品SKU
     * @ return | array(group_subject)
     */
    public function itemBatchBestKoubei($itemIds = array()){
        // 获取口碑最优id
        //$itemIds = array('1005598','1003113');
        if(!is_array($itemIds) || empty($itemIds)){
            return $this->succ(array());
        }
        $transfer_koubei = array();
        //通过商品id获取口碑id
        foreach ($itemIds as $item_id) {
            if(empty($item_id)){
                continue;
            }
            $res = $this->getItemKoubeiList($item_id)['data'];
            if(!empty($res) && !empty($res['koubei_info'])) {
                $transfer_koubei[$item_id] = $res['koubei_info'][0];
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

        if (!empty($tagInfo)) {
            $tag_id = $tagInfo['id'];
            $rootInfo = $this->koubeiModel->isRoot($tag_id);
            if (empty($rootInfo)) {
                $insertData['root'] = $tag_id;
                $insertData['parent'] = $tag_id;
                $insertData['tag_id'] = $tag_id;
                $this->koubeiModel->addTagsLayer($insertData);
                unset($insertData);
            }
            return $this->error(500, "标签存在");
        }
        //标签信息入库
        $insertData['tag_name'] = $tagName;
        $insertData['parent_id'] = 0;
        $insertData['positive'] = $positive;

        $tagId = $this->koubeiModel->addTag($insertData);
        unset($insertData);

        //向关系表添加一条根信息
        if ($tagId) {
            $insertData['root'] = $tagId;
            $insertData['parent'] = $tagId;
            $insertData['tag_id'] = $tagId;
            $resId = $this->koubeiModel->addTagsLayer($insertData);
            unset($insertData);
        }

        if ($tagId && $resId) {
            return $this->succ($tagId);
        } else {
            return $this->error(500,"添加失败");
        }
    }

    /**
     * 导入印象标签父子关系
     * @param $tagName  新定父标签
     * @param $parentName   新定子标签
     *
     * TODO getRoot 方法在多根的情况下得修改，因为多根下无法得到某个标签的具体根
     */
    public function syncTagsRelation($parentName, $childName)
    {
        if(empty($childName) || empty($parentName)){
            return $this->error(500);
        }
        if ($parentName == $childName) {
            return $this->error(500, "标签相同");
        }
        //检查父子标签名是否存在
        $childTagInfo = $this->koubeiModel->getTagInfo($childName);
        $parentTagInfo = $this->koubeiModel->getTagInfo($parentName);

        if (empty($childTagInfo)) {
            return $this->error(500, "标签不存在");
        }

        if (empty($parentTagInfo)) {
            $positive = $childTagInfo['positive'];
            $res = $this->syncTags($parentName, $positive);
            if($res['code'] != 0){
                return $this->error(500, "父标签插入失败");
            }
        }

        //修改关系
        //两标签是否都是根标签
        $is_root_1 = $this->koubeiModel->isRoot($parentTagInfo['id']);
        $is_root_2 = $this->koubeiModel->isRoot($childTagInfo['id']);
        if(!empty($is_root_1) && !empty($is_root_2)){
            //都是根标签
            //修改子标签的根和父
            $updateData[] = ['root', $parentTagInfo['id']];
            $updateData[] = ['parent', $parentTagInfo['id']];
            $where[] = ['id', $is_root_2['id']];
            $res = $this->koubeiModel->updateLayer($updateData,$where);
            unset($updateData);
            unset($where);


            //修改子标签下属子标签的根
            $updateData[] = ['root', $parentTagInfo['id']];
            $where[] = ['root', $childTagInfo['id']];


            $res = $this->koubeiModel->updateLayer($updateData,$where);
            unset($updateData);
            unset($where);
        }
        if(empty($is_root_1) && !empty($is_root_2))
        {
            //父是子标签，子是根标签
            //查询父的根
            $parentRoot = $this->koubeiModel->getRoot($parentTagInfo['id'])['root'];
            //修改子标签的根和父
            $updateData[] = ['root', $parentRoot];
            $updateData[] = ['parent', $parentTagInfo['id']];
            $where[] = ['id', $is_root_2['id']];
            $res = $this->koubeiModel->updateLayer($updateData, $where);
            unset($updateData);
            unset($where);

            //修改子标签下属子标签的根
            $updateData[] = ['root', $parentRoot];
            $where[] = ['root', $childTagInfo['id']];
            $res = $this->koubeiModel->updateLayer($updateData,$where);
            unset($updateData);
            unset($where);
        }

        if(!empty($is_root_1) && empty($is_root_2))
        {
            //父是根标签，子是子标签

            //查询子标签的根
            $childRoot = $this->koubeiModel->getRoot($childTagInfo['id']);

            //修改子标签的根和父
            $updateData[] = ['root', $parentTagInfo['id']];
            $updateData[] = ['parent', $parentTagInfo['id']];

            $where[] = ['id', $childRoot['id']];
            $res = $this->koubeiModel->updateLayer($updateData, $where);
            unset($updateData);
            unset($where);

            //修改子标签下属子标签的根
            //查询子标签下属所有标签
            $childList = $this->koubeiModel->getChildList($childTagInfo['id']);

            foreach ($childList as $v) {
                $updateData[] = ['root', $parentTagInfo['id']];
                $where[] = ['id', $v['id']];
                $res = $this->koubeiModel->updateLayer($updateData, $where);
                unset($updateData);
                unset($where);
            }
        }

        if(empty($is_root_1) && empty($is_root_2))
        {
            //都不是根标签
            //查询两个标签的根
            $parentRoot = $this->koubeiModel->getRoot($parentTagInfo['id']);
            $childRoot = $this->koubeiModel->getRoot($childTagInfo['id']);

            //修改子标签的根和父
            $updateData[] = ['root', $parentRoot['root']];
            $updateData[] = ['parent', $parentTagInfo['id']];

            $where[] = ['id', $childRoot['id']];
            $res = $this->koubeiModel->updateLayer($updateData, $where);
            unset($updateData);
            unset($where);

            //修改子标签下属子标签的根
            //查询子标签下属所有标签
            $childList = $this->koubeiModel->getChildList($childTagInfo['id']);

            foreach ($childList as $v) {
                $updateData[] = ['root', $parentRoot['root']];
                $where[] = ['id', $v['id']];
                $res = $this->koubeiModel->updateLayer($updateData, $where);
                unset($updateData);
                unset($where);
            }
        }
        return $this->succ("ok");
    }

    /**
     * 递归获取整个树结构
     * @return mixed
     */
    public function showTrees()
    {
        $res = $this->koubeiModel->showTrees();
        return $this->succ($res);
    }

    /**
     * 导入口碑和标签关系（单个导入不删除之前的）
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
     * 导入口碑和标签关系（批量导入，删除口碑之前所有标签）
     * @param $relationInfo tag_names（子标签数组） koubei_id
     */
    public function syncKoubeiTagsRelation($relationInfo)
    {
        $tag_names = $relationInfo["tag_names"];
        $koubei_id = $relationInfo["koubei_id"];

        if (empty($tag_names) || empty($koubei_id)) {
            return $this->error(500, "参数错误");
        }

        $item_id = $this->koubeiModel->getBatchKoubeiByIds([$koubei_id])[$koubei_id]['item_id'];
        if(empty($item_id)){
            return $this->error(500, "口碑不存在");
        }

        //查询口碑现有标签
        $res = $this->koubeiModel->getItemTags([':eq', 'koubei_id', $koubei_id]);
        $delIds = array_map(function ($v) {
            return $v['id'];
        }, $res);

        foreach ($tag_names as $tag_name){
            //根据标签名，检查标签是否存在
            $tagInfo = $this->koubeiModel->getTagInfo($tag_name);
            if (empty($tagInfo)) {
                return $this->error(500, "标签'".$tag_name."'不存在");
            }

            $insertData["tag_id_1"] = $tagInfo['id'];
            $insertData["koubei_id"] = $koubei_id;
            $insertData["item_id"] = $item_id;
            $insertArr[] = $insertData;
        }

        //关系数据入库
        $result = array_map(function($v){return $this->koubeiModel->addTagsRelation($v);},$insertArr);

        if (in_array(FALSE,$result)) {
            return $this->error(500,"部分操作添加失败，请重新操作！");
        } else {
            //删除之前该口碑的所有标签
            $this->koubeiModel->delTagsKoubeiRelation($delIds);
            return $this->succ($result);
        }
    }

    /**
     * 获取指定商品，指定标签的口碑列表
     * @param $item_id
     * @param $tag_id（父标签）
     * @param int $page
     * @param int $limit
     * @param int $userId
     * @return array
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

        $koubei_res = array("koubei_info" => array());

        //获取口碑数量
        $koubei_nums = $this->koubeiModel->getItemKoubeiNums($item_ids);
        if ($koubei_nums <= 0) {
            return $this->succ($koubei_res);
        }
        $koubei_res['total_count'] = $koubei_nums;//口碑数量
        //好评率
        $item_info = $item_service->getBatchItemBrandByIds([$item_id]);
        $feedbackRate = intval($item_info['data'][$item_id]['feedback_rate']);
        //获取用户评分
        $item_score = $this->koubeiModel->getItemUserScore($item_ids);
        //获取蜜粉推荐
        $item_rec_nums = $this->koubeiModel->getItemRecNums($item_ids);

        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        if ($type == "collect") {
            //聚合印象
            //查询口碑id列表
            $koubei_ids = $this->koubeiModel->getItemKoubeiIds($item_ids, $tag_id, $limit, $offset);
        }
        if ($type == "normal") {
            //普通印象
            switch ($tag_id) {
                case 1 ://全部
                    //通过商品id获取口碑id
                    $koubei_ids = $this->koubeiModel->getKoubeiIdsByItemIds($item_ids, $limit, $offset);
                    break;
                case 2 ://有图
                    //通过商品id获取口碑id
                    $condition = [];
                    $condition['with_pic'] = true;
                    $koubei_ids = $this->koubeiModel->getKoubeiByItemIdsAndCondition($item_ids, $condition, $limit, $offset);
                    break;
                case 3 ://好评
                    $koubei_ids = $this->koubeiModel->getKoubeiPraisedList($item_ids,[], $limit, $offset);
                    break;
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
        $koubei_res['total_count'] = $koubei_nums;//口碑数量
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
            if (!empty($tagsIds)) {//父标签为空，就不查了
                //查询标签信息
                $tagInfos = $this->koubeiModel->getTags(array_keys($tagsIds));

                //根据商品id查询每个标签数量
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
                    } elseif ($v['positive'] == 2 && $bad < 1) {
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
        }

        //常规印象为：全部，有图，好评，内容数量小于3则不显示
        if (in_array('normal', $field)) {
            //全部
            $totalNum = $this->koubeiModel->getItemKoubeiNums($item_ids);
            $picNum = $this->koubeiModel->getItemKoubeiNums($item_ids, 1);
            $praiseNum = $this->koubeiModel->getItemRecNums($item_ids);
            if ($totalNum >= 1) {
                $normalTags[] = ["type" => "normal", "tag_id" => "1", "tag_name" => "全部", "count" => intval($totalNum), 'positive' => 1];
            }
            if ($praiseNum >= 1) {
                $normalTags[] = ["type" => "normal", "tag_id" => "3", "tag_name" => "好评", "count" => intval($praiseNum), 'positive' => 1];
            }
            if ($picNum >= 1) {
                $normalTags[] = ["type" => "normal", "tag_id" => "2", "tag_name" => "晒图", "count" => intval($picNum), 'positive' => 1];
            }
        }
        $result = array_merge($normalTags,$tagList);
        return $this->succ($result);
    }

    /**
     * 获取口碑发布人数，口碑数
     * @param $item_id
     * @return mixed
     */
    public function getKoubeiNums($item_id)
    {
        //获取商品的关联商品或者套装单品
        $item_service = new ItemService();
        $item_ids = $item_service->getRelateItemById($item_id);
        if (empty($item_ids)) {
            $this->succ([]);
        }
        $result['user_unm'] = $this->koubeiModel->getItemKoubeiUserNums($item_ids);
        $result['item_rec_nums'] = $this->koubeiModel->getItemKoubeiNums($item_ids);
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
                $res[$v["root"]] = $v["num"];
            }
        }
        return $res;
    }

    /*
     * 批量获取供应商口碑评分数量
     * */
    public function getSupplierKoubeiScore($supplier = '',$search_time = ''){

        if(empty($supplier) || mb_strlen($search_time) != 10){
            return $this->succ(array());
        }

        $search_time = !empty($search_time) ? $search_time : time();

        $param_name = 'supplier_id';
        $supplier_list = array();
        $solr = new SolrRemote('koubei');
        $solr_supplier = new SolrRemote('supplier');
        // 获取商家口碑信息
        $supplier_info = $solr->getSupplierGoodsScore($param_name, $supplier, $search_time);
        // 获取默认5分好评
        $default_info = $solr_supplier->getDefaultScoreFive($param_name, $supplier, $search_time);
        $koubei_sum_score = array_sum($supplier_info['count']);

        $supplier_info['count']['num_default'] = 0;
        $default_count = $default_info['count'] - $koubei_sum_score;
        if($default_info['count'] > 0 && $default_count > 0){
            $supplier_info['count']['num_default'] = $default_count;
        }

        // 统计今日得分
        $numerator = (
                5*$supplier_info['count']['num_five']+
                4*$supplier_info['count']['num_four']+
                3*$supplier_info['count']['num_three']+
                2*$supplier_info['count']['num_two']+
                1*$supplier_info['count']['num_one']);
        $denominator = $koubei_sum_score;
        $supplier_info['count']['score_today'] = 0;
        if(!empty($denominator)){
            $supplier_info['count']['score_today'] = round($numerator/$denominator, 3);
        }
        $supplier_list[$supplier] = $supplier_info['count'];

        return $this->succ($supplier_list);
    }


    /**
     * 根据商品ID获取口碑ID集合
     */
    public function getBatchKoubeiIdsByItemId($item_id)
    {
        if(empty($item_id)){
            return $this->succ(array());
        }
        $solr = new SolrRemote('koubei');
        $solr_supplier = new SolrRemote('supplier');
        // 获取口碑各项得分
        $item_info = $solr->getSupplierGoodsScore('item_id', $item_id, time());
        $koubei_sum_score = array_sum($item_info['count']);
        // 获取商品默认5分好评
        $default_count = $solr_supplier->getDefaultScoreFive('item_id', $item_id, time());
        $default_count_five = 0;
        if($default_count['count'] > 0){
            $default_count_five = $default_count['count'] - $koubei_sum_score;
        }
        $item_score = array('each'=>$item_info['count'],'num_default'=>$default_count_five);
        return $this->succ($item_score);
    }
    
    /**
     * 更新口碑扩展字段
     */
    public function setKoubeiExtr($koubeiId, $extrInfo)
    {
        if(!is_numeric($koubeiId) || empty($extrInfo)){
            return $this->error(500);
        }

        //先查出口碑信息，看是否存在扩展字段
        $koubeInfo = $this->koubeiModel->getBatchKoubeiByIds(array($koubeiId), array())[$koubeiId];
        $newExtInfo = array();
        //如果存在，则在此基础上进行更新
        if(!empty($koubeInfo['extr_info'])){
            $extInfo = json_decode($koubeInfo['extr_info'],true);
            $newExtInfo = array_merge($extInfo,$extrInfo);
        }else{
            $newExtInfo = $extrInfo;
        }
        $newExtInfo = json_encode($newExtInfo);
        
        //更新口碑扩展字段
        $koubeUpData = array('extr_info'=>$newExtInfo);
        $koubeUpData['verify_time'] = date("Y-m-d H:i:s", time());
        
        $res = $this->koubeiModel->setKoubeiStatus($koubeiId, $koubeUpData);
        
        return $this->succ($res);
    }
    
    /**
     * 口碑信息更新操作
     */
    public function koubeiEditOperation($koubeiId, $setData)
    {
        if(!is_numeric($koubeiId) || empty($setData)){
            return $this->error(500);
        }
        
        $arrParams = array(
            'verify_time' => date("Y-m-d H:i:s", time()),
            'admin_id' => $setData['admin_id'],
            'rank_score'=>$setData['score']['rank_score'],
            'immutable_score'=>$setData['score']['immutable'],
            'subject_id' => $setData['subject_id'],
        );
        
        if(in_array($setData['status'],array(0,2))){
            $arrParams['status'] = $setData['status'];
        }
        
        if(in_array($setData['rank'],array(0,1))){
            $arrParams['rank'] = $setData['rank'];
        }
        if(in_array($setData['is_bottom'],array(0,1))){
            $arrParams['is_bottom'] = $setData['is_bottom'];
        }
        
        //蜜芽兔
        $userId = \F_Ice::$ins->workApp->config->get('busconf.user.miaTuUid');
        //如果有回复内容，则需要入评论表
        if(!empty($setData['comment']))
        {
            $fid = 0;
            if($setData['fid']){
                $fid = $setData['fid'];
            }
            
            $this->koubeiComment($setData['supplier_id'], $setData['subject_id'], $setData['comment'], $fid);
        }
        //如果为通过状态，发蜜豆
        if ($arrParams['status'] == 2)
        {
            //发蜜豆
            $mibean = new \mia\miagroup\Remote\MiBean();
            $param['user_id'] = $userId;//蜜芽兔
            $param['to_user_id'] = $setData['user_id'];
            $param['relation_type'] = "send_koubei";
            $param['relation_id'] = $koubeiId;
            
            //如果有图片，发10个蜜豆；如果没有，发5个
            $param['mibean'] = 5;
            if(!empty($setData['extr_info'])){
                $extInfo = json_decode($setData['extr_info'],true);
                $imageInfos = $extInfo['image'];
                if(!empty($imageInfos)){
                    $param['mibean'] = 10;
                }
            }
            
            $mibean->add($param);
        }
        
        //更新口碑扩展字段
        $res = $this->koubeiModel->setKoubeiStatus($koubeiId,$arrParams);
        return $this->succ($res);
    }

    /*
     * 甄选封测商品
     * 维度：用户，订单，商品
     * 用户购买的商品是否已评价
     * */
    public function checkItemKoubeiStatus($data)
    {
        //$user_id, $item_id, $order_id
        if(empty($data) || !is_array($data)) {
            return $this->succ([]);
        }
        $return = array();
        foreach($data as $info){
            $order_id = $info['order_id'];
            $item_id  = $info['item_id'];
            $user_id  = $info['user_id'];
            if(empty($order_id) || empty($item_id) || empty($user_id)) {
                return $this->succ([]);
            }
            $count = $this->koubeiModel->getCheckFirstComment($order_id, $item_id, $user_id);
            empty($count) ? $flag = 0 : $flag = 1;
            $return[$order_id] = $flag;
        }
        return $this->succ($return);
    }

    /*
 * 甄选封测商品
 * 批量获取口碑数，封测推荐测评数
 * 封测报告删除也需要统计
 * */
    public function getSelectionKoubeiInfo($item_ids)
    {
        if(empty($item_ids)) {
            return $this->succ([]);
        }
        $selection_info = array();
        foreach($item_ids as $item_id) {
            $recommend_count = 0;

            // 获取关联商品ID
            $item_service = new ItemService();
            $item_rel_ids = $item_service->getRelateItemById($item_id);
            if (empty($item_rel_ids)) {
                return $this->succ([]);
            }
            //获取封测报告数（包括删除的封测报告）
            $koubei_nums = $this->koubeiModel->getItemKoubeiNums($item_rel_ids, 0, array('status'=>[0,2]));
            //通过商品id获取口碑id(包括删除的封测报告)
            $koubei_ids = $this->koubeiModel->getKoubeiIdsByItemIds($item_rel_ids, 0, 0, array('status'=>[0,2]));
            //获取口碑信息(包括删除封测报告,帖子)
            $koubei_infos = $this->getBatchKoubeiByIds($koubei_ids, 0, array(), array(0,2))['data'];
            $selection_info[$item_id]['total_count'] = $koubei_nums;
            foreach($koubei_infos as $koubei) {
                if($koubei['item_koubei']['auto_evaluate'] == 1) {
                    // 默认好评算推荐
                    $recommend_count += 1;
                }else {
                    $extr_info = json_decode($koubei['item_koubei']['extr_info'], true);
                    if(!empty($extr_info['selection'])) {
                        $recommend_count += 1;
                    }
                }
            }
            $selection_info[$item_id]['recommend_count'] =  $recommend_count;
            if(!empty($koubei_nums)) {
                $selection_info[$item_id]['rate'] = round($recommend_count / $koubei_nums, 2);
            }
        }
        return $this->succ($selection_info);
    }

    /*
     * 5.4 代金券奖励规则设置
     * */
    public function getCouponRule($itemIds, $page=1, $count=1)
    {
        if(empty($itemIds)) {
            return $this->succ([]);
        }
        $coupons = [];
        $item_service = new ItemService();
        $offset = $page > 1 ? ($page - 1) * $count : 0;
        foreach($itemIds as $item_id) {
            $param = [];
            $item_info = $item_service->getBatchItemBrandByIds([$item_id])['data'];
            $param['brand_id'] = $item_info[$item_id]['brand_id'];
            // 类目投放维度
            $param['category_id'] = $item_info[$item_id]['category_id_ng'];
            // 代金券发放匹配
            $param['item_id'] = $item_id;
            $coupon_info = $this->koubeiModel->getCouponInfo($param, $count, $offset);
            $coupons[$item_id] = $coupon_info;
        }
        return $this->succ($coupons);
    }

    /*
     * 5.4 口碑发布成功奖励弹层信息
     * */
    public function release_issue($koubeiData)
    {
        if(empty($koubeiData)) {
            return $this->succ([]);
        }
        $item_id = (isset($koubeiData['item_id']) && intval($koubeiData['item_id']) > 0) ? intval($koubeiData['item_id']) : 0;
        //$item_id = 198309936;
        $text = trim($this->emojiUtil->emoji_unified_to_html($koubeiData['text']));
        $content = strip_tags($text, '<span><p>');
        $char_count = mb_strlen($content, 'utf8');
        if(!empty($koubeiData['image_infos'])) {
            $image_count = count($koubeiData['image_infos']);
        }
        $return = [];
        $data = $this->getCouponRule([$item_id]);
        if(!empty($data)) {
            $coupon_info = $data['data'][$item_id];
            if(!empty($coupon_info['ext_info'])) {
                $ext_info = json_decode($coupon_info['ext_info'], true);
                if($char_count >= $ext_info['chat_count'] && $image_count >= $ext_info['image_count']) {
                    $return['title'] = $ext_info['prompt'];
                    $return['content'] = $ext_info['intro'];
                }
            }
        }
        return $this->succ($return);
    }
}