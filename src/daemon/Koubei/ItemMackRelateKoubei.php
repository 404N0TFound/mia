<?php
namespace mia\miagroup\Daemon\Koubei;

use \mia\miagroup\Data\Item\Item as ItemData;
use \mia\miagroup\Data\Koubei\Koubei as KoubeiData;
use mia\miagroup\Service\Item as ItemService;
use mia\miagroup\Service\Koubei as KoubeiService;

/**
 * 更新商品打分
 */
class Itemmackrelatekoubei extends \FD_Daemon {

    private $koubeiData;
    private $itemData;
    private $itemService;
    private $koubeiService;
    private $lastIdFile;

    public function __construct() {
        $this->koubeiData = new KoubeiData();
        $this->itemData = new ItemData();
        $this->itemService = new ItemService();
        $this->koubeiService = new KoubeiService();
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $tempFilePath = $runFilePath . '/koubei/';
        $this->lastIdFile = $tempFilePath . 'item_score_last_id';
        //$this->lastIdFile = 'C:/Users/admin/PhpstormProjects/groupservice/var/daemonlogs/item_score_last_id';
    }

    public function execute() {
        // 全量更新商品口碑得分
        //$this->fullImportItemScore();
        // 增量更新商品口碑得分
        $this->deltaImportItemScore();
    }

    /*
     * 全量更新商品得分
     * */
    public function fullImportItemScore(){
        //读取上一次处理的id
        $lastId = 0;
        $fpLastIdFile = fopen($this->lastIdFile, 'r+');
        if (!file_exists($this->lastIdFile)) {
            $fpLastIdFile = fopen($this->lastIdFile, 'w');
        }
        if (!flock($fpLastIdFile, LOCK_EX | LOCK_NB)) {
            // 已经打开的指针操作
            fclose($fpLastIdFile);
            return;
        }
        if(!empty(filesize($this->lastIdFile))){
            $lastId = intval(fread($fpLastIdFile, filesize($this->lastIdFile)));
        }
        $itemInfoData = $this->itemData->getListById($lastId,10);
        foreach ($itemInfoData as $itemInfo){
            //$itemInfo['id'] = 1090471;
            if(empty($itemInfo['id'])) {
                continue;
            }
            if (isset($maxId)) {
                $maxId = $itemInfo['id'] > $maxId ? $itemInfo['id'] : $maxId;
            } else {
                $maxId = $itemInfo['id'];
            }
            $multiple_score = $this->sattis_item_score($itemInfo['id']);
            $itemSetData = array();
            $itemSetData[] = ['item_multiple_score',$multiple_score];
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

    /*
     * 增量更新商品得分
     * */
    public function deltaImportItemScore(){
        //获取当天新增口碑的商品id
        $incItemIds = $this->koubeiData->getTodayKoubeiItemId();
        if(!empty($incItemIds)){
            foreach ($incItemIds as $itemId){
                $multiple_score = $this->sattis_item_score($itemId);
                $itemSetData = array();
                $itemSetData[] = ['item_multiple_score',$multiple_score];
                $this->itemData->updateItemInfoById($itemId, $itemSetData);
            }
        }
    }

    /*
     * 计算商品今日得分（3个月的数据作为统计项）
     * */
    public function sattis_item_score($item_id){
        //通过商品id获取口碑评分信息(不关联商品统计)
        $item_score_info = $this->koubeiService->getBatchKoubeiIdsByItemId($item_id);
        //统计得分
        $numerator = (
                5*$item_score_info['data']['each']['num_five']+
                4*$item_score_info['data']['each']['num_four']+
                3*$item_score_info['data']['each']['num_three']+
                2*$item_score_info['data']['each']['num_two']+
                1*$item_score_info['data']['each']['num_one'])*100
            +5*$item_score_info['data']['num_default'];
        $denominator = array_sum($item_score_info['data']['each'])*100+$item_score_info['data']['num_default'];
        $multiple_score = 0;
        if(!empty($denominator)){
            $multiple_score = round($numerator/$denominator, 3);
        }
        return $multiple_score;
    }
}
