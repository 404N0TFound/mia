<?php
namespace mia\miagroup\Remote;

class RecommendNote
{
    public function __construct()
    {
        //获取配置信息
    }

    /**
     * 获取个性化tab列表
     * @param $userId
     * @return array
     */
    public function getRecommendTabList($userId)
    {
        return [13,14,15];
    }


    /**
     * 获取个性化笔记列表
     * @param $userId
     * @param $tabId
     * @return array [sujectId_note]  口碑帖子
     */
    public function getRecommendNoteList($userId, $tabId)
    {
        return ['267344_note','267343_note','267342_note','267341_note','267339_note','267338_note','267337_note'];
    }
}