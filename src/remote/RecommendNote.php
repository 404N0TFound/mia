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
     * @return array
     */
    public function getRecommendNoteList($userId, $tabId)
    {
        return [];
    }
}