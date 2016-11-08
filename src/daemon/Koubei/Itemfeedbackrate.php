<?php
namespace mia\miagroup\Daemon\Koubei;
use \mia\miagroup\Data\Item\Item;
use \mia\miagroup\Data\Koubei\Koubei;

class Itemfeedbackrate extends \FD_Daemon{
    
    
    public function execute(){
        $itemData = new Item();
        $koubeiData = new Koubei();
        $itemIds = $koubeiData->getTodayKoubeiItemId();
        if(empty($itemIds)){
            echo 'not new item';
            exit;
        }
        $score_data = $koubeiData->getKoubeiScoreByItemIds($itemIds);
        foreach($score_data as $value){
            $score_arr = explode(',', $value['score']);
            $feedback_rate = $this->getFeedbackRate($score_arr);
            $score_result[$value['item_id']] = $feedback_rate;
        }
        //保存到item表feedback_rate字段
        $data = array_chunk($score_result, 1000, true);
        foreach($data as $item_rate_data){
            //每次处理1000条
            $item_sql = "UPDATE item SET feedback_rate = CASE id ";
            foreach($item_rate_data as $itemId=>$rate){
                if($rate == 0){
                    continue;
                }
                $item_sql .= sprintf(' WHEN %s THEN %s ', $itemId, $rate);
            }
            $item_str = implode(array_keys($item_rate_data),',');
            $item_sql .= 'END  WHERE id IN ('.$item_str.')';
            $itemData->query($item_sql);
            echo $itemData->affectedNum() . '\n';
        }
        
    }
    
    /**
     * 计算好评率
     * @param unknown $score_arr
     */
    private function getFeedbackRate($score_arr){
        $score_count = count($score_arr);//总评价数量
        $good_count = 0;//好评数量，4星5星 好评
        foreach($score_arr as $score){
            if($score >= 4){
                $good_count += 1;
            }
        }
        $rate = $good_count/$score_count * 100;//好评率
        return round($rate,2);
    }
    
    public function fristexecute(){
        $itemData = new Item();
        $koubeiData = new Koubei();
        $sql = 'select group_concat(score) as score,item_id from koubei where status=2 group by item_id';
        $score_data = $koubeiData->query($sql);
        $score_result = array();
        foreach($score_data as $value){
            $score_arr = explode(',', $value['score']);
            $feedback_rate = $this->getFeedbackRate($score_arr);
            $score_result[$value['item_id']] = $feedback_rate;
        }
        //保存到item表feedback_rate字段
        $data = array_chunk($score_result, 1000, true);
        foreach($data as $item_rate_data){
            //每次处理1000条
            $item_sql = "UPDATE item SET feedback_rate = CASE id ";
            foreach($item_rate_data as $itemId=>$rate){
                if($rate == 0){
                    continue;
                }
                $item_sql .= sprintf(' WHEN %s THEN %s ', $itemId, $rate);
            }
            $item_str = implode(array_keys($item_rate_data),',');
            $item_sql .= 'END  WHERE id IN ('.$item_str.')';
            $itemData->query($item_sql);
            echo $itemData->affectedNum() . '\n';
        }
    }
    
}