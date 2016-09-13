<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\Feed\Subject as FeedSubject;

class Feed
{
    private $feedSubject;

    public function __construct()
    {
        $this->feedSubject = new FeedSubject();
    }


    /**
     * 获取用户帖子列表
     */
    public function getSubjectListByUids($userIds,$page=1,$limit=10)
    {
        $start = ($page - 1) * $limit;
        $data = $this->feedSubject->getSubjectList($userIds,$start,$limit);
        return $data;
    }

}