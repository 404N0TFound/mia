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
     * @return array [sujectId_subject]  口碑帖子
     */
    public function getRecommendNoteList($userId, $tabId)
    {
        return ['266898_subject','267343_subject','267342_subject','267341_subject','267339_subject','267338_subject','267337_subject'];
    }
    
    public function getRelatedNote($subjectId, $page = 1, $limit = 1) {
        return [267343, 266996, 266933, 266931, 266930, 266927];
    }
}