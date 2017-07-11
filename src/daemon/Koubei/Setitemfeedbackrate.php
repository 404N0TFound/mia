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
    private $lastIdFile;

    public function __construct() {
        $this->koubeiData = new KoubeiData();
        $this->itemData = new ItemData();
        $this->itemService = new ItemService();
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $tempFilePath = $runFilePath . '/koubei/';
        $this->lastIdFile = $tempFilePath . 'item_feedback_rate_last_id';
        //$this->lastIdFile = 'D:/htdocs/repos/groupservice/var/daemonlogs/item_feedback_rate_last_id';
    }

    public function execute() {
         //$this->setItemFeedbackRate();
         //更新增量的商品的口碑好评率
         $this->setIncrementItemFeedbackRate();
    }
    
    //全量计算且更新商品口碑好评率
    public function setItemFeedbackRate(){
        //读取上一次处理的id
        if (!file_exists($this->lastIdFile)) { //打开文件
            $lastId = 0;
            $fpLastIdFile = fopen($this->lastIdFile, 'w');
        } else {
            $fpLastIdFile = fopen($this->lastIdFile, 'r+');
        }
        
        if (!flock($fpLastIdFile, LOCK_EX | LOCK_NB)) { //加锁
            fclose($fpLastIdFile);
            return;
        }
        
        if (!isset($lastId)) { //获取last_id
            $lastId .= fread($fpLastIdFile, 1024);
            $lastId = intval($lastId);
        }
        //1、获取待计算口碑好评率的商品id
        $itemInfoData = $this->itemData->getListById($lastId,100);
        //循环将好评率更新到商品记录中
        foreach ($itemInfoData as $itemInfo){
            if (isset($maxId)) { //获取最大event_id
                $maxId = $itemInfo['id'] > $maxId ? $itemInfo['id'] : $maxId;
            } else {
                $maxId = $itemInfo['id'];
            }

            //2、通过口碑商品id获取商品关联款及套装款id
            $itemIds = $this->itemService->getRelateItemById($itemInfo['id']);
            $itemIds = array_unique($itemIds);
            //3、获取商品全部评分口碑数量
            $filed = ' count(distinct(koubei.id)) as nums ';
            $where = array();
            $where['item_id'] = $itemIds;
            $where['status'] = 2;
            $where['subject_id'] = 0;
            $totalNums = $this->koubeiData->getItemInvolveNums($filed, $where);
            if(empty($totalNums)) {
                continue;
            }
            //4、获取商品4分以上的评分口碑数量
            $where['score_in'] = [4, 5, 0];
            $highScoreNums = $this->koubeiData->getItemInvolveNums($filed, $where);
            //5、通过口碑评分，计算出商品关联及套装好评率
            $feedbackRate = round($highScoreNums/$totalNums* 100,2) ;

            //6、将好评率更新到待计算口碑好评率的商品记录中
            $itemSetData = array();
            $itemSetData[] = ['feedback_rate',$feedbackRate];
            $this->itemData->updateItemInfoById($itemInfo['id'], $itemSetData);
        }
        //写入本次处理的最大event_id
        if (isset($maxId)) {
            fseek($fpLastIdFile, 0, SEEK_SET);
            ftruncate($fpLastIdFile, 0);
            fwrite($fpLastIdFile, $maxId);
        }
        flock($fpLastIdFile, LOCK_UN);
        fclose($fpLastIdFile);
    }
    
    //增量更新商品口碑好评率
    public function setIncrementItemFeedbackRate(){
        //获取当天新增口碑的商品id
        $incItemIds = $this->koubeiData->getTodayKoubeiItemId();
        //循环将好评率更新到商品记录中
        foreach ($incItemIds as $itemId){
            //2、通过口碑商品id获取商品关联款及套装款id
            $itemIds = $this->itemService->getRelateItemById($itemId);
            //3、获取商品全部评分口碑数量
            $filed = ' count(*) as nums ';
            $where = array();
            $where['item_id'] = $itemIds;
            $where['status'] = 2;
            $where['subject_id'] = 0;
            
            $totalNums = $this->koubeiData->getItemInvolveNums($filed, $where);
            if(empty($totalNums)) {
                continue;
            }
            //4、获取商品4分以上的评分口碑数量
            $where['score_in'] = [4, 5, 0];
            $highScoreNums = $this->koubeiData->getItemInvolveNums($filed, $where);
        
            //5、通过口碑评分，计算出商品关联及套装好评率
            $feedbackRate = round($highScoreNums/$totalNums,2) * 100;
            //6、将好评率更新到待计算口碑好评率的商品记录中
            $itemSetData = array();
            $itemSetData[] = ['feedback_rate',$feedbackRate];
            $this->itemData->updateItemInfoById($itemId, $itemSetData);
        }
    }
}
