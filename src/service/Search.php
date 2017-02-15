<?php
namespace mia\miagroup\Service;

use \mia\miagroup\Lib\Service;
use mia\miagroup\Model\Album as AlbumModel;
use mia\miagroup\Model\Koubei as KoubeiModel;
use mia\miagroup\Service\User as UserService;
use mia\miagroup\Remote\Search as SearchRemote;

/**
 * 蜜芽圈搜索服务类
 * Class Search
 * @package mia\miagroup\Service
 */
class Search extends Service
{
    public function __construct()
    {
        $this->abumModel = new AlbumModel();
        $this->userService = new UserService();
        $this->subjectService = new Subject();
        $this->koubeiModel = new KoubeiModel();
        $this->searchRemote = new SearchRemote();
        parent::__construct();
    }

    /**
     * 笔记搜索
     * @param $keyWords
     * @param $page
     * @param $count
     * @return mixed
     */
    public function noteSearch($keyWords, $page = 1, $count = 20)
    {
        $noteIds = $this->searchRemote->noteSearch($keyWords, $page, $count);
        $noteInfos = $this->subjectService->getBatchSubjectInfos($noteIds)['data'];
        return $this->succ(array_values($noteInfos));
    }

    /**
     * 用户搜索
     * @param $keyWords
     * @param $page
     * @param $count
     * @return mixed
     */
    public function userSearch($keyWords, $page = 1, $count = 20)
    {
        $userIds = $this->searchRemote->userSearch($keyWords, $page, $count);
        $userList = $this->userService->getUserInfoByUids($userIds)['data'];
        return $this->succ(array_values($userList));
    }

    public function itemSearch($keyWords, $page, $count)
    {
        //复用商品的，在结果列表里添加额外信息
    }


    /**
     * 笔记搜索，推荐热词列表
     */
    public function noteHotWordsList()
    {
        $searchKeys['hot_words'] = $this->koubeiModel->getNoteSearchKey();
        return $this->succ($searchKeys);
    }

    /**
     * 获取推荐用户列表，最新的10个
     * @param int $count
     * @return mixed
     */
    public function userHotList($count = 10)
    {
        //推荐池数据
        $userIdRes = $this->abumModel->getGroupDoozerList();
        $userIds = array_slice($userIdRes, 0, $count);
        $userList = $this->userService->getUserInfoByUids($userIds)['data'];
        return $this->succ(array_values($userList));
    }

    /**
     * 商品搜索，推荐热词列表
     */
    public function itemHotWordsList()
    {
        //复用商品的，完全一样的接口
    }
}