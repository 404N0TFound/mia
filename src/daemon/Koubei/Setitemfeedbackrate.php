<?php
namespace mia\miagroup\Daemon\Koubei;

use \mia\miagroup\Data\Item\Item as ItemData;
use \mia\miagroup\Data\Koubei\Koubei as KoubeiData;
use mia\miagroup\Service\Item as ItemService;

/**
 * 更新商品口碑好评率
 */
class Setitemfeedbackrate extends \FD_Daemon {

    private $koubeiData;
    private $itemData;
    private $itemService;
    private $tempFilePath;
    private $fullLastIdFile;
    private $incrLastIdFile;

    public function __construct() {
        $this->koubeiData = new KoubeiData();
        $this->itemData = new ItemData();
        $this->itemService = new ItemService();
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $this->tempFilePath = $runFilePath . '/koubei/';
    }

    public function execute() {

        // 模式切换
        $this->mode = $this->request->argv[0];
        if (empty($this->mode)) {
            return ;
        }
        switch ($this->mode) {
            case 'full_dump':
                $this->fullLastIdFile = $this->tempFilePath . 'item_feedback_rate_last_id_full';
                $this->setFullItemFeedbackRate();
                break;
            case 'incremental_dump':
                $this->incrLastIdFile = $this->tempFilePath . 'item_feedback_rate_last_id_incr';
                $this->setIncrementItemFeedbackRate();
                break;
        }
    }
    
    /*
     * 全量计算且更新商品口碑好评率
     * */
    public function setFullItemFeedbackRate(){

        $i = 0;
        $maxId = 0;
        set_time_limit(0);
        $fpfull = fopen($this->fullLastIdFile, 'w');
        while(true) {
            if($i % 500 == 0) {
                sleep(1);
            }
            $fullItemIds = array();
            $currentItemInfoData = $this->itemData->getListById($maxId, 100);
            if(empty($currentItemInfoData)) {
                break;
            }
            foreach($currentItemInfoData as $itemInfo) {
                $fullItemIds[] = $itemInfo['id'];
            }
            $res = $this->handleItemScore($fullItemIds);
            if(empty($res)) {
                // 继续往下执行
                $maxId = $maxId + 1;
            }else{
                $maxId = $res;
            }
            $i += 100;
            // 日志
            fwrite($fpfull, $maxId);
        }
    }

    /*
     * 增量更新商品口碑好评率
     * */
    public function setIncrementItemFeedbackRate(){
        //获取当天新增口碑的商品id
        $fpincr = fopen($this->incrLastIdFile, 'w');
        $incItemIds = $this->koubeiData->getTodayKoubeiItemId();
        $maxId = $this->handleItemScore($incItemIds);
        // 日志
        fwrite($fpincr, $maxId);
    }


    /*
     * 获取商品的评分
     * 返回当前处理的最大id
     * */
    public function handleItemScore($handleItemIds) {

        if(empty($handleItemIds)) {
            return 0;
        }
        $register_ids = array();
        foreach ($handleItemIds as $item_id){
            $relation_itemIds = $this->itemService->getRelateItemById($item_id);
            $filed = ' count(distinct(koubei.id)) as nums ';
            $where = array();
            $where['item_id'] = $relation_itemIds;
            $where['status'] = 2;
            $where['subject_id'] = 0;
            $item_koubei_sum = $this->koubeiData->getItemInvolveNums($filed, $where);
            if(empty($item_koubei_sum)) {
                continue;
            }
            $where['score_in'] = [4, 5, 0];
            $high_quality_sum = $this->koubeiData->getItemInvolveNums($filed, $where);
            //5、通过口碑评分，计算出商品关联及套装好评率
            $feedbackRate = round($high_quality_sum/$item_koubei_sum, 2) * 100;
            $itemSetData = array();
            $itemSetData[] = ['feedback_rate',$feedbackRate];
            $this->itemData->updateItemInfoById($item_id, $itemSetData);
            // 记录操作id
            $register_ids[] = $item_id;
        }
        // 返回最大处理item_id
        return max($register_ids);
    }
}
